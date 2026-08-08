<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('research_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_lead')->default(false);
            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['research_project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_members');
    }
};
