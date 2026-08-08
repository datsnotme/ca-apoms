<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events (name-sync on User, single-current-semester on Semester)
     * must run during seeding, so WithoutModelEvents is intentionally not used.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            CollegeDepartmentSeeder::class,
            UserSeeder::class,
            YearLevelSeeder::class,
            GradingScaleSeeder::class,
            StudentSeeder::class,
            EnrollmentSeeder::class,
        ]);
    }
}
