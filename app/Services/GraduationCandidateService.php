<?php

namespace App\Services;

use App\Enums\GraduationCandidateStatus;
use App\Models\AcademicYear;
use App\Models\GraduationCandidate;
use App\Models\GraduationRequirementTemplate;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Identifies students eligible for graduation nomination and handles the
 * nomination itself. Eligibility is derived live from
 * ProgressComputationService (100% curriculum completion, zero unresolved
 * deficiencies) — there is no separate "is eligible" flag stored anywhere.
 */
class GraduationCandidateService
{
    public function __construct(private readonly ProgressComputationService $progress) {}

    /**
     * @return Collection<int, Student>
     */
    public function identifyEligibleStudents(): Collection
    {
        $alreadyCandidateIds = GraduationCandidate::where('status', '!=', GraduationCandidateStatus::Rejected->value)
            ->pluck('student_id');

        return Student::query()
            ->where('status', 'active')
            ->whereNotIn('id', $alreadyCandidateIds)
            ->whereNotNull('curriculum_id')
            ->get()
            ->filter(function (Student $student) {
                $completion = $this->progress->completionPercentage($student);
                $deficiencyCount = $student->deficiencies()->whereNull('resolved_at')->count();

                return $completion >= 100.0 && $deficiencyCount === 0;
            })
            ->values();
    }

    public function nominate(Student $student, AcademicYear $academicYear, Semester $semester, User $actor): GraduationCandidate
    {
        $alreadyActive = GraduationCandidate::where('student_id', $student->id)
            ->where('status', '!=', GraduationCandidateStatus::Rejected->value)
            ->exists();

        if ($alreadyActive) {
            throw ValidationException::withMessages(['student_id' => 'This student already has an active graduation candidacy.']);
        }

        return DB::transaction(function () use ($student, $academicYear, $semester, $actor) {
            $candidate = GraduationCandidate::create([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
                'semester_id' => $semester->id,
                'status' => GraduationCandidateStatus::Nominated->value,
                'gwa_snapshot' => $this->progress->gwa($student),
                'completion_percentage_snapshot' => $this->progress->completionPercentage($student),
                'deficiency_count_snapshot' => $student->deficiencies()->whereNull('resolved_at')->count(),
                'nominated_by' => $actor->id,
                'nominated_at' => now(),
                'created_by' => $actor->id,
            ]);

            $templates = GraduationRequirementTemplate::query()
                ->where(fn ($q) => $q->whereNull('program_id')->orWhere('program_id', $student->program_id))
                ->get();

            foreach ($templates as $template) {
                $candidate->requirements()->create([
                    'requirement_template_id' => $template->id,
                    'status' => 'pending',
                ]);
            }

            return $candidate;
        });
    }
}
