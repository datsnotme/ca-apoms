<?php

namespace App\Services;

use App\Enums\LatinHonorsTier;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Identifies students who currently qualify as Latin Honors prospects.
 * Deliberately scans every graduation-eligible active student directly
 * (not just students already nominated as a GraduationCandidate) — distinct
 * from that pipeline the same way StudentEvaluationService is distinct from
 * it, per ASSUMPTIONS.md.
 *
 * "No issues" reuses GraduationCandidateService::identifyEligibleStudents()'s
 * own bar exactly (100% curriculum completion, zero unresolved academic
 * deficiencies) rather than a stricter "never failed anything, ever" check —
 * the latter would require inspecting full grade-attempt history, which
 * nothing in the app tracks today (only the current/winning grade per
 * course is kept). A student who once failed a course but later passed it
 * on retake has no trace of that failure anywhere in their live record, so
 * this is not a corner that's being cut so much as a signal that doesn't
 * exist yet.
 *
 * Nothing here is persisted — like suggested_classification elsewhere in
 * this app, this is a computed hint an evaluator/registrar acts on
 * manually, never an automatic award.
 */
class LatinHonorsService
{
    public const MIN_QUALIFYING_GWA = 1.00;

    public const MAX_QUALIFYING_GWA = 1.75;

    public function __construct(private readonly ProgressComputationService $progress) {}

    /**
     * @return Collection<int, array{student: Student, gwa: float, completion_percentage: float, tier: LatinHonorsTier}>
     */
    public function identifyProspects(User $user): Collection
    {
        $students = Student::query()
            ->visibleTo($user)
            ->where('status', 'active')
            ->whereNotNull('curriculum_id')
            ->withCount(['deficiencies as unresolved_deficiency_count' => fn ($q) => $q->whereNull('resolved_at')])
            ->with(['department:id,name', 'program:id,name', 'yearLevel:id,level,label'])
            ->get();

        $this->progress->preloadForStudents($students);

        return $students
            ->map(fn (Student $student) => [
                'student' => $student,
                'gwa' => $this->progress->gwa($student),
                'completion_percentage' => $this->progress->completionPercentage($student),
                'unresolved_deficiency_count' => (int) $student->unresolved_deficiency_count,
            ])
            ->filter(fn (array $row) => $row['completion_percentage'] >= 100.0
                && $row['unresolved_deficiency_count'] === 0
                && $row['gwa'] !== null
                && $row['gwa'] >= self::MIN_QUALIFYING_GWA
                && $row['gwa'] <= self::MAX_QUALIFYING_GWA)
            ->map(fn (array $row) => [...$row, 'tier' => LatinHonorsTier::forGwa($row['gwa'])])
            ->sortBy('gwa')
            ->values();
    }
}
