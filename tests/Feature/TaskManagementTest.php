<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Task;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);
});

test('any authenticated user can create a task for themselves', function () {
    $response = $this->actingAs($this->faculty)->post('/tasks', [
        'title' => 'Grade midterms',
        'due_date' => now()->addWeek()->format('Y-m-d'),
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('tasks', [
        'title' => 'Grade midterms',
        'created_by' => $this->faculty->id,
        'assigned_to' => $this->faculty->id,
    ]);
});

test('a dean can create and assign a task to another user', function () {
    $response = $this->actingAs($this->dean)->post('/tasks', [
        'title' => 'Prepare accreditation report',
        'assigned_to' => $this->head->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('tasks', ['title' => 'Prepare accreditation report', 'assigned_to' => $this->head->id]);
});

test('a user not involved in a task cannot view, update, or delete it', function () {
    $task = Task::factory()->create(['created_by' => $this->head->id, 'assigned_to' => $this->faculty->id]);

    $this->actingAs($this->otherFaculty)->get("/tasks/{$task->id}/edit")->assertForbidden();
    $this->actingAs($this->otherFaculty)->put("/tasks/{$task->id}", ['status' => 'completed'])->assertForbidden();
    $this->actingAs($this->otherFaculty)->delete("/tasks/{$task->id}")->assertForbidden();
});

test('the creator can fully edit a task', function () {
    $task = Task::factory()->create(['created_by' => $this->head->id, 'assigned_to' => $this->faculty->id]);

    $response = $this->actingAs($this->head)->put("/tasks/{$task->id}", [
        'title' => 'Updated title',
        'assigned_to' => $this->otherFaculty->id,
        'due_date' => now()->addMonth()->format('Y-m-d'),
    ]);

    $response->assertRedirect();
    $task->refresh();
    expect($task->title)->toBe('Updated title');
    expect($task->assigned_to)->toBe($this->otherFaculty->id);
});

test('an assignee cannot reach the full edit page but can update the status of their own task', function () {
    $task = Task::factory()->create(['created_by' => $this->head->id, 'assigned_to' => $this->faculty->id]);

    $this->actingAs($this->faculty)->get("/tasks/{$task->id}/edit")->assertForbidden();

    $response = $this->actingAs($this->faculty)->put("/tasks/{$task->id}", ['status' => 'completed']);
    $response->assertRedirect();

    $task->refresh();
    expect($task->status->value)->toBe('completed');
    expect($task->completed_by)->toBe($this->faculty->id);
});

test('an assignee cannot change the title or reassign the task even by posting directly to the route', function () {
    $task = Task::factory()->create([
        'created_by' => $this->head->id,
        'assigned_to' => $this->faculty->id,
        'title' => 'Original title',
    ]);

    $this->actingAs($this->faculty)->put("/tasks/{$task->id}", [
        'title' => 'Hijacked title',
        'assigned_to' => $this->otherFaculty->id,
        'status' => 'in_progress',
    ])->assertRedirect();

    $task->refresh();
    expect($task->title)->toBe('Original title');
    expect($task->assigned_to)->toBe($this->faculty->id);
    expect($task->status->value)->toBe('in_progress');
});

test('an assignee cannot delete a task, but the creator can', function () {
    $task = Task::factory()->create(['created_by' => $this->head->id, 'assigned_to' => $this->faculty->id]);

    $this->actingAs($this->faculty)->delete("/tasks/{$task->id}")->assertForbidden();
    $this->actingAs($this->head)->delete("/tasks/{$task->id}")->assertRedirect();
    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

test('a user only sees tasks they created or were assigned, but an admin sees every task', function () {
    Task::factory()->create(['created_by' => $this->head->id, 'assigned_to' => $this->faculty->id, 'title' => 'Visible to faculty']);
    Task::factory()->create(['created_by' => $this->otherFaculty->id, 'assigned_to' => $this->otherFaculty->id, 'title' => 'Not visible to faculty']);

    $response = $this->actingAs($this->faculty)->get('/tasks');
    $response->assertInertia(fn ($page) => $page->has('tasks.data', 1));

    $response = $this->actingAs($this->admin)->get('/tasks');
    $response->assertInertia(fn ($page) => $page->has('tasks.data', 2));
});

test('the status filter narrows the task list', function () {
    Task::factory()->create(['created_by' => $this->faculty->id, 'assigned_to' => $this->faculty->id, 'status' => 'pending']);
    Task::factory()->create(['created_by' => $this->faculty->id, 'assigned_to' => $this->faculty->id, 'status' => 'completed']);

    $response = $this->actingAs($this->faculty)->get('/tasks?status=completed');

    $response->assertInertia(fn ($page) => $page->has('tasks.data', 1));
});

test('a creator can bulk remove multiple tasks at once', function () {
    $tasks = Task::factory()->count(3)->create(['created_by' => $this->head->id, 'assigned_to' => $this->faculty->id]);

    $response = $this->actingAs($this->head)->delete('/tasks/bulk-destroy', [
        'ids' => $tasks->pluck('id')->all(),
    ]);

    $response->assertRedirect('/tasks')->assertSessionHas('success', '3 task(s) removed.');
    $tasks->each(fn (Task $task) => $this->assertSoftDeleted('tasks', ['id' => $task->id]));
});

test('bulk-deleting tasks is scoped the same way as deleting one, so an assignee cannot bulk delete someone elses task', function () {
    $ownTask = Task::factory()->create(['created_by' => $this->head->id, 'assigned_to' => $this->faculty->id]);
    $otherTask = Task::factory()->create(['created_by' => $this->otherFaculty->id, 'assigned_to' => $this->otherFaculty->id]);

    $this->actingAs($this->faculty)->delete('/tasks/bulk-destroy', [
        'ids' => [$ownTask->id, $otherTask->id],
    ])->assertForbidden();

    $this->assertDatabaseHas('tasks', ['id' => $ownTask->id, 'deleted_at' => null]);
    $this->assertDatabaseHas('tasks', ['id' => $otherTask->id, 'deleted_at' => null]);
});
