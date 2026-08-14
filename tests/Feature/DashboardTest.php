<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\ProgressAlert;
use App\Models\Student;
use Database\Seeders\GradingScaleSeeder;

beforeEach(function () {
    // Dashboard now syncs progress alerts before rendering (see
    // ProgressAlertService::syncAlertsForScope()), which computes GWA and
    // therefore needs a grading scale to exist — same requirement
    // ProgressAlertTest/ProgressComputationTest already seed for.
    $this->seed(GradingScaleSeeder::class);

    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
});

test('an admin sees the college-wide dashboard with all departments represented', function () {
    Student::factory()->create(['department_id' => $this->department->id, 'status' => 'active']);
    Student::factory()->create(['department_id' => $this->otherDepartment->id, 'status' => 'active']);

    $response = $this->actingAs($this->admin)->get('/dashboard');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('role', 'administrator')
        ->where('departmentName', null)
        ->where('charts.studentsByStatus.values.0', 2)); // Active is the first StudentStatus case
});

test('a dean sees the college-wide dashboard', function () {
    $response = $this->actingAs($this->dean)->get('/dashboard');

    $response->assertOk()->assertInertia(fn ($page) => $page->where('role', 'dean'));
});

test('a department head only sees their own department in the student count and chart', function () {
    Student::factory()->create(['department_id' => $this->department->id, 'status' => 'active']);
    Student::factory()->create(['department_id' => $this->department->id, 'status' => 'active']);
    Student::factory()->create(['department_id' => $this->otherDepartment->id, 'status' => 'active']);

    $response = $this->actingAs($this->head)->get('/dashboard');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('role', 'department-head')
        ->where('departmentName', $this->department->name)
        ->where('charts.studentsByStatus.values.0', 2)
        ->where('statCards.0.value', 2));
});

test('a faculty member only sees their own advisees, not another advisers', function () {
    $myAdvisee = Student::factory()->create(['department_id' => $this->department->id, 'adviser_id' => $this->faculty->id]);
    Student::factory()->create(['department_id' => $this->department->id, 'adviser_id' => null]);

    $response = $this->actingAs($this->faculty)->get('/dashboard');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->where('role', 'faculty')
        ->where('statCards.1.label', 'My Advisees')
        ->where('statCards.1.value', 1));

    expect($myAdvisee->adviser_id)->toBe($this->faculty->id);
});

test('the students-by-classification chart buckets everything but Regular and Irregular into Others', function () {
    Student::factory()->create(['department_id' => $this->department->id, 'classification' => 'regular']);
    Student::factory()->create(['department_id' => $this->department->id, 'classification' => 'regular']);
    Student::factory()->create(['department_id' => $this->department->id, 'classification' => 'irregular']);
    Student::factory()->create(['department_id' => $this->department->id, 'classification' => 'transferee']);
    Student::factory()->create(['department_id' => $this->department->id, 'classification' => 'shiftee']);

    $response = $this->actingAs($this->admin)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->where('charts.studentsByClassification.labels', ['Regular', 'Irregular', 'Others'])
        ->where('charts.studentsByClassification.values', [2, 1, 2]));
});

test('a department head only sees their own department in the classification chart', function () {
    Student::factory()->create(['department_id' => $this->department->id, 'classification' => 'regular']);
    Student::factory()->create(['department_id' => $this->department->id, 'classification' => 'irregular']);
    Student::factory()->create(['department_id' => $this->otherDepartment->id, 'classification' => 'regular']);

    $response = $this->actingAs($this->head)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('charts.studentsByClassification.values', [1, 1, 0]));
});

test('every role can load the dashboard without error', function () {
    foreach ([$this->admin, $this->dean, $this->head, $this->faculty] as $user) {
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }
});

test('the active alerts count never disagrees with the at-risk page, because both sync before reading', function () {
    // Reproduces a real bug: a stale unresolved alert row whose underlying
    // condition no longer holds (here: no unresolved deficiencies exist for
    // this student at all) used to inflate the Dashboard's raw count, while
    // visiting the At-Risk page (which always re-evaluated before reading)
    // would immediately resolve it — so the two disagreed depending on
    // whichever page happened to be visited first.
    $student = Student::factory()->create(['department_id' => $this->department->id]);
    $staleAlert = ProgressAlert::factory()->create([
        'student_id' => $student->id,
        'alert_type' => 'multiple_deficiencies',
        'resolved_at' => null,
    ]);

    $dashboard = $this->actingAs($this->admin)->get('/dashboard');
    $dashboard->assertInertia(fn ($page) => $page->where('statCards.4.label', 'Active Alerts')->where('statCards.4.value', 0));

    $atRisk = $this->actingAs($this->admin)->get('/academic-progress');
    $atRisk->assertInertia(fn ($page) => $page->has('students.data', 0));

    expect($staleAlert->fresh()->resolved_at)->not->toBeNull();
});

test('an archived students unresolved alert is excluded from the active alerts count', function () {
    // The deeper form of the same bug: an archived (soft-deleted) student is
    // excluded from every "students in scope" query — including the sync
    // loop — so their old alert rows are never revisited and stay
    // unresolved forever. The count must still exclude them by joining
    // through the student relation (whose SoftDeletes scope drops archived
    // students automatically), the same way the At-Risk listing already
    // does by querying from the Student side.
    $student = Student::factory()->create(['department_id' => $this->department->id]);
    ProgressAlert::factory()->create([
        'student_id' => $student->id,
        'alert_type' => 'enrollment_status',
        'resolved_at' => null,
    ]);
    $student->delete();

    $response = $this->actingAs($this->admin)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page->where('statCards.4.label', 'Active Alerts')->where('statCards.4.value', 0));
});
