<?php

namespace App\Services;

use App\Enums\GraduationCandidateStatus;
use App\Models\GraduationCandidate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Carries a candidate through department recommendation and Dean approval.
 * Both steps advance GraduationCandidate.status directly — no separate
 * recommendation/approval history table, since a candidate only ever
 * receives one recommendation and one decision (a rejected candidacy is
 * terminal; a fresh attempt is a brand-new GraduationCandidate row, per the
 * Phase 4A nomination rules).
 */
class GraduationRecommendationService
{
    public function recommend(GraduationCandidate $candidate, User $actor, ?string $remarks): void
    {
        if ($candidate->status !== GraduationCandidateStatus::UnderEvaluation) {
            throw ValidationException::withMessages(['status' => 'Only candidates under evaluation can be recommended.']);
        }

        if (! $candidate->readyForRecommendation()) {
            throw ValidationException::withMessages(['status' => 'The requirement checklist and competency evaluation must both be complete before recommending this candidate.']);
        }

        $candidate->update([
            'status' => GraduationCandidateStatus::Recommended->value,
            'recommended_by' => $actor->id,
            'recommended_at' => now(),
            'recommendation_remarks' => $remarks,
        ]);
    }

    public function approve(GraduationCandidate $candidate, User $actor, ?string $remarks): void
    {
        $this->decide($candidate, $actor, GraduationCandidateStatus::Approved, $remarks);
    }

    public function reject(GraduationCandidate $candidate, User $actor, ?string $remarks): void
    {
        $this->decide($candidate, $actor, GraduationCandidateStatus::Rejected, $remarks);
    }

    /**
     * Registrar's final confirmation that an approved candidate actually
     * graduated (e.g. after commencement). The terminal step of the
     * pipeline — nothing advances past `graduated`.
     */
    public function markGraduated(GraduationCandidate $candidate): void
    {
        if ($candidate->status !== GraduationCandidateStatus::Approved) {
            throw ValidationException::withMessages(['status' => 'Only approved candidates can be marked as graduated.']);
        }

        $candidate->update([
            'status' => GraduationCandidateStatus::Graduated->value,
            'graduated_at' => now(),
        ]);
    }

    private function decide(GraduationCandidate $candidate, User $actor, GraduationCandidateStatus $outcome, ?string $remarks): void
    {
        if ($candidate->status !== GraduationCandidateStatus::Recommended) {
            throw ValidationException::withMessages(['status' => 'Only recommended candidates can be approved or rejected.']);
        }

        $candidate->update([
            'status' => $outcome->value,
            'decided_by' => $actor->id,
            'decided_at' => now(),
            'decision_remarks' => $remarks,
        ]);
    }
}
