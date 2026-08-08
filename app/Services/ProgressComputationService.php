<?php

namespace App\Services;

use App\Enums\CourseChecklistStatus;
use App\Enums\DeficiencyType;
use App\Models\AcademicDeficiency;
use App\Models\CurriculumCourse;
use App\Models\EnrollmentCourse;
use App\Models\GradingScale;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes a student's curriculum-checklist status on demand (never
 * cached — see ASSUMPTIONS.md) and syncs the one piece that *is*
 * persisted, academic_deficiencies, since a deficiency needs a resolution
 * workflow to attach to.
 */
class ProgressComputationService
{
    public function checklist(Student $student): Collection
    {
        if (! $student->curriculum_id) {
            return collect();
        }

        $curriculumCourses = CurriculumCourse::where('curriculum_id', $student->curriculum_id)
            ->with('course:id,code,title')
            ->orderBy('year_level')
            ->orderBy('sequence_order')
            ->orderBy('id')
            ->get();

        $attemptsByCourse = $this->attemptsByCourse($student);
        $currentYearLevel = $student->yearLevel?->level ?? 0;

        return $curriculumCourses->map(function (CurriculumCourse $cc) use ($attemptsByCourse, $currentYearLevel) {
            $attempts = $attemptsByCourse->get($cc->course_id, collect());
            $rawStatus = $this->resolveStatus($attempts);
            $isOverdue = $cc->year_level < $currentYearLevel;

            $status = ($rawStatus === CourseChecklistStatus::NotTaken && ! $isOverdue)
                ? CourseChecklistStatus::Pending
                : $rawStatus;

            $winningAttempt = $attempts->first(fn ($a) => $a['is_official'] && $a['scale_value']?->is_passing)
                ?? $attempts->sortByDesc('enrollment_course_id')->first();

            $isDeficiency = $cc->is_required
                && $isOverdue
                && ! in_array($status, [CourseChecklistStatus::Completed, CourseChecklistStatus::InProgress], true);

            return [
                'curriculum_course_id' => $cc->id,
                'course' => ['id' => $cc->course->id, 'code' => $cc->course->code, 'title' => $cc->course->title],
                'year_level' => $cc->year_level,
                'semester' => $cc->semester->value,
                'is_required' => $cc->is_required,
                'units' => (float) $cc->units,
                'status' => $status->value,
                'is_deficiency' => $isDeficiency,
                'grade' => $winningAttempt['grade_value'] ?? null,
                'numeric_equivalent' => $winningAttempt && $winningAttempt['scale_value']?->numeric_equivalent !== null
                    ? (float) $winningAttempt['scale_value']->numeric_equivalent
                    : null,
            ];
        });
    }

    /**
     * Philippine-convention General Weighted Average — lower is better,
     * matching the seeded 1.00-5.00 grading scale. Null if the student has
     * no finalized numeric-valued attempts yet.
     */
    public function gwa(Student $student): ?float
    {
        $rows = $this->checklist($student)->filter(
            fn ($row) => in_array($row['status'], ['completed', 'failed'], true) && $row['numeric_equivalent'] !== null
        );

        $totalUnits = $rows->sum('units');

        if ($totalUnits <= 0) {
            return null;
        }

        $weighted = $rows->sum(fn ($row) => $row['numeric_equivalent'] * $row['units']);

        return round($weighted / $totalUnits, 2);
    }

    public function completionPercentage(Student $student): float
    {
        $required = $this->checklist($student)->where('is_required', true);
        $totalUnits = $required->sum('units');

        if ($totalUnits <= 0) {
            return 0.0;
        }

        $completedUnits = $required->where('status', 'completed')->sum('units');

        return round(($completedUnits / $totalUnits) * 100, 1);
    }

    /**
     * Upserts one academic_deficiencies row per flagged curriculum course
     * and auto-resolves any previously-flagged row that no longer meets the
     * criteria (e.g. a later retake passed).
     */
    public function syncDeficiencies(Student $student): void
    {
        $checklist = $this->checklist($student);

        DB::transaction(function () use ($student, $checklist) {
            foreach ($checklist as $row) {
                if (! $row['is_deficiency']) {
                    AcademicDeficiency::where('student_id', $student->id)
                        ->where('curriculum_course_id', $row['curriculum_course_id'])
                        ->whereNull('resolved_at')
                        ->update([
                            'resolved_at' => now(),
                            'resolution_notes' => 'Auto-resolved: no longer meets deficiency criteria.',
                        ]);

                    continue;
                }

                AcademicDeficiency::updateOrCreate(
                    ['student_id' => $student->id, 'curriculum_course_id' => $row['curriculum_course_id']],
                    [
                        'deficiency_type' => $this->mapDeficiencyType($row['status']),
                        'detected_at' => now(),
                        'resolved_at' => null,
                        'resolved_via' => null,
                        'resolution_notes' => null,
                    ]
                );
            }
        });
    }

    /**
     * @return Collection<int, Collection<int, array<string, mixed>>> keyed by course_id
     */
    private function attemptsByCourse(Student $student): Collection
    {
        $scaleValues = GradingScale::default()->values->keyBy('value');

        return EnrollmentCourse::query()
            ->whereHas('studentEnrollment', fn ($q) => $q->where('student_id', $student->id))
            ->with(['classSection:id,course_id', 'studentGrade'])
            ->get()
            ->groupBy(fn (EnrollmentCourse $ec) => $ec->classSection->course_id)
            ->map(fn (Collection $group) => $group->map(fn (EnrollmentCourse $ec) => [
                'enrollment_course_id' => $ec->id,
                'enrollment_status' => $ec->status->value,
                'grade_value' => $ec->studentGrade?->grade,
                'is_official' => $ec->studentGrade?->status?->value === 'finalized',
                'scale_value' => $ec->studentGrade?->grade ? $scaleValues->get($ec->studentGrade->grade) : null,
            ]));
    }

    private function resolveStatus(Collection $attempts): CourseChecklistStatus
    {
        if ($attempts->isEmpty()) {
            return CourseChecklistStatus::NotTaken;
        }

        if ($attempts->contains(fn ($a) => $a['is_official'] && $a['scale_value']?->is_passing)) {
            return CourseChecklistStatus::Completed;
        }

        $latest = $attempts->sortByDesc('enrollment_course_id')->first();

        if (in_array($latest['enrollment_status'], ['Dropped', 'Withdrawn'], true)) {
            return CourseChecklistStatus::Dropped;
        }

        if (! $latest['is_official']) {
            return CourseChecklistStatus::InProgress;
        }

        if ($latest['grade_value'] === 'INC') {
            return CourseChecklistStatus::Incomplete;
        }

        if ($latest['grade_value'] === 'DRP') {
            return CourseChecklistStatus::Dropped;
        }

        if ($latest['scale_value']?->is_failing) {
            return CourseChecklistStatus::Failed;
        }

        return CourseChecklistStatus::Pending;
    }

    private function mapDeficiencyType(string $status): string
    {
        return match ($status) {
            'failed' => DeficiencyType::Failed->value,
            'incomplete' => DeficiencyType::Incomplete->value,
            'dropped' => DeficiencyType::Dropped->value,
            default => DeficiencyType::NotTaken->value,
        };
    }
}
