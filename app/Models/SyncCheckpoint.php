<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per (device, remote) pair — tracks how far a device's sync with a
 * specific remote (e.g. "lan-hub", "cloud") has progressed, so a pull only
 * asks for changes since this checkpoint rather than the whole table.
 */
class SyncCheckpoint extends Model
{
    protected $fillable = [
        'device_id', 'remote_target', 'last_synced_at', 'last_token',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
