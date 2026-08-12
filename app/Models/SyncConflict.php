<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Backs the (Phase 4) conflict-resolution screen. The sync engine writes
 * here instead of silently picking a winner when the same field changed on
 * both sides since the last common version.
 */
class SyncConflict extends Model
{
    protected $fillable = [
        'entity_table', 'entity_uuid', 'local_snapshot', 'remote_snapshot',
        'conflicting_fields', 'status', 'resolution', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'local_snapshot' => 'array',
            'remote_snapshot' => 'array',
            'conflicting_fields' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
