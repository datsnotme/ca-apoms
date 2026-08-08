<?php

namespace App\Services;

use App\Enums\EnrollmentCourseStatus;
use App\Models\ClassSection;
use App\Models\EnrollmentCourse;
use App\Models\StudentEnrollment;
use Illuminate\Validation\ValidationException;

/**
 * Owns the two business rules the spec calls out for enrollment that can't be
 * expressed as plain DB constraints: at most one active enrollment_courses
 * row per course per semester (unless explicitly Repeated), and a class
 * section can't be filled past its max_students. See ASSUMPTIONS.md.
 */
class EnrollmentService
{
    public function addCourse(StudentEnrollment $enrollment, ClassSection $classSection, bool $allowRepeat = false): EnrollmentCourse
    {
        $hasActiveDuplicate = $enrollment->enrollmentCourses()
            ->whereHas('classSection', fn ($q) => $q->where('course_id', $classSection->course_id))
            ->whereIn('status', ['Enrolled', 'Added', 'Repeated'])
            ->exists();

        if ($hasActiveDuplicate && ! $allowRepeat) {
            throw ValidationException::withMessages([
                'class_section_id' => 'This student already has an active enrollment in that course this semester. Mark it as a repeat to allow it.',
            ]);
        }

        if ($classSection->enrolledCount() >= $classSection->max_students) {
            throw ValidationException::withMessages([
                'class_section_id' => 'This class section is already at capacity.',
            ]);
        }

        return $enrollment->enrollmentCourses()->create([
            'class_section_id' => $classSection->id,
            'status' => $hasActiveDuplicate ? EnrollmentCourseStatus::Repeated->value : EnrollmentCourseStatus::Enrolled->value,
        ]);
    }
}
