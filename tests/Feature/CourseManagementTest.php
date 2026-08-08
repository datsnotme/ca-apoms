<?php

use App\Enums\RoleName;
use App\Models\Course;
use App\Models\Department;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
});

test('an admin can create a course', function () {
    $response = $this->actingAs($this->admin)->post('/courses', [
        'department_id' => $this->department->id,
        'code' => 'AGRO101',
        'title' => 'Introduction to Crop Science',
        'units' => 3,
        'category' => 'crop_science',
        'is_active' => true,
    ]);

    $response->assertRedirect('/courses');
    $this->assertDatabaseHas('courses', ['code' => 'AGRO101']);
});

test('course codes must be unique', function () {
    Course::factory()->create(['department_id' => $this->department->id, 'code' => 'AGRO101']);

    $response = $this->actingAs($this->admin)->post('/courses', [
        'department_id' => $this->department->id,
        'code' => 'AGRO101',
        'title' => 'Duplicate',
        'units' => 3,
        'category' => 'crop_science',
    ]);

    $response->assertSessionHasErrors('code');
});

test('prerequisites and corequisites are saved when creating a course', function () {
    $prereq = Course::factory()->create(['department_id' => $this->department->id]);
    $coreq = Course::factory()->create(['department_id' => $this->department->id]);

    $this->actingAs($this->admin)->post('/courses', [
        'department_id' => $this->department->id,
        'code' => 'AGRO201',
        'title' => 'Advanced Crop Science',
        'units' => 3,
        'category' => 'crop_science',
        'prerequisite_ids' => [$prereq->id],
        'corequisite_ids' => [$coreq->id],
    ]);

    $course = Course::where('code', 'AGRO201')->firstOrFail();
    expect($course->prerequisites->pluck('id')->all())->toBe([$prereq->id]);
    expect($course->corequisites->pluck('id')->all())->toBe([$coreq->id]);
});

test('a faculty member can view but not manage courses', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($faculty)->get('/courses')->assertOk();
    $this->actingAs($faculty)->get('/courses/create')->assertForbidden();
});

test('an admin can bulk archive multiple courses at once', function () {
    $courses = Course::factory()->count(3)->create(['department_id' => $this->department->id]);

    $response = $this->actingAs($this->admin)->delete('/courses/bulk-destroy', [
        'ids' => $courses->pluck('id')->all(),
    ]);

    $response->assertRedirect('/courses')->assertSessionHas('success', '3 course(s) archived.');
    $courses->each(fn (Course $c) => $this->assertSoftDeleted('courses', ['id' => $c->id]));
});

test('a faculty member cannot bulk delete courses', function () {
    $courses = Course::factory()->count(2)->create(['department_id' => $this->department->id]);
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($faculty)->delete('/courses/bulk-destroy', [
        'ids' => $courses->pluck('id')->all(),
    ])->assertForbidden();

    $courses->each(fn (Course $c) => $this->assertDatabaseHas('courses', ['id' => $c->id, 'deleted_at' => null]));
});

test('a department head only sees courses from their own department', function () {
    $otherDepartment = Department::factory()->create();
    Course::factory()->create(['department_id' => $this->department->id]);
    Course::factory()->create(['department_id' => $otherDepartment->id]);

    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $response = $this->actingAs($head)->get('/courses');

    $response->assertInertia(fn ($page) => $page->has('courses.data', 1));
});
