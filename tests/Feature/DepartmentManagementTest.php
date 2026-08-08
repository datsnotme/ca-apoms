<?php

use App\Enums\RoleName;
use App\Models\College;
use App\Models\Department;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->college = College::factory()->create();
});

test('an admin can create a department', function () {
    $response = $this->actingAs($this->admin)->post('/departments', [
        'college_id' => $this->college->id,
        'code' => 'CROPSCI',
        'name' => 'Department of Crop Science',
        'status' => 'active',
    ]);

    $response->assertRedirect('/departments');
    $this->assertDatabaseHas('departments', ['code' => 'CROPSCI']);
});

test('department codes must be unique', function () {
    Department::factory()->create(['college_id' => $this->college->id, 'code' => 'CROPSCI']);

    $response = $this->actingAs($this->admin)->post('/departments', [
        'college_id' => $this->college->id,
        'code' => 'CROPSCI',
        'name' => 'Duplicate',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('code');
});

test('an admin can archive a department and it disappears from the active list', function () {
    $department = Department::factory()->create(['college_id' => $this->college->id]);

    $this->actingAs($this->admin)->delete("/departments/{$department->id}");

    expect(Department::find($department->id))->toBeNull();
    expect(Department::withTrashed()->find($department->id))->not->toBeNull();
});

test('archiving a department is recorded in the activity log', function () {
    $department = Department::factory()->create(['college_id' => $this->college->id]);

    $this->actingAs($this->admin)->delete("/departments/{$department->id}");

    expect(Activity::where('log_name', 'departments')
        ->where('event', 'deleted')
        ->where('subject_id', $department->id)
        ->exists())->toBeTrue();
});
