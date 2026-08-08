<?php

namespace App\Services\Import;

use App\Enums\SemesterTerm;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\EnrollmentCourse;
use App\Models\GradingScale;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Services\GradeService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GradesImporter implements RowImporter
{
    public function __construct(private readonly GradeService $grades) {}

    public function headings(): array
    {
        return ['student_number', 'course_code', 'section_label', 'school_year_start', 'school_year_end', 'semester', 'grade'];
    }

    public function sampleRow(): array
    {
        return ['2026-00001', 'AGRO101', 'A', 2026, 2027, 'FIRST', '1.00'];
    }

    public function validateRow(array $row): array
    {
        // Spreadsheet software stores a numeric-looking cell (e.g. "1.00")
        // as an actual number, so PhpSpreadsheet hands it back as an int/
        // float (1), losing the trailing zero the grading scale expects.
        // Reconstruct the two-decimal string form before validating.
        if (isset($row['grade']) && is_numeric($row['grade'])) {
            $row['grade'] = number_format((float) $row['grade'], 2, '.', '');
        }

        $validated = Validator::make($row, [
            'student_number' => ['required', 'string', 'exists:students,student_number'],
            'course_code' => ['required', 'string', 'exists:courses,code'],
            'section_label' => ['required', 'string'],
            'school_year_start' => ['required', 'integer'],
            'school_year_end' => ['required', 'integer'],
            'semester' => ['required', Rule::enum(SemesterTerm::class)],
            'grade' => ['required', Rule::in(GradingScale::default()->values->pluck('value'))],
        ])->validate();

        $student = Student::where('student_number', $validated['student_number'])->firstOrFail();

        $academicYear = AcademicYear::where('start_year', $validated['school_year_start'])
            ->where('end_year', $validated['school_year_end'])
            ->first();

        if (! $academicYear) {
            throw ValidationException::withMessages(['school_year_start' => 'No academic year matches the given start/end years.']);
        }

        $semester = Semester::where('academic_year_id', $academicYear->id)->where('term', $validated['semester'])->first();

        if (! $semester) {
            throw ValidationException::withMessages(['semester' => 'No semester found for that academic year and term.']);
        }

        $course = Course::where('code', $validated['course_code'])->firstOrFail();

        $classSection = ClassSection::where('course_id', $course->id)
            ->where('semester_id', $semester->id)
            ->where('section_label', $validated['section_label'])
            ->first();

        if (! $classSection) {
            throw ValidationException::withMessages(['section_label' => 'No class section matches that course, semester, and section label.']);
        }

        $enrollmentCourse = $classSection->enrollmentCourses()
            ->whereHas('studentEnrollment', fn ($q) => $q->where('student_id', $student->id))
            ->whereIn('status', ['Enrolled', 'Added', 'Repeated'])
            ->first();

        if (! $enrollmentCourse) {
            throw ValidationException::withMessages(['student_number' => 'That student is not actively enrolled in this class section.']);
        }

        return [
            'enrollment_course_id' => $enrollmentCourse->id,
            'grade' => $validated['grade'],
        ];
    }

    public function persistRow(array $data, User $actor): void
    {
        $enrollmentCourse = EnrollmentCourse::findOrFail($data['enrollment_course_id']);

        $this->grades->encode($enrollmentCourse, $data['grade'], $actor);
    }
}
