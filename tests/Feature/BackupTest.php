<?php

use App\Enums\RoleName;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->head = userWithRole(RoleName::DepartmentHead->value);
    $this->faculty = userWithRole(RoleName::Faculty->value);
});

test('only an admin can view the backups index', function () {
    $this->actingAs($this->admin)->get('/backups')->assertOk();
    $this->actingAs($this->dean)->get('/backups')->assertForbidden();
    $this->actingAs($this->head)->get('/backups')->assertForbidden();
    $this->actingAs($this->faculty)->get('/backups')->assertForbidden();
});

test('an admin can trigger a backup, which shells out to mysqldump and writes a file to disk', function () {
    Process::fake(['*' => Process::result(output: '-- fake sql dump --')]);

    $response = $this->actingAs($this->admin)->post('/backups');

    $response->assertRedirect(route('backups.index'))->assertSessionHas('success');

    Process::assertRan(fn ($process) => str_contains(implode(' ', (array) $process->command), 'mysqldump'));

    $files = Storage::disk('local')->files('backups');
    expect($files)->toHaveCount(1);
    expect(Storage::disk('local')->get($files[0]))->toContain('-- fake sql dump --');

    expect(Activity::where('log_name', 'backups')->where('description', 'like', 'Created backup%')->exists())->toBeTrue();
});

test('a failed mysqldump does not write a file and flashes an error', function () {
    Process::fake(['*' => Process::result(output: '', errorOutput: 'mysqldump: command not found', exitCode: 1)]);

    $response = $this->actingAs($this->admin)->post('/backups');

    $response->assertRedirect(route('backups.index'))->assertSessionHas('error');
    expect(Storage::disk('local')->files('backups'))->toBeEmpty();
});

test('the backups index lists an existing backup file with size and created date', function () {
    Storage::disk('local')->put('backups/ca-apoms_2026-01-01_120000.sql', '-- dump --');

    $response = $this->actingAs($this->admin)->get('/backups');

    $response->assertOk()->assertInertia(fn ($page) => $page
        ->has('backups', 1)
        ->where('backups.0.filename', 'ca-apoms_2026-01-01_120000.sql'));
});

test('an admin can download an existing backup', function () {
    Storage::disk('local')->put('backups/ca-apoms_2026-01-01_120000.sql', '-- dump contents --');

    $response = $this->actingAs($this->admin)->get('/backups/ca-apoms_2026-01-01_120000.sql/download');

    $response->assertOk();
});

test('a non-admin cannot download a backup', function () {
    Storage::disk('local')->put('backups/ca-apoms_2026-01-01_120000.sql', '-- dump contents --');

    $this->actingAs($this->faculty)->get('/backups/ca-apoms_2026-01-01_120000.sql/download')->assertForbidden();
});

test('a malformed filename never reaches the controller', function () {
    $this->actingAs($this->admin)->get('/backups/../../.env/download')->assertNotFound();
    $this->actingAs($this->admin)->post('/backups/not-a-real-backup.sql/restore')->assertNotFound();
});

test('an admin can restore from an existing backup, which pipes its contents into the mysql client', function () {
    Process::fake(['*' => Process::result(output: '')]);
    Storage::disk('local')->put('backups/ca-apoms_2026-01-01_120000.sql', '-- dump contents --');

    $response = $this->actingAs($this->admin)->post('/backups/ca-apoms_2026-01-01_120000.sql/restore');

    $response->assertRedirect(route('backups.index'))->assertSessionHas('success');

    Process::assertRan(function ($process) {
        return str_contains(implode(' ', (array) $process->command), 'mysql')
            && ! str_contains(implode(' ', (array) $process->command), 'mysqldump')
            && $process->input === '-- dump contents --';
    });

    expect(Activity::where('log_name', 'backups')->where('description', 'like', 'Restored database%')->exists())->toBeTrue();
});

test('a failed restore flashes an error and is still logged', function () {
    Process::fake(['*' => Process::result(output: '', errorOutput: 'Access denied', exitCode: 1)]);
    Storage::disk('local')->put('backups/ca-apoms_2026-01-01_120000.sql', '-- dump contents --');

    $response = $this->actingAs($this->admin)->post('/backups/ca-apoms_2026-01-01_120000.sql/restore');

    $response->assertRedirect(route('backups.index'))->assertSessionHas('error');
    expect(Activity::where('log_name', 'backups')->where('description', 'like', 'Restore from%failed%')->exists())->toBeTrue();
});

test('a non-admin cannot trigger a backup or a restore', function () {
    Storage::disk('local')->put('backups/ca-apoms_2026-01-01_120000.sql', '-- dump contents --');

    $this->actingAs($this->faculty)->post('/backups')->assertForbidden();
    $this->actingAs($this->faculty)->post('/backups/ca-apoms_2026-01-01_120000.sql/restore')->assertForbidden();
});
