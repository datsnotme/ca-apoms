<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_historical_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year_label');
            $table->string('semester_label');
            $table->string('program_label')->nullable();
            $table->string('course_code');
            $table->string('course_title');
            $table->decimal('lecture_hours', 4, 2)->nullable();
            $table->decimal('laboratory_hours', 4, 2)->nullable();
            $table->decimal('units', 4, 2);
            $table->string('grade')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_historical_grades');
    }
};
