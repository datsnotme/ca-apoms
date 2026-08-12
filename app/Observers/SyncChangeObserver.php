<?php

namespace App\Observers;

use App\Models\SyncChange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use WeakMap;

/**
 * Registered against every sync-enabled model (see AppServiceProvider::boot()).
 * One class handles all of them — entity_table is read from the model
 * instance, not hardcoded — so adding a new synced model later (Phase 6)
 * is one observe() call, not a new observer class.
 *
 * Records local writes into the sync_changes outbox and keeps
 * uuid/sync_version correct. Since Phase 3, each outbox row also captures
 * changed_fields (which business columns this write touched) and
 * base_version (the row's version immediately before this write) — the
 * inputs SyncService needs for a three-way merge/conflict decision.
 *
 * device_id stays null for ordinary web-session writes — nothing sets a
 * "current device" context for those, only the sync API's own apply path
 * does (and applies quietly, bypassing this observer entirely — see
 * SyncService). user_id comes from the authenticated web user when one
 * exists.
 */
class SyncChangeObserver
{
    /**
     * Transient per-update state, keyed by model instance rather than
     * stored as a model attribute — setting a non-column property via
     * Eloquent's magic setter would get swept into the same save()'s
     * UPDATE statement and fail with an unknown-column error. A WeakMap
     * avoids that entirely and needs no manual cleanup.
     *
     * @var WeakMap<Model, array{changed_fields: array<int, string>, base_version: int}>
     */
    private WeakMap $pendingUpdates;

    public function __construct()
    {
        $this->pendingUpdates = new WeakMap;
    }

    public function creating(Model $model): void
    {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }

        if (empty($model->sync_version)) {
            $model->sync_version = 1;
        }
    }

    public function created(Model $model): void
    {
        $this->recordChange($model, 'created', $model->sync_version, changedFields: null, baseVersion: null);
    }

    public function updating(Model $model): void
    {
        $baseVersion = (int) $model->getOriginal('sync_version');

        // Ignore changes that only touch sync-metadata columns themselves —
        // those are housekeeping, not a business-data change that should
        // bump the version or spawn a new outbox row.
        $changedFields = collect($model->getDirty())
            ->keys()
            ->reject(fn ($key) => in_array($key, ['uuid', 'sync_version', 'origin_device_id', 'updated_at']))
            ->values();

        if ($changedFields->isNotEmpty()) {
            $model->sync_version = $baseVersion + 1;
            $this->pendingUpdates[$model] = ['changed_fields' => $changedFields->all(), 'base_version' => $baseVersion];
        }
    }

    public function updated(Model $model): void
    {
        if (! isset($this->pendingUpdates[$model])) {
            return;
        }

        ['changed_fields' => $changedFields, 'base_version' => $baseVersion] = $this->pendingUpdates[$model];
        unset($this->pendingUpdates[$model]);

        $this->recordChange($model, 'updated', $model->sync_version, $changedFields, $baseVersion);
    }

    public function deleted(Model $model): void
    {
        // Deliberately does not write a version bump back to the row: for a
        // soft delete the row still exists and SoftDeletes already saved
        // deleted_at; for a forceDelete() the row is already gone from the
        // DB by the time this fires, so attempting another UPDATE here
        // would be a no-op at best. The 'deleted' operation itself is the
        // meaningful signal — SyncService treats it as authoritative
        // regardless of the exact version number attached, and there's no
        // field-level diff to speak of for a deletion.
        $this->recordChange($model, 'deleted', (int) $model->sync_version, changedFields: null, baseVersion: null);
    }

    /**
     * @param  array<int, string>|null  $changedFields
     */
    private function recordChange(Model $model, string $operation, int $version, ?array $changedFields, ?int $baseVersion): void
    {
        SyncChange::create([
            'entity_table' => $model->getTable(),
            'entity_uuid' => $model->uuid,
            'operation' => $operation,
            'device_id' => null,
            'user_id' => auth()->id(),
            'version' => $version,
            'changed_fields' => $changedFields,
            'base_version' => $baseVersion,
            'sync_status' => 'pending',
        ]);
    }
}
