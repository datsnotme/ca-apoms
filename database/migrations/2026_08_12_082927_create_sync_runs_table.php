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
        // Backs Sync History (Phase 4). Each row is also mirrored as a
        // spatie/laravel-activitylog entry so the existing Audit Log viewer
        // surfaces sync events too, rather than this becoming a second,
        // disconnected log — see BrandingController-style precedent for
        // "no dedicated Policy, reuse what already exists."
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('direction');
            $table->string('target');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('uploaded_count')->default(0);
            $table->unsignedInteger('downloaded_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('deleted_count')->default(0);
            $table->unsignedInteger('conflict_count')->default(0);
            $table->string('status')->default('running');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
