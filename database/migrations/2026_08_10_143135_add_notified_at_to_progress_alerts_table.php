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
        Schema::table('progress_alerts', function (Blueprint $table) {
            // Set once a proactive notification has actually been sent for
            // this alert's current open episode. Distinct from
            // triggered_at, which gets bumped on every sync regardless of
            // whether the alert is new — this is what lets the scheduled
            // command notify once per episode instead of every day the
            // alert stays open. Cleared alongside acknowledged_by/
            // acknowledged_at when a resolved alert re-triggers, so a
            // fresh episode gets a fresh notification.
            $table->timestamp('notified_at')->nullable()->after('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('progress_alerts', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
