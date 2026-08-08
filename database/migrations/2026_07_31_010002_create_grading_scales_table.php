<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('grading_scale_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scale_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->decimal('numeric_equivalent', 4, 2)->nullable();
            $table->boolean('is_passing')->default(false);
            $table->boolean('is_failing')->default(false);
            $table->boolean('is_special')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['grading_scale_id', 'value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_scale_values');
        Schema::dropIfExists('grading_scales');
    }
};
