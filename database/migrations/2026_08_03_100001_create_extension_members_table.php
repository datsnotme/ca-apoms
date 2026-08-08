<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_lead')->default(false);
            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['extension_project_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_members');
    }
};
