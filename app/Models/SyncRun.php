<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per Update/Sync attempt — backs Sync History (Phase 4). Also
 * mirrored as a spatie/laravel-activitylog entry so sync events show up in
 * the existing Audit Log viewer instead of a second, disconnected log.
 */
class SyncRun extends Model
{
    protected $fillable = [
        'device_id', 'direction', 'target', 'started_at', 'finished_at',
        'uploaded_count', 'downloaded_count', 'created_count', 'updated_count',
        'deleted_count', 'conflict_count', 'status', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
