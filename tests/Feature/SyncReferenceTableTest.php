<?php

use App\Enums\RoleName;
use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\YearLevel;
use App\Services\SyncService;
use Illuminate\Support\Str;

/**
 * Phase 6 — proves FK_REFERENCES/resolveForeignKeys() actually translates
 * FK columns to/from uuid, rather than assuming both instances happen to
 * share matching reference-table IDs (the gap documented since Phase 2).
 * Changes are hand-crafted with deliberately "wrong" raw FK ids (simulating
 * a remote instance's own, different ID-space) — the assertion is always
 * that the LOCAL row ends up pointing at the correct LOCAL id, never the
 * raw value that arrived on the wire.
 */
beforeEach(function () {
    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->service = app(SyncService::class);
});

test('creating a Department from a remote change resolves college_id via uuid, not the raw incoming id', function () {
    $localCollege = College::factory()->create();
    $deptUuid = (string) Str::uuid();

    $change = [
        'entity_table' => 'departments',
        'entity_uuid' => $deptUuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'college_id' => $localCollege->id + 999, // deliberately wrong — simulates the remote's own id-space
            'code' => 'REMOTE-DEPT', 'name' => 'Remote Department', 'status' => 'active',
            'uuid' => $deptUuid, 'sync_version' => 1,
            '_fk_uuids' => ['college_id' => $localCollege->uuid],
        ],
    ];

    $counts = $this->service->applyIncoming([$change]);

    expect($counts['created'])->toBe(1);
    $department = Department::where('uuid', $deptUuid)->firstOrFail();
    expect($department->college_id)->toBe($localCollege->id);
});

test('creating a Program from a remote change resolves department_id via uuid', function () {
    $localCollege = College::factory()->create();
    $localDept = Department::factory()->create(['college_id' => $localCollege->id]);
    $programUuid = (string) Str::uuid();

    $change = [
        'entity_table' => 'programs',
        'entity_uuid' => $programUuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'department_id' => $localDept->id + 999,
            'code' => 'REMOTE-PROG', 'name' => 'Remote Program', 'status' => 'active',
            'uuid' => $programUuid, 'sync_version' => 1,
            '_fk_uuids' => ['department_id' => $localDept->uuid],
        ],
    ];

    $this->service->applyIncoming([$change]);

    expect(Program::where('uuid', $programUuid)->firstOrFail()->department_id)->toBe($localDept->id);
});

test('a Student change resolves all four reference-table FKs via uuid at once', function () {
    $localCollege = College::factory()->create();
    $localDept = Department::factory()->create(['college_id' => $localCollege->id]);
    $localProgram = Program::factory()->create(['department_id' => $localDept->id]);
    $localCurriculum = Curriculum::factory()->create(['program_id' => $localProgram->id]);
    $localYearLevel = YearLevel::factory()->create();
    $studentUuid = (string) Str::uuid();

    $change = [
        'entity_table' => 'students',
        'entity_uuid' => $studentUuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'student_number' => '2026-REMOTE', 'surname' => 'Remote', 'first_name' => 'Student',
            'sex' => 'Male', 'birth_date' => '2000-01-01',
            // All four deliberately wrong — the remote's own id-space.
            'department_id' => $localDept->id + 999, 'program_id' => $localProgram->id + 999,
            'curriculum_id' => $localCurriculum->id + 999, 'year_level_id' => $localYearLevel->id + 999,
            'admission_type' => 'Freshman', 'date_admitted' => '2020-01-01',
            'classification' => 'regular', 'status' => 'active',
            'uuid' => $studentUuid, 'sync_version' => 1,
            '_fk_uuids' => [
                'department_id' => $localDept->uuid, 'program_id' => $localProgram->uuid,
                'curriculum_id' => $localCurriculum->uuid, 'year_level_id' => $localYearLevel->uuid,
                // section_id deliberately absent — nullable, was null on the sender.
            ],
        ],
    ];

    $counts = $this->service->applyIncoming([$change]);

    expect($counts['created'])->toBe(1);
    $student = Student::where('uuid', $studentUuid)->firstOrFail();
    expect($student->department_id)->toBe($localDept->id);
    expect($student->program_id)->toBe($localProgram->id);
    expect($student->curriculum_id)->toBe($localCurriculum->id);
    expect($student->year_level_id)->toBe($localYearLevel->id);
    expect($student->section_id)->toBeNull();
});

test('a StudentEnrollment change resolves student_id via uuid — cross-pilot-table translation', function () {
    $localCollege = College::factory()->create();
    $localDept = Department::factory()->create(['college_id' => $localCollege->id]);
    $localProgram = Program::factory()->create(['department_id' => $localDept->id]);
    $localCurriculum = Curriculum::factory()->create(['program_id' => $localProgram->id]);
    $localYearLevel = YearLevel::factory()->create();
    $localStudent = Student::factory()->create([
        'department_id' => $localDept->id, 'program_id' => $localProgram->id,
        'curriculum_id' => $localCurriculum->id, 'year_level_id' => $localYearLevel->id,
    ]);
    $localSemester = Semester::factory()->create(['academic_year_id' => AcademicYear::factory()->create()->id]);

    $enrollmentUuid = (string) Str::uuid();
    $change = [
        'entity_table' => 'student_enrollments',
        'entity_uuid' => $enrollmentUuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'student_id' => $localStudent->id + 999, // wrong — remote's own id-space
            'semester_id' => $localSemester->id + 999,
            'status' => 'enrolled',
            'uuid' => $enrollmentUuid, 'sync_version' => 1,
            '_fk_uuids' => ['student_id' => $localStudent->uuid, 'semester_id' => $localSemester->uuid],
        ],
    ];

    $this->service->applyIncoming([$change]);

    $enrollment = StudentEnrollment::where('uuid', $enrollmentUuid)->firstOrFail();
    expect($enrollment->student_id)->toBe($localStudent->id);
    expect($enrollment->semester_id)->toBe($localSemester->id);
});

test('a change referencing a not-yet-synced dependency throws rather than silently applying a broken FK', function () {
    $unknownUuid = (string) Str::uuid();
    $deptUuid = (string) Str::uuid();

    $change = [
        'entity_table' => 'departments',
        'entity_uuid' => $deptUuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'college_id' => 12345,
            'code' => 'ORPHAN-DEPT', 'name' => 'Orphan Department', 'status' => 'active',
            'uuid' => $deptUuid, 'sync_version' => 1,
            '_fk_uuids' => ['college_id' => $unknownUuid],
        ],
    ];

    expect(fn () => $this->service->applyIncoming([$change]))->toThrow(RuntimeException::class);
    expect(Department::where('uuid', $deptUuid)->exists())->toBeFalse();
});

test('pendingChangesSince orders reference-table changes before their dependents regardless of raw SyncChange id order', function () {
    $college = College::factory()->create();
    $department = Department::factory()->create(['college_id' => $college->id]);
    $program = Program::factory()->create(['department_id' => $department->id]); // higher SyncChange id than the college update below

    // Touch the college AFTER the program already exists, so its latest
    // outbox row has a higher raw id than the program's — without the
    // dependency-order sort, "colleges" would appear after "programs" in
    // the batch purely because of this update's timing.
    $college->update(['name' => 'Renamed College']);

    $result = $this->service->pendingChangesSince(0);
    $tables = collect($result['changes'])->pluck('entity_table')->all();

    $collegePosition = array_search('colleges', $tables);
    $departmentPosition = array_search('departments', $tables);
    $programPosition = array_search('programs', $tables);

    expect($collegePosition)->not->toBeFalse();
    expect($collegePosition)->toBeLessThan($departmentPosition);
    expect($departmentPosition)->toBeLessThan($programPosition);
});

test('a change with no _fk_uuids at all leaves raw FK values untouched (backward compatible with pre-Phase-6 payloads)', function () {
    $department = Department::factory()->create();
    $programUuid = (string) Str::uuid();

    $change = [
        'entity_table' => 'programs',
        'entity_uuid' => $programUuid,
        'operation' => 'created',
        'version' => 1,
        'snapshot' => [
            'department_id' => $department->id,
            'code' => 'OLD-STYLE', 'name' => 'Old Style Program', 'status' => 'active',
            'uuid' => $programUuid, 'sync_version' => 1,
            // no _fk_uuids key at all
        ],
    ];

    $this->service->applyIncoming([$change]);

    expect(Program::where('uuid', $programUuid)->firstOrFail()->department_id)->toBe($department->id);
});
