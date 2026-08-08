<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduation\GraduationDecisionRequest;
use App\Models\GraduationCandidate;
use App\Services\GraduationRecommendationService;
use Illuminate\Http\RedirectResponse;

class GraduationDecisionController extends Controller
{
    public function __construct(private readonly GraduationRecommendationService $recommendations) {}

    public function store(GraduationDecisionRequest $request, GraduationCandidate $graduationCandidate): RedirectResponse
    {
        $remarks = $request->validated('remarks');

        if ($request->validated('decision') === 'approve') {
            $this->recommendations->approve($graduationCandidate, $request->user(), $remarks);

            return back()->with('success', 'Candidate approved for graduation.');
        }

        $this->recommendations->reject($graduationCandidate, $request->user(), $remarks);

        return back()->with('success', 'Candidate recommendation rejected.');
    }
}
