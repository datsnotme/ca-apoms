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
        // Nullable, not NOT NULL — doctrine/dbal isn't installed, so there's
        // no Schema::change() available to enforce it at the DB level after
        // backfilling. SyncChangeObserver's creating() hook always assigns a
        // uuid on every new row going forward; this migration backfills
        // existing rows in the same pass. deleted_at (SoftDeletes) already
        // gives this table a tombstone — no new deletion column needed.
        Schema::table('students', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->unsignedInteger('sync_version')->default(1)->after('uuid');
            $table->foreignId('origin_device_id')->nullable()->after('sync_version')
                ->constrained('devices')->nullOnDelete();
        });

        DB::table('students')->whereNull('uuid')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('students')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_device_id');
            $table->dropColumn(['uuid', 'sync_version']);
        });
    }
};
