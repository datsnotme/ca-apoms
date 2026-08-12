<?php

namespace App\Observers;

use App\Models\SyncChange;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Registered against every sync-enabled model (see AppServiceProvider::boot()).
 * One class handles all of them — entity_table is read from the model
 * instance, not hardcoded — so adding a new synced model later (Phase 6)
 * is one observe() call, not a new observer class.
 *
 * Phase 1 only: this records local writes into the sync_changes outbox and
 * keeps uuid/sync_version correct. It does not yet know about devices —
 * device_id stays null for ordinary web-session writes until Phase 2 adds a
 * device-auth context to attribute writes to. user_id is populated from the
 * authenticated web user when one exists (console/seeder writes leave it
 * null, which is fine — nothing consumes these rows until Phase 2).
 */
class SyncChangeObserver
{
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
        $this->recordChange($model, 'created', $model->sync_version);
    }

    public function updating(Model $model): void
    {
        // Ignore changes that only touch sync-metadata columns themselves —
        // those are housekeeping, not a business-data change that should
        // bump the version or spawn a new outbox row. Guards against a
        // future Phase 2 apply-incoming-change path (which sets these
        // columns directly) causing a version to bump twice.
        $businessColumnsChanged = collect($model->getDirty())
            ->keys()
            ->reject(fn ($key) => in_array($key, ['uuid', 'sync_version', 'origin_device_id', 'updated_at']))
            ->isNotEmpty();

        if ($businessColumnsChanged) {
            $model->sync_version = (int) $model->sync_version + 1;
        }
    }

    public function updated(Model $model): void
    {
        if ($model->wasChanged('sync_version')) {
            $this->recordChange($model, 'updated', $model->sync_version);
        }
    }

    public function deleted(Model $model): void
    {
        // Deliberately does not write a version bump back to the row: for a
        // soft delete the row still exists and SoftDeletes already saved
        // deleted_at; for a forceDelete() the row is already gone from the
        // DB by the time this fires, so attempting another UPDATE here
        // would be a no-op at best. The 'deleted' operation itself is the
        // meaningful signal — Phase 2+ treats it as authoritative regardless
        // of the exact version number attached.
        $this->recordChange($model, 'deleted', (int) $model->sync_version);
    }

    private function recordChange(Model $model, string $operation, int $version): void
    {
        SyncChange::create([
            'entity_table' => $model->getTable(),
            'entity_uuid' => $model->uuid,
            'operation' => $operation,
            'device_id' => null,
            'user_id' => auth()->id(),
            'version' => $version,
            'sync_status' => 'pending',
        ]);
    }
}
