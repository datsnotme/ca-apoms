<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduation\CompetencyEvaluatorRequest;
use App\Models\CompetencyEvaluator;
use App\Models\GraduationCandidate;
use App\Models\User;
use App\Services\CompetencyEvaluationService;
use Illuminate\Http\RedirectResponse;

class CompetencyEvaluatorController extends Controller
{
    public function __construct(private readonly CompetencyEvaluationService $evaluations) {}

    public function store(CompetencyEvaluatorRequest $request, GraduationCandidate $graduationCandidate): RedirectResponse
    {
        $evaluator = User::findOrFail($request->validated('evaluator_id'));

        $this->evaluations->assignEvaluator($graduationCandidate, $evaluator, $request->user());

        return back()->with('success', 'Evaluator assigned.');
    }

    public function destroy(GraduationCandidate $graduationCandidate, CompetencyEvaluator $evaluator): RedirectResponse
    {
        $this->authorize('update', $graduationCandidate);
        abort_unless($evaluator->graduation_candidate_id === $graduationCandidate->id, 404);

        $evaluator->delete();

        return back()->with('success', 'Evaluator removed.');
    }
}
