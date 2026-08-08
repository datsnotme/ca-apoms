<?php

use App\Enums\RoleName;
use App\Models\CompetencyCategory;
use App\Models\CompetencyIndicator;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\CurriculumCourse;
use App\Models\Department;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGrade;
use App\Models\YearLevel;
use App\Services\CompetencyEvaluationService;
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

    $this->candidates = app(GraduationCandidateService::class);
    $this->evaluations = app(CompetencyEvaluationService::class);

    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->indicator = CompetencyIndicator::factory()->create([
        'competency_category_id' => CompetencyCategory::factory()->create()->id,
    ]);

    $student = makeCompletedStudent();
    $this->candidate = $this->candidates->nominate($student, $this->academicYear, $this->semester, $this->admin);
});

test('assigning the first evaluator moves a candidate from nominated to under_evaluation', function () {
    expect($this->candidate->status->value)->toBe('nominated');

    $this->actingAs($this->admin)->post("/graduation-candidates/{$this->candidate->id}/evaluators", [
        'evaluator_id' => $this->faculty->id,
    ])->assertRedirect();

    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('under_evaluation');
    $this->assertDatabaseHas('competency_evaluators', [
        'graduation_candidate_id' => $this->candidate->id,
        'evaluator_id' => $this->faculty->id,
    ]);
});

test('the same evaluator cannot be assigned to a candidate twice', function () {
    $this->evaluations->assignEvaluator($this->candidate, $this->faculty, $this->admin);

    expect(fn () => $this->evaluations->assignEvaluator($this->candidate, $this->faculty, $this->admin))
        ->toThrow(ValidationException::class);
});

test('only faculty members can be assigned as competency evaluators', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->actingAs($this->admin)->post("/graduation-candidates/{$this->candidate->id}/evaluators", [
        'evaluator_id' => $head->id,
    ])->assertSessionHasErrors('evaluator_id');
});

test('an evaluator from a different department cannot be assigned', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, Department::factory()->create());

    $this->actingAs($this->admin)->post("/graduation-candidates/{$this->candidate->id}/evaluators", [
        'evaluator_id' => $otherFaculty->id,
    ])->assertSessionHasErrors('evaluator_id');
});

test('a department head cannot assign evaluators', function () {
    $head = userWithRole(RoleName::DepartmentHead->value, $this->department);

    $this->actingAs($head)->post("/graduation-candidates/{$this->candidate->id}/evaluators", [
        'evaluator_id' => $this->faculty->id,
    ])->assertForbidden();
});

test('an assigned evaluator can submit a rating for an indicator', function () {
    $this->evaluations->assignEvaluator($this->candidate, $this->faculty, $this->admin);

    $response = $this->actingAs($this->faculty)->put(
        "/graduation-candidates/{$this->candidate->id}/ratings/{$this->indicator->id}",
        ['rating' => 4, 'remarks' => 'Solid grasp of core concepts.']
    );

    $response->assertRedirect();
    $this->assertDatabaseHas('competency_ratings', [
        'competency_indicator_id' => $this->indicator->id,
        'rating' => 4,
    ]);
});

test('a faculty member who is not assigned as an evaluator cannot submit a rating', function () {
    $otherFaculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->actingAs($otherFaculty)->put(
        "/graduation-candidates/{$this->candidate->id}/ratings/{$this->indicator->id}",
        ['rating' => 4]
    )->assertForbidden();
});

test('a rating must be between 1 and 5', function () {
    $this->evaluations->assignEvaluator($this->candidate, $this->faculty, $this->admin);

    $this->actingAs($this->faculty)->put(
        "/graduation-candidates/{$this->candidate->id}/ratings/{$this->indicator->id}",
        ['rating' => 6]
    )->assertSessionHasErrors('rating');
});

test('evaluationComplete is false until every assigned evaluator rates every indicator, then true', function () {
    $assignment = $this->evaluations->assignEvaluator($this->candidate, $this->faculty, $this->admin);
    expect($this->candidate->evaluationComplete())->toBeFalse();

    $this->evaluations->submitRating($assignment, $this->indicator, 5, null);

    $this->candidate->refresh();
    expect($this->candidate->evaluationComplete())->toBeTrue();
});

test('a faculty evaluator only sees candidates they are assigned to evaluate', function () {
    $this->evaluations->assignEvaluator($this->candidate, $this->faculty, $this->admin);

    // A second, independent curriculum so this student's checklist isn't
    // polluted by the course added to $this->curriculum in beforeEach.
    $otherCurriculum = Curriculum::factory()->create(['program_id' => $this->program->id]);
    $otherCourse = Course::factory()->create(['department_id' => $this->department->id]);
    CurriculumCourse::factory()->create(['curriculum_id' => $otherCurriculum->id, 'course_id' => $otherCourse->id, 'year_level' => 1, 'units' => 3]);
    $otherStudent = Student::factory()->create([
        'department_id' => $this->department->id,
        'program_id' => $this->program->id,
        'curriculum_id' => $otherCurriculum->id,
        'year_level_id' => $this->yearLevel->id,
        'status' => 'active',
    ]);
    $ec = enrollStudentInCourse($otherStudent, $this->semester, $otherCourse);
    StudentGrade::factory()->create(['enrollment_course_id' => $ec->id, 'grade' => '1.00', 'status' => 'finalized']);

    $this->candidates->nominate($otherStudent, $this->academicYear, $this->semester, $this->admin);

    $response = $this->actingAs($this->faculty)->get('/graduation-candidates');

    $response->assertInertia(fn ($page) => $page->has('candidates.data', 1));
});

test('removing an evaluator deletes their ratings', function () {
    $assignment = $this->evaluations->assignEvaluator($this->candidate, $this->faculty, $this->admin);
    $this->evaluations->submitRating($assignment, $this->indicator, 3, null);

    $this->actingAs($this->admin)->delete("/graduation-candidates/{$this->candidate->id}/evaluators/{$assignment->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('competency_evaluators', ['id' => $assignment->id]);
    $this->assertDatabaseMissing('competency_ratings', ['competency_evaluator_id' => $assignment->id]);
});
