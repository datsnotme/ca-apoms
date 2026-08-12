<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per local write to a sync-enabled model — the change outbox.
 * Written exclusively by SyncChangeObserver; a sync run reads pending rows
 * from here rather than diffing whole tables.
 */
class SyncChange extends Model
{
    protected $fillable = [
        'entity_table', 'entity_uuid', 'operation', 'device_id', 'user_id',
        'version', 'sync_status', 'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
