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
        // Phase 3's inputs for a three-way merge decision: changed_fields is
        // which business columns THIS specific write touched (null for a
        // 'deleted' operation — nothing to diff); base_version is what
        // sync_version the row was at immediately before this write (null
        // for a 'created' operation — there's no "before"). Together they
        // let SyncService tell "local and remote touched different fields
        // since they last agreed" (safe to merge) from "they touched the
        // same field" (a real conflict) — see plans/quirky-popping-parnas.md.
        Schema::table('sync_changes', function (Blueprint $table) {
            $table->json('changed_fields')->nullable()->after('version');
            $table->unsignedInteger('base_version')->nullable()->after('changed_fields');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sync_changes', function (Blueprint $table) {
            $table->dropColumn(['changed_fields', 'base_version']);
        });
    }
};
