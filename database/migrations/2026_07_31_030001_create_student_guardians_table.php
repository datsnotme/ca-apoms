<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // guardian | emergency_contact
            $table->string('name');
            $table->string('relationship')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'type']);
        });

        Schema::create('student_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // permanent | current
            $table->string('address_line');
            $table->timestamps();

            $table->unique(['student_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_addresses');
        Schema::dropIfExists('student_guardians');
    }
};
