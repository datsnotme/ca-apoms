<?php

use App\Enums\RoleName;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Device;
use App\Models\Program;
use App\Models\Student;
use App\Models\SyncConflict;
use App\Models\SyncRemote;
use App\Models\SyncRun;
use App\Models\YearLevel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $this->yearLevel = YearLevel::factory()->create();
});

test('a non-admin cannot view or act on any Sync Center route', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-DEAN01', 'name' => 'Dean Device', 'status' => 'active']);
    $remote = SyncRemote::create(['name' => 'Cloud', 'base_url' => 'https://remote.test', 'token' => 'secret']);

    $this->actingAs($this->dean)->get('/sync')->assertForbidden();
    $this->actingAs($this->dean)->get('/sync/history')->assertForbidden();
    $this->actingAs($this->dean)->get('/sync/conflicts')->assertForbidden();
    $this->actingAs($this->dean)->post("/sync/devices/{$device->id}/set-local")->assertForbidden();
    $this->actingAs($this->dean)->post('/sync/remotes', ['name' => 'x', 'base_url' => 'https://x.test', 'token' => 't'])->assertForbidden();
    $this->actingAs($this->dean)->post("/sync/remotes/{$remote->id}/sync")->assertForbidden();
});

test('an admin can view the Sync Center overview with devices, remotes, and pending conflict count', function () {
    Device::create(['device_code' => 'CAAPOMS-A', 'name' => 'Instance A', 'status' => 'active']);
    SyncRemote::create(['name' => 'Cloud', 'base_url' => 'https://remote.test', 'token' => 'secret']);
    SyncConflict::create([
        'entity_table' => 'students', 'entity_uuid' => (string) Str::uuid(),
        'local_snapshot' => ['classification' => 'regular'], 'remote_snapshot' => ['classification' => 'irregular'],
        'conflicting_fields' => ['classification'], 'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)->get('/sync');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->component('Sync/Index')
        ->has('devices', 1)
        ->has('remotes', 1)
        ->where('pendingConflictCount', 1)
    );
});

test('an admin can set a device as this instance, unsetting any previous local device', function () {
    $first = Device::create(['device_code' => 'CAAPOMS-1', 'name' => 'One', 'status' => 'active', 'is_local' => true]);
    $second = Device::create(['device_code' => 'CAAPOMS-2', 'name' => 'Two', 'status' => 'active']);

    $this->actingAs($this->admin)->post("/sync/devices/{$second->id}/set-local")->assertRedirect();

    expect($first->refresh()->is_local)->toBeFalse();
    expect($second->refresh()->is_local)->toBeTrue();
});

test('an admin can add, update, and remove a sync remote', function () {
    $this->actingAs($this->admin)->post('/sync/remotes', [
        'name' => 'Cloud', 'base_url' => 'https://cloud.test', 'token' => 'abc123',
    ])->assertRedirect();

    $remote = SyncRemote::where('name', 'Cloud')->firstOrFail();
    expect($remote->base_url)->toBe('https://cloud.test');
    expect($remote->token)->toBe('abc123');

    $this->actingAs($this->admin)->put("/sync/remotes/{$remote->id}", [
        'name' => 'Cloud Renamed', 'base_url' => 'https://cloud.test', 'token' => '',
    ])->assertRedirect();

    $remote->refresh();
    expect($remote->name)->toBe('Cloud Renamed');
    expect($remote->token)->toBe('abc123'); // blank token on update leaves it unchanged

    $this->actingAs($this->admin)->delete("/sync/remotes/{$remote->id}")->assertRedirect();
    expect(SyncRemote::find($remote->id))->toBeNull();
});

test('adding a remote requires a token but updating does not', function () {
    $this->actingAs($this->admin)->post('/sync/remotes', [
        'name' => 'Cloud', 'base_url' => 'https://cloud.test',
    ])->assertSessionHasErrors('token');
});

test('syncNow reconciles with the remote using the local device and reports counts', function () {
    $local = Device::create(['device_code' => 'CAAPOMS-LOCAL', 'name' => 'Local', 'status' => 'active', 'is_local' => true]);
    $remote = SyncRemote::create(['name' => 'Cloud', 'base_url' => 'https://remote.test', 'token' => 'tok']);

    Http::fake([
        'remote.test/api/sync/pull*' => Http::response(['changes' => [], 'next_since_id' => 0], 200),
        'remote.test/api/sync/push*' => Http::response(['created' => 0, 'updated' => 0, 'merged' => 0, 'conflicted' => 0, 'deleted' => 0, 'skipped' => 0], 200),
    ]);

    $response = $this->actingAs($this->admin)->post("/sync/remotes/{$remote->id}/sync");

    $response->assertRedirect()->assertSessionHas('success');
    expect(SyncRun::where('device_id', $local->id)->count())->toBe(2); // one pull, one push
});

test('syncNow without a local device asks the admin to set one first', function () {
    $remote = SyncRemote::create(['name' => 'Cloud', 'base_url' => 'https://remote.test', 'token' => 'tok']);

    $response = $this->actingAs($this->admin)->post("/sync/remotes/{$remote->id}/sync");

    $response->assertRedirect()->assertSessionHas('error');
    expect(SyncRun::count())->toBe(0);
});

test('an admin can view paginated sync history', function () {
    $device = Device::create(['device_code' => 'CAAPOMS-H', 'name' => 'H', 'status' => 'active']);
    SyncRun::create(['device_id' => $device->id, 'direction' => 'pull', 'target' => 'cloud', 'started_at' => now(), 'status' => 'success']);

    $response = $this->actingAs($this->admin)->get('/sync/history');

    $response->assertOk()->assertInertia(fn ($page) => $page->component('Sync/History')->has('runs.data', 1));
});

test('conflicts index defaults to pending status and can filter to resolved', function () {
    $pendingUuid = (string) Str::uuid();
    $resolvedUuid = (string) Str::uuid();
    SyncConflict::create(['entity_table' => 'students', 'entity_uuid' => $pendingUuid, 'local_snapshot' => [], 'remote_snapshot' => [], 'conflicting_fields' => [], 'status' => 'pending']);
    SyncConflict::create(['entity_table' => 'students', 'entity_uuid' => $resolvedUuid, 'local_snapshot' => [], 'remote_snapshot' => [], 'conflicting_fields' => [], 'status' => 'resolved']);

    $this->actingAs($this->admin)->get('/sync/conflicts')
        ->assertInertia(fn ($page) => $page->has('conflicts.data', 1)->where('conflicts.data.0.entity_uuid', $pendingUuid));

    $this->actingAs($this->admin)->get('/sync/conflicts?status=resolved')
        ->assertInertia(fn ($page) => $page->has('conflicts.data', 1)->where('conflicts.data.0.entity_uuid', $resolvedUuid));
});

test('resolving a conflict with take_remote overwrites the local entity and marks it resolved', function () {
    $student = Student::factory()->create([
        'department_id' => $this->department->id, 'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id, 'year_level_id' => $this->yearLevel->id,
        'classification' => 'irregular',
    ]);
    $conflict = SyncConflict::create([
        'entity_table' => 'students', 'entity_uuid' => $student->uuid,
        'local_snapshot' => ['classification' => 'irregular'],
        'remote_snapshot' => ['classification' => 'graduating'],
        'conflicting_fields' => ['classification'], 'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)->post("/sync/conflicts/{$conflict->id}/resolve", ['resolution' => 'take_remote']);

    $response->assertRedirect()->assertSessionHas('success');
    expect($student->refresh()->classification->value)->toBe('graduating');
    $conflict->refresh();
    expect($conflict->status)->toBe('resolved');
    expect($conflict->resolution)->toBe('take_remote');
    expect($conflict->resolved_by)->toBe($this->admin->id);
    expect($conflict->resolved_at)->not->toBeNull();
});

test('resolving a conflict with keep_local leaves the entity untouched and marks it resolved', function () {
    $student = Student::factory()->create([
        'department_id' => $this->department->id, 'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id, 'year_level_id' => $this->yearLevel->id,
        'classification' => 'irregular',
    ]);
    $conflict = SyncConflict::create([
        'entity_table' => 'students', 'entity_uuid' => $student->uuid,
        'local_snapshot' => ['classification' => 'irregular'],
        'remote_snapshot' => ['classification' => 'graduating'],
        'conflicting_fields' => ['classification'], 'status' => 'pending',
    ]);

    $this->actingAs($this->admin)->post("/sync/conflicts/{$conflict->id}/resolve", ['resolution' => 'keep_local'])
        ->assertRedirect()->assertSessionHas('success');

    expect($student->refresh()->classification->value)->toBe('irregular');
    expect($conflict->refresh()->status)->toBe('resolved');
    expect($conflict->resolution)->toBe('keep_local');
});

test('an invalid resolution value is rejected', function () {
    $conflict = SyncConflict::create([
        'entity_table' => 'students', 'entity_uuid' => (string) Str::uuid(),
        'local_snapshot' => [], 'remote_snapshot' => [], 'conflicting_fields' => [], 'status' => 'pending',
    ]);

    $this->actingAs($this->admin)->post("/sync/conflicts/{$conflict->id}/resolve", ['resolution' => 'flip_a_coin'])
        ->assertSessionHasErrors('resolution');
});

test('the pending sync conflict count is shared for an admin but not for other roles', function () {
    SyncConflict::create([
        'entity_table' => 'students', 'entity_uuid' => (string) Str::uuid(),
        'local_snapshot' => [], 'remote_snapshot' => [], 'conflicting_fields' => [], 'status' => 'pending',
    ]);

    $this->actingAs($this->admin)->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('pendingSyncConflicts', 1));

    $this->actingAs($this->dean)->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('pendingSyncConflicts', null));
});
