<?php

namespace App\Services;

use App\Enums\CourseBucket;
use App\Models\Student;

/**
 * Formats ProgressComputationService's checklist into the paper Individual
 * Student Evaluation form's shape: courses grouped into the five form
 * buckets, plus a non-persisted suggested regular/irregular signal the
 * evaluator can act on manually (this service never writes to the
 * database — the student's actual classification is still set by hand via
 * the Student edit form, same as today). See ASSUMPTIONS.md.
 */
class StudentEvaluationService
{
    public function __construct(private readonly ProgressComputationService $progress) {}

    /**
     * @return array<string, mixed>
     */
    public function evaluate(Student $student): array
    {
        $checklist = $this->progress->checklist($student);

        $buckets = collect(CourseBucket::cases())
            ->map(function (CourseBucket $bucket) use ($checklist) {
                $rows = $checklist->filter(fn ($row) => $row['course']['bucket'] === $bucket->value)->values();

                return [
                    'bucket' => $bucket->value,
                    'label' => $bucket->label(),
                    'rows' => $rows,
                    'total_units' => (float) $rows->sum('units'),
                    'earned_units' => (float) $rows->where('status', 'completed')->sum('units'),
                ];
            })
            ->filter(fn (array $group) => $group['rows']->isNotEmpty())
            ->values();

        $flaggedRows = $checklist->filter(
            fn ($row) => $row['is_deficiency'] || in_array($row['status'], ['failed', 'incomplete', 'dropped'], true)
        )->values();

        return [
            'buckets' => $buckets,
            'gwa' => $this->progress->gwa($student),
            'completion_percentage' => $this->progress->completionPercentage($student),
            'flagged_courses' => $flaggedRows,
            'suggested_classification' => $flaggedRows->isEmpty() ? 'regular' : 'irregular',
        ];
    }
}
