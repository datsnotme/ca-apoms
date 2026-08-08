<?php

namespace App\Services;

use App\Enums\GradeSubmissionStatus;
use App\Enums\StudentGradeStatus;
use App\Models\ClassSection;
use App\Models\EnrollmentCourse;
use App\Models\GradeSubmission;
use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns the draft -> submit -> review -> finalize state machine described in
 * ASSUMPTIONS.md: the batch state lives on grade_submissions (one row per
 * class section), while individual student_grades rows carry the same
 * status for filtering/display but are the actual source of truth for the
 * grade value. A post-finalization single-row fix is a "correction", not a
 * reopen of the whole batch.
 */
class GradeService
{
    public function submissionFor(ClassSection $classSection): GradeSubmission
    {
        return GradeSubmission::firstOrCreate(
            ['class_section_id' => $classSection->id],
            ['status' => GradeSubmissionStatus::Draft->value]
        );
    }

    public function encode(EnrollmentCourse $enrollmentCourse, ?string $grade, User $actor): StudentGrade
    {
        $submission = $this->submissionFor($enrollmentCourse->classSection);

        if (! in_array($submission->status, [GradeSubmissionStatus::Draft, GradeSubmissionStatus::Returned], true)) {
            throw ValidationException::withMessages([
                'grade' => 'Grades can only be edited while the class submission is in draft or returned for correction.',
            ]);
        }

        $studentGrade = StudentGrade::firstOrNew(['enrollment_course_id' => $enrollmentCourse->id]);
        $studentGrade->grade = $grade;
        $studentGrade->status = StudentGradeStatus::Draft->value;
        $studentGrade->encoded_by = $actor->id;
        $studentGrade->save();

        return $studentGrade;
    }

    public function submitForReview(ClassSection $classSection, User $actor): GradeSubmission
    {
        $submission = $this->submissionFor($classSection);

        $roster = $classSection->enrollmentCourses()->whereIn('status', ['Enrolled', 'Added', 'Repeated'])->with('studentGrade')->get();

        if ($roster->isEmpty()) {
            throw ValidationException::withMessages(['submit' => 'This class section has no enrolled students to grade.']);
        }

        if ($roster->contains(fn (EnrollmentCourse $ec) => blank($ec->studentGrade?->grade))) {
            throw ValidationException::withMessages(['submit' => 'Every enrolled student must have a grade before submitting.']);
        }

        return DB::transaction(function () use ($submission, $classSection, $actor) {
            $submission->update([
                'status' => GradeSubmissionStatus::Submitted->value,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ]);

            StudentGrade::whereIn('enrollment_course_id', $classSection->enrollmentCourses()->pluck('id'))
                ->update(['status' => StudentGradeStatus::Submitted->value]);

            return $submission->fresh();
        });
    }

    public function returnForCorrection(GradeSubmission $submission, string $remarks, User $actor): GradeSubmission
    {
        return DB::transaction(function () use ($submission, $remarks, $actor) {
            $submission->update([
                'status' => GradeSubmissionStatus::Returned->value,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_remarks' => $remarks,
            ]);

            StudentGrade::whereIn('enrollment_course_id', $submission->classSection->enrollmentCourses()->pluck('id'))
                ->update(['status' => StudentGradeStatus::Draft->value]);

            return $submission->fresh();
        });
    }

    public function approve(GradeSubmission $submission, User $actor): GradeSubmission
    {
        return DB::transaction(function () use ($submission, $actor) {
            $submission->update([
                'status' => GradeSubmissionStatus::Reviewed->value,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_remarks' => null,
            ]);

            StudentGrade::whereIn('enrollment_course_id', $submission->classSection->enrollmentCourses()->pluck('id'))
                ->update(['status' => StudentGradeStatus::Reviewed->value]);

            return $submission->fresh();
        });
    }

    public function finalize(GradeSubmission $submission, User $actor): GradeSubmission
    {
        if ($submission->status !== GradeSubmissionStatus::Reviewed) {
            throw ValidationException::withMessages(['finalize' => 'Only a reviewed submission can be finalized.']);
        }

        return DB::transaction(function () use ($submission, $actor) {
            $submission->update([
                'status' => GradeSubmissionStatus::Finalized->value,
                'finalized_by' => $actor->id,
                'finalized_at' => now(),
            ]);

            StudentGrade::whereIn('enrollment_course_id', $submission->classSection->enrollmentCourses()->pluck('id'))
                ->update(['status' => StudentGradeStatus::Finalized->value]);

            return $submission->fresh();
        });
    }

    public function correct(StudentGrade $studentGrade, string $newGrade, string $reason, User $actor): StudentGrade
    {
        if ($studentGrade->status !== StudentGradeStatus::Finalized) {
            throw ValidationException::withMessages(['grade' => 'Only a finalized grade can be corrected this way; edit it directly while the submission is in draft.']);
        }

        $studentGrade->grade = $newGrade;
        $studentGrade->encoded_by = $actor->id;
        $studentGrade->changeReason = $reason;
        $studentGrade->changeApprovedBy = $actor->id;
        $studentGrade->save();

        return $studentGrade;
    }
}
