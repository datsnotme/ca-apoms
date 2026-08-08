<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\User;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
});

test('an admin can create a user, which generates a temporary password requiring change', function () {
    $response = $this->actingAs($this->admin)->post('/users', [
        'employee_number' => 'EMP-1001',
        'surname' => 'Dela Cruz',
        'first_name' => 'Juan',
        'email' => 'juan.delacruz@example.com',
        'username' => 'jdelacruz',
        'role' => RoleName::Faculty->value,
        'department_id' => $this->department->id,
        'status' => 'active',
    ]);

    $response->assertRedirect('/users');

    $user = User::where('email', 'juan.delacruz@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->must_change_password)->toBeTrue();
    expect($user->hasRole(RoleName::Faculty->value))->toBeTrue();
    expect($user->created_by)->toBe($this->admin->id);
});

test('employee number, email, and username must each be unique', function () {
    User::factory()->create(['employee_number' => 'EMP-1001', 'email' => 'a@example.com', 'username' => 'auser']);

    $response = $this->actingAs($this->admin)->post('/users', [
        'employee_number' => 'EMP-1001',
        'surname' => 'Test',
        'first_name' => 'User',
        'email' => 'a@example.com',
        'username' => 'auser',
        'role' => RoleName::Faculty->value,
        'department_id' => $this->department->id,
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors(['employee_number', 'email', 'username']);
});

test('a department head or faculty role requires a department', function () {
    $response = $this->actingAs($this->admin)->post('/users', [
        'employee_number' => 'EMP-1002',
        'surname' => 'Test',
        'first_name' => 'User',
        'email' => 'b@example.com',
        'username' => 'buser',
        'role' => RoleName::DepartmentHead->value,
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors('department_id');
});

test('an admin cannot deactivate/archive their own account', function () {
    $response = $this->actingAs($this->admin)->delete("/users/{$this->admin->id}");

    $response->assertForbidden();
    expect(User::find($this->admin->id))->not->toBeNull();
});

test('an archived user can be restored', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin)->delete("/users/{$user->id}");
    expect(User::find($user->id))->toBeNull();

    $this->actingAs($this->admin)->patch("/users/{$user->id}/reactivate");
    expect(User::find($user->id))->not->toBeNull();
});

test('a disabled account cannot access the application', function () {
    $user = User::factory()->create(['status' => 'inactive']);

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('a non-admin cannot create users even by posting directly to the route', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $response = $this->actingAs($faculty)->post('/users', [
        'employee_number' => 'EMP-9999',
        'surname' => 'Hacker',
        'first_name' => 'Test',
        'email' => 'hacker@example.com',
        'username' => 'hacker',
        'role' => RoleName::Administrator->value,
        'status' => 'active',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
});
