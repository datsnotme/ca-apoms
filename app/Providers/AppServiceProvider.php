<?php

namespace App\Providers;

use App\Models\EnrollmentCourse;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Observers\SyncChangeObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Sync pilot set (Phase 1 — see plans/quirky-popping-parnas.md).
        // Expanding sync to another model later is one more line here, not
        // a new observer class — SyncChangeObserver is model-agnostic.
        Student::observe(SyncChangeObserver::class);
        StudentEnrollment::observe(SyncChangeObserver::class);
        EnrollmentCourse::observe(SyncChangeObserver::class);
        StudentGrade::observe(SyncChangeObserver::class);
    }
}
