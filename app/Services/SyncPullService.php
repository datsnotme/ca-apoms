<?php

namespace App\Services;

use App\Models\Device;
use App\Models\EnrollmentCourse;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\SyncChange;
use App\Models\SyncCheckpoint;
use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Phase 2 of the sync plan (plans/quirky-popping-parnas.md) — one-way,
 * pull-based sync. One instance serves pendingChangesSince() over
 * /api/sync/pull; another instance calls pullFrom() to fetch and apply it.
 * Both directions live in this one service since every CA-APOMS instance
 * runs the identical codebase and can be on either side.
 *
 * Known limitation, deliberately not solved here: pilot models carry
 * foreign keys (department_id, program_id, curriculum_id, ...) into
 * reference tables that are NOT themselves synced yet. Applying an
 * incoming Student only produces correct data if both instances share the
 * same reference-table IDs. Real production use needs those reference
 * tables synced too — that's Phase 6 (Expansion), not this phase. This
 * phase proves the sync *mechanism* (uuid-keyed, version-guarded,
 * idempotent, tombstone-aware) is correct, not full cross-instance data
 * portability.
 */
class SyncPullService
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
     * distinct (entity_table, entity_uuid) touched in that range — if a row
     * was updated three times since the caller's last pull, they get one
     * entry reflecting its current state, not three redundant ones.
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

        $latestPerEntity = $rows->groupBy(fn (SyncChange $c) => $c->entity_table.'|'.$c->entity_uuid)
            ->map(fn (Collection $group) => $group->sortByDesc('id')->first());

        $changes = $latestPerEntity->map(function (SyncChange $change) {
            if ($change->operation === 'deleted') {
                return [
                    'entity_table' => $change->entity_table,
                    'entity_uuid' => $change->entity_uuid,
                    'operation' => 'deleted',
                    'version' => $change->version,
                ];
            }

            $modelClass = self::SYNCED_MODELS[$change->entity_table];
            $current = $modelClass::withTrashed()->where('uuid', $change->entity_uuid)->first();

            // The row was deleted again after this change row was written,
            // or otherwise no longer matches what we expect — tell the
            // puller to tombstone it rather than send stale/missing data.
            if (! $current) {
                return [
                    'entity_table' => $change->entity_table,
                    'entity_uuid' => $change->entity_uuid,
                    'operation' => 'deleted',
                    'version' => $change->version,
                ];
            }

            return [
                'entity_table' => $change->entity_table,
                'entity_uuid' => $change->entity_uuid,
                'operation' => $current->trashed() ? 'deleted' : $change->operation,
                'version' => $current->sync_version,
                'snapshot' => $current->trashed() ? null : $this->snapshotFor($current),
            ];
        })->values()->all();

        return ['changes' => $changes, 'next_since_id' => (int) $rows->max('id')];
    }

    /**
     * Consuming side: apply a batch of changes (as returned by
     * pendingChangesSince(), whether fetched locally or over HTTP) keyed by
     * uuid. Idempotent by design — applying the same batch twice is a
     * no-op the second time, since the version-guard sees the local row
     * already at or past the incoming version.
     *
     * Writes are deliberately quiet (saveQuietly / withoutEvents): this
     * data originated on another instance and already carries its own
     * uuid/sync_version, which must be preserved exactly, not re-processed
     * by SyncChangeObserver as if it were a brand-new local business change
     * (that would both double-log it to this instance's own outbox and
     * bump a version that's meant to reflect the *source's* history).
     *
     * Known gap, not solved here: saveQuietly() suppresses *all* model
     * events for that write, not just SyncChangeObserver — so an incoming
     * StudentGrade change also skips that model's own booted() hook that
     * writes grade_change_logs. A synced grade change is a real grade
     * change and arguably deserves a local audit entry too; Laravel has no
     * "suppress this one listener, keep the rest" primitive, so doing that
     * properly needs a flag-based opt-out on the observer rather than
     * blanket event suppression. Left for Phase 3+, where push/conflict
     * handling will need finer-grained event control anyway.
     *
     * @param  array<int, array<string, mixed>>  $changes
     * @return array{created: int, updated: int, deleted: int, skipped: int}
     */
    public function applyIncoming(array $changes, ?int $sourceDeviceId = null): array
    {
        $counts = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => 0];

        foreach ($changes as $change) {
            $modelClass = self::SYNCED_MODELS[$change['entity_table']] ?? null;

            if (! $modelClass || empty($change['entity_uuid'])) {
                $counts['skipped']++;

                continue;
            }

            /** @var Model|null $local */
            $local = $modelClass::withTrashed()->where('uuid', $change['entity_uuid'])->first();

            if ($change['operation'] === 'deleted') {
                if ($local && ! $local->trashed()) {
                    $local->delete();
                    $counts['deleted']++;
                } else {
                    $counts['skipped']++;
                }

                continue;
            }

            if ($local && (int) $local->sync_version >= (int) $change['version']) {
                $counts['skipped']++;

                continue;
            }

            $attributes = collect($change['snapshot'] ?? [])
                ->except(['id', 'created_at', 'updated_at'])
                ->put('origin_device_id', $sourceDeviceId)
                ->toArray();

            $target = $local ?? new $modelClass;
            $target->forceFill($attributes);
            $target->saveQuietly();

            $counts[$local ? 'updated' : 'created']++;
        }

        return $counts;
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
                'updated_count' => $counts['updated'],
                'deleted_count' => $counts['deleted'],
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
     * @return array<string, mixed>
     */
    private function snapshotFor(Model $model): array
    {
        return $model->only(array_merge($model->getFillable(), ['uuid', 'sync_version']));
    }
}
