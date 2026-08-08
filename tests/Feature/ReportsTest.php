<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Student;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
});

test('admin, dean, and department head can view the reports index; faculty cannot', function () {
    $this->actingAs($this->admin)->get('/reports')->assertOk();
    $this->actingAs($this->dean)->get('/reports')->assertOk();
    $this->actingAs($this->head)->get('/reports')->assertOk();
    $this->actingAs($this->faculty)->get('/reports')->assertForbidden();
});

test('every report type renders for an authorized role', function () {
    foreach (['enrollment', 'grades', 'at-risk', 'faculty-workload', 'graduation-pipeline'] as $type) {
        $this->actingAs($this->admin)->get("/reports/{$type}")->assertOk()
            ->assertInertia(fn ($page) => $page->where('type', $type));
    }
});

test('an unknown report type 404s instead of erroring', function () {
    $this->actingAs($this->admin)->get('/reports/not-a-real-report')->assertNotFound();
});

test('a faculty member is forbidden from every report route, including pdf and excel', function () {
    $this->actingAs($this->faculty)->get('/reports/enrollment')->assertForbidden();
    $this->actingAs($this->faculty)->get('/reports/enrollment/pdf')->assertForbidden();
    $this->actingAs($this->faculty)->get('/reports/enrollment/excel')->assertForbidden();
});

test('the enrollment report is scoped to the department heads own department only', function () {
    Student::factory()->create(['department_id' => $this->department->id, 'status' => 'active']);
    Student::factory()->create(['department_id' => $this->otherDepartment->id, 'status' => 'active']);

    $response = $this->actingAs($this->head)->get('/reports/enrollment');

    $response->assertOk()->assertInertia(fn ($page) => $page->where('scopeDescription', $this->department->name));
});

test('an admin can narrow the enrollment report to a specific department via filter', function () {
    $response = $this->actingAs($this->admin)->get('/reports/enrollment?department_id='.$this->department->id);

    $response->assertOk()->assertInertia(fn ($page) => $page->where('scopeDescription', $this->department->name));
});

test('the pdf export streams a pdf response', function () {
    $response = $this->actingAs($this->admin)->get('/reports/enrollment/pdf');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('the excel export streams a downloadable spreadsheet', function () {
    $response = $this->actingAs($this->admin)->get('/reports/enrollment/excel');

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('enrollment-report.xlsx');
});
