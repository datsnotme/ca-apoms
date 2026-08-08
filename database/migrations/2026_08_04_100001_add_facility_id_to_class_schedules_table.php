<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills class_schedules.room (free text since Phase 2) into a real
 * facilities FK, per the commitment made in DATABASE_DESIGN.md when that
 * column was first introduced: "It becomes a real FK once that module
 * ships, via a migration that backfills matched facility rows."
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->foreignId('facility_id')->nullable()->after('room')->constrained()->nullOnDelete();
        });

        $rooms = DB::table('class_schedules')
            ->whereNotNull('room')
            ->where('room', '!=', '')
            ->distinct()
            ->pluck('room');

        foreach ($rooms as $room) {
            $facilityId = DB::table('facilities')->insertGetId([
                'name' => $room,
                'type' => 'Classroom',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('class_schedules')->where('room', $room)->update(['facility_id' => $facilityId]);
        }

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('room');
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->string('room')->nullable()->after('end_time');
        });

        DB::table('class_schedules')
            ->join('facilities', 'class_schedules.facility_id', '=', 'facilities.id')
            ->update(['class_schedules.room' => DB::raw('facilities.name')]);

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('facility_id');
        });
    }
};
