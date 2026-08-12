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
        // One row per (device, remote) pair — a device may sync with more
        // than one remote (e.g. the LAN hub and, later, the cloud), each
        // tracked independently so a pull only asks for "changes since
        // last_synced_at" against that specific remote.
        Schema::create('sync_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('remote_target');
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_token')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'remote_target']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_checkpoints');
    }
};
