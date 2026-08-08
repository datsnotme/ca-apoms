<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_graduation_requirements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('graduation_candidate_id');
            $table->foreign('graduation_candidate_id', 'grad_requirements_candidate_fk')
                ->references('id')->on('graduation_candidates')->cascadeOnDelete();

            $table->foreignId('requirement_template_id');
            $table->foreign('requirement_template_id', 'grad_requirements_template_fk')
                ->references('id')->on('graduation_requirement_templates')->restrictOnDelete();

            $table->string('status')->default('pending');
            $table->foreignId('satisfied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('satisfied_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['graduation_candidate_id', 'requirement_template_id'], 'grad_requirements_candidate_template_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_graduation_requirements');
    }
};
