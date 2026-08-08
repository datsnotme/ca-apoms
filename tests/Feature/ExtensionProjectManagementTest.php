<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\ExtensionProject;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->otherFaculty = userWithRole(RoleName::Faculty->value, $this->otherDepartment);
});

test('a faculty member can create their own extension project and is automatically the lead', function () {
    $response = $this->actingAs($this->faculty)->post('/extension-projects', [
        'title' => 'Community Rice Farming Training',
        'department_id' => $this->otherDepartment->id, // ignored — always forced to own department
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('extension_projects', [
        'title' => 'Community Rice Farming Training',
        'department_id' => $this->department->id,
        'created_by' => $this->faculty->id,
    ]);

    $project = ExtensionProject::first();
    $this->assertDatabaseHas('extension_members', [
        'extension_project_id' => $project->id,
        'user_id' => $this->faculty->id,
        'is_lead' => true,
    ]);
});

test('an admin can create an extension project for any department', function () {
    $response = $this->actingAs($this->admin)->post('/extension-projects', [
        'title' => 'College-wide Farmer Outreach',
        'department_id' => $this->department->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('extension_projects', ['title' => 'College-wide Farmer Outreach', 'department_id' => $this->department->id]);
});

test('a department head cannot create an extension project despite view access', function () {
    $this->actingAs($this->head)->post('/extension-projects', [
        'title' => 'Not Allowed',
    ])->assertForbidden();
});

test('a dean cannot create an extension project', function () {
    $this->actingAs($this->dean)->post('/extension-projects', [
        'title' => 'Not Allowed',
    ])->assertForbidden();
});

test('only the project lead or an admin can update or delete a project', function () {
    $project = ExtensionProject::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->faculty->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);
    $project->members()->create(['user_id' => $this->otherFaculty->id, 'is_lead' => false, 'added_by' => $this->faculty->id]);

    // Lead can update
    $this->actingAs($this->faculty)->put("/extension-projects/{$project->id}", [
        'title' => 'Updated Title',
    ])->assertRedirect();
    $this->assertDatabaseHas('extension_projects', ['id' => $project->id, 'title' => 'Updated Title']);

    // Non-lead member cannot update
    $this->actingAs($this->otherFaculty)->put("/extension-projects/{$project->id}", [
        'title' => 'Hijacked',
    ])->assertForbidden();

    // Department head (view-only) cannot update, even for their own department's project
    $this->actingAs($this->head)->put("/extension-projects/{$project->id}", [
        'title' => 'Hijacked',
    ])->assertForbidden();

    // Admin can update and delete
    $this->actingAs($this->admin)->put("/extension-projects/{$project->id}", [
        'title' => 'Admin Fixed',
    ])->assertRedirect();

    $this->actingAs($this->admin)->delete("/extension-projects/{$project->id}")->assertRedirect();
    $this->assertSoftDeleted('extension_projects', ['id' => $project->id]);
});

test('visibility is department-scoped for department head and faculty, unrestricted for admin and dean', function () {
    $ownProject = ExtensionProject::factory()->create(['department_id' => $this->department->id]);
    $otherProject = ExtensionProject::factory()->create(['department_id' => $this->otherDepartment->id]);

    $this->actingAs($this->head)->get("/extension-projects/{$ownProject->id}")->assertOk();
    $this->actingAs($this->head)->get("/extension-projects/{$otherProject->id}")->assertForbidden();

    $this->actingAs($this->faculty)->get("/extension-projects/{$ownProject->id}")->assertOk();
    $this->actingAs($this->faculty)->get("/extension-projects/{$otherProject->id}")->assertForbidden();

    $this->actingAs($this->dean)->get("/extension-projects/{$otherProject->id}")->assertOk();
    $this->actingAs($this->admin)->get("/extension-projects/{$otherProject->id}")->assertOk();
});

// --- Members ---

test('the project lead can add and remove members', function () {
    $project = ExtensionProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);

    $response = $this->actingAs($this->faculty)->post("/extension-projects/{$project->id}/members", [
        'user_id' => $this->otherFaculty->id,
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('extension_members', ['extension_project_id' => $project->id, 'user_id' => $this->otherFaculty->id]);

    $member = $project->members()->where('user_id', $this->otherFaculty->id)->first();
    $this->actingAs($this->faculty)->delete("/extension-projects/{$project->id}/members/{$member->id}")->assertRedirect();
    $this->assertDatabaseMissing('extension_members', ['id' => $member->id]);
});

test('a non-lead member cannot add other members', function () {
    $project = ExtensionProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);
    $project->members()->create(['user_id' => $this->otherFaculty->id, 'is_lead' => false, 'added_by' => $this->faculty->id]);

    $this->actingAs($this->otherFaculty)->post("/extension-projects/{$project->id}/members", [
        'user_id' => $this->admin->id,
    ])->assertForbidden();
});

test('the same user cannot be added to the same project twice', function () {
    $project = ExtensionProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);

    $this->actingAs($this->faculty)->post("/extension-projects/{$project->id}/members", [
        'user_id' => $this->faculty->id,
    ])->assertSessionHasErrors('user_id');
});

// --- Activities ---

test('the project lead can add and remove activities', function () {
    $project = ExtensionProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);

    $response = $this->actingAs($this->faculty)->post("/extension-projects/{$project->id}/activities", [
        'title' => 'Farmer Training Seminar',
        'activity_type' => 'Training Seminar',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('extension_activities', [
        'extension_project_id' => $project->id,
        'title' => 'Farmer Training Seminar',
        'created_by' => $this->faculty->id,
    ]);

    $activity = $project->activities()->first();
    $this->actingAs($this->faculty)->delete("/extension-projects/{$project->id}/activities/{$activity->id}")->assertRedirect();
    $this->assertSoftDeleted('extension_activities', ['id' => $activity->id]);
});

test('a non-lead member cannot add activities', function () {
    $project = ExtensionProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);
    $project->members()->create(['user_id' => $this->otherFaculty->id, 'is_lead' => false, 'added_by' => $this->faculty->id]);

    $this->actingAs($this->otherFaculty)->post("/extension-projects/{$project->id}/activities", [
        'title' => 'Not Allowed',
        'activity_type' => 'Training Seminar',
    ])->assertForbidden();
});

// --- Beneficiaries ---

test('the project lead can add and remove beneficiaries', function () {
    $project = ExtensionProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);

    $response = $this->actingAs($this->faculty)->post("/extension-projects/{$project->id}/beneficiaries", [
        'beneficiary_name' => 'Barangay Farmers Cooperative',
        'beneficiary_type' => 'Farmer Cooperative',
        'count' => 45,
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('extension_beneficiaries', [
        'extension_project_id' => $project->id,
        'beneficiary_name' => 'Barangay Farmers Cooperative',
        'count' => 45,
        'created_by' => $this->faculty->id,
    ]);

    $beneficiary = $project->beneficiaries()->first();
    $this->actingAs($this->faculty)->delete("/extension-projects/{$project->id}/beneficiaries/{$beneficiary->id}")->assertRedirect();
    $this->assertSoftDeleted('extension_beneficiaries', ['id' => $beneficiary->id]);
});

test('a non-lead member cannot add beneficiaries', function () {
    $project = ExtensionProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);
    $project->members()->create(['user_id' => $this->otherFaculty->id, 'is_lead' => false, 'added_by' => $this->faculty->id]);

    $this->actingAs($this->otherFaculty)->post("/extension-projects/{$project->id}/beneficiaries", [
        'beneficiary_name' => 'Not Allowed',
        'beneficiary_type' => 'Individual Farmer',
    ])->assertForbidden();
});
