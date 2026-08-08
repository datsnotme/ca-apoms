<?php

namespace App\Services;

use App\Enums\GraduationCandidateStatus;
use App\Models\CompetencyEvaluator;
use App\Models\CompetencyIndicator;
use App\Models\CompetencyRating;
use App\Models\GraduationCandidate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Assigns evaluators to a graduation candidate and records their competency
 * ratings. A candidate moves from "nominated" to "under_evaluation" the
 * moment its first evaluator is assigned — the same "meaningful action
 * triggers the status" shape used for adviser reassignment in Phase 3B.
 */
class CompetencyEvaluationService
{
    public function assignEvaluator(GraduationCandidate $candidate, User $evaluator, User $actor): CompetencyEvaluator
    {
        if ($candidate->competencyEvaluators()->where('evaluator_id', $evaluator->id)->exists()) {
            throw ValidationException::withMessages(['evaluator_id' => 'This evaluator is already assigned to this candidate.']);
        }

        $assignment = $candidate->competencyEvaluators()->create([
            'evaluator_id' => $evaluator->id,
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
        ]);

        if ($candidate->status === GraduationCandidateStatus::Nominated) {
            $candidate->update(['status' => GraduationCandidateStatus::UnderEvaluation->value]);
        }

        return $assignment;
    }

    public function submitRating(CompetencyEvaluator $assignment, CompetencyIndicator $indicator, int $rating, ?string $remarks): CompetencyRating
    {
        return CompetencyRating::updateOrCreate(
            ['competency_evaluator_id' => $assignment->id, 'competency_indicator_id' => $indicator->id],
            ['rating' => $rating, 'remarks' => $remarks, 'rated_at' => now()]
        );
    }
}
