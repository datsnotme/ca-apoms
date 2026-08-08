<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graduation_candidates', function (Blueprint $table) {
            $table->foreignId('recommended_by')->nullable()->after('deficiency_count_snapshot')->constrained('users')->nullOnDelete();
            $table->timestamp('recommended_at')->nullable()->after('recommended_by');
            $table->text('recommendation_remarks')->nullable()->after('recommended_at');

            $table->foreignId('decided_by')->nullable()->after('recommendation_remarks')->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable()->after('decided_by');
            $table->text('decision_remarks')->nullable()->after('decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('graduation_candidates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recommended_by');
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['recommended_at', 'recommendation_remarks', 'decided_at', 'decision_remarks']);
        });
    }
};
