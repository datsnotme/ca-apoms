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

function approveCandidate(): GraduationCandidate
{
    $test = test();
    $assignment = $test->evaluations->assignEvaluator($test->candidate, $test->faculty, $test->admin);
    $test->evaluations->submitRating($assignment, $test->indicator, 5, null);
    $test->recommendations->recommend($test->candidate->refresh(), $test->head, null);
    $test->recommendations->approve($test->candidate->refresh(), $test->dean, null);

    return $test->candidate->refresh();
}

test('an admin can mark an approved candidate as graduated', function () {
    approveCandidate();

    $response = $this->actingAs($this->admin)->post("/graduation-candidates/{$this->candidate->id}/confer");

    $response->assertRedirect();
    $this->candidate->refresh();
    expect($this->candidate->status->value)->toBe('graduated');
    expect($this->candidate->graduated_at)->not->toBeNull();
});

test('marking graduated fails unless the candidate is approved', function () {
    expect(fn () => $this->recommendations->markGraduated($this->candidate))
        ->toThrow(ValidationException::class);
});

test('a department head cannot mark a candidate as graduated', function () {
    approveCandidate();

    $this->actingAs($this->head)->post("/graduation-candidates/{$this->candidate->id}/confer")
        ->assertForbidden();
});

test('an authorized user can download the individual candidate PDF report', function () {
    approveCandidate();

    $response = $this->actingAs($this->admin)->get("/graduation-candidates/{$this->candidate->id}/report");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('a faculty member not assigned to a candidate cannot download its report', function () {
    approveCandidate();
    $otherFaculty = userWithRole(RoleName::Faculty->value, Department::factory()->create());

    $this->actingAs($otherFaculty)->get("/graduation-candidates/{$this->candidate->id}/report")
        ->assertForbidden();
});

test('an admin can download the batch graduation list PDF', function () {
    approveCandidate();

    $response = $this->actingAs($this->admin)->get('/graduation-candidates/report/batch?'.http_build_query([
        'academic_year_id' => $this->academicYear->id,
        'semester_id' => $this->semester->id,
    ]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('the batch report requires an academic year and semester', function () {
    $this->actingAs($this->admin)->get('/graduation-candidates/report/batch')
        ->assertSessionHasErrors(['academic_year_id', 'semester_id']);
});
