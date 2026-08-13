<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Phase 2/3's pullFrom()/pushTo() take $baseUrl/$bearerToken as
        // plain arguments rather than reading stored config, deliberately
        // deferred to "Phase 4 adds the Admin-configurable remote address
        // UI/storage this will read from" (see SyncPullService's original
        // docblock, now SyncService). This is that storage — no generic
        // key-value settings table exists anywhere in this app, so this
        // follows the established convention of a small purpose-built
        // table (same shape as sync_checkpoints) rather than introducing
        // one. `token` is a Sanctum bearer token for THIS instance to
        // authenticate as when calling that remote's own /api/sync/*
        // routes — stored via SyncRemote's `encrypted` cast, not plaintext.
        Schema::create('sync_remotes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url');
            $table->text('token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_remotes');
    }
};
