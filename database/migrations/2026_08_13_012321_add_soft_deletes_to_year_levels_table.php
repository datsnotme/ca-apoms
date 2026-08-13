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
        // Phase 6 adds year_levels to the synced table set, and the sync
        // engine's tombstone mechanism (see SyncService/SyncChangeObserver)
        // universally relies on SoftDeletes — a hard-deleted row simply
        // vanishes with no trace for a remote to be told about. Every other
        // synced model already has this trait; year_levels was the one
        // exception (nothing in the app currently deletes a YearLevel at
        // all, so this is a behavior no-op until sync starts using it).
        Schema::table('year_levels', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('year_levels', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
