<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('units', 4, 2);
            $table->decimal('lecture_hours', 4, 2)->default(0);
            $table->decimal('laboratory_hours', 4, 2)->default(0);
            $table->string('category');
            $table->unsignedTinyInteger('recommended_year_level')->nullable();
            $table->string('recommended_semester')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
