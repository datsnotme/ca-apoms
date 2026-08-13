<?php

use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Student;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
});

test('an unauthenticated request is rejected', function () {
    $this->getJson('/api/sync/pull')->assertUnauthorized();
});

test('an authenticated user without sync.manage is forbidden', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);
    Sanctum::actingAs($faculty, ['sync:read']);

    $this->getJson('/api/sync/pull')->assertForbidden();
});

test('a sync.manage user with a valid token can call status', function () {
    Sanctum::actingAs($this->admin, ['sync:read']);

    $response = $this->getJson('/api/sync/status');

    $response->assertOk()->assertJsonStructure(['ok', 'server_time', 'authenticated_as' => ['id', 'name']]);
});

test('pull returns a newly created student with a full snapshot', function () {
    Sanctum::actingAs($this->admin, ['sync:read']);
    $student = Student::factory()->create(['department_id' => $this->department->id]);

    $response = $this->getJson('/api/sync/pull?since_id=0');

    $response->assertOk();
    // Creating the Student's own reference-data fixtures (Department,
    // Program, Curriculum, YearLevel, ...) now also produces outbox
    // entries, since Phase 6 tracks those tables too — find the student's
    // own entry rather than assuming it's the only one in the batch.
    $change = collect($response->json('changes'))->firstWhere('entity_uuid', $student->uuid);
    expect($change['entity_table'])->toBe('students');
    expect($change['operation'])->toBe('created');
    expect($change['snapshot']['student_number'])->toBe($student->student_number);
    expect($response->json('next_since_id'))->toBeGreaterThan(0);
});

test('pulling again with the returned next_since_id yields nothing new', function () {
    Sanctum::actingAs($this->admin, ['sync:read']);
    Student::factory()->create(['department_id' => $this->department->id]);

    $first = $this->getJson('/api/sync/pull?since_id=0');
    $cursor = $first->json('next_since_id');

    $second = $this->getJson("/api/sync/pull?since_id={$cursor}");

    expect($second->json('changes'))->toBe([]);
    expect($second->json('next_since_id'))->toBe($cursor);
});

test('multiple writes to the same student collapse into one current-state change', function () {
    Sanctum::actingAs($this->admin, ['sync:read']);
    $student = Student::factory()->create(['department_id' => $this->department->id, 'classification' => 'regular']);
    $student->update(['classification' => 'irregular']);
    $student->update(['classification' => 'graduating']);

    $response = $this->getJson('/api/sync/pull?since_id=0');

    $changes = collect($response->json('changes'))->where('entity_uuid', $student->uuid);
    expect($changes)->toHaveCount(1);
    expect($changes->first()['snapshot']['classification'])->toBe('graduating');
    expect($changes->first()['version'])->toBe(3);
});

test('a deleted student is reported with a deleted operation and no snapshot', function () {
    Sanctum::actingAs($this->admin, ['sync:read']);
    $student = Student::factory()->create(['department_id' => $this->department->id]);
    $student->delete();

    $response = $this->getJson('/api/sync/pull?since_id=0');

    $change = collect($response->json('changes'))->firstWhere('entity_uuid', $student->uuid);
    expect($change['operation'])->toBe('deleted');
    expect($change)->not->toHaveKey('snapshot');
});
