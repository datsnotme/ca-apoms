<?php

use App\Enums\RoleName;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\YearLevel;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $this->yearLevel = YearLevel::factory()->create(['level' => 1]);
});

test('an admin can register a student', function () {
    $response = $this->actingAs($this->admin)->post('/students', [
        'student_number' => '2026-00001',
        'surname' => 'Dela Cruz',
        'first_name' => 'Juan',
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
        'classification' => 'regular',
        'status' => 'active',
        'guardian_name' => 'Maria Dela Cruz',
        'permanent_address' => 'Zamboanga City',
    ]);

    $student = Student::where('student_number', '2026-00001')->firstOrFail();
    $response->assertRedirect("/students/{$student->id}/edit");

    $this->assertDatabaseHas('students', ['student_number' => '2026-00001']);
    $this->assertDatabaseHas('student_guardians', ['student_id' => $student->id, 'type' => 'guardian', 'name' => 'Maria Dela Cruz']);
    $this->assertDatabaseHas('student_addresses', ['student_id' => $student->id, 'type' => 'permanent', 'address_line' => 'Zamboanga City']);
    $this->assertDatabaseHas('student_status_histories', ['student_id' => $student->id, 'to_status' => 'active', 'from_status' => null]);
});

test('student numbers must be unique', function () {
    Student::factory()->create([
        'student_number' => '2026-00001',
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);

    $response = $this->actingAs($this->admin)->post('/students', [
        'student_number' => '2026-00001',
        'surname' => 'Santos',
        'first_name' => 'Pedro',
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
        'classification' => 'regular',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('student_number');
});

test('changing a student status records a status history entry with a reason', function () {
    $student = Student::factory()->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)->put("/students/{$student->id}", [
        'student_number' => $student->student_number,
        'surname' => $student->surname,
        'first_name' => $student->first_name,
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
        'classification' => 'regular',
        'status' => 'on_leave',
        'status_reason' => 'Medical leave',
    ]);

    $this->assertDatabaseHas('student_status_histories', [
        'student_id' => $student->id,
        'from_status' => 'active',
        'to_status' => 'on_leave',
        'reason' => 'Medical leave',
    ]);
});

test('a faculty member can view but not manage students', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($faculty)->get('/students')->assertOk();
    $this->actingAs($faculty)->get('/students/create')->assertForbidden();
});

test('the year level filter narrows the student list', function () {
    $otherYearLevel = YearLevel::factory()->create(['level' => 2]);

    Student::factory()->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);
    Student::factory()->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $otherYearLevel->id,
    ]);

    $response = $this->actingAs($this->admin)->get("/students?year_level_id={$this->yearLevel->id}");

    $response->assertInertia(fn ($page) => $page->has('students.data', 1)
        ->where('students.data.0.year_level.label', $this->yearLevel->label));
});

test('a department head only sees students from their own department', function () {
    $otherDepartment = Department::factory()->create();
    $otherProgram = Program::factory()->create(['department_id' => $otherDepartment->id]);
    $otherCurriculum = Curriculum::factory()->create(['program_id' => $otherProgram->id]);

    Student::factory()->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);
    Student::factory()->create([
        'department_id' => $otherDepartment->id,
        'program_id' => $otherProgram->id,
        'curriculum_id' => $otherCurriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);

    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $response = $this->actingAs($head)->get('/students');

    $response->assertInertia(fn ($page) => $page->has('students.data', 1));
});

test('a department head cannot plant a student in another department via a crafted department_id', function () {
    $otherDepartment = Department::factory()->create();
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->actingAs($head)->post('/students', [
        'student_number' => '2026-00099',
        'surname' => 'Cruz',
        'first_name' => 'Ana',
        'department_id' => $otherDepartment->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
        'classification' => 'regular',
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('students', [
        'student_number' => '2026-00099',
        'department_id' => $this->department->id,
    ]);
    $this->assertDatabaseMissing('students', [
        'student_number' => '2026-00099',
        'department_id' => $otherDepartment->id,
    ]);
});

test('archiving a student soft deletes the record', function () {
    $student = Student::factory()->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);

    $this->actingAs($this->admin)->delete("/students/{$student->id}")->assertRedirect('/students');

    $this->assertSoftDeleted('students', ['id' => $student->id]);
});

test('an admin can bulk archive multiple students at once', function () {
    $students = Student::factory()->count(3)->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);

    $response = $this->actingAs($this->admin)->delete('/students/bulk-destroy', [
        'ids' => $students->pluck('id')->all(),
    ]);

    $response->assertRedirect('/students')->assertSessionHas('success', '3 student(s) archived.');
    $students->each(fn (Student $s) => $this->assertSoftDeleted('students', ['id' => $s->id]));
});

test('a department head bulk-selecting a student from another department deletes nothing', function () {
    $otherDepartment = Department::factory()->create();
    $otherProgram = Program::factory()->create(['department_id' => $otherDepartment->id]);
    $otherCurriculum = Curriculum::factory()->create(['program_id' => $otherProgram->id]);

    $myStudent = Student::factory()->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);
    $otherStudent = Student::factory()->create([
        'department_id' => $otherDepartment->id,
        'program_id' => $otherProgram->id,
        'curriculum_id' => $otherCurriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);

    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->actingAs($head)->delete('/students/bulk-destroy', [
        'ids' => [$myStudent->id, $otherStudent->id],
    ])->assertForbidden();

    // All-or-nothing: even the student the head WAS allowed to delete stays untouched.
    $this->assertDatabaseHas('students', ['id' => $myStudent->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('students', ['id' => $otherStudent->id, 'deleted_at' => null]);
});
