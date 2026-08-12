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
        // Model::observe() registers the observer as a "Class@method" string
        // listener, which Laravel's event dispatcher resolves fresh from the
        // container on every single event firing UNLESS the class is bound
        // as shared here — without this, SyncChangeObserver::updating() and
        // ::updated() would run on two different instances per save() call,
        // silently breaking the WeakMap it uses to pass changed_fields/
        // base_version between them. Safe as a singleton because this app
        // has no persistent worker (no Octane) — each HTTP request gets a
        // fresh container, so there's no cross-request state leakage risk.
        $this->app->singleton(SyncChangeObserver::class);
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
