<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'device_code', 'name', 'owner_user_id', 'role_hint',
        'last_seen_at', 'last_sync_at', 'app_version', 'db_schema_version', 'status', 'is_local',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'is_local' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(SyncChange::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(SyncCheckpoint::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(SyncRun::class);
    }
}
