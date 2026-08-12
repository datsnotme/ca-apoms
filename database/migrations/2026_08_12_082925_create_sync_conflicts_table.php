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
        // Backs the (Phase 4) conflict-resolution screen. Created in Phase 1
        // so Phase 3's conflict-detection logic has somewhere to write to
        // without needing a schema change of its own.
        Schema::create('sync_conflicts', function (Blueprint $table) {
            $table->id();
            $table->string('entity_table');
            $table->string('entity_uuid');
            $table->json('local_snapshot');
            $table->json('remote_snapshot');
            $table->json('conflicting_fields');
            $table->string('status')->default('pending');
            $table->string('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['entity_table', 'entity_uuid']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_conflicts');
    }
};
