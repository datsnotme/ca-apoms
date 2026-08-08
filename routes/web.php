<?php

use App\Http\Controllers\Academic\AcademicYearController;
use App\Http\Controllers\Academic\CourseController;
use App\Http\Controllers\Academic\CurriculumController;
use App\Http\Controllers\Academic\CurriculumCourseController;
use App\Http\Controllers\Academic\DepartmentController;
use App\Http\Controllers\Academic\ProgramController;
use App\Http\Controllers\Academic\SemesterController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Advising\AdvisingController;
use App\Http\Controllers\Advising\StudentAdvisingRecordController;
use App\Http\Controllers\Advising\StudentInterventionFollowupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Enrollment\ClassScheduleController;
use App\Http\Controllers\Enrollment\ClassSectionController;
use App\Http\Controllers\Enrollment\EnrollmentCourseController;
use App\Http\Controllers\Enrollment\StudentEnrollmentController;
use App\Http\Controllers\Extension\ExtensionActivityController;
use App\Http\Controllers\Extension\ExtensionBeneficiaryController;
use App\Http\Controllers\Extension\ExtensionMemberController;
use App\Http\Controllers\Extension\ExtensionProjectController;
use App\Http\Controllers\Faculty\FacultyAwardController;
use App\Http\Controllers\Faculty\FacultyCredentialController;
use App\Http\Controllers\Faculty\FacultyDocumentController;
use App\Http\Controllers\Faculty\FacultyEducationController;
use App\Http\Controllers\Faculty\FacultyProfileController;
use App\Http\Controllers\Faculty\FacultyTrainingController;
use App\Http\Controllers\Faculty\FacultyWorkloadController;
use App\Http\Controllers\Grading\GradeController;
use App\Http\Controllers\Graduation\CompetencyCategoryController;
use App\Http\Controllers\Graduation\CompetencyEvaluatorController;
use App\Http\Controllers\Graduation\CompetencyIndicatorController;
use App\Http\Controllers\Graduation\CompetencyRatingController;
use App\Http\Controllers\Graduation\GraduationCandidateController;
use App\Http\Controllers\Graduation\GraduationConferralController;
use App\Http\Controllers\Graduation\GraduationDecisionController;
use App\Http\Controllers\Graduation\GraduationRecommendationController;
use App\Http\Controllers\Graduation\GraduationReportController;
use App\Http\Controllers\Graduation\GraduationRequirementTemplateController;
use App\Http\Controllers\Graduation\StudentGraduationRequirementController;
use App\Http\Controllers\Import\ImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Operations\AnnouncementController;
use App\Http\Controllers\Operations\DocumentCategoryController;
use App\Http\Controllers\Operations\DocumentController;
use App\Http\Controllers\Operations\DocumentVersionController;
use App\Http\Controllers\Operations\EquipmentAccountabilityController;
use App\Http\Controllers\Operations\EquipmentBorrowingController;
use App\Http\Controllers\Operations\EquipmentController;
use App\Http\Controllers\Operations\EquipmentMaintenanceController;
use App\Http\Controllers\Operations\EquipmentReturnController;
use App\Http\Controllers\Operations\EventController;
use App\Http\Controllers\Operations\FacilityController;
use App\Http\Controllers\Operations\InternalRequestController;
use App\Http\Controllers\Operations\MeetingActionItemController;
use App\Http\Controllers\Operations\MeetingAttendeeController;
use App\Http\Controllers\Operations\MeetingController;
use App\Http\Controllers\Operations\TaskController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Progress\AtRiskController;
use App\Http\Controllers\Progress\StudentProgressController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Research\ResearchMemberController;
use App\Http\Controllers\Research\ResearchOutputController;
use App\Http\Controllers\Research\ResearchProjectController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Student\StudentDocumentController;
use App\Services\BackupService;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

    Route::resource('departments', DepartmentController::class)->except(['show']);
    Route::resource('programs', ProgramController::class)->except(['show']);
    Route::resource('academic-years', AcademicYearController::class)->except(['show']);
    Route::resource('semesters', SemesterController::class)->except(['show']);

    Route::delete('courses/bulk-destroy', [CourseController::class, 'destroyMany'])->name('courses.destroyMany');
    Route::resource('courses', CourseController::class)->except(['show']);
    Route::delete('curricula/bulk-destroy', [CurriculumController::class, 'destroyMany'])->name('curricula.destroyMany');
    Route::resource('curricula', CurriculumController::class)->except(['show']);
    Route::post('curricula/{curriculum}/courses', [CurriculumCourseController::class, 'store'])
        ->name('curricula.courses.store');
    Route::put('curricula/{curriculum}/courses/{curriculumCourse}', [CurriculumCourseController::class, 'update'])
        ->name('curricula.courses.update');
    Route::delete('curricula/{curriculum}/courses/{curriculumCourse}', [CurriculumCourseController::class, 'destroy'])
        ->name('curricula.courses.destroy');

    // Registered before the resource route below — a wildcard {student}
    // route registered first would otherwise capture "bulk-destroy" as an
    // id. See DATABASE_DESIGN.md/PROJECT_PLAN.md's Phase 7A note on this
    // exact wildcard-capture trap.
    Route::delete('students/bulk-destroy', [StudentController::class, 'destroyMany'])->name('students.destroyMany');
    Route::resource('students', StudentController::class)->except(['show']);
    Route::post('students/{student}/documents', [StudentDocumentController::class, 'store'])
        ->name('students.documents.store');
    Route::get('students/{student}/documents/{document}/download', [StudentDocumentController::class, 'download'])
        ->name('students.documents.download');
    Route::patch('students/{student}/documents/{document}/verify', [StudentDocumentController::class, 'verify'])
        ->name('students.documents.verify');
    Route::delete('students/{student}/documents/{document}', [StudentDocumentController::class, 'destroy'])
        ->name('students.documents.destroy');

    Route::resource('class-sections', ClassSectionController::class)
        ->except(['show'])
        ->parameters(['class-sections' => 'classSection']);
    Route::get('class-sections/{classSection}/roster', [ClassSectionController::class, 'roster'])
        ->name('class-sections.roster');
    Route::post('class-sections/{classSection}/schedules', [ClassScheduleController::class, 'store'])
        ->name('class-sections.schedules.store');
    Route::delete('class-sections/{classSection}/schedules/{schedule}', [ClassScheduleController::class, 'destroy'])
        ->name('class-sections.schedules.destroy');

    Route::delete('enrollments/bulk-destroy', [StudentEnrollmentController::class, 'destroyMany'])->name('enrollments.destroyMany');
    Route::resource('enrollments', StudentEnrollmentController::class)->except(['show']);
    Route::post('enrollments/{studentEnrollment}/courses', [EnrollmentCourseController::class, 'store'])
        ->name('enrollments.courses.store');
    Route::patch('enrollments/{studentEnrollment}/courses/{enrollmentCourse}', [EnrollmentCourseController::class, 'update'])
        ->name('enrollments.courses.update');
    Route::delete('enrollments/{studentEnrollment}/courses/{enrollmentCourse}', [EnrollmentCourseController::class, 'destroy'])
        ->name('enrollments.courses.destroy');

    Route::get('grades', [GradeController::class, 'index'])->name('grades.index');
    Route::get('class-sections/{classSection}/grades', [GradeController::class, 'show'])->name('class-sections.grades.show');
    Route::put('class-sections/{classSection}/grades/{enrollmentCourse}', [GradeController::class, 'update'])->name('class-sections.grades.update');
    Route::post('class-sections/{classSection}/grades/submit', [GradeController::class, 'submit'])->name('class-sections.grades.submit');
    Route::post('class-sections/{classSection}/grades/approve', [GradeController::class, 'approve'])->name('class-sections.grades.approve');
    Route::post('class-sections/{classSection}/grades/return', [GradeController::class, 'return'])->name('class-sections.grades.return');
    Route::post('class-sections/{classSection}/grades/finalize', [GradeController::class, 'finalize'])->name('class-sections.grades.finalize');
    Route::patch('class-sections/{classSection}/grades/{studentGrade}/correct', [GradeController::class, 'correct'])->name('class-sections.grades.correct');

    Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
    Route::get('imports/{type}/template', [ImportController::class, 'template'])->name('imports.template');
    Route::post('imports/{type}', [ImportController::class, 'store'])->name('imports.store');
    Route::get('imports/batches/{batch}', [ImportController::class, 'show'])->name('imports.show');
    Route::get('imports/batches/{batch}/errors', [ImportController::class, 'errors'])->name('imports.errors');

    Route::get('students/{student}/progress', [StudentProgressController::class, 'show'])->name('students.progress.show');

    Route::get('academic-progress', [AtRiskController::class, 'index'])->name('academic-progress.index');
    Route::patch('students/{student}/alerts/{alert}/acknowledge', [AtRiskController::class, 'acknowledge'])
        ->name('students.alerts.acknowledge');

    Route::get('advising', [AdvisingController::class, 'index'])->name('advising.index');
    Route::post('students/{student}/advising-records', [StudentAdvisingRecordController::class, 'store'])
        ->name('students.advising-records.store');
    Route::put('students/{student}/advising-records/{record}', [StudentAdvisingRecordController::class, 'update'])
        ->name('students.advising-records.update');
    Route::delete('students/{student}/advising-records/{record}', [StudentAdvisingRecordController::class, 'destroy'])
        ->name('students.advising-records.destroy');

    Route::post('students/{student}/followups', [StudentInterventionFollowupController::class, 'store'])
        ->name('students.followups.store');
    Route::put('students/{student}/followups/{followup}', [StudentInterventionFollowupController::class, 'update'])
        ->name('students.followups.update');
    Route::delete('students/{student}/followups/{followup}', [StudentInterventionFollowupController::class, 'destroy'])
        ->name('students.followups.destroy');

    Route::resource('graduation-requirement-templates', GraduationRequirementTemplateController::class)->except(['show']);

    Route::resource('competency-categories', CompetencyCategoryController::class)->except(['show']);
    Route::post('competency-categories/{competencyCategory}/indicators', [CompetencyIndicatorController::class, 'store'])
        ->name('competency-categories.indicators.store');
    Route::put('competency-categories/{competencyCategory}/indicators/{indicator}', [CompetencyIndicatorController::class, 'update'])
        ->name('competency-categories.indicators.update');
    Route::delete('competency-categories/{competencyCategory}/indicators/{indicator}', [CompetencyIndicatorController::class, 'destroy'])
        ->name('competency-categories.indicators.destroy');

    Route::get('graduation-candidates/report/batch', [GraduationReportController::class, 'batch'])
        ->name('graduation-candidates.report.batch');

    Route::resource('graduation-candidates', GraduationCandidateController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::put('graduation-candidates/{graduationCandidate}/requirements/{requirement}', [StudentGraduationRequirementController::class, 'update'])
        ->name('graduation-candidates.requirements.update');
    Route::post('graduation-candidates/{graduationCandidate}/evaluators', [CompetencyEvaluatorController::class, 'store'])
        ->name('graduation-candidates.evaluators.store');
    Route::delete('graduation-candidates/{graduationCandidate}/evaluators/{evaluator}', [CompetencyEvaluatorController::class, 'destroy'])
        ->name('graduation-candidates.evaluators.destroy');
    Route::put('graduation-candidates/{graduationCandidate}/ratings/{indicator}', [CompetencyRatingController::class, 'update'])
        ->name('graduation-candidates.ratings.update');
    Route::post('graduation-candidates/{graduationCandidate}/recommendation', [GraduationRecommendationController::class, 'store'])
        ->name('graduation-candidates.recommendation.store');
    Route::post('graduation-candidates/{graduationCandidate}/decision', [GraduationDecisionController::class, 'store'])
        ->name('graduation-candidates.decision.store');
    Route::post('graduation-candidates/{graduationCandidate}/confer', [GraduationConferralController::class, 'store'])
        ->name('graduation-candidates.confer.store');
    Route::get('graduation-candidates/{graduationCandidate}/report', [GraduationReportController::class, 'show'])
        ->name('graduation-candidates.report.show');

    Route::get('faculty-profiles', [FacultyProfileController::class, 'index'])->name('faculty-profiles.index');
    Route::get('faculty-profiles/{user}', [FacultyProfileController::class, 'show'])->name('faculty-profiles.show');
    Route::put('faculty-profiles/{user}', [FacultyProfileController::class, 'update'])->name('faculty-profiles.update');

    Route::post('faculty-profiles/{user}/education', [FacultyEducationController::class, 'store'])->name('faculty-profiles.education.store');
    Route::put('faculty-profiles/{user}/education/{education}', [FacultyEducationController::class, 'update'])->name('faculty-profiles.education.update');
    Route::delete('faculty-profiles/{user}/education/{education}', [FacultyEducationController::class, 'destroy'])->name('faculty-profiles.education.destroy');

    Route::post('faculty-profiles/{user}/credentials', [FacultyCredentialController::class, 'store'])->name('faculty-profiles.credentials.store');
    Route::put('faculty-profiles/{user}/credentials/{credential}', [FacultyCredentialController::class, 'update'])->name('faculty-profiles.credentials.update');
    Route::delete('faculty-profiles/{user}/credentials/{credential}', [FacultyCredentialController::class, 'destroy'])->name('faculty-profiles.credentials.destroy');

    Route::post('faculty-profiles/{user}/trainings', [FacultyTrainingController::class, 'store'])->name('faculty-profiles.trainings.store');
    Route::put('faculty-profiles/{user}/trainings/{training}', [FacultyTrainingController::class, 'update'])->name('faculty-profiles.trainings.update');
    Route::delete('faculty-profiles/{user}/trainings/{training}', [FacultyTrainingController::class, 'destroy'])->name('faculty-profiles.trainings.destroy');

    Route::post('faculty-profiles/{user}/awards', [FacultyAwardController::class, 'store'])->name('faculty-profiles.awards.store');
    Route::put('faculty-profiles/{user}/awards/{award}', [FacultyAwardController::class, 'update'])->name('faculty-profiles.awards.update');
    Route::delete('faculty-profiles/{user}/awards/{award}', [FacultyAwardController::class, 'destroy'])->name('faculty-profiles.awards.destroy');

    Route::post('faculty-profiles/{user}/documents', [FacultyDocumentController::class, 'store'])->name('faculty-profiles.documents.store');
    Route::get('faculty-profiles/{user}/documents/{document}/download', [FacultyDocumentController::class, 'download'])->name('faculty-profiles.documents.download');
    Route::patch('faculty-profiles/{user}/documents/{document}/verify', [FacultyDocumentController::class, 'verify'])->name('faculty-profiles.documents.verify');
    Route::delete('faculty-profiles/{user}/documents/{document}', [FacultyDocumentController::class, 'destroy'])->name('faculty-profiles.documents.destroy');

    Route::get('faculty-workload', [FacultyWorkloadController::class, 'index'])->name('faculty-workload.index');
    Route::get('faculty-workload/{user}', [FacultyWorkloadController::class, 'show'])->name('faculty-workload.show');

    Route::delete('announcements/bulk-destroy', [AnnouncementController::class, 'destroyMany'])->name('announcements.destroyMany');
    Route::resource('announcements', AnnouncementController::class)->except(['show']);
    Route::delete('events/bulk-destroy', [EventController::class, 'destroyMany'])->name('events.destroyMany');
    Route::resource('events', EventController::class)->except(['show']);
    Route::resource('facilities', FacilityController::class)->except(['show']);

    Route::get('equipment/accountability', [EquipmentAccountabilityController::class, 'index'])->name('equipment.accountability');
    Route::resource('equipment', EquipmentController::class);
    Route::post('equipment/{equipment}/borrowings', [EquipmentBorrowingController::class, 'store'])->name('equipment.borrowings.store');
    Route::post('equipment/{equipment}/borrowings/{borrowing}/return', [EquipmentReturnController::class, 'store'])->name('equipment.borrowings.return');
    Route::post('equipment/{equipment}/maintenance', [EquipmentMaintenanceController::class, 'store'])->name('equipment.maintenance.store');
    Route::patch('equipment/{equipment}/maintenance/{maintenance}/complete', [EquipmentMaintenanceController::class, 'complete'])->name('equipment.maintenance.complete');

    Route::delete('meetings/bulk-destroy', [MeetingController::class, 'destroyMany'])->name('meetings.destroyMany');
    Route::resource('meetings', MeetingController::class);
    Route::post('meetings/{meeting}/attendees', [MeetingAttendeeController::class, 'store'])->name('meetings.attendees.store');
    Route::patch('meetings/{meeting}/attendees/{attendee}', [MeetingAttendeeController::class, 'update'])->name('meetings.attendees.update');
    Route::delete('meetings/{meeting}/attendees/{attendee}', [MeetingAttendeeController::class, 'destroy'])->name('meetings.attendees.destroy');
    Route::post('meetings/{meeting}/action-items', [MeetingActionItemController::class, 'store'])->name('meetings.action-items.store');
    Route::put('meetings/{meeting}/action-items/{actionItem}', [MeetingActionItemController::class, 'update'])->name('meetings.action-items.update');
    Route::delete('meetings/{meeting}/action-items/{actionItem}', [MeetingActionItemController::class, 'destroy'])->name('meetings.action-items.destroy');

    Route::delete('tasks/bulk-destroy', [TaskController::class, 'destroyMany'])->name('tasks.destroyMany');
    Route::resource('tasks', TaskController::class)->except(['show']);

    Route::resource('internal-requests', InternalRequestController::class)->only(['index', 'create', 'store']);
    Route::patch('internal-requests/{internalRequest}/review', [InternalRequestController::class, 'review'])->name('internal-requests.review');
    Route::patch('internal-requests/{internalRequest}/cancel', [InternalRequestController::class, 'cancel'])->name('internal-requests.cancel');

    Route::resource('document-categories', DocumentCategoryController::class)->only(['index', 'store', 'destroy']);

    Route::delete('documents/bulk-destroy', [DocumentController::class, 'destroyMany'])->name('documents.destroyMany');
    Route::resource('documents', DocumentController::class);
    Route::post('documents/{document}/versions', [DocumentVersionController::class, 'store'])->name('documents.versions.store');
    Route::get('documents/{document}/versions/{version}/download', [DocumentVersionController::class, 'download'])->name('documents.versions.download');
    Route::delete('documents/{document}/versions/{version}', [DocumentVersionController::class, 'destroy'])->name('documents.versions.destroy');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::resource('research-projects', ResearchProjectController::class)
        ->parameters(['research-projects' => 'researchProject']);
    Route::post('research-projects/{researchProject}/members', [ResearchMemberController::class, 'store'])->name('research-projects.members.store');
    Route::delete('research-projects/{researchProject}/members/{member}', [ResearchMemberController::class, 'destroy'])->name('research-projects.members.destroy');
    Route::post('research-projects/{researchProject}/outputs', [ResearchOutputController::class, 'store'])->name('research-projects.outputs.store');
    Route::delete('research-projects/{researchProject}/outputs/{output}', [ResearchOutputController::class, 'destroy'])->name('research-projects.outputs.destroy');

    Route::resource('extension-projects', ExtensionProjectController::class)
        ->parameters(['extension-projects' => 'extensionProject']);
    Route::post('extension-projects/{extensionProject}/members', [ExtensionMemberController::class, 'store'])->name('extension-projects.members.store');
    Route::delete('extension-projects/{extensionProject}/members/{member}', [ExtensionMemberController::class, 'destroy'])->name('extension-projects.members.destroy');
    Route::post('extension-projects/{extensionProject}/activities', [ExtensionActivityController::class, 'store'])->name('extension-projects.activities.store');
    Route::delete('extension-projects/{extensionProject}/activities/{activity}', [ExtensionActivityController::class, 'destroy'])->name('extension-projects.activities.destroy');
    Route::post('extension-projects/{extensionProject}/beneficiaries', [ExtensionBeneficiaryController::class, 'store'])->name('extension-projects.beneficiaries.store');
    Route::delete('extension-projects/{extensionProject}/beneficiaries/{beneficiary}', [ExtensionBeneficiaryController::class, 'destroy'])->name('extension-projects.beneficiaries.destroy');

    Route::middleware('permission:users.manage')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        Route::patch('users/{user}/reactivate', [UserController::class, 'reactivate'])->name('users.reactivate');
    });

    Route::middleware('permission:audit-logs.view')->group(function () {
        Route::get('/audit-logs', AuditLogController::class)->name('audit-logs.index');
    });

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');
        Route::get('reports/{type}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
        Route::get('reports/{type}/excel', [ReportController::class, 'excel'])->name('reports.excel');
    });

    Route::middleware('permission:backups.manage')->group(function () {
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('backups/{filename}/download', [BackupController::class, 'download'])
            ->where('filename', BackupService::FILENAME_PATTERN_ROUTE)
            ->name('backups.download');
        Route::post('backups/{filename}/restore', [BackupController::class, 'restore'])
            ->where('filename', BackupService::FILENAME_PATTERN_ROUTE)
            ->name('backups.restore');
    });
});

require __DIR__.'/auth.php';
