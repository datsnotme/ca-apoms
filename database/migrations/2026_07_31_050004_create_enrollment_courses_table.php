<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('Enrolled');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_enrollment_id', 'class_section_id']);
            $table->index('class_section_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_courses');
    }
};
