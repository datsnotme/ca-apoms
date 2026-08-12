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

test('an admin can bulk archive multiple programs at once', function () {
    $programs = Program::factory()->count(3)->create(['department_id' => $this->department->id]);

    $response = $this->actingAs($this->admin)->delete('/programs/bulk-destroy', [
        'ids' => $programs->pluck('id')->all(),
    ]);

    $response->assertRedirect('/programs')->assertSessionHas('success', '3 program(s) archived.');
    $programs->each(fn (Program $p) => $this->assertSoftDeleted('programs', ['id' => $p->id]));
});

test('a department head cannot bulk delete programs', function () {
    $program = Program::factory()->create(['department_id' => $this->department->id]);
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->actingAs($head)->delete('/programs/bulk-destroy', [
        'ids' => [$program->id],
    ])->assertForbidden();

    $this->assertDatabaseHas('programs', ['id' => $program->id, 'deleted_at' => null]);
});
