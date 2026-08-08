<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\ResearchProject;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->otherDepartment = Department::factory()->create();
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);
    $this->otherFaculty = userWithRole(RoleName::Faculty->value, $this->otherDepartment);
});

test('a faculty member can create their own research project and is automatically the lead', function () {
    $response = $this->actingAs($this->faculty)->post('/research-projects', [
        'title' => 'Rice Yield Optimization',
        'department_id' => $this->otherDepartment->id, // ignored — always forced to own department
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('research_projects', [
        'title' => 'Rice Yield Optimization',
        'department_id' => $this->department->id,
        'created_by' => $this->faculty->id,
    ]);

    $project = ResearchProject::first();
    $this->assertDatabaseHas('research_members', [
        'research_project_id' => $project->id,
        'user_id' => $this->faculty->id,
        'is_lead' => true,
    ]);
});

test('an admin can create a research project for any department', function () {
    $response = $this->actingAs($this->admin)->post('/research-projects', [
        'title' => 'College-wide Soil Study',
        'department_id' => $this->department->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('research_projects', ['title' => 'College-wide Soil Study', 'department_id' => $this->department->id]);
});

test('a department head cannot create a research project despite view access', function () {
    $this->actingAs($this->head)->post('/research-projects', [
        'title' => 'Not Allowed',
    ])->assertForbidden();
});

test('a dean cannot create a research project', function () {
    $this->actingAs($this->dean)->post('/research-projects', [
        'title' => 'Not Allowed',
    ])->assertForbidden();
});

test('only the project lead or an admin can update or delete a project', function () {
    $project = ResearchProject::factory()->create(['department_id' => $this->department->id, 'created_by' => $this->faculty->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);
    $project->members()->create(['user_id' => $this->otherFaculty->id, 'is_lead' => false, 'added_by' => $this->faculty->id]);

    // Lead can update
    $this->actingAs($this->faculty)->put("/research-projects/{$project->id}", [
        'title' => 'Updated Title',
    ])->assertRedirect();
    $this->assertDatabaseHas('research_projects', ['id' => $project->id, 'title' => 'Updated Title']);

    // Non-lead member cannot update
    $this->actingAs($this->otherFaculty)->put("/research-projects/{$project->id}", [
        'title' => 'Hijacked',
    ])->assertForbidden();

    // Department head (view-only) cannot update, even for their own department's project
    $this->actingAs($this->head)->put("/research-projects/{$project->id}", [
        'title' => 'Hijacked',
    ])->assertForbidden();

    // Admin can update and delete
    $this->actingAs($this->admin)->put("/research-projects/{$project->id}", [
        'title' => 'Admin Fixed',
    ])->assertRedirect();

    $this->actingAs($this->admin)->delete("/research-projects/{$project->id}")->assertRedirect();
    $this->assertSoftDeleted('research_projects', ['id' => $project->id]);
});

test('visibility is department-scoped for department head and faculty, unrestricted for admin and dean', function () {
    $ownProject = ResearchProject::factory()->create(['department_id' => $this->department->id]);
    $otherProject = ResearchProject::factory()->create(['department_id' => $this->otherDepartment->id]);

    $this->actingAs($this->head)->get("/research-projects/{$ownProject->id}")->assertOk();
    $this->actingAs($this->head)->get("/research-projects/{$otherProject->id}")->assertForbidden();

    $this->actingAs($this->faculty)->get("/research-projects/{$ownProject->id}")->assertOk();
    $this->actingAs($this->faculty)->get("/research-projects/{$otherProject->id}")->assertForbidden();

    $this->actingAs($this->dean)->get("/research-projects/{$otherProject->id}")->assertOk();
    $this->actingAs($this->admin)->get("/research-projects/{$otherProject->id}")->assertOk();
});

// --- Members ---

test('the project lead can add and remove members', function () {
    $project = ResearchProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);

    $response = $this->actingAs($this->faculty)->post("/research-projects/{$project->id}/members", [
        'user_id' => $this->otherFaculty->id,
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('research_members', ['research_project_id' => $project->id, 'user_id' => $this->otherFaculty->id]);

    $member = $project->members()->where('user_id', $this->otherFaculty->id)->first();
    $this->actingAs($this->faculty)->delete("/research-projects/{$project->id}/members/{$member->id}")->assertRedirect();
    $this->assertDatabaseMissing('research_members', ['id' => $member->id]);
});

test('a non-lead member cannot add other members', function () {
    $project = ResearchProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);
    $project->members()->create(['user_id' => $this->otherFaculty->id, 'is_lead' => false, 'added_by' => $this->faculty->id]);

    $this->actingAs($this->otherFaculty)->post("/research-projects/{$project->id}/members", [
        'user_id' => $this->admin->id,
    ])->assertForbidden();
});

test('the same user cannot be added to the same project twice', function () {
    $project = ResearchProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);

    $this->actingAs($this->faculty)->post("/research-projects/{$project->id}/members", [
        'user_id' => $this->faculty->id,
    ])->assertSessionHasErrors('user_id');
});

// --- Outputs ---

test('the project lead can add and remove outputs', function () {
    $project = ResearchProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);

    $response = $this->actingAs($this->faculty)->post("/research-projects/{$project->id}/outputs", [
        'title' => 'Published Paper',
        'type' => 'Journal Article',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('research_outputs', [
        'research_project_id' => $project->id,
        'title' => 'Published Paper',
        'created_by' => $this->faculty->id,
    ]);

    $output = $project->outputs()->first();
    $this->actingAs($this->faculty)->delete("/research-projects/{$project->id}/outputs/{$output->id}")->assertRedirect();
    $this->assertSoftDeleted('research_outputs', ['id' => $output->id]);
});

test('a non-lead member cannot add outputs', function () {
    $project = ResearchProject::factory()->create(['department_id' => $this->department->id]);
    $project->members()->create(['user_id' => $this->faculty->id, 'is_lead' => true, 'added_by' => $this->faculty->id]);
    $project->members()->create(['user_id' => $this->otherFaculty->id, 'is_lead' => false, 'added_by' => $this->faculty->id]);

    $this->actingAs($this->otherFaculty)->post("/research-projects/{$project->id}/outputs", [
        'title' => 'Not Allowed',
        'type' => 'Journal Article',
    ])->assertForbidden();
});
