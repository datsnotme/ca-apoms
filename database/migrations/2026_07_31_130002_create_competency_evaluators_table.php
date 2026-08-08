<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_evaluators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('graduation_candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['graduation_candidate_id', 'evaluator_id'], 'competency_evaluators_candidate_evaluator_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_evaluators');
    }
};
