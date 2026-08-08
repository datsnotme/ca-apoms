<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graduation_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('nominated');
            $table->decimal('gwa_snapshot', 4, 2)->nullable();
            $table->decimal('completion_percentage_snapshot', 5, 2)->nullable();
            $table->unsignedInteger('deficiency_count_snapshot')->default(0);
            $table->foreignId('nominated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('nominated_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graduation_candidates');
    }
};
