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
        // Phase 4 (Sync Center) needs to know which of the possibly-many
        // registered devices IS this running instance, so it knows whose
        // identity to use as the $device argument to SyncService's
        // pullFrom()/pushTo() — those calls own this instance's
        // sync_checkpoints/sync_runs bookkeeping. Not enforced unique at
        // the schema level (no partial-unique-index support without
        // doctrine/dbal for a conditional constraint); SyncCenterController
        // enforces "only one true at a time" the same way BrandingController
        // enforces "one College row" — at the application layer.
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('is_local')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('is_local');
        });
    }
};
