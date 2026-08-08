<?php

namespace App\Services\Import;

use App\Enums\SemesterTerm;
use App\Models\AcademicYear;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EnrollmentImporter implements RowImporter
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    public function headings(): array
    {
        return ['student_number', 'school_year_start', 'school_year_end', 'semester', 'course_code', 'section_label'];
    }

    public function sampleRow(): array
    {
        return ['2026-00001', 2026, 2027, 'FIRST', 'AGRO101', 'A'];
    }

    public function validateRow(array $row): array
    {
        $validated = Validator::make($row, [
            'student_number' => ['required', 'string', 'exists:students,student_number'],
            'school_year_start' => ['required', 'integer'],
            'school_year_end' => ['required', 'integer'],
            'semester' => ['required', Rule::enum(SemesterTerm::class)],
            'course_code' => ['required', 'string', 'exists:courses,code'],
            'section_label' => ['required', 'string'],
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

        return [
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'class_section_id' => $classSection->id,
        ];
    }

    public function persistRow(array $data, User $actor): void
    {
        $enrollment = StudentEnrollment::firstOrCreate(
            ['student_id' => $data['student_id'], 'semester_id' => $data['semester_id']],
            ['status' => 'enrolled', 'enrolled_by' => $actor->id]
        );

        $classSection = ClassSection::findOrFail($data['class_section_id']);

        $alreadyEnrolled = $enrollment->enrollmentCourses()->where('class_section_id', $classSection->id)->exists();

        if ($alreadyEnrolled) {
            return;
        }

        $this->enrollments->addCourse($enrollment, $classSection);
    }
}
