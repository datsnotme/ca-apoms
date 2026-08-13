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
        // Phase 6: colleges joins the synced table set — Department.college_id
        // is a NOT NULL FK, so without this, cross-instance Department sync
        // only works if both sides happen to already agree on a college_id
        // (the "known gap" documented since Phase 2). See
        // ASSUMPTIONS.md and SyncService::FK_REFERENCES.
        Schema::table('colleges', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->unsignedInteger('sync_version')->default(1)->after('uuid');
            $table->foreignId('origin_device_id')->nullable()->after('sync_version')
                ->constrained('devices')->nullOnDelete();
        });

        DB::table('colleges')->whereNull('uuid')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('colleges')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colleges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('origin_device_id');
            $table->dropColumn(['uuid', 'sync_version']);
        });
    }
};
