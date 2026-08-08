<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type');
            $table->text('description')->nullable();
            $table->date('output_date')->nullable();
            $table->string('reference_url')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_outputs');
    }
};
