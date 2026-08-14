<?php

namespace App\Console\Commands;

use App\Models\SyncChange;
use App\Services\SyncService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Closes the gap Phase 6's ASSUMPTIONS.md already called out as a known,
 * deliberate limitation: a row that predates its table joining the synced
 * set has a backfilled uuid but no sync_changes history, so it never
 * appears in pendingChangesSince() until *something* touches it again.
 * That's fine for a single stray row (documented as "one edit brings it
 * into the outbox"), but a fresh instance's first-ever cold sync can pull
 * in a dependent row (e.g. a Curriculum) whose FK target (an AcademicYear)
 * predates the outbox entirely and was never touched since — exactly the
 * failure this command exists to prevent, discovered via a live two-instance
 * file-sync proof where a pre-existing AcademicYear had no outbox entry.
 *
 * Writes one synthetic 'created' entry per outbox-less row, using the row's
 * current sync_version/uuid — the same shape SyncChangeObserver::created()
 * would have written had the observer existed when the row was made. Safe
 * to run repeatedly: only rows still missing an entry are touched.
 */
class BackfillSyncOutbox extends Command
{
    protected $signature = 'sync:backfill-outbox';

    protected $description = 'Write missing sync_changes outbox entries for pre-existing rows on synced tables.';

    public function handle(SyncService $syncService): int
    {
        $total = 0;

        foreach ($syncService->syncedTables() as $table => $modelClass) {
            /** @var Model $model */
            $model = new $modelClass;
            $query = $modelClass::query();

            if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
                $query->withTrashed();
            }

            $existingUuids = SyncChange::where('entity_table', $table)->pluck('entity_uuid');
            $missing = $query->whereNotIn($model->getTable().'.uuid', $existingUuids)->get();

            foreach ($missing as $row) {
                SyncChange::create([
                    'entity_table' => $table,
                    'entity_uuid' => $row->uuid,
                    'operation' => 'created',
                    'device_id' => null,
                    'user_id' => null,
                    'version' => $row->sync_version ?: 1,
                    'changed_fields' => null,
                    'base_version' => null,
                    'sync_status' => 'pending',
                ]);
                $total++;
            }

            if ($missing->isNotEmpty()) {
                $this->line("{$table}: backfilled {$missing->count()}");
            }
        }

        $this->info("Total outbox entries written: {$total}");

        return self::SUCCESS;
    }
}
