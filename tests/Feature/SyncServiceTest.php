<?php

use App\Enums\RoleName;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Device;
use App\Models\EnrollmentCourse;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\SyncChange;
use App\Models\SyncCheckpoint;
use App\Models\SyncConflict;
use App\Models\SyncRun;
use App\Models\YearLevel;
use App\Services\SyncService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $this->yearLevel = YearLevel::factory()->create(['level' => 1]);
    $this->service = app(SyncService::class);
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

function emptyCounts(array $overrides = []): array
{
    return array_merge(
        ['created' => 0, 'updated' => 0, 'merged' => 0, 'conflicted' => 0, 'deleted' => 0, 'skipped' => 0],
        $overrides,
    );
}

test('applying an incoming created change materializes a new local student with the same uuid', function () {
    $uuid = (string) Str::uuid();
    $change = sampleStudentChange($uuid, $this->department->id);

    $counts = $this->service->applyIncoming([$change]);

    expect($counts)->toBe(emptyCounts(['created' => 1]));
    $this->assertDatabaseHas('students', ['uuid' => $uuid, 'student_number' => $change['snapshot']['student_number']]);
});

test('applying the same batch twice does not duplicate the record', function () {
    $uuid = (string) Str::uuid();
    $change = sampleStudentChange($uuid, $this->department->id);

    $this->service->applyIncoming([$change]);
    $counts = $this->service->applyIncoming([$change]);

    expect($counts)->toBe(emptyCounts(['skipped' => 1]));
    expect(Student::where('uuid', $uuid)->count())->toBe(1);
});

test('a newer incoming version fast-forwards the local record; an equal-or-older one is skipped', function () {
    $uuid = (string) Str::uuid();
    $this->service->applyIncoming([sampleStudentChange($uuid, $this->department->id, version: 1)]);

    $newer = $this->service->applyIncoming([
        sampleStudentChange($uuid, $this->department->id, version: 2, overrides: ['classification' => 'irregular']),
    ]);
    expect($newer)->toBe(emptyCounts(['updated' => 1]));
    expect(Student::where('uuid', $uuid)->first()->classification->value)->toBe('irregular');

    $stale = $this->service->applyIncoming([
        sampleStudentChange($uuid, $this->department->id, version: 2, overrides: ['classification' => 'regular']),
    ]);
    expect($stale)->toBe(emptyCounts(['skipped' => 1]));
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

    expect($counts)->toBe(emptyCounts(['deleted' => 1]));
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

test('pushTo gathers local pending changes, posts them, and advances the push cursor', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-TEST03', 'name' => 'Test Device', 'owner_user_id' => $this->admin->id, 'status' => 'active']);
    $student = Student::factory()->create(['department_id' => $this->department->id]);

    Http::fake([
        'remote.test/api/sync/push*' => Http::response(emptyCounts(['created' => 1]), 200),
    ]);

    $run = $this->service->pushTo($device, 'https://remote.test', 'fake-token', 'lan-hub');

    expect($run->status)->toBe('success');
    expect($run->uploaded_count)->toBe(1);
    expect($run->created_count)->toBe(1);

    Http::assertSent(function ($request) use ($student) {
        return $request->url() === 'https://remote.test/api/sync/push'
            && collect($request->data()['changes'])->contains(fn ($c) => $c['entity_uuid'] === $student->uuid);
    });

    $pushCheckpoint = SyncCheckpoint::where('device_id', $device->id)->where('remote_target', 'lan-hub:push')->first();
    expect($pushCheckpoint->last_synced_at)->not->toBeNull();

    // A second push with nothing new locally sends an empty batch.
    Http::fake(['remote.test/api/sync/push*' => Http::response(emptyCounts(), 200)]);
    $second = $this->service->pushTo($device, 'https://remote.test', 'fake-token', 'lan-hub');
    expect($second->uploaded_count)->toBe(0);
});

// --- Three-way merge / conflict logic -----------------------------------

test('non-overlapping concurrent edits auto-merge, preserving both sides fields', function () {
    $uuid = (string) Str::uuid();
    $this->service->applyIncoming([sampleStudentChange($uuid, $this->department->id, version: 1)]);

    $local = Student::where('uuid', $uuid)->first();
    $local->update(['classification' => 'irregular']); // local diverges to version 2, touching classification

    // Remote independently diverged from the same base (version 1),
    // touching a different field (status), also reaching version 2.
    $remoteChange = sampleStudentChange($uuid, $this->department->id, version: 2, overrides: [
        'status' => 'inactive',
        'classification' => 'regular', // remote's own (stale, from its perspective) copy — must NOT clobber local's edit
    ]);
    $remoteChange['base_version'] = 1;
    $remoteChange['changed_fields'] = ['status'];

    $counts = $this->service->applyIncoming([$remoteChange]);

    expect($counts)->toBe(emptyCounts(['merged' => 1]));
    $local->refresh();
    expect($local->classification->value)->toBe('irregular'); // local's edit preserved
    expect($local->status->value)->toBe('inactive'); // remote's edit applied
    expect(SyncConflict::count())->toBe(0);
});

test('overlapping concurrent edits to the same field record a conflict and do not overwrite local', function () {
    $uuid = (string) Str::uuid();
    $this->service->applyIncoming([sampleStudentChange($uuid, $this->department->id, version: 1)]);

    $local = Student::where('uuid', $uuid)->first();
    $local->update(['classification' => 'irregular']); // local diverges to version 2, touching classification

    $remoteChange = sampleStudentChange($uuid, $this->department->id, version: 2, overrides: [
        'classification' => 'graduating', // remote touched the SAME field
    ]);
    $remoteChange['base_version'] = 1;
    $remoteChange['changed_fields'] = ['classification'];

    $counts = $this->service->applyIncoming([$remoteChange]);

    expect($counts)->toBe(emptyCounts(['conflicted' => 1]));
    $local->refresh();
    expect($local->classification->value)->toBe('irregular'); // untouched

    $conflict = SyncConflict::where('entity_uuid', $uuid)->first();
    expect($conflict)->not->toBeNull();
    expect($conflict->status)->toBe('pending');
    expect($conflict->conflicting_fields)->toBe(['classification']);
    expect($conflict->local_snapshot['classification'])->toBe('irregular');
    expect($conflict->remote_snapshot['classification'])->toBe('graduating');
});

test('a repeated conflicting change updates the existing pending conflict instead of duplicating it', function () {
    $uuid = (string) Str::uuid();
    $this->service->applyIncoming([sampleStudentChange($uuid, $this->department->id, version: 1)]);
    Student::where('uuid', $uuid)->first()->update(['classification' => 'irregular']);

    $conflictingChange = function (int $version) use ($uuid) {
        $change = sampleStudentChange($uuid, $this->department->id, version: $version, overrides: ['classification' => 'graduating']);
        $change['base_version'] = 1;
        $change['changed_fields'] = ['classification'];

        return $change;
    };

    $this->service->applyIncoming([$conflictingChange(2)]);
    $this->service->applyIncoming([$conflictingChange(2)]);

    expect(SyncConflict::where('entity_uuid', $uuid)->count())->toBe(1);
});

test('grades always conflict on any concurrent divergence, even for non-overlapping fields', function () {
    $enrollmentCourse = EnrollmentCourse::factory()->create();
    $uuid = (string) Str::uuid();

    $created = [
        'entity_table' => 'student_grades',
        'entity_uuid' => $uuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'enrollment_course_id' => $enrollmentCourse->id,
            'grade' => null,
            'status' => 'draft',
            'encoded_by' => $this->admin->id,
            'uuid' => $uuid,
            'sync_version' => 1,
        ],
    ];
    $this->service->applyIncoming([$created]);

    $local = StudentGrade::where('uuid', $uuid)->first();
    $local->update(['status' => 'submitted']); // local diverges to version 2, touching status only

    // Remote touched a completely different field (grade) — would be a
    // safe merge for any other pilot model, but grades never auto-merge.
    $remoteChange = [
        'entity_table' => 'student_grades',
        'entity_uuid' => $uuid,
        'operation' => 'updated',
        'version' => 2,
        'base_version' => 1,
        'changed_fields' => ['grade'],
        'snapshot' => [
            'enrollment_course_id' => $enrollmentCourse->id,
            'grade' => '1.00',
            'status' => 'draft',
            'encoded_by' => $this->admin->id,
            'uuid' => $uuid,
            'sync_version' => 2,
        ],
    ];

    $counts = $this->service->applyIncoming([$remoteChange]);

    expect($counts)->toBe(emptyCounts(['conflicted' => 1]));
    $local->refresh();
    expect($local->status->value)->toBe('submitted');
    expect($local->grade)->toBeNull();

    $conflict = SyncConflict::where('entity_uuid', $uuid)->first();
    expect($conflict)->not->toBeNull();
    expect($conflict->conflicting_fields)->toContain('grade', 'status');
});

test('a remote delete after local diverged records a conflict instead of deleting', function () {
    $uuid = (string) Str::uuid();
    $this->service->applyIncoming([sampleStudentChange($uuid, $this->department->id, version: 1)]);
    Student::where('uuid', $uuid)->first()->update(['classification' => 'irregular']); // local -> version 2

    $counts = $this->service->applyIncoming([[
        'entity_table' => 'students',
        'entity_uuid' => $uuid,
        'operation' => 'deleted',
        'version' => 1, // remote deleted while still at version 1 — local has since moved past it
    ]]);

    expect($counts)->toBe(emptyCounts(['conflicted' => 1]));
    $local = Student::where('uuid', $uuid)->first();
    expect($local)->not->toBeNull();
    expect($local->trashed())->toBeFalse();

    $conflict = SyncConflict::where('entity_uuid', $uuid)->first();
    expect($conflict->conflicting_fields)->toContain('__remote_deleted__');
});

test('a remote delete with no local divergence since its version applies cleanly', function () {
    $uuid = (string) Str::uuid();
    $this->service->applyIncoming([sampleStudentChange($uuid, $this->department->id, version: 1)]);

    $counts = $this->service->applyIncoming([[
        'entity_table' => 'students',
        'entity_uuid' => $uuid,
        'operation' => 'deleted',
        'version' => 1,
    ]]);

    expect($counts)->toBe(emptyCounts(['deleted' => 1]));
    expect(Student::withTrashed()->where('uuid', $uuid)->first()->trashed())->toBeTrue();
});
