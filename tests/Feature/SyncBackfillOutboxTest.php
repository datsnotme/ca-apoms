<?php

use App\Models\AcademicYear;
use App\Models\College;
use App\Models\SyncChange;
use App\Services\SyncService;

test('writes a created outbox entry for a synced row that predates the outbox', function () {
    $college = College::factory()->create();

    // Simulate a row that existed before its table joined the synced set:
    // it has a uuid/sync_version (backfilled by migration) but no outbox
    // history, because the observer wasn't registered yet when it was made.
    SyncChange::where('entity_table', 'colleges')->where('entity_uuid', $college->uuid)->delete();

    expect(SyncChange::where('entity_table', 'colleges')->where('entity_uuid', $college->uuid)->exists())->toBeFalse();

    $this->artisan('sync:backfill-outbox')->assertSuccessful();

    $this->assertDatabaseHas('sync_changes', [
        'entity_table' => 'colleges',
        'entity_uuid' => $college->uuid,
        'operation' => 'created',
        'version' => $college->sync_version,
        'base_version' => null,
        'sync_status' => 'pending',
    ]);
});

test('does not duplicate an outbox entry for a row that already has one', function () {
    College::factory()->create();

    $before = SyncChange::where('entity_table', 'colleges')->count();

    $this->artisan('sync:backfill-outbox')->assertSuccessful();

    expect(SyncChange::where('entity_table', 'colleges')->count())->toBe($before);
});

test('is idempotent across repeated runs', function () {
    $academicYear = AcademicYear::factory()->create();
    SyncChange::where('entity_table', 'academic_years')->where('entity_uuid', $academicYear->uuid)->delete();

    $this->artisan('sync:backfill-outbox')->assertSuccessful();
    $countAfterFirstRun = SyncChange::where('entity_table', 'academic_years')->where('entity_uuid', $academicYear->uuid)->count();

    $this->artisan('sync:backfill-outbox')->assertSuccessful();
    $countAfterSecondRun = SyncChange::where('entity_table', 'academic_years')->where('entity_uuid', $academicYear->uuid)->count();

    expect($countAfterFirstRun)->toBe(1);
    expect($countAfterSecondRun)->toBe(1);
});

test('covers every synced table, not just the pilot four', function () {
    $modelClasses = app(SyncService::class)->syncedTables();

    expect($modelClasses)->toHaveKey('colleges');
    expect($modelClasses)->toHaveKey('documents');
    expect($modelClasses)->toHaveKey('student_documents');
});
