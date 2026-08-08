<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduation\GraduationRecommendationRequest;
use App\Models\GraduationCandidate;
use App\Services\GraduationRecommendationService;
use Illuminate\Http\RedirectResponse;

class GraduationRecommendationController extends Controller
{
    public function __construct(private readonly GraduationRecommendationService $recommendations) {}

    public function store(GraduationRecommendationRequest $request, GraduationCandidate $graduationCandidate): RedirectResponse
    {
        $this->recommendations->recommend($graduationCandidate, $request->user(), $request->validated('remarks'));

        return back()->with('success', 'Candidate recommended for graduation.');
    }
}
