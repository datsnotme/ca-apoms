<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->unique();
            $table->string('surname');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->string('sex')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('profile_photo_path')->nullable();

            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('curriculum_id')->constrained()->restrictOnDelete();
            $table->foreignId('year_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('adviser_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('admission_type')->nullable();
            $table->date('date_admitted')->nullable();
            $table->date('expected_graduation_date')->nullable();
            $table->string('scholarship_status')->nullable();
            $table->string('classification')->default('regular');
            $table->string('status')->default('active');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['department_id', 'status']);
            $table->index(['program_id', 'year_level_id']);
            $table->index('classification');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
