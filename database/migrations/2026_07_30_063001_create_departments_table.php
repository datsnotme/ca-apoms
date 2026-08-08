<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            // No FK constraint yet: users table doesn't exist until a later migration.
            // Constraint is added in add_department_head_foreign_to_departments_table.
            $table->unsignedBigInteger('department_head_id')->nullable();
            $table->string('office_location')->nullable();
            $table->string('contact_info')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
