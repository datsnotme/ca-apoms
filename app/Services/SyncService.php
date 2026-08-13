<?php

namespace App\Services;

use App\Models\Device;
use App\Models\EnrollmentCourse;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\SyncChange;
use App\Models\SyncCheckpoint;
use App\Models\SyncConflict;
use App\Models\SyncRemote;
use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Renamed from SyncPullService (Phase 2) — this now serves both directions
 * of sync (see plans/quirky-popping-parnas.md, Phase 3): pendingChangesSince()
 * backs both /api/sync/pull's response and /api/sync/push's request body, and
 * applyIncoming() is the one place either direction's incoming data gets
 * merged in, so pull and push can never disagree about what counts as a
 * conflict.
 *
 * Known limitation, deliberately not solved here: pilot models carry
 * foreign keys (department_id, program_id, curriculum_id, ...) into
 * reference tables that are NOT themselves synced yet. Applying an
 * incoming Student only produces correct data if both instances share the
 * same reference-table IDs. Real production use needs those reference
 * tables synced too — that's Phase 6 (Expansion), not this phase.
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
        'students' => Student::class,
        'student_enrollments' => StudentEnrollment::class,
        'enrollment_courses' => EnrollmentCourse::class,
        'student_grades' => StudentGrade::class,
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
            $mergedAttributes = collect($change['snapshot'] ?? [])
                ->only($remoteChangedFields)
                ->put('sync_version', max((int) $local->sync_version, (int) $change['version']))
                ->put('origin_device_id', $sourceDeviceId)
                ->toArray();

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
        return collect($change['snapshot'] ?? [])
            ->except(['id', 'created_at', 'updated_at'])
            ->put('origin_device_id', $sourceDeviceId)
            ->toArray();
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
                $attributes = collect($conflict->remote_snapshot)
                    ->except(['id', 'uuid', 'sync_version', 'created_at', 'updated_at'])
                    ->toArray();
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
     * @return array<string, mixed>
     */
    private function snapshotFor(Model $model): array
    {
        return $model->only(array_merge($model->getFillable(), ['uuid', 'sync_version']));
    }
}
