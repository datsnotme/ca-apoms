<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('start_year');
            $table->unsignedSmallInteger('end_year');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['start_year', 'end_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
