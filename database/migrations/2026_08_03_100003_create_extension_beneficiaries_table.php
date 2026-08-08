<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_project_id')->constrained()->cascadeOnDelete();
            $table->string('beneficiary_name');
            $table->string('beneficiary_type');
            $table->unsignedInteger('count')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_beneficiaries');
    }
};
