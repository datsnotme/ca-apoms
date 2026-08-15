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
 * cached across requests — see ASSUMPTIONS.md) and syncs the one piece
 * that *is* persisted, academic_deficiencies, since a deficiency needs a
 * resolution workflow to attach to.
 *
 * Within a single request, results ARE memoized per student/curriculum/
 * grading-scale on this instance — none of that underlying data is
 * written by any code path that also reads it back in the same request,
 * so this changes query count, not behavior. `preloadForStudents()` lets
 * a caller that's about to loop over many students (e.g.
 * GraduationCandidateService, ProgressAlertService::syncAlertsForScope())
 * batch the per-student queries into one each, instead of N.
 */
class ProgressComputationService
{
    private ?Collection $scaleValuesCache = null;

    /** @var array<int, Collection<int, CurriculumCourse>> keyed by curriculum_id */
    private array $curriculumCoursesCache = [];

    /** @var Collection<int, Collection<int, Collection<int, array<string, mixed>>>>|null keyed by student_id, set only after preloadForStudents() */
    private ?Collection $attemptsCache = null;

    /**
     * Which student IDs preloadForStudents() has actually fetched attempts
     * for — distinct from $attemptsCache's own keys, since a student with
     * zero enrollment courses is legitimately absent from $attemptsCache
     * after a real preload. Checking this instead of "is $attemptsCache
     * non-null" stops a student outside the preloaded batch from silently
     * reading back an empty collection instead of falling through to its
     * own query, if this service instance is ever reused across an
     * unrelated batch and a single-student call in the same request.
     *
     * Note: checklist()'s own *result* is deliberately NOT memoized per
     * student, unlike the caches above. A student's enrollment/grade data
     * can legitimately change between two checklist()-derived calls on the
     * same service instance (e.g. syncDeficiencies() called again after a
     * retake is recorded — see ProgressComputationTest), so only the
     * inputs that never change mid-request (grading scale, curriculum
     * definition, and one preloaded batch's own attempts) are cached here.
     *
     * @var array<int, true> keyed by student_id
     */
    private array $preloadedStudentIds = [];

    /**
     * Batch-fetches everything checklist() would otherwise fetch once per
     * student — the default grading scale (identical for every student),
     * each distinct curriculum's course list (shared by every student on
     * that curriculum), and every given student's enrollment/grade
     * history (the one part that's genuinely per-student, now one query
     * for the whole collection instead of one per student). Safe to call
     * with any Student collection; students without a curriculum_id are
     * simply skipped, matching checklist()'s own early-return for them.
     *
     * A genuinely empty collection is a true no-op, same as the loop this
     * replaces never having anything to iterate — deliberately does NOT
     * touch GradingScale::default() in that case, since that scope uses
     * firstOrFail() and would turn "no students in scope" into a hard
     * error on any install/test that hasn't seeded a default grading
     * scale yet (a real regression this caused before this guard existed:
     * DashboardController calls syncAlertsForScope() on every visit,
     * including for a brand new account with zero students).
     *
     * @param  Collection<int, Student>  $students
     */
    public function preloadForStudents(Collection $students): void
    {
        if ($students->isEmpty()) {
            return;
        }

        $this->scaleValues();

        $curriculumIds = $students->pluck('curriculum_id')->filter()->unique()->values();

        if ($curriculumIds->isNotEmpty()) {
            $this->curriculumCoursesCache += $this->fetchCurriculumCourses($curriculumIds)->all();
        }

        $studentIds = $students->pluck('id')->values();

        if ($studentIds->isNotEmpty()) {
            $attemptsByStudent = EnrollmentCourse::query()
                ->whereHas('studentEnrollment', fn ($q) => $q->whereIn('student_id', $studentIds))
                ->with(['classSection:id,course_id', 'studentGrade', 'studentEnrollment:id,student_id'])
                ->get()
                ->groupBy(fn (EnrollmentCourse $ec) => $ec->studentEnrollment->student_id)
                ->map(fn (Collection $group) => $this->groupAttemptsByCourse($group));

            // union(), not merge() — merge() is backed by array_merge(), which
            // re-indexes integer keys (student IDs) instead of preserving
            // them, silently corrupting this cache's keys.
            $this->attemptsCache = ($this->attemptsCache ?? collect())->union($attemptsByStudent);

            foreach ($studentIds as $id) {
                $this->preloadedStudentIds[$id] = true;
            }
        }
    }

    public function checklist(Student $student): Collection
    {
        if (! $student->curriculum_id) {
            return collect();
        }

        $curriculumCourses = $this->curriculumCoursesCache[$student->curriculum_id]
            ??= $this->fetchCurriculumCourses(collect([$student->curriculum_id]))->get($student->curriculum_id, collect());

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
                'course' => [
                    'id' => $cc->course->id,
                    'code' => $cc->course->code,
                    'title' => $cc->course->title,
                    'bucket' => $cc->course->bucket?->value,
                    'lecture_hours' => (float) $cc->course->lecture_hours,
                    'laboratory_hours' => (float) $cc->course->laboratory_hours,
                ],
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
        if (isset($this->preloadedStudentIds[$student->id])) {
            return $this->attemptsCache->get($student->id, collect());
        }

        $attempts = EnrollmentCourse::query()
            ->whereHas('studentEnrollment', fn ($q) => $q->where('student_id', $student->id))
            ->with(['classSection:id,course_id', 'studentGrade'])
            ->get();

        return $this->groupAttemptsByCourse($attempts);
    }

    /**
     * @param  Collection<int, EnrollmentCourse>  $attempts
     * @return Collection<int, Collection<int, array<string, mixed>>> keyed by course_id
     */
    private function groupAttemptsByCourse(Collection $attempts): Collection
    {
        $scaleValues = $this->scaleValues();

        return $attempts
            ->groupBy(fn (EnrollmentCourse $ec) => $ec->classSection->course_id)
            ->map(fn (Collection $group) => $group->map(fn (EnrollmentCourse $ec) => [
                'enrollment_course_id' => $ec->id,
                'enrollment_status' => $ec->status->value,
                'grade_value' => $ec->studentGrade?->grade,
                'is_official' => $ec->studentGrade?->status?->value === 'finalized',
                'scale_value' => $ec->studentGrade?->grade ? $scaleValues->get($ec->studentGrade->grade) : null,
            ]));
    }

    private function scaleValues(): Collection
    {
        return $this->scaleValuesCache ??= GradingScale::default()->values->keyBy('value');
    }

    /**
     * @param  Collection<int, int>  $curriculumIds
     * @return Collection<int, Collection<int, CurriculumCourse>> keyed by curriculum_id
     */
    private function fetchCurriculumCourses(Collection $curriculumIds): Collection
    {
        return CurriculumCourse::whereIn('curriculum_id', $curriculumIds)
            ->with('course:id,code,title,bucket,lecture_hours,laboratory_hours')
            ->orderBy('year_level')
            ->orderBy('sequence_order')
            ->orderBy('id')
            ->get()
            ->groupBy('curriculum_id');
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
