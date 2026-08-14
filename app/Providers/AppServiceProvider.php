<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\College;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentVersion;
use App\Models\EnrollmentCourse;
use App\Models\Program;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\YearLevel;
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

        // Phase 6 — reference tables the pilot set depends on, so a Student
        // (etc.) synced to another instance resolves correctly instead of
        // assuming both sides happen to share matching reference-table IDs.
        // See SyncService::FK_REFERENCES for the FK-to-uuid translation this
        // enables.
        College::observe(SyncChangeObserver::class);
        AcademicYear::observe(SyncChangeObserver::class);
        YearLevel::observe(SyncChangeObserver::class);
        Department::observe(SyncChangeObserver::class);
        Program::observe(SyncChangeObserver::class);
        Curriculum::observe(SyncChangeObserver::class);
        Semester::observe(SyncChangeObserver::class);
        Course::observe(SyncChangeObserver::class);
        ClassSection::observe(SyncChangeObserver::class);
        Section::observe(SyncChangeObserver::class);

        // File/document sync — the row *and* its actual file bytes (see
        // SyncService::FILE_COLUMNS and downloadMissingFiles()/
        // uploadChangedFiles()). FacultyDocument is deliberately excluded:
        // its owning relationship is user_id, and User accounts aren't
        // synced — see ASSUMPTIONS.md.
        DocumentCategory::observe(SyncChangeObserver::class);
        Document::observe(SyncChangeObserver::class);
        DocumentVersion::observe(SyncChangeObserver::class);
        StudentDocument::observe(SyncChangeObserver::class);
    }
}
