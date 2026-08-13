<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An Admin-configured remote CA-APOMS instance to sync with — the storage
 * Phase 2/3's pullFrom()/pushTo() were always meant to read from instead
 * of taking $baseUrl/$bearerToken as raw arguments. `token` is this
 * instance's own Sanctum bearer token for authenticating against that
 * remote's /api/sync/* routes, kept out of plaintext via the `encrypted`
 * cast.
 */
class SyncRemote extends Model
{
    protected $fillable = ['name', 'base_url', 'token'];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
        ];
    }
}
