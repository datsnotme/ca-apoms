<?php

namespace App\Services;

use App\Enums\CourseBucket;
use App\Enums\SemesterTerm;
use App\Models\Student;

/**
 * Formats ProgressComputationService's checklist into a printable Individual
 * Student Evaluation: courses grouped by year level (1st through the
 * student's current year) then by semester within each year — mirroring the
 * college's official curriculum prospectus layout — plus a non-persisted
 * suggested regular/irregular signal the evaluator can act on manually
 * (this service never writes to the database — the student's actual
 * classification is still set by hand via the Student edit form, same as
 * today). See ASSUMPTIONS.md.
 */
class StudentEvaluationService
{
    private const SEMESTER_ORDER = [
        SemesterTerm::First->value => 1,
        SemesterTerm::Second->value => 2,
        SemesterTerm::Summer->value => 3,
    ];

    public function __construct(private readonly ProgressComputationService $progress) {}

    /**
     * @return array<string, mixed>
     */
    public function evaluate(Student $student): array
    {
        $checklist = $this->progress->checklist($student);
        $currentYearLevel = $student->yearLevel?->level;

        // Only 1st year through the student's current year — this evaluates
        // what has actually happened so far, not a preview of untaken future
        // terms (unlike the checklist itself, which spans the whole
        // curriculum so Progress/Show.tsx can show "pending" rows).
        $relevant = $currentYearLevel !== null
            ? $checklist->filter(fn ($row) => $row['year_level'] <= $currentYearLevel)
            : $checklist;

        $years = $relevant->groupBy('year_level')
            ->sortKeys()
            ->map(function ($yearRows, $yearLevel) {
                $semesters = $yearRows->groupBy('semester')
                    ->sortBy(fn ($rows, $semester) => self::SEMESTER_ORDER[$semester] ?? 99)
                    ->map(fn ($semRows, $semester) => [
                        'semester' => $semester,
                        'rows' => $semRows->values(),
                        'total_lecture_hours' => (float) $semRows->sum(fn ($row) => $row['course']['lecture_hours']),
                        'total_laboratory_hours' => (float) $semRows->sum(fn ($row) => $row['course']['laboratory_hours']),
                        'total_units' => (float) $semRows->sum('units'),
                    ])
                    ->values();

                return ['year_level' => (int) $yearLevel, 'semesters' => $semesters];
            })
            ->values();

        // A compact per-bucket recap (not a full course listing) for the
        // Summary box — Year/Semester is now the primary structure the
        // courses are printed under, so the five-bucket breakdown from the
        // official form is folded into one summary line per bucket instead
        // of its own set of boxes.
        $bucketSummary = collect(CourseBucket::cases())->map(function (CourseBucket $bucket) use ($checklist) {
            $rows = $checklist->filter(fn ($row) => $row['course']['bucket'] === $bucket->value);

            return [
                'label' => $bucket->label(),
                'total_units' => (float) $rows->sum('units'),
                'earned_units' => (float) $rows->where('status', 'completed')->sum('units'),
            ];
        });

        $flaggedRows = $checklist->filter(
            fn ($row) => $row['is_deficiency'] || in_array($row['status'], ['failed', 'incomplete', 'dropped'], true)
        )->values();

        // Required/earned/etc. reflect the WHOLE degree program (all four
        // years), not just the years rendered above — "Total Units
        // Required" means the full 182-unit curriculum, same as the paper
        // form, regardless of how far the student has progressed.
        $totalUnitsRequired = (float) $checklist->sum('units');
        $totalUnitsEarned = (float) $checklist->where('status', 'completed')->sum('units');
        $currentlyEnrolledUnits = (float) $checklist->where('status', 'in_progress')->sum('units');
        $incompleteUnits = (float) $checklist->where('status', 'incomplete')->sum('units');

        // Imported prior-program record (e.g. a shiftee's coursework under
        // a program the student is no longer in) — grouped by the
        // free-text academic year/semester label the evaluator entered at
        // import time, in upload order. Deliberately excluded from every
        // stat below (GWA, completion percentage, bucket/summary totals):
        // it's display-only context, not part of the current curriculum's
        // progress tracking. See ASSUMPTIONS.md and StudentHistoricalGrade.
        $priorAcademicRecord = $student->historicalGrades
            ->groupBy(fn ($row) => $row->academic_year_label.'|'.$row->semester_label)
            ->map(fn ($rows) => [
                'academic_year_label' => $rows->first()->academic_year_label,
                'semester_label' => $rows->first()->semester_label,
                'program_label' => $rows->first()->program_label,
                'rows' => $rows->values(),
                'total_units' => (float) $rows->sum('units'),
            ])
            ->values();

        return [
            'years' => $years,
            'bucket_summary' => $bucketSummary,
            'prior_academic_record' => $priorAcademicRecord,
            'gwa' => $this->progress->gwa($student),
            'completion_percentage' => $this->progress->completionPercentage($student),
            'flagged_courses' => $flaggedRows,
            'suggested_classification' => $flaggedRows->isEmpty() ? 'regular' : 'irregular',
            'summary' => [
                'total_units_required' => $totalUnitsRequired,
                'total_units_earned' => $totalUnitsEarned,
                'currently_enrolled_units' => $currentlyEnrolledUnits,
                // Not tracked as a distinct state today — a course with no
                // grade yet and a course with a submitted-but-unfinalized
                // grade both surface as "in_progress" (see
                // ProgressComputationService::resolveStatus()), so this
                // always prints as 0 rather than guessing. See
                // ASSUMPTIONS.md.
                'taken_no_grade_units' => 0.0,
                'incomplete_units' => $incompleteUnits,
                'remaining_units' => max(
                    $totalUnitsRequired - $totalUnitsEarned - $currentlyEnrolledUnits - $incompleteUnits,
                    0
                ),
            ],
        ];
    }
}
