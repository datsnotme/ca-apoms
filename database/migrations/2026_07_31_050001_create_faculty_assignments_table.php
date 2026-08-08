<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('users')->restrictOnDelete();
            $table->string('role')->default('primary');
            $table->timestamps();

            $table->unique(['class_section_id', 'faculty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_assignments');
    }
};
