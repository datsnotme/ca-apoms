<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_borrowing_id')->unique()->constrained('equipment_borrowings')->cascadeOnDelete();
            $table->dateTime('returned_at');
            $table->string('condition_on_return')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_returns');
    }
};
