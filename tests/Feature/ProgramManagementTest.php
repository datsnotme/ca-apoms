<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Program;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
});

test('an admin can create a program under a department', function () {
    $response = $this->actingAs($this->admin)->post('/programs', [
        'department_id' => $this->department->id,
        'code' => 'BSA-CS',
        'name' => 'BS Agriculture major in Crop Science',
        'status' => 'active',
        'duration_years' => 4,
    ]);

    $response->assertRedirect('/programs');
    $this->assertDatabaseHas('programs', ['code' => 'BSA-CS', 'department_id' => $this->department->id]);
});

test('program codes must be unique', function () {
    Program::factory()->create(['department_id' => $this->department->id, 'code' => 'BSA-CS']);

    $response = $this->actingAs($this->admin)->post('/programs', [
        'department_id' => $this->department->id,
        'code' => 'BSA-CS',
        'name' => 'Duplicate',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('code');
});

test('a department head cannot manage programs', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->actingAs($head)->get('/programs/create')->assertForbidden();
});
