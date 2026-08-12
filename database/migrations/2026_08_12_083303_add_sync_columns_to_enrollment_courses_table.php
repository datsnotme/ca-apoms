<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // See add_sync_columns_to_students_table for the nullable-uuid /
        // backfill-in-migration / reuse-existing-tombstone reasoning — same
        // shape applies here (this table already has SoftDeletes).
        Schema::table('enrollment_courses', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->unsignedInteger('sync_version')->default(1)->after('uuid');
            $table->foreignId('origin_device_id')->nullable()->after('sync_version')
                ->constrained('devices')->nullOnDelete();
        });

        DB::table('enrollment_courses')->whereNull('uuid')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('enrollment_courses')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollment_courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_device_id');
            $table->dropColumn(['uuid', 'sync_version']);
        });
    }
};
