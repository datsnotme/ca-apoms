<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_deficiencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('curriculum_course_id')->constrained()->cascadeOnDelete();
            $table->string('deficiency_type');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_via')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'curriculum_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_deficiencies');
    }
};
