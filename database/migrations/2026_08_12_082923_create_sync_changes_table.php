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
        // The change outbox: one row per local write to a sync-enabled model,
        // written by SyncChangeObserver — never by hand. A later sync run
        // reads pending rows here rather than diffing the whole table.
        Schema::create('sync_changes', function (Blueprint $table) {
            $table->id();
            $table->string('entity_table');
            $table->string('entity_uuid');
            $table->string('operation');
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->string('sync_status')->default('pending');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['entity_table', 'entity_uuid']);
            $table->index('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_changes');
    }
};
