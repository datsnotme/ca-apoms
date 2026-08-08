<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Models\GraduationCandidate;
use App\Services\GraduationRecommendationService;
use Illuminate\Http\RedirectResponse;

class GraduationConferralController extends Controller
{
    public function __construct(private readonly GraduationRecommendationService $recommendations) {}

    public function store(GraduationCandidate $graduationCandidate): RedirectResponse
    {
        $this->authorize('update', $graduationCandidate);

        $this->recommendations->markGraduated($graduationCandidate);

        return back()->with('success', 'Candidate marked as graduated.');
    }
}
