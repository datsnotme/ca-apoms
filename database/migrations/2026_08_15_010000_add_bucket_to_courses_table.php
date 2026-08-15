<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('bucket')->default('major_subjects')->after('category');
        });

        // Backfill from the existing subject-domain category where it maps
        // cleanly onto a form bucket. Everything else keeps the column
        // default (major_subjects) until an admin reclassifies it via the
        // Course edit form — see ASSUMPTIONS.md.
        DB::table('courses')->where('category', 'general_education')->update(['bucket' => 'general_education']);
        DB::table('courses')->where('category', 'nstp_pe')->update(['bucket' => 'physical_education']);
        DB::table('courses')->whereIn('category', ['research', 'thesis', 'practicum', 'internship'])
            ->update(['bucket' => 'required_courses']);
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('bucket');
        });
    }
};
