<?php

use App\Enums\RoleName;
use App\Models\AcademicDeficiency;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\Department;
use App\Models\GraduationCandidate;
use App\Models\GraduationRequirementTemplate;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\YearLevel;
use App\Services\GraduationCandidateService;
use Database\Seeders\GradingScaleSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(GradingScaleSeeder::class);

    $this->admin = userWithRole(RoleName::Administrator->value);
    $this->department = Department::factory()->create();
    $this->program = Program::factory()->create(['department_id' => $this->department->id]);
    $this->curriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $this->semester = Semester::factory()->create();
    $this->yearLevel = YearLevel::factory()->create(['level' => 1]);
    $this->academicYear = $this->semester->academicYear;

    $this->service = app(GraduationCandidateService::class);
});

function makeCompletedStudent(): Student
{
    $test = test();
    $course = Course::factory()->create(['department_id' => $test->department->id]);
    $cc = CurriculumCourse::factory()->create(['curriculum_id' => $test->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $student = Student::factory()->create([
        'department_id' => $test->department->id,
        'program_id' => $test->program->id,
        'curriculum_id' => $test->curriculum->id,
        'year_level_id' => $test->yearLevel->id,
        'status' => 'active',
    ]);

    $ec = enrollStudentInCourse($student, $test->semester, $course);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '1.00', 'status' => 'finalized']);

    return $student;
}

test('a student with 100% completion and no deficiencies is eligible for nomination', function () {
    $student = makeCompletedStudent();

    $eligible = $this->service->identifyEligibleStudents();

    expect($eligible->pluck('id'))->toContain($student->id);
});

test('a student with an unresolved deficiency is not eligible', function () {
    $student = makeCompletedStudent();
    AcademicDeficiency::factory()->create(['student_id' => $student->id]);

    $eligible = $this->service->identifyEligibleStudents();

    expect($eligible->pluck('id'))->not->toContain($student->id);
});

test('a student with an incomplete checklist is not eligible', function () {
    $course = Course::factory()->create(['department_id' => $this->department->id]);
    CurriculumCourse::factory()->create(['curriculum_id' => $this->curriculum->id, 'course_id' => $course->id, 'year_level' => 1, 'units' => 3]);

    $student = Student::factory()->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $this->curriculum->id,
        'year_level_id' => $this->yearLevel->id,
        'status' => 'active',
    ]);

    $eligible = $this->service->identifyEligibleStudents();

    expect($eligible->pluck('id'))->not->toContain($student->id);
});

test('nominating a student snapshots academic standing and generates the requirement checklist', function () {
    $student = makeCompletedStudent();
    GraduationRequirementTemplate::factory()->create(['program_id' => null, 'title' => 'Library Clearance']);
    GraduationRequirementTemplate::factory()->create(['program_id' => $this->program->id, 'title' => 'Thesis Defense']);
    GraduationRequirementTemplate::factory()->create(['program_id' => Program::factory()->create()->id, 'title' => 'Other Program Only']);

    $candidate = $this->service->nominate($student, $this->academicYear, $this->semester, $this->admin);

    expect($candidate->gwa_snapshot)->toBe('1.00');
    expect((float) $candidate->completion_percentage_snapshot)->toBe(100.0);
    expect($candidate->deficiency_count_snapshot)->toBe(0);
    expect($candidate->requirements()->count())->toBe(2);
    $this->assertDatabaseHas('student_graduation_requirements', ['graduation_candidate_id' => $candidate->id, 'status' => 'pending']);
});

test('a student cannot be nominated twice while an active candidacy exists', function () {
    $student = makeCompletedStudent();
    $this->service->nominate($student, $this->academicYear, $this->semester, $this->admin);

    expect(fn () => $this->service->nominate($student, $this->academicYear, $this->semester, $this->admin))
        ->toThrow(ValidationException::class);
});

test('an admin can nominate a student via the UI', function () {
    $student = makeCompletedStudent();

    $response = $this->actingAs($this->admin)->post('/graduation-candidates', [
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'semester_id' => $this->semester->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('graduation_candidates', ['student_id' => $student->id, 'status' => 'nominated']);
});

test('a department head cannot nominate a student', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $student = makeCompletedStudent();

    $this->actingAs($head)->post('/graduation-candidates', [
        'student_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'semester_id' => $this->semester->id,
    ])->assertForbidden();
});

test('an admin can satisfy and waive requirements, and a department head cannot', function () {
    $student = makeCompletedStudent();
    GraduationRequirementTemplate::factory()->create(['program_id' => null, 'title' => 'Library Clearance']);
    $candidate = $this->service->nominate($student, $this->academicYear, $this->semester, $this->admin);
    $requirement = $candidate->requirements()->first();

    $response = $this->actingAs($this->admin)->put(
        "/graduation-candidates/{$candidate->id}/requirements/{$requirement->id}",
        ['status' => 'satisfied', 'remarks' => 'Cleared on file.']
    );

    $response->assertRedirect();
    $requirement->refresh();
    expect($requirement->status->value)->toBe('satisfied');
    expect($requirement->satisfied_by)->toBe($this->admin->id);
    expect($requirement->satisfied_at)->not->toBeNull();

    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->actingAs($head)->put(
        "/graduation-candidates/{$candidate->id}/requirements/{$requirement->id}",
        ['status' => 'waived']
    )->assertForbidden();
});

test('resetting a requirement to pending clears who satisfied it', function () {
    $student = makeCompletedStudent();
    GraduationRequirementTemplate::factory()->create(['program_id' => null, 'title' => 'Library Clearance']);
    $candidate = $this->service->nominate($student, $this->academicYear, $this->semester, $this->admin);
    $requirement = $candidate->requirements()->first();
    $requirement->update(['status' => 'satisfied', 'satisfied_by' => $this->admin->id, 'satisfied_at' => now()]);

    $this->actingAs($this->admin)->put(
        "/graduation-candidates/{$candidate->id}/requirements/{$requirement->id}",
        ['status' => 'pending']
    );

    $requirement->refresh();
    expect($requirement->status->value)->toBe('pending');
    expect($requirement->satisfied_by)->toBeNull();
    expect($requirement->satisfied_at)->toBeNull();
});

test('a department head only sees candidates from their own department', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $student = makeCompletedStudent();
    $this->service->nominate($student, $this->academicYear, $this->semester, $this->admin);

    $otherDepartment = Department::factory()->create();
    $otherProgram = Program::factory()->create(['department_id' => $otherDepartment->id]);
    $otherCurriculum = Curriculum::factory()->create(['program_id' => $otherProgram->id]);
    $otherStudent = Student::factory()->create([
        'department_id' => $otherDepartment->id,
        'program_id' => $otherProgram->id,
        'curriculum_id' => $otherCurriculum->id,
        'year_level_id' => $this->yearLevel->id,
    ]);
    GraduationCandidate::factory()->create([
        'student_id' => $otherStudent->id,
        'academic_year_id' => $this->academicYear->id,
        'semester_id' => $this->semester->id,
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($head)->get('/graduation-candidates');

    $response->assertInertia(fn ($page) => $page->has('candidates.data', 1));
});
