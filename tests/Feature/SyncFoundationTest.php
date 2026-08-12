<?php

use App\Enums\RoleName;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Department;
use App\Models\EnrollmentCourse;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\SyncChange;
use Database\Seeders\GradingScaleSeeder;

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->course = Course::factory()->create(['department_id' => $this->department->id]);
    $this->semester = Semester::factory()->create();
    $this->section = ClassSection::factory()->create(['course_id' => $this->course->id, 'semester_id' => $this->semester->id]);
});

test('creating a student assigns a uuid, sets sync_version to 1, and writes a created outbox row', function () {
    $student = Student::factory()->create(['department_id' => $this->department->id]);

    expect($student->uuid)->not->toBeNull();
    expect($student->sync_version)->toBe(1);

    $this->assertDatabaseHas('sync_changes', [
        'entity_table' => 'students',
        'entity_uuid' => $student->uuid,
        'operation' => 'created',
        'version' => 1,
        'sync_status' => 'pending',
    ]);
});

test('updating a business field bumps sync_version and writes an updated outbox row', function () {
    $student = Student::factory()->create(['department_id' => $this->department->id]);

    $student->update(['classification' => 'irregular']);

    expect($student->fresh()->sync_version)->toBe(2);
    $this->assertDatabaseHas('sync_changes', [
        'entity_table' => 'students',
        'entity_uuid' => $student->uuid,
        'operation' => 'updated',
        'version' => 2,
    ]);
});

test('touching a record with no real field change does not bump the version or write an outbox row', function () {
    $student = Student::factory()->create(['department_id' => $this->department->id]);
    $countBefore = SyncChange::where('entity_uuid', $student->uuid)->count();

    $student->touch();

    expect($student->fresh()->sync_version)->toBe(1);
    expect(SyncChange::where('entity_uuid', $student->uuid)->count())->toBe($countBefore);
});

test('soft-deleting a student writes a deleted outbox row and the row remains tombstoned, not gone', function () {
    $student = Student::factory()->create(['department_id' => $this->department->id]);
    $uuid = $student->uuid;

    $student->delete();

    expect(Student::withTrashed()->where('uuid', $uuid)->first()?->trashed())->toBeTrue();
    $this->assertDatabaseHas('sync_changes', [
        'entity_table' => 'students',
        'entity_uuid' => $uuid,
        'operation' => 'deleted',
    ]);
});

test('the sync observer applies to all four pilot models, not just Student', function () {
    $student = Student::factory()->create(['department_id' => $this->department->id]);
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'semester_id' => $this->semester->id]);
    $enrollmentCourse = EnrollmentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id,
        'class_section_id' => $this->section->id,
    ]);
    $grade = StudentGrade::create(['enrollment_course_id' => $enrollmentCourse->id, 'grade' => '1.00', 'status' => 'draft', 'encoded_by' => $this->admin->id]);

    foreach ([$enrollment, $enrollmentCourse, $grade] as $record) {
        expect($record->uuid)->not->toBeNull();
        expect($record->sync_version)->toBe(1);
    }

    $this->assertDatabaseHas('sync_changes', ['entity_table' => 'student_enrollments', 'entity_uuid' => $enrollment->uuid, 'operation' => 'created']);
    $this->assertDatabaseHas('sync_changes', ['entity_table' => 'enrollment_courses', 'entity_uuid' => $enrollmentCourse->uuid, 'operation' => 'created']);
    $this->assertDatabaseHas('sync_changes', ['entity_table' => 'student_grades', 'entity_uuid' => $grade->uuid, 'operation' => 'created']);
});

test('deleting a grade tombstones it via the existing SoftDeletes column, not a new one', function () {
    $student = Student::factory()->create(['department_id' => $this->department->id]);
    $enrollment = StudentEnrollment::factory()->create(['student_id' => $student->id, 'semester_id' => $this->semester->id]);
    $enrollmentCourse = EnrollmentCourse::factory()->create([
        'student_enrollment_id' => $enrollment->id,
        'class_section_id' => $this->section->id,
    ]);
    $grade = StudentGrade::create(['enrollment_course_id' => $enrollmentCourse->id, 'grade' => '1.00', 'status' => 'draft', 'encoded_by' => $this->admin->id]);

    $grade->delete();

    expect(StudentGrade::withTrashed()->find($grade->id)->trashed())->toBeTrue();
    $this->assertDatabaseHas('sync_changes', ['entity_table' => 'student_grades', 'entity_uuid' => $grade->uuid, 'operation' => 'deleted']);
});

test('a Sanctum token can be issued for a user and identifies that user on use', function () {
    $token = $this->admin->createToken('lan-hub-device', ['sync:read', 'sync:write']);

    expect($token->accessToken->tokenable_id)->toBe($this->admin->id);
    expect($token->accessToken->can('sync:write'))->toBeTrue();
    expect($token->accessToken->can('users:manage'))->toBeFalse();
});
