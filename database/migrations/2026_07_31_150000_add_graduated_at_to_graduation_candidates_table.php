<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduation_candidates', function (Blueprint $table) {
            $table->timestamp('graduated_at')->nullable()->after('decision_remarks');
        });
    }

    public function down(): void
    {
        Schema::table('graduation_candidates', function (Blueprint $table) {
            $table->dropColumn('graduated_at');
        });
    }
};
