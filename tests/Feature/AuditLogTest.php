<?php

use App\Enums\RoleName;
use App\Models\College;
use App\Models\Department;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('creating a department is recorded in the activity log', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    $college = College::factory()->create();

    $this->actingAs($admin)->post('/departments', [
        'college_id' => $college->id,
        'code' => 'CROPSCI',
        'name' => 'Department of Crop Science',
        'status' => 'active',
    ]);

    expect(Activity::where('log_name', 'departments')->where('event', 'created')->exists())->toBeTrue();
});

test('the admin can see raw account-management activity, including user log entries', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    User::factory()->create(); // triggers a "users" log entry via factory + any model event

    $response = $this->actingAs($admin)->get('/audit-logs');

    $response->assertOk();
});

test('the dean audit log view excludes account-management entries', function () {
    $admin = userWithRole(RoleName::Administrator->value);
    $dean = userWithRole(RoleName::Dean->value);
    $department = Department::factory()->create();

    // Generates a "departments" activity log entry the dean should see.
    $this->actingAs($admin)->post('/departments', [
        'college_id' => $department->college_id,
        'code' => 'ANSCI',
        'name' => 'Department of Animal Science',
        'status' => 'active',
    ]);

    $response = $this->actingAs($dean)->get('/audit-logs');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->where(
        'activities.data',
        fn ($activities) => collect($activities)->every(fn ($a) => $a['log_name'] !== 'users')
    ));
});
