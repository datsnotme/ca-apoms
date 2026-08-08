<?php

use App\Enums\RoleName;
use App\Models\CompetencyCategory;
use App\Models\CompetencyIndicator;
use App\Models\Curriculum;
use App\Models\Department;
use App\Models\GraduationCandidate;
use App\Models\Program;
use App\Models\Semester;
use App\Models\YearLevel;
use App\Services\CompetencyEvaluationService;
use App\Services\GraduationCandidateService;
use App\Services\GraduationRecommendationService;
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

    $this->head = userWithRole(RoleName::DepartmentHead->value, $this->department);
    $this->dean = userWithRole(RoleName::Dean->value);
    $this->faculty = userWithRole(RoleName::Faculty->value, $this->department);

    $this->candidates = app(GraduationCandidateService::class);
    $this->evaluations = app(CompetencyEvaluationService::class);
    $this->recommendations = app(GraduationRecommendationService::class);

    $this->indicator = CompetencyIndicator::factory()->create([
        'competency_category_id' => CompetencyCategory::factory()->create()->id,
    ]);

    $student = makeCompletedStudent();
    $this->candidate = $this->candidates->nominate($student, $this->academicYear, $this->semester, $this->admin);
});

function readyCandidate(): GraduationCandidate
{
    $test = test();
    $assignment = $test->evaluations->assignEvaluator($test->candidate, $test->faculty, $test->admin);
    $test->evaluations->submitRating($assignment, $test->indicator, 5, null);

    return $test->candidate->refresh();
}

test('a department head can recommend a fully-evaluated candidate', function () {
    readyCandidate();

    $response = $this->actingAs($this->head)->post("/graduation-candidates/{$this->candidate->id}/recommendation", [
        'remarks' => 'Meets all departmental standards.',
    ]);

    $response->assertRedirect();
    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('recommended');
    expect($this->candidate->recommended_by)->toBe($this->head->id);
    expect($this->candidate->recommended_at)->not->toBeNull();
    expect($this->candidate->recommendation_remarks)->toBe('Meets all departmental standards.');
});

test('recommending fails while still nominated (no evaluator assigned yet)', function () {
    expect(fn () => $this->recommendations->recommend($this->candidate, $this->head, null))
        ->toThrow(ValidationException::class);

    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('nominated');
});

test('recommending fails once under evaluation if the evaluation is not yet complete', function () {
    $this->evaluations->assignEvaluator($this->candidate, $this->faculty, $this->admin);
    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('under_evaluation');

    expect(fn () => $this->recommendations->recommend($this->candidate, $this->head, null))
        ->toThrow(ValidationException::class);

    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('under_evaluation');
});

test('recommending fails once a candidate is no longer under evaluation', function () {
    readyCandidate();
    $this->recommendations->recommend($this->candidate, $this->head, null);

    expect(fn () => $this->recommendations->recommend($this->candidate->refresh(), $this->head, null))
        ->toThrow(ValidationException::class);
});

test('a department head from a different department cannot recommend', function () {
    readyCandidate();
    $otherHead = userWithRole(RoleName::DepartmentHead->value, Department::factory()->create());

    $this->actingAs($otherHead)->post("/graduation-candidates/{$this->candidate->id}/recommendation", [
        'remarks' => null,
    ])->assertForbidden();
});

test('an admin cannot recommend a candidate', function () {
    readyCandidate();

    $this->actingAs($this->admin)->post("/graduation-candidates/{$this->candidate->id}/recommendation", [
        'remarks' => null,
    ])->assertForbidden();
});

test('a dean can approve a recommended candidate', function () {
    readyCandidate();
    $this->recommendations->recommend($this->candidate, $this->head, null);

    $response = $this->actingAs($this->dean)->post("/graduation-candidates/{$this->candidate->id}/decision", [
        'decision' => 'approve',
        'remarks' => 'Approved by the Dean.',
    ]);

    $response->assertRedirect();
    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('approved');
    expect($this->candidate->decided_by)->toBe($this->dean->id);
    expect($this->candidate->decision_remarks)->toBe('Approved by the Dean.');
});

test('a dean can reject a recommended candidate', function () {
    readyCandidate();
    $this->recommendations->recommend($this->candidate, $this->head, null);

    $this->actingAs($this->dean)->post("/graduation-candidates/{$this->candidate->id}/decision", [
        'decision' => 'reject',
    ])->assertRedirect();

    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('rejected');
});

test('a decision fails unless the candidate has been recommended', function () {
    readyCandidate();

    expect(fn () => $this->recommendations->approve($this->candidate, $this->dean, null))
        ->toThrow(ValidationException::class);
});

test('a department head cannot approve or reject their own recommendation', function () {
    readyCandidate();
    $this->recommendations->recommend($this->candidate, $this->head, null);

    $this->actingAs($this->head)->post("/graduation-candidates/{$this->candidate->id}/decision", [
        'decision' => 'approve',
    ])->assertForbidden();
});

test('the full lifecycle from nomination to approval progresses correctly', function () {
    expect($this->candidate->status->value)->toBe('nominated');

    $this->evaluations->assignEvaluator($this->candidate, $this->faculty, $this->admin);
    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('under_evaluation');

    $assignment = $this->candidate->competencyEvaluators()->first();
    $this->evaluations->submitRating($assignment, $this->indicator, 4, null);

    $this->recommendations->recommend($this->candidate->refresh(), $this->head, 'Ready.');
    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('recommended');

    $this->recommendations->approve($this->candidate, $this->dean, 'Confirmed.');
    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('approved');
});
