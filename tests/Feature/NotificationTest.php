<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\InternalRequest;
use App\Models\Meeting;
use App\Models\Task;
use App\Notifications\InternalRequestStatusChangedNotification;
use App\Notifications\MeetingInvitationNotification;
use App\Notifications\NewAnnouncementNotification;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->otherFaculty = userWithRole(RoleName::Faculty->value, $this->otherDepartment);
});

test('posting a college-wide announcement notifies every active user except the poster', function () {
    Notification::fake();

    $this->actingAs($this->admin)->post('/announcements', [
        'title' => 'College Assembly', 'body' => 'Details.', 'department_id' => '',
    ]);

    Notification::assertSentTo([$this->head, $this->faculty, $this->otherFaculty], NewAnnouncementNotification::class);
    Notification::assertNotSentTo($this->admin, NewAnnouncementNotification::class);
});

test('posting a department-scoped announcement only notifies that department', function () {
    Notification::fake();

    $this->actingAs($this->head)->post('/announcements', [
        'title' => 'Dept Meeting', 'body' => 'Details.', 'department_id' => $this->department->id,
    ]);

    Notification::assertSentTo($this->faculty, NewAnnouncementNotification::class);
    Notification::assertNotSentTo($this->otherFaculty, NewAnnouncementNotification::class);
    Notification::assertNotSentTo($this->head, NewAnnouncementNotification::class);
});

test('inviting a meeting attendee notifies that attendee', function () {
    Notification::fake();

    $meeting = Meeting::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->head->id]);

    $this->actingAs($this->head)->post("/meetings/{$meeting->id}/attendees", ['user_id' => $this->faculty->id]);

    Notification::assertSentTo($this->faculty, MeetingInvitationNotification::class);
});

test('assigning a task to someone else notifies them, but self-assignment sends no notification', function () {
    Notification::fake();

    $this->actingAs($this->head)->post('/tasks', [
        'title' => 'Prepare report', 'assigned_to' => $this->faculty->id,
    ]);
    Notification::assertSentTo($this->faculty, TaskAssignedNotification::class);

    $this->actingAs($this->head)->post('/tasks', ['title' => 'My own task']);
    Notification::assertSentToTimes($this->head, TaskAssignedNotification::class, 0);
});

test('reassigning a task notifies the new assignee', function () {
    Notification::fake();

    $task = Task::factory()->create(['created_by' => $this->head->id, 'assigned_to' => $this->head->id]);

    $this->actingAs($this->head)->put("/tasks/{$task->id}", [
        'title' => $task->title,
        'assigned_to' => $this->faculty->id,
    ]);

    Notification::assertSentTo($this->faculty, TaskAssignedNotification::class);
});

test('approving or rejecting an internal request notifies the requester', function () {
    Notification::fake();

    $req = InternalRequest::factory()->create(['requester_id' => $this->faculty->id, 'department_id' => $this->department->id]);

    $this->actingAs($this->head)->patch("/internal-requests/{$req->id}/review", ['decision' => 'approved']);

    Notification::assertSentTo($this->faculty, InternalRequestStatusChangedNotification::class);
});

test('a user can mark their own notification as read but not someone elses', function () {
    $this->faculty->notify(new TaskAssignedNotification(Task::factory()->create(['created_by' => $this->head->id])));
    $notification = $this->faculty->notifications()->first();

    $this->actingAs($this->otherFaculty)->patch("/notifications/{$notification->id}/read")->assertNotFound();

    $this->actingAs($this->faculty)->patch("/notifications/{$notification->id}/read")->assertRedirect();
    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('mark all read clears every unread notification for the current user', function () {
    $task = Task::factory()->create(['created_by' => $this->head->id]);
    $this->faculty->notify(new TaskAssignedNotification($task));
    $this->faculty->notify(new TaskAssignedNotification($task));

    expect($this->faculty->unreadNotifications()->count())->toBe(2);

    $this->actingAs($this->faculty)->post('/notifications/read-all')->assertRedirect();

    expect($this->faculty->unreadNotifications()->count())->toBe(0);
});

test('the notifications index page lists the current users notifications', function () {
    $task = Task::factory()->create(['created_by' => $this->head->id]);
    $this->faculty->notify(new TaskAssignedNotification($task));

    $response = $this->actingAs($this->faculty)->get('/notifications');

    $response->assertInertia(fn ($page) => $page->has('notifications.data', 1));
});
