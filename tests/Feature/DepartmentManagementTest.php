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

test('an admin can bulk archive multiple departments at once', function () {
    $departments = Department::factory()->count(3)->create(['college_id' => $this->college->id]);

    $response = $this->actingAs($this->admin)->delete('/departments/bulk-destroy', [
        'ids' => $departments->pluck('id')->all(),
    ]);

    $response->assertRedirect('/departments')->assertSessionHas('success', '3 department(s) archived.');
    $departments->each(fn (Department $d) => $this->assertSoftDeleted('departments', ['id' => $d->id]));
});

test('a department head cannot bulk delete departments', function () {
    $department = Department::factory()->create(['college_id' => $this->college->id]);
    $head = userWithRole(RoleName::DepartmentHead->value, $department);

    $this->actingAs($head)->delete('/departments/bulk-destroy', [
        'ids' => [$department->id],
    ])->assertForbidden();

    $this->assertDatabaseHas('departments', ['id' => $department->id, 'deleted_at' => null]);
});
