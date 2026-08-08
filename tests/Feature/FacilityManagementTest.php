<?php

use App\Enums\RoleName;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\Facility;
use App\Models\Semester;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
});

test('an admin can register a shared, college-wide facility', function () {
    $response = $this->actingAs($this->admin)->post('/facilities', [
        'name' => 'Main Lecture Hall',
        'type' => 'Classroom',
        'department_id' => '',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('facilities', [
        'name' => 'Main Lecture Hall',
        'department_id' => null,
        'created_by' => $this->admin->id,
    ]);
});

test('a department head can only register a facility scoped to their own department, regardless of input', function () {
    $response = $this->actingAs($this->head)->post('/facilities', [
        'name' => 'Crop Science Lab 1',
        'type' => 'Laboratory',
        'department_id' => $this->otherDepartment->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('facilities', [
        'name' => 'Crop Science Lab 1',
        'department_id' => $this->department->id,
    ]);
});

test('a dean and a faculty member cannot register a facility', function () {
    $this->actingAs($this->dean)->post('/facilities', [
        'name' => 'X', 'type' => 'Classroom', 'department_id' => '',
    ])->assertForbidden();

    $this->actingAs($this->faculty)->post('/facilities', [
        'name' => 'X', 'type' => 'Classroom', 'department_id' => '',
    ])->assertForbidden();
});

test('a shared facility is visible to everyone, a department facility only to that department', function () {
    $shared = Facility::factory()->create(['department_id' => null, 'name' => 'Shared Hall']);
    $scoped = Facility::factory()->create(['department_id' => $this->department->id, 'name' => 'Dept Lab']);
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->otherDepartment);

    $this->actingAs($this->faculty)->get('/facilities')->assertOk()
        ->assertInertia(fn ($page) => $page->where('facilities.data', fn ($rows) => collect($rows)->pluck('name')->contains('Shared Hall')
            && collect($rows)->pluck('name')->contains('Dept Lab')));

    $this->actingAs($otherFaculty)->get('/facilities')->assertOk()
        ->assertInertia(fn ($page) => $page->where('facilities.data', fn ($rows) => collect($rows)->pluck('name')->contains('Shared Hall')
            && ! collect($rows)->pluck('name')->contains('Dept Lab')));
});

test('a department head cannot edit a shared facility or another departments facility', function () {
    $shared = Facility::factory()->create(['department_id' => null, 'created_by' => $this->admin->id]);
    $otherDept = Facility::factory()->create(['department_id' => $this->otherDepartment->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->head)->put("/facilities/{$shared->id}", [
        'name' => 'Updated', 'type' => 'Classroom', 'department_id' => '',
    ])->assertForbidden();

    $this->actingAs($this->head)->put("/facilities/{$otherDept->id}", [
        'name' => 'Updated', 'type' => 'Classroom', 'department_id' => $this->otherDepartment->id,
    ])->assertForbidden();
});

test('facility names must be unique', function () {
    Facility::factory()->create(['name' => 'Duplicate Hall']);

    $this->actingAs($this->admin)->post('/facilities', [
        'name' => 'Duplicate Hall', 'type' => 'Classroom', 'department_id' => '',
    ])->assertSessionHasErrors('name');
});

// --- Class schedule integration ---

test('a class schedule can be assigned a facility', function () {
    $facility = Facility::factory()->create(['department_id' => null]);
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $semester = Semester::factory()->create();
    $classSection = ClassSection::factory()->create(['course_id' => $course->id, 'semester_id' => $semester->id]);

    $response = $this->actingAs($this->admin)->post("/class-sections/{$classSection->id}/schedules", [
        'day_of_week' => 'monday',
        'start_time' => '08:00',
        'end_time' => '09:00',
        'facility_id' => $facility->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('class_schedules', [
        'class_section_id' => $classSection->id,
        'facility_id' => $facility->id,
    ]);
});
