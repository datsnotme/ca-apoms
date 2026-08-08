<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_number')->unique()->nullable()->after('id');
            $table->string('surname')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('surname');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('suffix')->nullable()->after('middle_name');
            $table->string('username')->unique()->nullable()->after('email');
            $table->string('contact_number')->nullable()->after('username');
            $table->foreignId('department_id')->nullable()->after('contact_number')
                ->constrained()->nullOnDelete();
            $table->string('status')->default('active')->after('department_id');
            $table->string('profile_photo_path')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('profile_photo_path');
            $table->timestamp('password_changed_at')->nullable()->after('last_login_at');
            $table->boolean('must_change_password')->default(false)->after('password_changed_at');
            $table->foreignId('created_by')->nullable()->after('must_change_password')
                ->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'employee_number', 'surname', 'first_name', 'middle_name', 'suffix',
                'username', 'contact_number', 'status', 'profile_photo_path',
                'last_login_at', 'password_changed_at', 'must_change_password',
            ]);
        });
    }
};
