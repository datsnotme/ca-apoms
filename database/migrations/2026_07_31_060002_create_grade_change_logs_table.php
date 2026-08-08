<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_grade_id')->constrained()->cascadeOnDelete();
            $table->string('previous_grade')->nullable();
            $table->string('new_grade');
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_change_logs');
    }
};
