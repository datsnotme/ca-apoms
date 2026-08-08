<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Meeting;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->otherFaculty = userWithRole(RoleName::Faculty->value, $this->otherDepartment);
});

test('an admin can schedule a college-wide meeting', function () {
    $response = $this->actingAs($this->admin)->post('/meetings', [
        'title' => 'College Assembly',
        'start_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'department_id' => '',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('meetings', ['title' => 'College Assembly', 'department_id' => null]);
});

test('a department head can only schedule a meeting scoped to their own department', function () {
    $response = $this->actingAs($this->head)->post('/meetings', [
        'title' => 'Dept Meeting',
        'start_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'department_id' => $this->otherDepartment->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('meetings', ['title' => 'Dept Meeting', 'department_id' => $this->department->id]);
});

test('a dean and a faculty member cannot schedule a meeting', function () {
    $this->actingAs($this->dean)->post('/meetings', [
        'title' => 'X', 'start_at' => now()->addWeek()->format('Y-m-d H:i:s'), 'department_id' => '',
    ])->assertForbidden();

    $this->actingAs($this->faculty)->post('/meetings', [
        'title' => 'X', 'start_at' => now()->addWeek()->format('Y-m-d H:i:s'), 'department_id' => '',
    ])->assertForbidden();
});

test('a department head cannot edit a college-wide meeting or another departments meeting', function () {
    $collegeWide = Meeting::factory()->create(['department_id' => null, 'created_by' => $this->admin->id]);
    $otherDept = Meeting::factory()->create(['department_id' => $this->otherDepartment->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->head)->put("/meetings/{$collegeWide->id}", [
        'title' => 'Updated', 'start_at' => now()->addWeek()->format('Y-m-d H:i:s'), 'department_id' => '',
    ])->assertForbidden();

    $this->actingAs($this->head)->put("/meetings/{$otherDept->id}", [
        'title' => 'Updated', 'start_at' => now()->addWeek()->format('Y-m-d H:i:s'), 'department_id' => $this->otherDepartment->id,
    ])->assertForbidden();
});

test('a faculty member cannot view a meeting scoped to another department', function () {
    $otherDeptMeeting = Meeting::factory()->create(['department_id' => $this->otherDepartment->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->faculty)->get("/meetings/{$otherDeptMeeting->id}")->assertForbidden();
});

// --- Attendees ---

test('a department head can invite an attendee to their own meeting and mark attendance', function () {
    $meeting = Meeting::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->head->id]);

    $response = $this->actingAs($this->head)->post("/meetings/{$meeting->id}/attendees", [
        'user_id' => $this->faculty->id,
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('meeting_attendees', ['meeting_id' => $meeting->id, 'user_id' => $this->faculty->id, 'attended' => false]);

    $attendee = $meeting->attendees()->first();
    $this->actingAs($this->head)->patch("/meetings/{$meeting->id}/attendees/{$attendee->id}", ['attended' => true])
        ->assertRedirect();
    $this->assertDatabaseHas('meeting_attendees', ['id' => $attendee->id, 'attended' => true]);
});

test('a faculty member cannot invite attendees to a meeting', function () {
    $meeting = Meeting::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->head->id]);

    $this->actingAs($this->faculty)->post("/meetings/{$meeting->id}/attendees", [
        'user_id' => $this->faculty->id,
    ])->assertForbidden();
});

test('the same user cannot be invited to the same meeting twice', function () {
    $meeting = Meeting::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->head->id]);
    $meeting->attendees()->create(['user_id' => $this->faculty->id, 'invited_by' => $this->head->id]);

    $this->actingAs($this->head)->post("/meetings/{$meeting->id}/attendees", [
        'user_id' => $this->faculty->id,
    ])->assertSessionHasErrors('user_id');
});

// --- Action Items ---

test('a department head can add an action item and assign it to an attendee', function () {
    $meeting = Meeting::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->head->id]);

    $response = $this->actingAs($this->head)->post("/meetings/{$meeting->id}/action-items", [
        'description' => 'Prepare budget report',
        'assigned_to' => $this->faculty->id,
        'due_date' => now()->addWeek()->format('Y-m-d'),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('meeting_action_items', [
        'meeting_id' => $meeting->id,
        'description' => 'Prepare budget report',
        'assigned_to' => $this->faculty->id,
        'status' => 'pending',
    ]);
});

test('a faculty member cannot create an action item but can update the status of one assigned to them', function () {
    $meeting = Meeting::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->head->id]);

    $this->actingAs($this->faculty)->post("/meetings/{$meeting->id}/action-items", [
        'description' => 'Not allowed',
    ])->assertForbidden();

    $item = $meeting->actionItems()->create([
        'description' => 'Prepare handouts',
        'assigned_to' => $this->faculty->id,
        'status' => 'pending',
        'created_by' => $this->head->id,
    ]);

    $response = $this->actingAs($this->faculty)->put("/meetings/{$meeting->id}/action-items/{$item->id}", [
        'status' => 'completed',
    ]);
    $response->assertRedirect();

    $item->refresh();
    expect($item->status->value)->toBe('completed');
    expect($item->completed_by)->toBe($this->faculty->id);
    expect($item->completed_at)->not->toBeNull();
});

test('a faculty member cannot update an action item assigned to someone else', function () {
    $meeting = Meeting::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->head->id]);
    $item = $meeting->actionItems()->create([
        'description' => 'Prepare handouts',
        'assigned_to' => $this->otherFaculty->id,
        'status' => 'pending',
        'created_by' => $this->head->id,
    ]);

    $this->actingAs($this->faculty)->put("/meetings/{$meeting->id}/action-items/{$item->id}", [
        'status' => 'completed',
    ])->assertForbidden();
});

test('reverting a completed action item to pending clears the completion attribution', function () {
    $meeting = Meeting::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->head->id]);
    $item = $meeting->actionItems()->create([
        'description' => 'Prepare handouts',
        'status' => 'completed',
        'completed_by' => $this->head->id,
        'completed_at' => now(),
        'created_by' => $this->head->id,
    ]);

    $this->actingAs($this->head)->put("/meetings/{$meeting->id}/action-items/{$item->id}", [
        'status' => 'pending',
    ])->assertRedirect();

    $item->refresh();
    expect($item->completed_by)->toBeNull();
    expect($item->completed_at)->toBeNull();
});

test('an admin can bulk remove multiple meetings at once', function () {
    $meetings = Meeting::factory()->count(3)->create(['department_id' => null, 'created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->delete('/meetings/bulk-destroy', [
        'ids' => $meetings->pluck('id')->all(),
    ]);

    $response->assertRedirect('/meetings')->assertSessionHas('success', '3 meeting(s) removed.');
    $meetings->each(fn (Meeting $m) => $this->assertSoftDeleted('meetings', ['id' => $m->id]));
});

test('bulk-deleting meetings is scoped the same way as editing one', function () {
    $deptMeeting = Meeting::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->admin->id]);
    $otherDeptMeeting = Meeting::factory()->create(['department_id' => $this->otherDepartment->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->head)->delete('/meetings/bulk-destroy', [
        'ids' => [$deptMeeting->id, $otherDeptMeeting->id],
    ])->assertForbidden();

    $this->assertDatabaseHas('meetings', ['id' => $deptMeeting->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('meetings', ['id' => $otherDeptMeeting->id, 'deleted_at' => null]);
});
