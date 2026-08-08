<?php

use App\Enums\RoleName;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Event;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->otherFaculty = userWithRole(RoleName::Faculty->value, $this->otherDepartment);
});

// --- Announcements ---

test('an admin can post a college-wide announcement', function () {
    $response = $this->actingAs($this->admin)->post('/announcements', [
        'title' => 'Semester Break',
        'body' => 'Classes are suspended next week.',
        'department_id' => '',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('announcements', [
        'title' => 'Semester Break',
        'department_id' => null,
        'created_by' => $this->admin->id,
    ]);
});

test('a department head can only post an announcement scoped to their own department, regardless of input', function () {
    $response = $this->actingAs($this->head)->post('/announcements', [
        'title' => 'Department Meeting',
        'body' => 'Meeting Friday.',
        'department_id' => $this->otherDepartment->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('announcements', [
        'title' => 'Department Meeting',
        'department_id' => $this->department->id,
    ]);
});

test('a dean and a faculty member cannot post an announcement', function () {
    $this->actingAs($this->dean)->post('/announcements', [
        'title' => 'X', 'body' => 'Y', 'department_id' => '',
    ])->assertForbidden();

    $this->actingAs($this->faculty)->post('/announcements', [
        'title' => 'X', 'body' => 'Y', 'department_id' => '',
    ])->assertForbidden();
});

test('a college-wide announcement is visible to everyone, a department announcement only to that department', function () {
    Announcement::factory()->create(['department_id' => null, 'created_by' => $this->admin->id, 'title' => 'College Wide']);
    Announcement::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->admin->id, 'title' => 'Dept Only']);

    $response = $this->actingAs($this->otherFaculty)->get('/announcements');
    $response->assertInertia(fn ($page) => $page->has('announcements.data', 1));

    $response = $this->actingAs($this->faculty)->get('/announcements');
    $response->assertInertia(fn ($page) => $page->has('announcements.data', 2));
});

test('a department head can edit a department-scoped announcement in their department but not a college-wide one', function () {
    $deptAnnouncement = Announcement::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->admin->id]);
    $collegeAnnouncement = Announcement::factory()->create(['department_id' => null, 'created_by' => $this->admin->id]);

    $this->actingAs($this->head)->put("/announcements/{$deptAnnouncement->id}", [
        'title' => 'Updated', 'body' => 'Updated body', 'department_id' => $this->department->id,
    ])->assertRedirect();

    $this->actingAs($this->head)->put("/announcements/{$collegeAnnouncement->id}", [
        'title' => 'Updated', 'body' => 'Updated body', 'department_id' => '',
    ])->assertForbidden();
});

test('a department head cannot edit another departments announcement', function () {
    $otherDeptAnnouncement = Announcement::factory()->create(['department_id' => $this->otherDepartment->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->head)->put("/announcements/{$otherDeptAnnouncement->id}", [
        'title' => 'Updated', 'body' => 'Updated body', 'department_id' => $this->otherDepartment->id,
    ])->assertForbidden();
});

test('deleting an announcement is scoped the same way as editing it', function () {
    $deptAnnouncement = Announcement::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->faculty)->delete("/announcements/{$deptAnnouncement->id}")->assertForbidden();
    $this->actingAs($this->head)->delete("/announcements/{$deptAnnouncement->id}")->assertRedirect();
    $this->assertSoftDeleted('announcements', ['id' => $deptAnnouncement->id]);
});

test('an admin can bulk remove multiple announcements at once', function () {
    $announcements = Announcement::factory()->count(3)->create(['department_id' => null, 'created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->delete('/announcements/bulk-destroy', [
        'ids' => $announcements->pluck('id')->all(),
    ]);

    $response->assertRedirect('/announcements')->assertSessionHas('success', '3 announcement(s) removed.');
    $announcements->each(fn (Announcement $a) => $this->assertSoftDeleted('announcements', ['id' => $a->id]));
});

test('bulk-deleting announcements is scoped the same way as deleting one', function () {
    $deptAnnouncement = Announcement::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->admin->id]);
    $otherDeptAnnouncement = Announcement::factory()->create(['department_id' => $this->otherDepartment->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->head)->delete('/announcements/bulk-destroy', [
        'ids' => [$deptAnnouncement->id, $otherDeptAnnouncement->id],
    ])->assertForbidden();

    $this->assertDatabaseHas('announcements', ['id' => $deptAnnouncement->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('announcements', ['id' => $otherDeptAnnouncement->id, 'deleted_at' => null]);
});

// --- Events ---

test('an admin can add a college-wide event', function () {
    $response = $this->actingAs($this->admin)->post('/events', [
        'title' => 'Foundation Day',
        'start_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'department_id' => '',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('events', ['title' => 'Foundation Day', 'department_id' => null]);
});

test('a department head can only add an event scoped to their own department', function () {
    $response = $this->actingAs($this->head)->post('/events', [
        'title' => 'Dept Seminar',
        'start_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'department_id' => $this->otherDepartment->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('events', ['title' => 'Dept Seminar', 'department_id' => $this->department->id]);
});

test('an event end date before its start date is rejected', function () {
    $this->actingAs($this->admin)->post('/events', [
        'title' => 'Bad Event',
        'start_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'end_at' => now()->format('Y-m-d H:i:s'),
        'department_id' => '',
    ])->assertSessionHasErrors('end_at');
});

test('past events are excluded by default but included when requested', function () {
    Event::factory()->create(['department_id' => null, 'created_by' => $this->admin->id, 'start_at' => now()->subMonth()]);
    Event::factory()->create(['department_id' => null, 'created_by' => $this->admin->id, 'start_at' => now()->addMonth()]);

    $this->actingAs($this->faculty)->get('/events')
        ->assertInertia(fn ($page) => $page->has('events.data', 1));

    $this->actingAs($this->faculty)->get('/events?include_past=1')
        ->assertInertia(fn ($page) => $page->has('events.data', 2));
});

test('a department head cannot edit an event from another department', function () {
    $otherDeptEvent = Event::factory()->create(['department_id' => $this->otherDepartment->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->head)->put("/events/{$otherDeptEvent->id}", [
        'title' => 'Updated', 'start_at' => now()->addWeek()->format('Y-m-d H:i:s'), 'department_id' => $this->otherDepartment->id,
    ])->assertForbidden();
});

test('an admin can bulk remove multiple events at once', function () {
    $events = Event::factory()->count(3)->create(['department_id' => null, 'created_by' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->delete('/events/bulk-destroy', [
        'ids' => $events->pluck('id')->all(),
    ]);

    $response->assertRedirect('/events')->assertSessionHas('success', '3 event(s) removed.');
    $events->each(fn (Event $e) => $this->assertSoftDeleted('events', ['id' => $e->id]));
});

test('bulk-deleting events is scoped the same way as editing one', function () {
    $deptEvent = Event::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->admin->id]);
    $otherDeptEvent = Event::factory()->create(['department_id' => $this->otherDepartment->id, 'created_by' => $this->admin->id]);

    $this->actingAs($this->head)->delete('/events/bulk-destroy', [
        'ids' => [$deptEvent->id, $otherDeptEvent->id],
    ])->assertForbidden();

    $this->assertDatabaseHas('events', ['id' => $deptEvent->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('events', ['id' => $otherDeptEvent->id, 'deleted_at' => null]);
});
