<?php

use App\Enums\RoleName;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Device;
use App\Models\Program;
use App\Models\YearLevel;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $this->yearLevel = YearLevel::factory()->create();
});

test('an unauthenticated request is rejected', function () {
    $this->postJson('/api/sync/push', ['changes' => []])->assertUnauthorized();
});

test('an authenticated user without sync.manage is forbidden', function () {
    $faculty = userWithRole(RoleName::Faculty->value, $this->department);
    Sanctum::actingAs($faculty, ['sync:write']);

    $this->postJson('/api/sync/push', ['changes' => []])->assertForbidden();
});

test('pushing a created change materializes the entity locally and reports counts', function () {
    Sanctum::actingAs($this->admin, ['sync:write']);
    $uuid = (string) Str::uuid();

    $response = $this->postJson('/api/sync/push', [
        'changes' => [[
            'entity_table' => 'students',
            'entity_uuid' => $uuid,
            'operation' => 'created',
            'version' => 1,
            'snapshot' => [
                'student_number' => '2026-00001',
                'surname' => 'Pushed',
                'first_name' => 'Student',
                'sex' => 'Male',
                'birth_date' => '2000-01-01',
                'department_id' => $this->department->id,
                'program_id' => $this->program->id,
                'curriculum_id' => $this->curriculum->id,
                'year_level_id' => $this->yearLevel->id,
                'admission_type' => 'Freshman',
                'date_admitted' => '2020-01-01',
                'classification' => 'regular',
                'status' => 'active',
                'uuid' => $uuid,
                'sync_version' => 1,
            ],
        ]],
    ]);

    $response->assertOk()->assertJsonStructure(['created', 'updated', 'merged', 'conflicted', 'deleted', 'skipped', 'server_time']);
    expect($response->json('created'))->toBe(1);
    $this->assertDatabaseHas('students', ['uuid' => $uuid]);
});

test('the applied change is attributed to the pushing device via origin_device_id', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-PUSH01', 'name' => 'Pusher', 'owner_user_id' => $this->admin->id, 'status' => 'active']);
    $token = $this->admin->createToken($device->device_code, ['sync:write']);
    $this->withToken($token->plainTextToken);

    $uuid = (string) Str::uuid();

    $this->postJson('/api/sync/push', [
        'changes' => [[
            'entity_table' => 'students',
            'entity_uuid' => $uuid,
            'operation' => 'created',
            'version' => 1,
            'snapshot' => [
                'student_number' => '2026-00002',
                'surname' => 'Attributed',
                'first_name' => 'Student',
                'sex' => 'Female',
                'birth_date' => '2000-01-01',
                'department_id' => $this->department->id,
                'program_id' => $this->program->id,
                'curriculum_id' => $this->curriculum->id,
                'year_level_id' => $this->yearLevel->id,
                'admission_type' => 'Freshman',
                'date_admitted' => '2020-01-01',
                'classification' => 'regular',
                'status' => 'active',
                'uuid' => $uuid,
                'sync_version' => 1,
            ],
        ]],
    ])->assertOk();

    $this->assertDatabaseHas('students', ['uuid' => $uuid, 'origin_device_id' => $device->id]);
});

test('an empty changes array is a valid no-op push', function () {
    Sanctum::actingAs($this->admin, ['sync:write']);

    $response = $this->postJson('/api/sync/push', ['changes' => []]);

    $response->assertOk();
    expect($response->json())->toMatchArray(['created' => 0, 'updated' => 0, 'merged' => 0, 'conflicted' => 0, 'deleted' => 0, 'skipped' => 0]);
});
