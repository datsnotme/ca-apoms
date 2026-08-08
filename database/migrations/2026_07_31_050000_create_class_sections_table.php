<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->string('section_label');
            $table->unsignedSmallInteger('max_students')->default(40);
            $table->string('status')->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['course_id', 'semester_id', 'section_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sections');
    }
};
