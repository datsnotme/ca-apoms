<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\College;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Device;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentVersion;
use App\Models\EnrollmentCourse;
use App\Models\Program;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\SyncChange;
use App\Models\SyncCheckpoint;
use App\Models\SyncConflict;
use App\Models\SyncRemote;
use App\Models\SyncRun;
use App\Models\YearLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Renamed from SyncPullService (Phase 2) — this now serves both directions
 * of sync (see plans/quirky-popping-parnas.md, Phase 3): pendingChangesSince()
 * backs both /api/sync/pull's response and /api/sync/push's request body, and
 * applyIncoming() is the one place either direction's incoming data gets
 * merged in, so pull and push can never disagree about what counts as a
 * conflict.
 *
 * Phase 6 closed the reference-table gap noted in Phase 2/3: FK_REFERENCES
 * declares which columns on which synced tables point at another synced
 * table, and snapshotFor()/attributesFor()/resolveForeignKeys() translate
 * those columns to/from the referenced row's uuid on the wire — a raw
 * `department_id` is meaningless once two instances have independently
 * auto-incremented their own IDs, but the uuid is portable. This still
 * assumes the referenced row has already synced (see SYNC_ORDER); a
 * genuinely brand-new second instance with zero shared history still needs
 * to be bootstrapped via backup/restore, not two independent `db:seed`
 * runs — sync heals *ongoing* divergence, it doesn't invent a shared
 * history that was never there. See ASSUMPTIONS.md.
 *
 * Three-way merge, in brief: every outbox row now carries base_version (the
 * entity's sync_version immediately before that write) and changed_fields
 * (which business columns that write touched) — see SyncChangeObserver.
 * pendingChangesSince() collapses a run of changes per entity into one
 * payload entry carrying the EARLIEST base_version and the UNION of
 * changed_fields in that run. applyIncoming() then classifies each incoming
 * change as:
 *   - fast-forward (local hasn't changed since the incoming change's base) —
 *     apply the full snapshot, no conflict possible;
 *   - safe merge (local diverged since base, but touched different fields
 *     than the incoming change) — apply only the incoming change's fields,
 *     keep local's own edits to the other fields;
 *   - hard conflict (both sides touched the same field, or the entity is a
 *     StudentGrade and diverged at all — grades never auto-merge, per the
 *     spec's principle that a grade must never be silently overwritten) —
 *     record to sync_conflicts and leave the local row untouched.
 */
class SyncService
{
    /** @var array<string, class-string<Model>> */
    private const SYNCED_MODELS = [
        'colleges' => College::class,
        'academic_years' => AcademicYear::class,
        'year_levels' => YearLevel::class,
        'departments' => Department::class,
        'programs' => Program::class,
        'courses' => Course::class,
        'curricula' => Curriculum::class,
        'semesters' => Semester::class,
        'sections' => Section::class,
        'class_sections' => ClassSection::class,
        'students' => Student::class,
        'student_enrollments' => StudentEnrollment::class,
        'enrollment_courses' => EnrollmentCourse::class,
        'student_grades' => StudentGrade::class,
        'document_categories' => DocumentCategory::class,
        'documents' => Document::class,
        'document_versions' => DocumentVersion::class,
        'student_documents' => StudentDocument::class,
    ];

    /**
     * Topological order (dependencies before dependents) that
     * pendingChangesSince() sorts its output by, so that within one batch a
     * dependent entity's FK_REFERENCES always resolve against a
     * reference-table row that appears earlier in the same batch. Doesn't
     * need to be the *only* valid order, just *a* valid one.
     *
     * @var array<int, string>
     */
    private const SYNC_ORDER = [
        'colleges', 'academic_years', 'year_levels',
        'departments',
        'programs', 'courses',
        'curricula', 'semesters',
        'sections', 'class_sections',
        'students',
        'student_enrollments',
        'enrollment_courses',
        'student_grades',
        'document_categories',
        'documents',
        'document_versions',
        'student_documents',
    ];

    /**
     * Declares which FK columns on which synced tables point at another
     * synced table's `id` — the ones whose raw value is meaningless across
     * instances and must travel as the referenced row's uuid instead. FKs
     * into `users` (encoded_by, adviser_id, ...) are deliberately absent:
     * User accounts aren't synced (out of scope — see ASSUMPTIONS.md), so
     * those columns stay as opaque local IDs, same as before Phase 6.
     *
     * @var array<string, array<string, string>>
     */
    private const FK_REFERENCES = [
        'departments' => ['college_id' => 'colleges'],
        'programs' => ['department_id' => 'departments'],
        'courses' => ['department_id' => 'departments'],
        'curricula' => ['program_id' => 'programs', 'effective_academic_year_id' => 'academic_years'],
        'semesters' => ['academic_year_id' => 'academic_years'],
        'sections' => ['program_id' => 'programs', 'year_level_id' => 'year_levels', 'academic_year_id' => 'academic_years'],
        'class_sections' => ['course_id' => 'courses', 'semester_id' => 'semesters'],
        'students' => [
            'department_id' => 'departments', 'program_id' => 'programs',
            'curriculum_id' => 'curricula', 'year_level_id' => 'year_levels',
            'section_id' => 'sections',
        ],
        'student_enrollments' => ['student_id' => 'students', 'semester_id' => 'semesters'],
        'enrollment_courses' => ['student_enrollment_id' => 'student_enrollments', 'class_section_id' => 'class_sections'],
        'student_grades' => ['enrollment_course_id' => 'enrollment_courses'],
        'documents' => ['document_category_id' => 'document_categories', 'department_id' => 'departments'],
        'document_versions' => ['document_id' => 'documents'],
        'student_documents' => ['student_id' => 'students'],
    ];

    /**
     * Declares which synced tables carry an actual uploaded file alongside
     * their row data, and where that file lives — the row/DB-column sync
     * above only ever moved the *path string*; these are the ones whose
     * bytes also need to travel. `FacultyDocument` is deliberately absent:
     * its owning relationship is `user_id`, and `users` isn't a synced
     * table (out of scope — see ASSUMPTIONS.md), so there's no uuid to
     * translate that FK through even if the row itself were added to
     * SYNCED_MODELS. See downloadMissingFiles()/uploadChangedFiles().
     *
     * @var array<string, array{column: string, disk: string}>
     */
    private const FILE_COLUMNS = [
        'colleges' => ['column' => 'logo_path', 'disk' => 'public'],
        'document_versions' => ['column' => 'file_path', 'disk' => 'local'],
        'student_documents' => ['column' => 'file_path', 'disk' => 'local'],
    ];

    /**
     * Serving side: everything changed since $sinceId, one entry per
     * distinct (entity_table, entity_uuid) touched in that range. A run of
     * several writes to the same entity collapses into one entry carrying
     * the entity's CURRENT state, the EARLIEST base_version in the run
     * (the version before any of these writes), and the UNION of
     * changed_fields across the run — the inputs applyIncoming() needs to
     * tell "diverged but non-overlapping" from "diverged and conflicting".
     *
     * @return array{changes: array<int, array<string, mixed>>, next_since_id: int}
     */
    public function pendingChangesSince(int $sinceId, int $limit = 200): array
    {
        $rows = SyncChange::query()
            ->whereIn('entity_table', array_keys(self::SYNCED_MODELS))
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return ['changes' => [], 'next_since_id' => $sinceId];
        }

        $changes = $rows->groupBy(fn (SyncChange $c) => $c->entity_table.'|'.$c->entity_uuid)
            ->map(fn (Collection $group) => $this->buildChangeEntry($group->sortBy('id')->values()))
            ->sortBy(fn (array $change) => array_search($change['entity_table'], self::SYNC_ORDER))
            ->values()
            ->all();

        return ['changes' => $changes, 'next_since_id' => (int) $rows->max('id')];
    }

    /**
     * @param  Collection<int, SyncChange>  $sortedGroup  ascending by id, all rows for one entity
     * @return array<string, mixed>
     */
    private function buildChangeEntry(Collection $sortedGroup): array
    {
        $latest = $sortedGroup->last();
        $modelClass = self::SYNCED_MODELS[$latest->entity_table];
        $current = $modelClass::withTrashed()->where('uuid', $latest->entity_uuid)->first();

        // The row is gone (hard-deleted) or was deleted after this run of
        // changes was written — tell the other side to tombstone it rather
        // than send stale/missing data. No base_version needed: a delete
        // is authoritative regardless of what preceded it.
        if (! $current || $current->trashed()) {
            return [
                'entity_table' => $latest->entity_table,
                'entity_uuid' => $latest->entity_uuid,
                'operation' => 'deleted',
                'version' => $current->sync_version ?? $latest->version,
            ];
        }

        // base_version/changed_fields come from the group's UPDATED rows
        // only, regardless of whether a 'created' row also appears in this
        // id range — that create event may just be this outbox's own
        // genesis record for an entity the receiver already independently
        // has (e.g. two instances seeded from the same baseline before any
        // sync ran). What matters for divergence detection is the version
        // right before the earliest business-field change in this run; if
        // there are no updates at all (a pure, never-touched-since create),
        // base_version is correctly null — there's nothing to diverge from
        // yet, so the receiver either creates it fresh or fast-forwards.
        $updates = $sortedGroup->where('operation', 'updated');

        $baseVersion = $updates->first()?->base_version;
        $changedFields = $updates->pluck('changed_fields')->filter()->flatten()->unique()->values()->all();

        return [
            'entity_table' => $latest->entity_table,
            'entity_uuid' => $latest->entity_uuid,
            'operation' => $latest->operation,
            'version' => $current->sync_version,
            'base_version' => $baseVersion,
            'changed_fields' => empty($changedFields) ? null : $changedFields,
            'snapshot' => $this->snapshotFor($current),
        ];
    }

    /**
     * Consuming side: apply a batch of changes (as returned by
     * pendingChangesSince(), whether fetched locally, pulled over HTTP, or
     * received via a push request) keyed by uuid.
     *
     * Idempotent for the fast-forward case: applying the same batch twice
     * is a no-op the second time, since the version-guard sees the local
     * row already at or past the incoming version.
     *
     * Writes are deliberately quiet (saveQuietly): this data originated
     * elsewhere and already carries its own uuid/sync_version, which must
     * be preserved (or deliberately combined, for a merge), not
     * re-processed by SyncChangeObserver as a brand-new local business
     * change. See the known gap on grade_change_logs suppression noted in
     * ASSUMPTIONS.md (Phase 2) — unchanged by Phase 3.
     *
     * @param  array<int, array<string, mixed>>  $changes
     * @return array{created: int, updated: int, merged: int, conflicted: int, deleted: int, skipped: int}
     */
    public function applyIncoming(array $changes, ?int $sourceDeviceId = null): array
    {
        $counts = ['created' => 0, 'updated' => 0, 'merged' => 0, 'conflicted' => 0, 'deleted' => 0, 'skipped' => 0];

        foreach ($changes as $change) {
            $entityTable = $change['entity_table'] ?? null;
            $modelClass = self::SYNCED_MODELS[$entityTable] ?? null;

            if (! $modelClass || empty($change['entity_uuid'])) {
                $counts['skipped']++;

                continue;
            }

            /** @var Model|null $local */
            $local = $modelClass::withTrashed()->where('uuid', $change['entity_uuid'])->first();

            if ($change['operation'] === 'deleted') {
                $counts[$this->applyDelete($entityTable, $local, $change)]++;

                continue;
            }

            if (! $local) {
                $attributes = $this->attributesFor($change, $sourceDeviceId);
                (new $modelClass)->forceFill($attributes)->saveQuietly();
                $counts['created']++;

                continue;
            }

            $baseVersion = $change['base_version'] ?? null;

            // Fast-forward only requires that local hasn't changed since
            // the incoming change's base — NOT that local's raw version
            // number is behind the incoming one. Once two sides diverge,
            // each keeps incrementing its own counter independently, so
            // two genuinely different states can carry the same (or even
            // a "higher" local) version number; comparing base_version is
            // the only reliable signal once concurrent edits are possible.
            if ($baseVersion === null) {
                if ((int) $local->sync_version >= (int) $change['version']) {
                    $counts['skipped']++;

                    continue;
                }

                $local->forceFill($this->attributesFor($change, $sourceDeviceId))->saveQuietly();
                $counts['updated']++;

                continue;
            }

            if ((int) $local->sync_version <= (int) $baseVersion) {
                // Local hasn't diverged since this change's base — the
                // incoming snapshot is a strict superset of local's state.
                $local->forceFill($this->attributesFor($change, $sourceDeviceId))->saveQuietly();
                $counts['updated']++;

                continue;
            }

            // Divergence: local changed since $baseVersion, and so did the
            // remote (that's this very change). Figure out whether the two
            // sides touched the same fields.
            $localChangedFields = $this->localChangedFieldsSince($entityTable, $change['entity_uuid'], (int) $baseVersion);
            $remoteChangedFields = $change['changed_fields'] ?? null;
            $overlap = $remoteChangedFields === null ? null : array_values(array_intersect($localChangedFields, $remoteChangedFields));
            $isGrade = $entityTable === 'student_grades';

            if ($isGrade || $remoteChangedFields === null || ! empty($overlap)) {
                $this->recordConflict(
                    $entityTable,
                    $change['entity_uuid'],
                    $local,
                    $change,
                    $isGrade ? array_values(array_unique(array_merge($localChangedFields, $remoteChangedFields ?? []))) : $overlap,
                );
                $counts['conflicted']++;

                continue;
            }

            // Safe merge: apply only the fields the remote actually
            // touched, leaving local's own edits to its own fields intact.
            // Any of those touched fields that are themselves FK columns
            // (e.g. a moved department_id) still need translating — the
            // raw value in $change['snapshot'] is the sender's, not ours.
            $resolvedFks = collect($this->resolveForeignKeys($entityTable, $change['snapshot'] ?? []))->only($remoteChangedFields);
            $mergedAttributes = collect($change['snapshot'] ?? [])
                ->only($remoteChangedFields)
                ->merge($resolvedFks)
                ->put('sync_version', max((int) $local->sync_version, (int) $change['version']))
                ->put('origin_device_id', $sourceDeviceId)
                ->toArray();
            $mergedAttributes = $this->resolveFileAttributes($entityTable, $change['entity_uuid'], $mergedAttributes);

            $local->forceFill($mergedAttributes)->saveQuietly();
            $counts['merged']++;
        }

        return $counts;
    }

    private function applyDelete(string $entityTable, ?Model $local, array $change): string
    {
        if (! $local || $local->trashed()) {
            return 'skipped';
        }

        $referenceVersion = (int) ($change['version'] ?? 0);

        if ((int) $local->sync_version > $referenceVersion) {
            // Local kept editing this row after the point the remote
            // decided to delete it — that's a real conflict, not a
            // clear-cut "delete wins" or "edit wins". Leave it alive
            // locally pending resolution.
            $localChangedFields = $this->localChangedFieldsSince($entityTable, $change['entity_uuid'], $referenceVersion);
            $deletionMarker = array_merge($change, ['snapshot' => ['__deleted__' => true, 'version' => $referenceVersion]]);
            $this->recordConflict($entityTable, $change['entity_uuid'], $local, $deletionMarker, array_merge(['__remote_deleted__'], $localChangedFields));

            return 'conflicted';
        }

        $local->delete();

        return 'deleted';
    }

    /**
     * @return array<int, string>
     */
    private function localChangedFieldsSince(string $entityTable, string $entityUuid, int $sinceVersion): array
    {
        return SyncChange::query()
            ->where('entity_table', $entityTable)
            ->where('entity_uuid', $entityUuid)
            ->where('operation', 'updated')
            ->where('version', '>', $sinceVersion)
            ->pluck('changed_fields')
            ->filter()
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $conflictingFields
     */
    private function recordConflict(string $entityTable, string $entityUuid, Model $local, array $change, array $conflictingFields): void
    {
        $payload = [
            'entity_table' => $entityTable,
            'entity_uuid' => $entityUuid,
            'local_snapshot' => $this->snapshotFor($local),
            'remote_snapshot' => $change['snapshot'] ?? null,
            'conflicting_fields' => $conflictingFields,
            'status' => 'pending',
        ];

        $existing = SyncConflict::query()
            ->where('entity_table', $entityTable)
            ->where('entity_uuid', $entityUuid)
            ->where('status', 'pending')
            ->first();

        // A conflict already flagged for this entity gets its snapshots
        // refreshed rather than duplicated — the remote may have diverged
        // further since the first detection, but it's still one conflict
        // to resolve, not several.
        $existing ? $existing->update($payload) : SyncConflict::create($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFor(array $change, ?int $sourceDeviceId): array
    {
        $snapshot = $change['snapshot'] ?? [];
        $resolvedFks = $this->resolveForeignKeys($change['entity_table'], $snapshot);

        $attributes = collect($snapshot)
            ->except(['id', 'created_at', 'updated_at', '_fk_uuids', '_file_hash'])
            ->merge($resolvedFks)
            ->put('origin_device_id', $sourceDeviceId)
            ->toArray();

        return $this->resolveFileAttributes($change['entity_table'], $change['entity_uuid'], $attributes);
    }

    /**
     * Orchestrates a full pull from a remote CA-APOMS instance: reads this
     * device's checkpoint for $remoteTarget, calls the remote's
     * /api/sync/pull, applies what comes back, advances the checkpoint,
     * and records a sync_runs row. $baseUrl/$bearerToken are passed in
     * rather than read from stored config — Phase 4 adds the
     * Admin-configurable "remote address" UI/storage this will read from;
     * this phase proves the mechanism works given a target.
     */
    public function pullFrom(Device $device, string $baseUrl, string $bearerToken, string $remoteTarget): SyncRun
    {
        $checkpoint = SyncCheckpoint::firstOrCreate(
            ['device_id' => $device->id, 'remote_target' => $remoteTarget],
        );

        $run = SyncRun::create([
            'device_id' => $device->id,
            'direction' => 'pull',
            'target' => $remoteTarget,
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $response = Http::withToken($bearerToken)
                ->timeout(30)
                ->get(rtrim($baseUrl, '/').'/api/sync/pull', [
                    'since_id' => (int) ($checkpoint->last_token ?? 0),
                ])
                ->throw();

            $payload = $response->json();
            $counts = DB::transaction(fn () => $this->applyIncoming($payload['changes'] ?? [], $device->id));

            // Deliberately outside the DB transaction above: file transfer
            // is a separate concern from the row-level apply, and a file
            // failure shouldn't roll back row changes that already
            // applied correctly (see downloadMissingFiles()'s docblock).
            $fileCounts = $this->downloadMissingFiles($payload['changes'] ?? [], $baseUrl, $bearerToken);

            $checkpoint->update([
                'last_synced_at' => now(),
                'last_token' => (string) ($payload['next_since_id'] ?? $checkpoint->last_token),
            ]);

            $run->update([
                'finished_at' => now(),
                'downloaded_count' => count($payload['changes'] ?? []),
                'created_count' => $counts['created'],
                'updated_count' => $counts['updated'] + $counts['merged'],
                'deleted_count' => $counts['deleted'],
                'conflict_count' => $counts['conflicted'],
                'status' => 'success',
                'error_message' => $fileCounts['failed'] > 0
                    ? "{$fileCounts['failed']} file(s) failed to transfer — will retry next sync."
                    : null,
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'finished_at' => now(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $device->update(['last_sync_at' => now()]);

        return $run->fresh();
    }

    /**
     * Orchestrates a full push to a remote CA-APOMS instance: gathers this
     * device's own local changes since its last push to $remoteTarget (a
     * separate cursor from pullFrom()'s — one tracks what we've consumed
     * from the remote, the other what we've sent to it, so they're kept
     * under distinct SyncCheckpoint rows via a ":push" suffix on
     * remote_target rather than overloading one row for both directions),
     * POSTs them to the remote's /api/sync/push, advances the push
     * cursor, and records a sync_runs row.
     */
    public function pushTo(Device $device, string $baseUrl, string $bearerToken, string $remoteTarget): SyncRun
    {
        $checkpoint = SyncCheckpoint::firstOrCreate(
            ['device_id' => $device->id, 'remote_target' => "{$remoteTarget}:push"],
        );

        $run = SyncRun::create([
            'device_id' => $device->id,
            'direction' => 'push',
            'target' => $remoteTarget,
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $outgoing = $this->pendingChangesSince((int) ($checkpoint->last_token ?? 0));

            $response = Http::withToken($bearerToken)
                ->timeout(30)
                ->post(rtrim($baseUrl, '/').'/api/sync/push', ['changes' => $outgoing['changes']])
                ->throw();

            $counts = $response->json();
            $fileCounts = $this->uploadChangedFiles($outgoing['changes'], $baseUrl, $bearerToken);

            $checkpoint->update([
                'last_synced_at' => now(),
                'last_token' => (string) $outgoing['next_since_id'],
            ]);

            $run->update([
                'finished_at' => now(),
                'uploaded_count' => count($outgoing['changes']),
                'created_count' => $counts['created'] ?? 0,
                'updated_count' => ($counts['updated'] ?? 0) + ($counts['merged'] ?? 0),
                'deleted_count' => $counts['deleted'] ?? 0,
                'conflict_count' => $counts['conflicted'] ?? 0,
                'status' => 'success',
                'error_message' => $fileCounts['failed'] > 0
                    ? "{$fileCounts['failed']} file(s) failed to transfer — will retry next sync."
                    : null,
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'finished_at' => now(),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $device->update(['last_sync_at' => now()]);

        return $run->fresh();
    }

    /**
     * Phase 4's "Sync Now" button: a full reconciliation against one
     * configured SyncRemote — pull first, then push, so an interrupted or
     * one-way-failing attempt still makes whatever progress it can rather
     * than an all-or-nothing transaction. Each direction is caught
     * independently: a pull failure (e.g. the remote is offline) shouldn't
     * prevent trying the push, and vice versa.
     *
     * @return array{pull: SyncRun|null, pullError: string|null, push: SyncRun|null, pushError: string|null}
     */
    public function reconcile(Device $localDevice, SyncRemote $remote): array
    {
        $result = ['pull' => null, 'pullError' => null, 'push' => null, 'pushError' => null];

        try {
            $result['pull'] = $this->pullFrom($localDevice, $remote->base_url, $remote->token, $remote->name);
        } catch (\Throwable $e) {
            $result['pullError'] = $e->getMessage();
        }

        try {
            $result['push'] = $this->pushTo($localDevice, $remote->base_url, $remote->token, $remote->name);
        } catch (\Throwable $e) {
            $result['pushError'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Phase 4's conflict-resolution screen: an Admin picks a winner for a
     * pending SyncConflict. 'take_remote' applies the remote's snapshot
     * through a normal (non-quiet) save — deliberately NOT saveQuietly(),
     * unlike applyIncoming()'s merge/fast-forward paths, so this resolution
     * itself becomes a fresh, correctly-based local SyncChange outbox
     * entry (base_version = the version right before adopting the remote's
     * values) that can propagate onward to other remotes. 'keep_local'
     * mutates nothing — local's current row already holds the value the
     * Admin chose.
     *
     * Known, deliberate gap: this resolves the conflict LOCALLY and marks
     * it resolved for tracking/audit purposes, but does not guarantee the
     * same historical conflicting change can never be re-offered by a
     * remote that re-sends an old, already-acknowledged batch (e.g. after
     * a checkpoint reset). Full resolution-propagation across N remotes is
     * out of scope for this pilot's single-remote-pair topology — the same
     * class of scoping decision as the reference-table FK gap (Phase 2)
     * and the double-apply idempotency gap (Phase 3); see ASSUMPTIONS.md.
     */
    public function applyResolution(SyncConflict $conflict, string $resolution, ?int $resolvedById): void
    {
        if ($resolution === 'take_remote') {
            $modelClass = self::SYNCED_MODELS[$conflict->entity_table] ?? null;
            $local = $modelClass ? $modelClass::withTrashed()->where('uuid', $conflict->entity_uuid)->first() : null;

            if ($local && $conflict->remote_snapshot) {
                $resolvedFks = $this->resolveForeignKeys($conflict->entity_table, $conflict->remote_snapshot);
                $attributes = collect($conflict->remote_snapshot)
                    ->except(['id', 'uuid', 'sync_version', 'created_at', 'updated_at', '_fk_uuids', '_file_hash'])
                    ->merge($resolvedFks)
                    ->toArray();
                $attributes = $this->resolveFileAttributes($conflict->entity_table, $conflict->entity_uuid, $attributes);
                $local->forceFill($attributes)->save();
            }
        }

        $conflict->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_by' => $resolvedById,
            'resolved_at' => now(),
        ]);
    }

    /**
     * The model class for a synced table, or null — a public accessor so
     * SyncFileController can resolve `{table}` from a route parameter
     * without SYNCED_MODELS needing to stop being private.
     *
     * @return class-string<Model>|null
     */
    public function modelFor(string $entityTable): ?string
    {
        return self::SYNCED_MODELS[$entityTable] ?? null;
    }

    /**
     * The full synced-table → model map, for callers (BackfillSyncOutbox)
     * that need to iterate every synced table rather than resolve one.
     *
     * @return array<string, class-string<Model>>
     */
    public function syncedTables(): array
    {
        return self::SYNCED_MODELS;
    }

    /**
     * @return array{column: string, disk: string}|null
     */
    public function fileColumnFor(string $entityTable): ?array
    {
        return self::FILE_COLUMNS[$entityTable] ?? null;
    }

    public function resolveSyncedFilePath(string $entityTable, string $entityUuid): string
    {
        return $this->resolveFilePath($entityTable, $entityUuid);
    }

    /**
     * Whether $change represents an actual change to the file column
     * itself — not every update to a file-bearing row touches its file
     * (e.g. editing a Document's title), and re-transferring the file on
     * every such update would be wasteful. A 'created' change always
     * needs the file (first time either side has seen this row); an
     * 'updated'/'merged' change only needs it when the file column is
     * specifically among what changed.
     */
    private function needsFileTransfer(array $change): bool
    {
        if (! isset(self::FILE_COLUMNS[$change['entity_table']])) {
            return false;
        }

        if ($change['operation'] === 'deleted') {
            return false;
        }

        if ($change['operation'] === 'created') {
            return true;
        }

        $column = self::FILE_COLUMNS[$change['entity_table']]['column'];

        return in_array($column, $change['changed_fields'] ?? [], true);
    }

    /**
     * Pull-side file transfer: for each applied change that touched a
     * file-bearing row's file, fetch the bytes from the same remote we
     * just pulled the row data from — never a different, potentially
     * unreachable address; whatever base_url/token got the row change
     * also gets the file. Skips entities whose local file already
     * matches the incoming hash (a no-op re-sync, or a merge/fast-forward
     * that happened to reapply the same content). A transfer failure is
     * caught, not thrown — the row already applied successfully and that
     * shouldn't be undone over a file that can be retried next sync (the
     * local file still won't match the hash, so it naturally retries).
     *
     * @param  array<int, array<string, mixed>>  $changes
     * @return array{downloaded: int, skipped: int, failed: int}
     */
    public function downloadMissingFiles(array $changes, string $baseUrl, string $bearerToken): array
    {
        $counts = ['downloaded' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($changes as $change) {
            if (! $this->needsFileTransfer($change)) {
                continue;
            }

            $fileConf = self::FILE_COLUMNS[$change['entity_table']];
            $incomingHash = $change['snapshot']['_file_hash'] ?? null;

            if ($incomingHash === null) {
                $counts['skipped']++;

                continue;
            }

            $localPath = $this->resolveFilePath($change['entity_table'], $change['entity_uuid']);
            $disk = Storage::disk($fileConf['disk']);

            if ($disk->exists($localPath) && hash('sha256', $disk->get($localPath)) === $incomingHash) {
                $counts['skipped']++;

                continue;
            }

            try {
                $response = Http::withToken($bearerToken)
                    ->timeout(60)
                    ->get(rtrim($baseUrl, '/')."/api/sync/files/{$change['entity_table']}/{$change['entity_uuid']}")
                    ->throw();

                $disk->put($localPath, $response->body());
                $counts['downloaded']++;
            } catch (\Throwable) {
                $counts['failed']++;
            }
        }

        return $counts;
    }

    /**
     * Push-side file transfer: for each of our own outgoing changes that
     * touched a file-bearing row's file, upload the bytes (which we, as
     * the sender, already have at our own native path) to the same
     * remote we just pushed the row data to. No "does the receiver
     * already have this" round trip before uploading — sync runs are
     * manual and infrequent and files are capped at 20MB (see
     * ASSUMPTIONS.md), so the bandwidth cost of an occasional redundant
     * upload is cheaper than an extra request; the receiving endpoint can
     * still no-op cheaply if the content is byte-identical.
     *
     * @param  array<int, array<string, mixed>>  $changes
     * @return array{uploaded: int, skipped: int, failed: int}
     */
    public function uploadChangedFiles(array $changes, string $baseUrl, string $bearerToken): array
    {
        $counts = ['uploaded' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($changes as $change) {
            if (! $this->needsFileTransfer($change)) {
                continue;
            }

            $fileConf = self::FILE_COLUMNS[$change['entity_table']];
            $path = $change['snapshot'][$fileConf['column']] ?? null;
            $disk = Storage::disk($fileConf['disk']);

            if (! $path || ! $disk->exists($path)) {
                $counts['skipped']++;

                continue;
            }

            try {
                Http::withToken($bearerToken)
                    ->timeout(60)
                    ->attach('file', $disk->get($path), basename($path))
                    ->post(rtrim($baseUrl, '/')."/api/sync/files/{$change['entity_table']}/{$change['entity_uuid']}")
                    ->throw();

                $counts['uploaded']++;
            } catch (\Throwable) {
                $counts['failed']++;
            }
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFor(Model $model): array
    {
        $snapshot = $model->only(array_merge($model->getFillable(), ['uuid', 'sync_version']));

        $fkUuids = [];
        foreach (self::FK_REFERENCES[$model->getTable()] ?? [] as $column => $referencedTable) {
            $value = $model->{$column};
            if ($value === null) {
                continue;
            }

            $referencedModel = self::SYNCED_MODELS[$referencedTable];
            $uuid = $referencedModel::withTrashed()->find($value)?->uuid;
            if ($uuid !== null) {
                $fkUuids[$column] = $uuid;
            }
        }

        if (! empty($fkUuids)) {
            $snapshot['_fk_uuids'] = $fkUuids;
        }

        // Computed fresh from the actual bytes, not persisted — a stored
        // hash column would need every upload path (BrandingController,
        // DocumentVersionController, StudentDocumentController, ...) to
        // remember to keep it updated, and would go stale the moment one
        // didn't. Reading+hashing a ≤20MB file (see ASSUMPTIONS.md for the
        // app's actual upload size ceilings) only happens when this row
        // appears in a sync batch, not on every request.
        if ($fileConf = self::FILE_COLUMNS[$model->getTable()] ?? null) {
            $path = $model->{$fileConf['column']};
            if ($path && Storage::disk($fileConf['disk'])->exists($path)) {
                $snapshot['_file_hash'] = hash('sha256', Storage::disk($fileConf['disk'])->get($path));
            }
        }

        return $snapshot;
    }

    /**
     * Every synced table in FILE_COLUMNS gets its file column rewritten to
     * this deterministic, uuid-keyed path on receipt — never a blind copy
     * of the sender's raw path string. The native paths these controllers
     * generate (e.g. `documents/{document_id}/xyz.pdf`,
     * `branding/xyz.jpg`) embed the *sender's own* auto-increment id or are
     * otherwise only meaningful in the sender's own storage layout; once
     * two instances have independently assigned different local ids to the
     * "same" (by uuid) row, that path is wrong on the receiving side. The
     * uuid is the one thing guaranteed portable, so every instance that
     * *received* (rather than originated) this entity's file stores it
     * under a namespace keyed by that uuid instead.
     */
    private function resolveFilePath(string $entityTable, string $entityUuid): string
    {
        return "synced/{$entityTable}/{$entityUuid}";
    }

    /**
     * Overrides a file-bearing table's file column with resolveFilePath()'s
     * deterministic value, but only when that column is actually present
     * in $attributes — for a full snapshot (create/fast-forward) it always
     * is; for a field-level merge it's only present when the file itself
     * was one of the fields that actually changed, and this must not
     * inject a path change into a merge that never touched the file.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function resolveFileAttributes(string $entityTable, string $entityUuid, array $attributes): array
    {
        $fileConf = self::FILE_COLUMNS[$entityTable] ?? null;
        if (! $fileConf || ! array_key_exists($fileConf['column'], $attributes)) {
            return $attributes;
        }

        $attributes[$fileConf['column']] = $this->resolveFilePath($entityTable, $entityUuid);

        return $attributes;
    }

    /**
     * Translates a snapshot's `_fk_uuids` (see snapshotFor()) into local
     * IDs for this instance, keyed by the FK column they belong on. Throws
     * rather than silently skipping when a referenced row can't be found
     * locally — deliberately: this runs inside applyIncoming(), itself
     * always called inside a DB::transaction() (pullFrom()'s own wrap, or
     * SyncController::push()'s, per Phase 5), so throwing rolls back the
     * whole batch and leaves the checkpoint un-advanced. The next sync
     * attempt naturally retries the same batch, by which point — assuming
     * SYNC_ORDER did its job and this was a batch-boundary edge case, not a
     * genuinely missing reference row — the dependency has usually synced.
     * A silent skip would be worse: the checkpoint would still advance past
     * a change that never actually applied correctly.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, int>
     */
    private function resolveForeignKeys(string $entityTable, array $snapshot): array
    {
        $fkColumns = self::FK_REFERENCES[$entityTable] ?? [];
        if (empty($fkColumns)) {
            return [];
        }

        $fkUuids = $snapshot['_fk_uuids'] ?? [];
        $resolved = [];

        foreach ($fkColumns as $column => $referencedTable) {
            if (! array_key_exists($column, $fkUuids)) {
                continue; // null on the sender (nullable FK) — leave unset.
            }

            $referencedModel = self::SYNCED_MODELS[$referencedTable];
            $localId = $referencedModel::withTrashed()->where('uuid', $fkUuids[$column])->value('id');

            if ($localId === null) {
                throw new RuntimeException(
                    "Cannot resolve {$entityTable}.{$column}: no local {$referencedTable} row with uuid ".
                    "{$fkUuids[$column]} — its own change may not have synced yet."
                );
            }

            $resolved[$column] = $localId;
        }

        return $resolved;
    }
}
