<?php

use App\Enums\RoleName;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Program;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->academicYear = AcademicYear::factory()->create();
});

test('an admin can create a curriculum', function () {
    $response = $this->actingAs($this->admin)->post('/curricula', [
        'program_id' => $this->program->id,
        'effective_academic_year_id' => $this->academicYear->id,
        'code' => 'BSA-CS-2026',
        'name' => 'BS Agriculture (Crop Science) 2026',
        'status' => 'active',
    ]);

    $curriculum = Curriculum::where('code', 'BSA-CS-2026')->firstOrFail();
    $response->assertRedirect("/curricula/{$curriculum->id}/edit");
});

test('a course can be added to a curriculum with year level and semester', function () {
    $curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $course = Course::factory()->create(['department_id' => $this->department->id, 'units' => 3]);

    $response = $this->actingAs($this->admin)->post("/curricula/{$curriculum->id}/courses", [
        'course_id' => $course->id,
        'year_level' => 1,
        'semester' => 'FIRST',
        'is_required' => true,
        'units' => 3,
    ]);

    $response->assertRedirect("/curricula/{$curriculum->id}/edit");
    $this->assertDatabaseHas('curriculum_courses', [
        'curriculum_id' => $curriculum->id,
        'course_id' => $course->id,
        'year_level' => 1,
    ]);
});

test('the same course cannot be added to a curriculum twice', function () {
    $curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $curriculum->curriculumCourses()->create([
        'course_id' => $course->id, 'year_level' => 1, 'semester' => 'FIRST', 'units' => 3,
    ]);

    $response = $this->actingAs($this->admin)->post("/curricula/{$curriculum->id}/courses", [
        'course_id' => $course->id,
        'year_level' => 2,
        'semester' => 'SECOND',
        'units' => 3,
    ]);

    $response->assertSessionHasErrors('course_id');
});

test('a course can be removed from a curriculum', function () {
    $curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $curriculumCourse = $curriculum->curriculumCourses()->create([
        'course_id' => $course->id, 'year_level' => 1, 'semester' => 'FIRST', 'units' => 3,
    ]);

    $this->actingAs($this->admin)->delete("/curricula/{$curriculum->id}/courses/{$curriculumCourse->id}");

    $this->assertDatabaseMissing('curriculum_courses', ['id' => $curriculumCourse->id]);
});

test('an admin can bulk archive multiple curricula at once', function () {
    $curricula = Curriculum::factory()->count(3)->create(['program_id' => $this->program->id]);

    $response = $this->actingAs($this->admin)->delete('/curricula/bulk-destroy', [
        'ids' => $curricula->pluck('id')->all(),
    ]);

    $response->assertRedirect('/curricula')->assertSessionHas('success', '3 curriculum(s) archived.');
    $curricula->each(fn (Curriculum $c) => $this->assertSoftDeleted('curricula', ['id' => $c->id]));
});

test('a department head cannot bulk delete curricula at all, since curricula.manage is admin-only', function () {
    $curricula = Curriculum::factory()->count(2)->create(['program_id' => $this->program->id]);
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->actingAs($head)->delete('/curricula/bulk-destroy', [
        'ids' => $curricula->pluck('id')->all(),
    ])->assertForbidden();

    $curricula->each(fn (Curriculum $c) => $this->assertDatabaseHas('curricula', ['id' => $c->id, 'deleted_at' => null]));
});

test('CurriculumPolicy still scopes delete by department for defense-in-depth, even though curricula.manage is admin-only today', function () {
    $otherDepartment = Department::factory()->create();
    $otherProgram = Program::factory()->create(['department_id' => $otherDepartment->id]);

    $myCurriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $otherCurriculum = Curriculum::factory()->create(['program_id' => $otherProgram->id]);

    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $head->givePermissionTo('curricula.manage');

    $this->actingAs($head)->delete('/curricula/bulk-destroy', [
        'ids' => [$myCurriculum->id, $otherCurriculum->id],
    ])->assertForbidden();

    // All-or-nothing: even the in-department curriculum stays untouched.
    $this->assertDatabaseHas('curricula', ['id' => $myCurriculum->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('curricula', ['id' => $otherCurriculum->id, 'deleted_at' => null]);
});

test('a faculty member cannot add courses to a curriculum', function () {
    $curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $response = $this->actingAs($faculty)->post("/curricula/{$curriculum->id}/courses", [
        'course_id' => $course->id,
        'year_level' => 1,
        'semester' => 'FIRST',
        'units' => 3,
    ]);

    $response->assertForbidden();
});
