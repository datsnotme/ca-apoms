<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduation\CompetencyRatingRequest;
use App\Models\CompetencyIndicator;
use App\Models\GraduationCandidate;
use App\Services\CompetencyEvaluationService;
use Illuminate\Http\RedirectResponse;

class CompetencyRatingController extends Controller
{
    public function __construct(private readonly CompetencyEvaluationService $evaluations) {}

    public function update(CompetencyRatingRequest $request, GraduationCandidate $graduationCandidate, CompetencyIndicator $indicator): RedirectResponse
    {
        $assignment = $graduationCandidate->competencyEvaluators()
            ->where('evaluator_id', $request->user()->id)
            ->firstOrFail();

        $this->evaluations->submitRating(
            $assignment,
            $indicator,
            (int) $request->validated('rating'),
            $request->validated('remarks')
        );

        return back()->with('success', 'Rating saved.');
    }
}
