<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentAdviser;
use App\Models\StudentAdvisingRecord;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->semester = Semester::factory()->create();

    $this->student = Student::factory()->create(['department_id' => $this->department->id]);
});

test('assigning an adviser via the student form writes a student_advisers history row', function () {
    $this->actingAs($this->admin)->put("/students/{$this->student->id}", [
        'student_number' => $this->student->student_number,
        'surname' => $this->student->surname,
        'first_name' => $this->student->first_name,
        'department_id' => $this->student->department_id,
        'program_id' => $this->student->program_id,
        'curriculum_id' => $this->student->curriculum_id,
        'year_level_id' => $this->student->year_level_id,
        'classification' => $this->student->classification->value,
        'status' => $this->student->status->value,
        'adviser_id' => $this->faculty->id,
    ]);

    $this->assertDatabaseHas('student_advisers', [
        'student_id' => $this->student->id,
        'faculty_id' => $this->faculty->id,
        'unassigned_at' => null,
    ]);
    expect($this->student->fresh()->adviser_id)->toBe($this->faculty->id);
});

test('reassigning an adviser closes the previous history row and opens a new one', function () {
    $this->student->update(['adviser_id' => $this->faculty->id]);

    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->student->update(['adviser_id' => $otherFaculty->id]);

    $this->assertDatabaseHas('student_advisers', ['student_id' => $this->student->id, 'faculty_id' => $this->faculty->id]);
    $firstRow = StudentAdviser::where('student_id', $this->student->id)->where('faculty_id', $this->faculty->id)->firstOrFail();
    expect($firstRow->unassigned_at)->not->toBeNull();

    $activeRow = StudentAdviser::where('student_id', $this->student->id)->whereNull('unassigned_at')->firstOrFail();
    expect($activeRow->faculty_id)->toBe($otherFaculty->id);
});

test('the assigned adviser can log an advising session for their advisee', function () {
    $this->student->update(['adviser_id' => $this->faculty->id]);

    $response = $this->actingAs($this->faculty)->post("/students/{$this->student->id}/advising-records", [
        'semester_id' => $this->semester->id,
        'session_date' => now()->toDateString(),
        'summary' => 'Discussed course load for next semester.',
        'recommendations' => 'Consider dropping one elective.',
        'follow_up_required' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_advising_records', [
        'student_id' => $this->student->id,
        'adviser_id' => $this->faculty->id,
        'summary' => 'Discussed course load for next semester.',
        'follow_up_required' => 1,
    ]);
});

test('a faculty member cannot log a session for a student who is not their advisee', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($otherFaculty)->post("/students/{$this->student->id}/advising-records", [
        'semester_id' => $this->semester->id,
        'session_date' => now()->toDateString(),
        'summary' => 'Should not be allowed.',
    ])->assertForbidden();
});

test('a department head can log a session for any student in their department', function () {
    $response = $this->actingAs($this->head)->post("/students/{$this->student->id}/advising-records", [
        'semester_id' => $this->semester->id,
        'session_date' => now()->toDateString(),
        'summary' => 'Department head check-in.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_advising_records', ['student_id' => $this->student->id, 'adviser_id' => $this->head->id]);
});

test('a dean cannot log an advising session', function () {
    $dean = userWithRole(RoleName::Dean->value);

    $this->actingAs($dean)->post("/students/{$this->student->id}/advising-records", [
        'semester_id' => $this->semester->id,
        'session_date' => now()->toDateString(),
        'summary' => 'Dean attempt.',
    ])->assertForbidden();
});

test('a faculty member only sees their own advisees on the advising index', function () {
    $this->student->update(['adviser_id' => $this->faculty->id]);
    $otherStudent = Student::factory()->create(['department_id' => $this->department->id]);

    $response = $this->actingAs($this->faculty)->get('/advising');

    $response->assertInertia(fn ($page) => $page->has('students.data', 1));
});

test('only the record owner or an authorized manager can delete an advising record', function () {
    $this->student->update(['adviser_id' => $this->faculty->id]);
    $record = StudentAdvisingRecord::factory()->create([
        'student_id' => $this->student->id,
        'adviser_id' => $this->faculty->id,
        'semester_id' => $this->semester->id,
    ]);

    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->actingAs($otherFaculty)->delete("/students/{$this->student->id}/advising-records/{$record->id}")->assertForbidden();

    $this->actingAs($this->faculty)->delete("/students/{$this->student->id}/advising-records/{$record->id}")->assertRedirect();
    $this->assertSoftDeleted('student_advising_records', ['id' => $record->id]);
});
