<?php

use App\Enums\RoleName;
use App\Models\College;
use App\Models\Department;

beforeEach(function () {
    $college = College::factory()->create();
    $this->deptA = Department::factory()->create(['college_id' => $college->id, 'name' => 'Crop Science']);
    $this->deptB = Department::factory()->create(['college_id' => $college->id, 'name' => 'Animal Science']);
});

test('the college dean sees departments across the whole college', function () {
    $dean = userWithRole(RoleName::Dean->value);

    $response = $this->actingAs($dean)->get('/departments');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('departments.data', 2));
});

test('a department head only sees their own department', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->deptA);

    $response = $this->actingAs($head)->get('/departments');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('departments.data', 1)
        ->where('departments.data.0.id', $this->deptA->id));
});

test('a faculty member can view but not manage departments', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->deptA);

    $this->actingAs($faculty)->get('/departments')->assertOk()
        ->assertInertia(fn ($page) => $page->missing('potentialHeads'));
    $this->actingAs($faculty)->post('/departments', ['name' => 'New Dept'])->assertForbidden();
});

test('guests are redirected away from protected pages', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/departments')->assertRedirect('/login');
    $this->get('/users')->assertRedirect('/login');
});

test('a non-admin cannot reach user management at all', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->deptA);

    $this->actingAs($head)->get('/users')->assertForbidden();
});

test('a non-admin cannot reach audit logs without the permission', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->deptA);

    $this->actingAs($faculty)->get('/audit-logs')->assertForbidden();
});
