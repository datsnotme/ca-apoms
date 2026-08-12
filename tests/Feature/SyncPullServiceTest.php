<?php

use App\Enums\RoleName;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Device;
use App\Models\Program;
use App\Models\Student;
use App\Models\SyncChange;
use App\Models\SyncCheckpoint;
use App\Models\SyncRun;
use App\Models\YearLevel;
use App\Services\SyncPullService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $this->yearLevel = YearLevel::factory()->create(['level' => 1]);
    $this->service = app(SyncPullService::class);
});

function sampleStudentChange(string $uuid, int $departmentId, int $version = 1, array $overrides = []): array
{
    $test = test();

    return [
        'entity_table' => 'students',
        'entity_uuid' => $uuid,
        'operation' => 'created',
        'version' => $version,
        'snapshot' => array_merge([
            'student_number' => '2026-'.substr($uuid, 0, 5),
            'surname' => 'Incoming',
            'first_name' => 'Sync',
            'sex' => 'Male',
            'birth_date' => '2000-01-01',
            'department_id' => $departmentId,
            'program_id' => $test->program->id,
            'curriculum_id' => $test->curriculum->id,
            'year_level_id' => $test->yearLevel->id,
            'admission_type' => 'Freshman',
            'date_admitted' => '2020-01-01',
            'classification' => 'regular',
            'status' => 'active',
            'uuid' => $uuid,
            'sync_version' => $version,
        ], $overrides),
    ];
}

test('applying an incoming created change materializes a new local student with the same uuid', function () {
    $uuid = (string) Str::uuid();
    $change = sampleStudentChange($uuid, $this->department->id);

    $counts = $this->service->applyIncoming([$change]);

    expect($counts)->toBe(['created' => 1, 'updated' => 0, 'deleted' => 0, 'skipped' => 0]);
    $this->assertDatabaseHas('students', ['uuid' => $uuid, 'student_number' => $change['snapshot']['student_number']]);
});

test('applying the same batch twice does not duplicate the record', function () {
    $uuid = (string) Str::uuid();
    $change = sampleStudentChange($uuid, $this->department->id);

    $this->service->applyIncoming([$change]);
    $counts = $this->service->applyIncoming([$change]);

    expect($counts)->toBe(['created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => 1]);
    expect(Student::where('uuid', $uuid)->count())->toBe(1);
});

test('a newer incoming version updates the local record; an equal-or-older one is skipped', function () {
    $uuid = (string) Str::uuid();
    $this->service->applyIncoming([sampleStudentChange($uuid, $this->department->id, version: 1)]);

    $newer = $this->service->applyIncoming([
        sampleStudentChange($uuid, $this->department->id, version: 2, overrides: ['classification' => 'irregular']),
    ]);
    expect($newer)->toBe(['created' => 0, 'updated' => 1, 'deleted' => 0, 'skipped' => 0]);
    expect(Student::where('uuid', $uuid)->first()->classification->value)->toBe('irregular');

    $stale = $this->service->applyIncoming([
        sampleStudentChange($uuid, $this->department->id, version: 2, overrides: ['classification' => 'regular']),
    ]);
    expect($stale)->toBe(['created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => 1]);
    expect(Student::where('uuid', $uuid)->first()->classification->value)->toBe('irregular');
});

test('an incoming deleted operation soft-deletes the matching local record', function () {
    $uuid = (string) Str::uuid();
    $this->service->applyIncoming([sampleStudentChange($uuid, $this->department->id)]);

    $counts = $this->service->applyIncoming([[
        'entity_table' => 'students',
        'entity_uuid' => $uuid,
        'operation' => 'deleted',
        'version' => 1,
    ]]);

    expect($counts)->toBe(['created' => 0, 'updated' => 0, 'deleted' => 1, 'skipped' => 0]);
    expect(Student::withTrashed()->where('uuid', $uuid)->first()->trashed())->toBeTrue();
});

test('applying an incoming change does not write a new local outbox row', function () {
    $uuid = (string) Str::uuid();
    $countBefore = SyncChange::count();

    $this->service->applyIncoming([sampleStudentChange($uuid, $this->department->id)]);

    expect(SyncChange::count())->toBe($countBefore);
});

test('pullFrom fetches, applies, and advances the checkpoint using a faked HTTP remote', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-TEST01', 'name' => 'Test Device', 'owner_user_id' => $this->admin->id, 'status' => 'active']);
    $uuid = (string) Str::uuid();

    Http::fake([
        'remote.test/api/sync/pull*' => Http::response([
            'changes' => [sampleStudentChange($uuid, $this->department->id)],
            'next_since_id' => 7,
        ], 200),
    ]);

    $run = $this->service->pullFrom($device, 'https://remote.test', 'fake-token', 'lan-hub');

    expect($run->status)->toBe('success');
    expect($run->created_count)->toBe(1);
    expect($run->downloaded_count)->toBe(1);
    $this->assertDatabaseHas('students', ['uuid' => $uuid]);

    $checkpoint = SyncCheckpoint::where('device_id', $device->id)->where('remote_target', 'lan-hub')->first();
    expect($checkpoint->last_token)->toBe('7');
    expect($checkpoint->last_synced_at)->not->toBeNull();
});

test('pullFrom records a failed run and rethrows when the remote errors', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-TEST02', 'name' => 'Test Device', 'owner_user_id' => $this->admin->id, 'status' => 'active']);

    Http::fake(['remote.test/api/sync/pull*' => Http::response(['message' => 'Unauthorized'], 401)]);

    expect(fn () => $this->service->pullFrom($device, 'https://remote.test', 'bad-token', 'lan-hub'))
        ->toThrow(RequestException::class);

    $run = SyncRun::where('device_id', $device->id)->latest()->first();
    expect($run->status)->toBe('failed');
    expect($run->error_message)->not->toBeNull();
});
