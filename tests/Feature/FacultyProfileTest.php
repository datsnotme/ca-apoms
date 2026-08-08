<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\FacultyProfile;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
});

test('viewing a faculty profile for the first time lazily creates it', function () {
    $this->assertDatabaseMissing('faculty_profiles', ['user_id' => $this->faculty->id]);

    $this->actingAs($this->admin)->get("/faculty-profiles/{$this->faculty->id}")->assertOk();

    $this->assertDatabaseHas('faculty_profiles', ['user_id' => $this->faculty->id]);
});

test('an admin can edit every field on any faculty profile', function () {
    $response = $this->actingAs($this->admin)->put("/faculty-profiles/{$this->faculty->id}", [
        'academic_rank' => 'Associate Professor',
        'employment_status' => 'part_time',
        'date_hired' => '2020-06-01',
        'specialization' => 'Soil Science',
        'office_location' => 'Room 204',
        'bio' => 'Focuses on sustainable agriculture.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('faculty_profiles', [
        'user_id' => $this->faculty->id,
        'academic_rank' => 'Associate Professor',
        'employment_status' => 'part_time',
        'specialization' => 'Soil Science',
    ]);
});

test('a faculty member can edit their own profile but not HR-controlled fields', function () {
    FacultyProfile::factory()->create(['user_id' => $this->faculty->id, 'academic_rank' => 'Instructor I']);

    $response = $this->actingAs($this->faculty)->put("/faculty-profiles/{$this->faculty->id}", [
        'academic_rank' => 'Full Professor',
        'employment_status' => 'visiting',
        'specialization' => 'Agronomy',
        'office_location' => 'Room 101',
        'bio' => 'My own bio.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('faculty_profiles', [
        'user_id' => $this->faculty->id,
        'academic_rank' => 'Instructor I',
        'specialization' => 'Agronomy',
        'office_location' => 'Room 101',
    ]);
});

test('a faculty member cannot view or edit another faculty member profile', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($this->faculty)->get("/faculty-profiles/{$otherFaculty->id}")->assertForbidden();
    $this->actingAs($this->faculty)->put("/faculty-profiles/{$otherFaculty->id}", ['specialization' => 'x'])->assertForbidden();
});

test('a department head can view but not edit a profile in their own department', function () {
    $this->actingAs($this->head)->get("/faculty-profiles/{$this->faculty->id}")->assertOk();
    $this->actingAs($this->head)->put("/faculty-profiles/{$this->faculty->id}", ['specialization' => 'x'])->assertForbidden();
});

test('a department head cannot view a profile from another department', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, Department::factory()->create());

    $this->actingAs($this->head)->get("/faculty-profiles/{$otherFaculty->id}")->assertForbidden();
});

test('a dean can view any faculty profile college-wide, read-only', function () {
    $this->actingAs($this->dean)->get("/faculty-profiles/{$this->faculty->id}")->assertOk();
    $this->actingAs($this->dean)->put("/faculty-profiles/{$this->faculty->id}", ['specialization' => 'x'])->assertForbidden();
});

test('a non-faculty user has no faculty profile route', function () {
    $this->actingAs($this->admin)->get("/faculty-profiles/{$this->head->id}")->assertNotFound();
});

test('the index is scoped per role', function () {
    $otherDeptFaculty = userWithRole(RoleName::Faculty->value, Department::factory()->create());

    $headResponse = $this->actingAs($this->head)->get('/faculty-profiles');
    $headResponse->assertInertia(fn ($page) => $page->has('faculty.data', 1));

    $facultyResponse = $this->actingAs($this->faculty)->get('/faculty-profiles');
    $facultyResponse->assertInertia(fn ($page) => $page->has('faculty.data', 1));

    $adminResponse = $this->actingAs($this->admin)->get('/faculty-profiles');
    $adminResponse->assertInertia(fn ($page) => $page->has('faculty.data', 2));
});
