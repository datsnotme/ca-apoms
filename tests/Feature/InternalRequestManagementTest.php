<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\InternalRequest;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->otherFaculty = userWithRole(RoleName::Faculty->value, $this->otherDepartment);
});

test('any authenticated user can submit an internal request, scoped to their own department', function () {
    $response = $this->actingAs($this->faculty)->post('/internal-requests', [
        'type' => 'Leave',
        'title' => 'Sick leave',
        'description' => 'Requesting 3 days sick leave.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('internal_requests', [
        'requester_id' => $this->faculty->id,
        'department_id' => $this->department->id,
        'status' => 'pending',
    ]);

    $requestId = InternalRequest::first()->id;
    $this->assertDatabaseHas('request_histories', [
        'internal_request_id' => $requestId,
        'from_status' => null,
        'to_status' => 'pending',
    ]);
});

test('a department head can approve a request from their own department', function () {
    $req = InternalRequest::factory()->create(['requester_id' => $this->faculty->id, 'department_id' => $this->department->id]);

    $response = $this->actingAs($this->head)->patch("/internal-requests/{$req->id}/review", [
        'decision' => 'approved',
        'remarks' => 'Approved, enjoy your leave.',
    ]);

    $response->assertRedirect();
    $req->refresh();
    expect($req->status->value)->toBe('approved');
    expect($req->reviewed_by)->toBe($this->head->id);
    expect($req->remarks)->toBe('Approved, enjoy your leave.');

    $this->assertDatabaseHas('request_histories', [
        'internal_request_id' => $req->id,
        'from_status' => 'pending',
        'to_status' => 'approved',
        'reason' => 'Approved, enjoy your leave.',
        'changed_by' => $this->head->id,
    ]);
});

test('a department head cannot review a request from another department', function () {
    $req = InternalRequest::factory()->create(['requester_id' => $this->otherFaculty->id, 'department_id' => $this->otherDepartment->id]);

    $this->actingAs($this->head)->patch("/internal-requests/{$req->id}/review", [
        'decision' => 'approved',
    ])->assertForbidden();
});

test('a department head cannot review their own request', function () {
    $req = InternalRequest::factory()->create(['requester_id' => $this->head->id, 'department_id' => $this->department->id]);

    $this->actingAs($this->head)->patch("/internal-requests/{$req->id}/review", [
        'decision' => 'approved',
    ])->assertForbidden();
});

test('an already-decided request cannot be reviewed again', function () {
    $req = InternalRequest::factory()->create([
        'requester_id' => $this->faculty->id,
        'department_id' => $this->department->id,
        'status' => 'approved',
    ]);

    $this->actingAs($this->head)->patch("/internal-requests/{$req->id}/review", [
        'decision' => 'rejected',
    ])->assertForbidden();
});

test('dean and faculty cannot review any request', function () {
    $req = InternalRequest::factory()->create(['requester_id' => $this->otherFaculty->id, 'department_id' => $this->department->id]);

    $this->actingAs($this->dean)->patch("/internal-requests/{$req->id}/review", ['decision' => 'approved'])->assertForbidden();
    $this->actingAs($this->faculty)->patch("/internal-requests/{$req->id}/review", ['decision' => 'approved'])->assertForbidden();
});

test('the requester can cancel their own pending request but not once it has been decided', function () {
    $req = InternalRequest::factory()->create(['requester_id' => $this->faculty->id, 'department_id' => $this->department->id]);

    $this->actingAs($this->faculty)->patch("/internal-requests/{$req->id}/cancel")->assertRedirect();
    $req->refresh();
    expect($req->status->value)->toBe('cancelled');

    $approved = InternalRequest::factory()->create([
        'requester_id' => $this->faculty->id,
        'department_id' => $this->department->id,
        'status' => 'approved',
    ]);
    $this->actingAs($this->faculty)->patch("/internal-requests/{$approved->id}/cancel")->assertForbidden();
});

test('a faculty member cannot cancel someone elses request', function () {
    $req = InternalRequest::factory()->create(['requester_id' => $this->otherFaculty->id, 'department_id' => $this->otherDepartment->id]);

    $this->actingAs($this->faculty)->patch("/internal-requests/{$req->id}/cancel")->assertForbidden();
});

test('a faculty member only sees their own requests, a department head sees their whole departments, and admin sees all', function () {
    InternalRequest::factory()->create(['requester_id' => $this->faculty->id, 'department_id' => $this->department->id]);
    InternalRequest::factory()->create(['requester_id' => $this->head->id, 'department_id' => $this->department->id]);
    InternalRequest::factory()->create(['requester_id' => $this->otherFaculty->id, 'department_id' => $this->otherDepartment->id]);

    $this->actingAs($this->faculty)->get('/internal-requests')
        ->assertInertia(fn ($page) => $page->has('requests.data', 1));

    $this->actingAs($this->head)->get('/internal-requests')
        ->assertInertia(fn ($page) => $page->has('requests.data', 2));

    $this->actingAs($this->admin)->get('/internal-requests')
        ->assertInertia(fn ($page) => $page->has('requests.data', 3));
});
