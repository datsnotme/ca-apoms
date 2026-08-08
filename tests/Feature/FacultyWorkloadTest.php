<?php

use App\Enums\RoleName;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\Semester;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->currentSemester = Semester::factory()->create(['is_current' => true]);
    $this->pastSemester = Semester::factory()->create(['is_current' => false]);

    $this->course = Course::factory()->create(['department_id' => $this->department->id, 'units' => 3]);
    $this->section = ClassSection::factory()->create([
        'course_id' => $this->course->id,
        'semester_id' => $this->currentSemester->id,
    ]);
    $this->section->facultyAssignments()->create(['faculty_id' => $this->faculty->id, 'role' => 'primary']);

    $pastCourse = Course::factory()->create(['department_id' => $this->department->id, 'units' => 5]);
    $pastSection = ClassSection::factory()->create([
        'course_id' => $pastCourse->id,
        'semester_id' => $this->pastSemester->id,
    ]);
    $pastSection->facultyAssignments()->create(['faculty_id' => $this->faculty->id, 'role' => 'primary']);
});

test('a faculty member sees only their own classes for the current semester by default', function () {
    $response = $this->actingAs($this->faculty)->get('/faculty-workload');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('FacultyWorkload/Index')
        ->where('mode', 'own')
        ->where('totalUnits', fn ($units) => (float) $units === 3.0)
        ->has('sections', 1)
    );
});

test('a faculty member can filter their classes to a past semester', function () {
    $response = $this->actingAs($this->faculty)->get('/faculty-workload?semester_id='.$this->pastSemester->id);

    $response->assertInertia(fn ($page) => $page
        ->where('mode', 'own')
        ->where('totalUnits', fn ($units) => (float) $units === 5.0)
        ->has('sections', 1)
    );
});

test('a faculty member cannot view another faculty members workload', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($this->faculty)->get("/faculty-workload/{$otherFaculty->id}")->assertForbidden();
});

test('a department head sees a workload dashboard scoped to their own department', function () {
    $otherDepartment = Department::factory()->create();
    $otherFaculty = userWithRole(RoleName::Faculty->value, $otherDepartment);
    $otherCourse = Course::factory()->create(['department_id' => $otherDepartment->id, 'units' => 4]);
    $otherSection = ClassSection::factory()->create(['course_id' => $otherCourse->id, 'semester_id' => $this->currentSemester->id]);
    $otherSection->facultyAssignments()->create(['faculty_id' => $otherFaculty->id, 'role' => 'primary']);

    $response = $this->actingAs($this->head)->get('/faculty-workload');

    $response->assertInertia(fn ($page) => $page
        ->component('FacultyWorkload/Index')
        ->where('mode', 'dashboard')
        // Both the Department Head themselves and the Faculty member in
        // their department are visible — a Department Head may also teach.
        ->has('workloads', 2)
        ->where('workloads', function ($workloads) {
            $row = collect($workloads)->firstWhere('id', $this->faculty->id);

            return $row && (float) $row['total_units'] === 3.0 && $row['section_count'] === 1;
        })
    );
});

test('a department head can drill into a faculty members classes in their own department', function () {
    $response = $this->actingAs($this->head)->get("/faculty-workload/{$this->faculty->id}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('FacultyWorkload/Show')
        ->where('totalUnits', fn ($units) => (float) $units === 3.0)
        ->has('sections', 1)
    );
});

test('a department head cannot drill into a faculty member from another department', function () {
    $otherDepartment = Department::factory()->create();
    $otherFaculty = userWithRole(RoleName::Faculty->value, $otherDepartment);

    $this->actingAs($this->head)->get("/faculty-workload/{$otherFaculty->id}")->assertForbidden();
});

test('the dean sees a college-wide workload dashboard', function () {
    $otherDepartment = Department::factory()->create();
    $otherFaculty = userWithRole(RoleName::Faculty->value, $otherDepartment);
    $otherCourse = Course::factory()->create(['department_id' => $otherDepartment->id, 'units' => 4]);
    $otherSection = ClassSection::factory()->create(['course_id' => $otherCourse->id, 'semester_id' => $this->currentSemester->id]);
    $otherSection->facultyAssignments()->create(['faculty_id' => $otherFaculty->id, 'role' => 'primary']);

    $response = $this->actingAs($this->dean)->get('/faculty-workload');

    // College-wide: the Department Head, the Faculty member, and the
    // other-department Faculty member — the Dean role itself is never
    // included, only Faculty/Department Head accounts.
    $response->assertInertia(fn ($page) => $page
        ->component('FacultyWorkload/Index')
        ->where('mode', 'dashboard')
        ->has('workloads', 3)
    );
});

test('total units sum correctly across multiple sections assigned to the same faculty member', function () {
    $secondCourse = Course::factory()->create(['department_id' => $this->department->id, 'units' => 2]);
    $secondSection = ClassSection::factory()->create(['course_id' => $secondCourse->id, 'semester_id' => $this->currentSemester->id]);
    $secondSection->facultyAssignments()->create(['faculty_id' => $this->faculty->id, 'role' => 'co-faculty']);

    $response = $this->actingAs($this->faculty)->get('/faculty-workload');

    $response->assertInertia(fn ($page) => $page
        ->where('totalUnits', fn ($units) => (float) $units === 5.0)
        ->has('sections', 2)
    );
});
