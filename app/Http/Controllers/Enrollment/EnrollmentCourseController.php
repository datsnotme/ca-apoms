<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\EnrollmentCourseRequest;
use App\Http\Requests\Enrollment\EnrollmentCourseStatusRequest;
use App\Models\ClassSection;
use App\Models\EnrollmentCourse;
use App\Models\StudentEnrollment;
use App\Services\EnrollmentService;
use Illuminate\Http\RedirectResponse;

class EnrollmentCourseController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    public function store(EnrollmentCourseRequest $request, StudentEnrollment $studentEnrollment): RedirectResponse
    {
        $classSection = ClassSection::findOrFail($request->validated('class_section_id'));

        $this->enrollments->addCourse($studentEnrollment, $classSection, (bool) $request->validated('allow_repeat', false));

        return redirect()->route('enrollments.edit', $studentEnrollment)->with('success', 'Course added to enrollment.');
    }

    public function update(EnrollmentCourseStatusRequest $request, StudentEnrollment $studentEnrollment, EnrollmentCourse $enrollmentCourse): RedirectResponse
    {
        abort_unless($enrollmentCourse->student_enrollment_id === $studentEnrollment->id, 404);

        $enrollmentCourse->update($request->validated());

        return redirect()->route('enrollments.edit', $studentEnrollment)->with('success', 'Course status updated.');
    }

    public function destroy(StudentEnrollment $studentEnrollment, EnrollmentCourse $enrollmentCourse): RedirectResponse
    {
        $this->authorize('update', $studentEnrollment);

        abort_unless($enrollmentCourse->student_enrollment_id === $studentEnrollment->id, 404);

        $enrollmentCourse->delete();

        return redirect()->route('enrollments.edit', $studentEnrollment)->with('success', 'Course removed from enrollment.');
    }
}
