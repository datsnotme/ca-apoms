<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Student;
use App\Models\StudentInterventionFollowup;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->student = Student::factory()->create(['department_id' => $this->department->id, 'adviser_id' => $this->faculty->id]);
});

test('the assigned adviser can create a follow-up for their advisee', function () {
    $response = $this->actingAs($this->faculty)->post("/students/{$this->student->id}/followups", [
        'description' => 'Refer to the tutoring center.',
        'due_date' => now()->addWeek()->toDateString(),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_intervention_followups', [
        'student_id' => $this->student->id,
        'description' => 'Refer to the tutoring center.',
        'created_by' => $this->faculty->id,
        'status' => 'pending',
    ]);
});

test('a faculty member cannot create a follow-up for a student who is not their advisee', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($otherFaculty)->post("/students/{$this->student->id}/followups", [
        'description' => 'Should not be allowed.',
    ])->assertForbidden();
});

test('a status-only update (no description) succeeds, matching the quick-action buttons in the UI', function () {
    $followup = StudentInterventionFollowup::factory()->create([
        'student_id' => $this->student->id,
        'created_by' => $this->faculty->id,
    ]);

    $response = $this->actingAs($this->faculty)->put("/students/{$this->student->id}/followups/{$followup->id}", [
        'status' => 'completed',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('student_intervention_followups', ['id' => $followup->id, 'status' => 'completed']);
});

test('marking a follow-up completed records who and when', function () {
    $followup = StudentInterventionFollowup::factory()->create([
        'student_id' => $this->student->id,
        'created_by' => $this->faculty->id,
    ]);

    $response = $this->actingAs($this->faculty)->put("/students/{$this->student->id}/followups/{$followup->id}", [
        'description' => $followup->description,
        'status' => 'completed',
    ]);

    $response->assertRedirect();
    $followup->refresh();
    expect($followup->status->value)->toBe('completed');
    expect($followup->completed_by)->toBe($this->faculty->id);
    expect($followup->completed_at)->not->toBeNull();
});

test('reopening a completed follow-up clears the completion fields', function () {
    $followup = StudentInterventionFollowup::factory()->create([
        'student_id' => $this->student->id,
        'created_by' => $this->faculty->id,
        'status' => 'completed',
        'completed_by' => $this->faculty->id,
        'completed_at' => now(),
    ]);

    $this->actingAs($this->faculty)->put("/students/{$this->student->id}/followups/{$followup->id}", [
        'description' => $followup->description,
        'status' => 'in_progress',
    ]);

    $followup->refresh();
    expect($followup->completed_by)->toBeNull();
    expect($followup->completed_at)->toBeNull();
});

test('a follow-up assigned to a different faculty member can still be updated by them', function () {
    $tutor = userWithRole(RoleName::Faculty->value, $this->department);
    $followup = StudentInterventionFollowup::factory()->create([
        'student_id' => $this->student->id,
        'created_by' => $this->faculty->id,
        'assigned_to' => $tutor->id,
    ]);

    $response = $this->actingAs($tutor)->put("/students/{$this->student->id}/followups/{$followup->id}", [
        'description' => $followup->description,
        'status' => 'completed',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_intervention_followups', ['id' => $followup->id, 'status' => 'completed']);
});

test('a department head can create and manage follow-ups for any student in their department', function () {
    $response = $this->actingAs($this->head)->post("/students/{$this->student->id}/followups", [
        'description' => 'Department head follow-up.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('student_intervention_followups', ['student_id' => $this->student->id, 'created_by' => $this->head->id]);
});

test('a dean cannot create a follow-up', function () {
    $dean = userWithRole(RoleName::Dean->value);

    $this->actingAs($dean)->post("/students/{$this->student->id}/followups", [
        'description' => 'Dean attempt.',
    ])->assertForbidden();
});

test('deleting a follow-up soft deletes it', function () {
    $followup = StudentInterventionFollowup::factory()->create([
        'student_id' => $this->student->id,
        'created_by' => $this->faculty->id,
    ]);

    $this->actingAs($this->faculty)->delete("/students/{$this->student->id}/followups/{$followup->id}")->assertRedirect();

    $this->assertSoftDeleted('student_intervention_followups', ['id' => $followup->id]);
});
