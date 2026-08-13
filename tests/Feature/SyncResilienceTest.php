<?php

use App\Enums\RoleName;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Device;
use App\Models\Program;
use App\Models\Student;
use App\Models\SyncCheckpoint;
use App\Models\SyncRun;
use App\Models\YearLevel;
use App\Services\SyncService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

/**
 * Phase 5 — the resilience half of "manual Update/Sync button reconciling
 * changes without ever silently losing/overwriting data": what happens
 * when a sync is interrupted partway (a batch that fails mid-application,
 * a network drop before a response arrives)? These tests don't add new
 * sync *logic* — they prove properties the Phase 2/3 design already
 * claimed but never had a dedicated regression test for.
 */
beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $this->yearLevel = YearLevel::factory()->create(['level' => 1]);
    $this->service = app(SyncService::class);
});

function validStudentChange(string $uuid, int $departmentId, int $programId, int $curriculumId, int $yearLevelId): array
{
    return [
        'entity_table' => 'students',
        'entity_uuid' => $uuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'student_number' => '2026-'.substr($uuid, 0, 5),
            'surname' => 'Valid', 'first_name' => 'Student', 'sex' => 'Male', 'birth_date' => '2000-01-01',
            'department_id' => $departmentId, 'program_id' => $programId,
            'curriculum_id' => $curriculumId, 'year_level_id' => $yearLevelId,
            'admission_type' => 'Freshman', 'date_admitted' => '2020-01-01',
            'classification' => 'regular', 'status' => 'active',
            'uuid' => $uuid, 'sync_version' => 1,
        ],
    ];
}

function malformedStudentChange(string $uuid): array
{
    return [
        'entity_table' => 'students',
        'entity_uuid' => $uuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            // department_id/program_id/curriculum_id/year_level_id deliberately
            // omitted — all four are NOT NULL FKs with no default, so this
            // change throws a QueryException partway through applyIncoming().
            'student_number' => '2026-'.substr($uuid, 0, 5),
            'surname' => 'Malformed', 'first_name' => 'Student', 'sex' => 'Male', 'birth_date' => '2000-01-01',
            'admission_type' => 'Freshman', 'date_admitted' => '2020-01-01',
            'classification' => 'regular', 'status' => 'active',
            'uuid' => $uuid, 'sync_version' => 1,
        ],
    ];
}

test('a push batch that fails partway rolls back atomically, not partially', function () {
    Sanctum::actingAs($this->admin, ['sync:write']);

    $validUuid = (string) Str::uuid();
    $malformedUuid = (string) Str::uuid();

    $response = $this->postJson('/api/sync/push', [
        'changes' => [
            validStudentChange($validUuid, $this->department->id, $this->program->id, $this->curriculum->id, $this->yearLevel->id),
            malformedStudentChange($malformedUuid),
        ],
    ]);

    $response->assertServerError();
    // The valid change was processed first, inside the same DB transaction
    // as the change that later threw — it must not have survived the
    // rollback. A partially-applied batch (one entity created, the batch
    // otherwise failed) would be a silent, undetectable data-loss bug.
    expect(Student::where('uuid', $validUuid)->exists())->toBeFalse();
    expect(Student::where('uuid', $malformedUuid)->exists())->toBeFalse();
});

test('a pull batch that fails partway rolls back atomically, not partially', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-RES01', 'name' => 'Resilience Test', 'owner_user_id' => $this->admin->id, 'status' => 'active']);
    $validUuid = (string) Str::uuid();
    $malformedUuid = (string) Str::uuid();

    Http::fake([
        'remote.test/api/sync/pull*' => Http::response([
            'changes' => [
                validStudentChange($validUuid, $this->department->id, $this->program->id, $this->curriculum->id, $this->yearLevel->id),
                malformedStudentChange($malformedUuid),
            ],
            'next_since_id' => 5,
        ], 200),
    ]);

    expect(fn () => $this->service->pullFrom($device, 'https://remote.test', 'fake-token', 'lan-hub'))
        ->toThrow(QueryException::class);

    expect(Student::where('uuid', $validUuid)->exists())->toBeFalse();
    expect(Student::where('uuid', $malformedUuid)->exists())->toBeFalse();

    // The run is recorded as failed, and — critically — the checkpoint
    // that decides what gets fetched NEXT time was never advanced, so the
    // retry re-fetches this exact same (still-broken) batch rather than
    // silently skipping past the entity that never made it in.
    $run = SyncRun::where('device_id', $device->id)->latest()->first();
    expect($run->status)->toBe('failed');
    $checkpoint = SyncCheckpoint::where('device_id', $device->id)->where('remote_target', 'lan-hub')->first();
    expect($checkpoint->last_token ?? null)->not->toBe('5');
});

test('a failed pushTo leaves its push checkpoint untouched, so the retry resends the same batch', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-RES02', 'name' => 'Resilience Test', 'owner_user_id' => $this->admin->id, 'status' => 'active']);
    Student::factory()->create([
        'department_id' => $this->department->id, 'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id, 'year_level_id' => $this->yearLevel->id,
    ]);

    Http::fake(['remote.test/api/sync/push*' => Http::response(['message' => 'Service Unavailable'], 503)]);

    expect(fn () => $this->service->pushTo($device, 'https://remote.test', 'bad-token', 'lan-hub'))
        ->toThrow(RequestException::class);

    $checkpoint = SyncCheckpoint::where('device_id', $device->id)->where('remote_target', 'lan-hub:push')->first();
    expect($checkpoint->last_token ?? null)->toBeNull();
    expect($checkpoint->last_synced_at)->toBeNull();
});

test('resending an already-applied batch after a simulated lost response is a safe no-op', function () {
    // Models the case where the remote fully processed a push but the
    // caller never received the success response (connection reset,
    // proxy timeout) — the caller's checkpoint never advances, so its
    // next attempt resends the exact same batch. The receiving side must
    // treat that as a no-op, not a duplicate or an error.
    $uuid = (string) Str::uuid();
    $change = validStudentChange($uuid, $this->department->id, $this->program->id, $this->curriculum->id, $this->yearLevel->id);

    $firstAttempt = $this->service->applyIncoming([$change]);
    expect($firstAttempt['created'])->toBe(1);
    expect(Student::where('uuid', $uuid)->count())->toBe(1);

    $retryAfterLostResponse = $this->service->applyIncoming([$change]);
    expect($retryAfterLostResponse)->toBe(['created' => 0, 'updated' => 0, 'merged' => 0, 'conflicted' => 0, 'deleted' => 0, 'skipped' => 1]);
    expect(Student::where('uuid', $uuid)->count())->toBe(1);
});

test('resending an already-applied push batch through the real API endpoint is a safe no-op', function () {
    Sanctum::actingAs($this->admin, ['sync:write']);
    $uuid = (string) Str::uuid();
    $change = validStudentChange($uuid, $this->department->id, $this->program->id, $this->curriculum->id, $this->yearLevel->id);

    $first = $this->postJson('/api/sync/push', ['changes' => [$change]]);
    $first->assertOk();
    expect($first->json('created'))->toBe(1);

    $retry = $this->postJson('/api/sync/push', ['changes' => [$change]]);
    $retry->assertOk();
    expect($retry->json('skipped'))->toBe(1);
    expect($retry->json('created'))->toBe(0);
    expect(Student::where('uuid', $uuid)->count())->toBe(1);
});

test('a device that pushes to us has its last_sync_at recorded', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-RES03', 'name' => 'Pusher', 'owner_user_id' => $this->admin->id, 'status' => 'active']);
    $token = $this->admin->createToken($device->device_code, ['sync:write']);
    $this->withToken($token->plainTextToken);

    expect($device->last_sync_at)->toBeNull();

    $this->postJson('/api/sync/push', ['changes' => []])->assertOk();

    expect($device->refresh()->last_sync_at)->not->toBeNull();
});
