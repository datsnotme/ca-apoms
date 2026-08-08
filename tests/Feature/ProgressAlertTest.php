<?php

use App\Enums\RoleName;
use App\Models\AcademicDeficiency;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\Department;
use App\Models\ProgressAlert;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\YearLevel;
use App\Services\ProgressAlertService;
use Database\Seeders\GradingScaleSeeder;

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->department = Department::factory()->create();
    $this->curriculum = Curriculum::factory()->create();
    $this->semester = Semester::factory()->create();
    $this->yearLevel1 = YearLevel::factory()->create(['level' => 1]);
    $this->yearLevel2 = YearLevel::factory()->create(['level' => 2]);

    $this->student = Student::factory()->create([
        'department_id' => $this->department->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel2->id,
    ]);

    $this->service = app(ProgressAlertService::class);
});

test('two or more unresolved deficiencies trigger a multiple deficiencies alert', function () {
    AcademicDeficiency::factory()->count(2)->create(['student_id' => $this->student->id]);

    $this->service->syncAlerts($this->student);

    $this->assertDatabaseHas('progress_alerts', [
        'student_id' => $this->student->id,
        'alert_type' => 'multiple_deficiencies',
        'severity' => 'warning',
        'resolved_at' => null,
    ]);
});

test('four or more unresolved deficiencies escalate the alert to critical', function () {
    AcademicDeficiency::factory()->count(4)->create(['student_id' => $this->student->id]);

    $this->service->syncAlerts($this->student);

    $this->assertDatabaseHas('progress_alerts', [
        'student_id' => $this->student->id,
        'alert_type' => 'multiple_deficiencies',
        'severity' => 'critical',
    ]);
});

test('a GWA at the passing threshold triggers a warning, and failing range triggers critical', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $ec = enrollStudentInCourse($this->student, $this->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '3.00', 'status' => 'finalized']);

    $this->service->syncAlerts($this->student);

    $this->assertDatabaseHas('progress_alerts', ['student_id' => $this->student->id, 'alert_type' => 'low_gwa', 'severity' => 'warning']);
});

test('a failing-range GWA triggers a critical alert', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $ec = enrollStudentInCourse($this->student, $this->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '4.00', 'status' => 'finalized']);

    $this->service->syncAlerts($this->student);

    $this->assertDatabaseHas('progress_alerts', ['student_id' => $this->student->id, 'alert_type' => 'low_gwa', 'severity' => 'critical']);
});

test('an on_leave status triggers a warning enrollment status alert', function () {
    $this->student->update(['status' => 'on_leave']);

    $this->service->syncAlerts($this->student);

    $this->assertDatabaseHas('progress_alerts', ['student_id' => $this->student->id, 'alert_type' => 'enrollment_status', 'severity' => 'warning']);
});

test('a withdrawn status triggers a critical enrollment status alert', function () {
    $this->student->update(['status' => 'withdrawn']);

    $this->service->syncAlerts($this->student);

    $this->assertDatabaseHas('progress_alerts', ['student_id' => $this->student->id, 'alert_type' => 'enrollment_status', 'severity' => 'critical']);
});

test('syncAlerts resolves an alert once the condition no longer applies', function () {
    $this->student->update(['status' => 'on_leave']);
    $this->service->syncAlerts($this->student);
    $this->assertDatabaseHas('progress_alerts', ['student_id' => $this->student->id, 'alert_type' => 'enrollment_status', 'resolved_at' => null]);

    $this->student->update(['status' => 'active']);
    $this->service->syncAlerts($this->student);

    $alert = ProgressAlert::where('student_id', $this->student->id)->where('alert_type', 'enrollment_status')->firstOrFail();
    expect($alert->resolved_at)->not->toBeNull();
});

test('a re-triggered alert requires acknowledgment again', function () {
    $reviewer = userWithRole(RoleName::Administrator->value);

    $this->student->update(['status' => 'on_leave']);
    $this->service->syncAlerts($this->student);

    $alert = ProgressAlert::where('student_id', $this->student->id)->where('alert_type', 'enrollment_status')->firstOrFail();
    $alert->update(['acknowledged_by' => $reviewer->id, 'acknowledged_at' => now()]);

    $this->student->update(['status' => 'active']);
    $this->service->syncAlerts($this->student);

    $this->student->update(['status' => 'on_leave']);
    $this->service->syncAlerts($this->student);

    $reopened = ProgressAlert::where('student_id', $this->student->id)->where('alert_type', 'enrollment_status')->firstOrFail();
    expect($reopened->acknowledged_at)->toBeNull();
    expect($reopened->resolved_at)->toBeNull();
});

test('a faculty member only sees at-risk students among their own advisees', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->student->update(['adviser_id' => $faculty->id, 'status' => 'withdrawn']);

    Student::factory()->create(['department_id' => $this->department->id, 'year_level_id' => $this->yearLevel1->id, 'status' => 'withdrawn']);

    $response = $this->actingAs($faculty)->get('/academic-progress');

    $response->assertInertia(fn ($page) => $page->has('students.data', 1));
});

test('a department head sees at-risk students across their department', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->student->update(['status' => 'withdrawn']);

    $otherDepartment = Department::factory()->create();
    Student::factory()->create(['department_id' => $otherDepartment->id, 'year_level_id' => $this->yearLevel1->id, 'status' => 'withdrawn']);

    $response = $this->actingAs($head)->get('/academic-progress');

    $response->assertInertia(fn ($page) => $page->has('students.data', 1));
});

test('acknowledging an alert records who and when', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    $this->student->update(['status' => 'withdrawn']);
    $this->service->syncAlerts($this->student);
    $alert = ProgressAlert::where('student_id', $this->student->id)->firstOrFail();

    $response = $this->actingAs($admin)->patch("/students/{$this->student->id}/alerts/{$alert->id}/acknowledge");

    $response->assertRedirect();
    $this->assertDatabaseHas('progress_alerts', ['id' => $alert->id, 'acknowledged_by' => $admin->id]);
});
