<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Same reasoning as year_levels in Phase 6: the sync engine's
        // tombstone mechanism universally relies on SoftDeletes, and
        // document_versions was the one file-bearing table missing it.
        Schema::table('document_versions', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('document_versions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
