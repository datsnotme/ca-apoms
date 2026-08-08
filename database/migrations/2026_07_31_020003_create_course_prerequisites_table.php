<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_course_id')->constrained('courses')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'prerequisite_course_id']);
        });

        Schema::create('course_corequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('corequisite_course_id')->constrained('courses')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['course_id', 'corequisite_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_corequisites');
        Schema::dropIfExists('course_prerequisites');
    }
};
