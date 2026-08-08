<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->string('semester');
            $table->boolean('is_required')->default(true);
            $table->decimal('units', 4, 2);
            $table->unsignedSmallInteger('sequence_order')->default(0);
            $table->timestamps();

            $table->unique(['curriculum_id', 'course_id']);
            $table->index(['curriculum_id', 'year_level', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_courses');
    }
};
