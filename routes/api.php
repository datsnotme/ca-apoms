<?php

use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\SyncFileController;
use Illuminate\Support\Facades\Route;

// Sync API (Phase 2 — see plans/quirky-popping-parnas.md). Sanctum-token
// auth, completely separate from the web session guard everything else in
// this app uses. sync.manage is Admin-only (see ROLE_PERMISSIONS.md) — the
// Administrator's PC is the designated LAN sync hub, so nobody else needs a
// device token at all.
Route::middleware(['auth:sanctum', 'permission:sync.manage'])
    ->prefix('sync')
    ->name('api.sync.')
    ->group(function () {
        Route::get('status', [SyncController::class, 'status'])->name('status');
        Route::get('pull', [SyncController::class, 'pull'])->name('pull');
        Route::post('push', [SyncController::class, 'push'])->name('push');

        Route::get('files/{table}/{uuid}', [SyncFileController::class, 'download'])
            ->where(['table' => '[a-z_]+', 'uuid' => '[0-9a-f-]{36}'])
            ->name('files.download');
        Route::post('files/{table}/{uuid}', [SyncFileController::class, 'upload'])
            ->where(['table' => '[a-z_]+', 'uuid' => '[0-9a-f-]{36}'])
            ->name('files.upload');
    });
