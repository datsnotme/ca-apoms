<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_evaluator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_indicator_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('remarks')->nullable();
            $table->timestamp('rated_at')->nullable();
            $table->timestamps();

            $table->unique(['competency_evaluator_id', 'competency_indicator_id'], 'competency_ratings_evaluator_indicator_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_ratings');
    }
};
