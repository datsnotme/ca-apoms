<?php

namespace App\Http\Controllers\Grading;

use App\Http\Controllers\Controller;
use App\Http\Requests\Grading\GradeCorrectRequest;
use App\Http\Requests\Grading\GradeEncodeRequest;
use App\Http\Requests\Grading\GradeReturnRequest;
use App\Models\ClassSection;
use App\Models\EnrollmentCourse;
use App\Models\GradingScale;
use App\Models\Semester;
use App\Models\StudentGrade;
use App\Services\GradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function __construct(private readonly GradeService $grades) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('grades.view'), 403);

        $classSections = ClassSection::query()
            ->visibleTo($request->user())
            ->with(['course:id,code,title,department_id', 'semester:id,academic_year_id,term', 'semester.academicYear:id,start_year,end_year', 'gradeSubmission'])
            ->when($request->string('semester_id')->trim()->isNotEmpty(), fn ($q) => $q->where('semester_id', $request->integer('semester_id')))
            ->orderByDesc('semester_id')
            ->orderBy('section_label')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Grades/Index', [
            'classSections' => $classSections,
            'filters' => $request->only('semester_id'),
            'semesters' => Semester::query()
                ->with('academicYear:id,start_year,end_year')
                ->orderByDesc('id')
                ->get(['id', 'academic_year_id', 'term'])
                ->map(fn ($s) => ['id' => $s->id, 'label' => "{$s->academicYear->start_year}-{$s->academicYear->end_year} {$s->term->label()}"]),
        ]);
    }

    public function show(Request $request, ClassSection $classSection): Response
    {
        $this->authorize('viewGrades', $classSection);

        $submission = $this->grades->submissionFor($classSection);

        $classSection->load([
            'course:id,code,title,units',
            'semester:id,academic_year_id,term', 'semester.academicYear:id,start_year,end_year',
            'enrollmentCourses' => fn ($q) => $q->whereIn('status', ['Enrolled', 'Added', 'Repeated'])
                ->with(['studentEnrollment.student:id,student_number,surname,first_name,middle_name', 'studentGrade']),
        ]);

        return Inertia::render('Grades/Show', [
            'classSection' => [
                ...$classSection->only(['id', 'section_label', 'max_students']),
                'course' => $classSection->course,
                'semester' => $classSection->semester,
            ],
            'submission' => $submission,
            'roster' => $classSection->enrollmentCourses->map(fn (EnrollmentCourse $ec) => [
                'enrollment_course_id' => $ec->id,
                'student' => $ec->studentEnrollment->student,
                'grade' => $ec->studentGrade?->grade,
                'status' => $ec->studentGrade?->status?->value,
                'student_grade_id' => $ec->studentGrade?->id,
            ]),
            'gradingScaleValues' => GradingScale::default()->values->map(fn ($v) => ['value' => $v->value, 'label' => $v->label]),
            'can' => [
                'encode' => $request->user()->can('encodeGrades', $classSection),
                'review' => $request->user()->can('reviewGrades', $classSection),
            ],
        ]);
    }

    public function update(GradeEncodeRequest $request, ClassSection $classSection, EnrollmentCourse $enrollmentCourse): RedirectResponse
    {
        abort_unless($enrollmentCourse->class_section_id === $classSection->id, 404);

        $this->grades->encode($enrollmentCourse, $request->validated('grade'), $request->user());

        return back()->with('success', 'Grade saved.');
    }

    public function submit(Request $request, ClassSection $classSection): RedirectResponse
    {
        $this->authorize('encodeGrades', $classSection);

        $this->grades->submitForReview($classSection, $request->user());

        return redirect()->route('class-sections.grades.show', $classSection)->with('success', 'Grades submitted for review.');
    }

    public function approve(Request $request, ClassSection $classSection): RedirectResponse
    {
        $this->authorize('reviewGrades', $classSection);

        $this->grades->approve($this->grades->submissionFor($classSection), $request->user());

        return redirect()->route('class-sections.grades.show', $classSection)->with('success', 'Grades approved.');
    }

    public function return(GradeReturnRequest $request, ClassSection $classSection): RedirectResponse
    {
        $this->grades->returnForCorrection($this->grades->submissionFor($classSection), $request->validated('remarks'), $request->user());

        return redirect()->route('class-sections.grades.show', $classSection)->with('success', 'Returned to faculty for correction.');
    }

    public function finalize(Request $request, ClassSection $classSection): RedirectResponse
    {
        $this->authorize('reviewGrades', $classSection);

        $this->grades->finalize($this->grades->submissionFor($classSection), $request->user());

        return redirect()->route('class-sections.grades.show', $classSection)->with('success', 'Grades finalized.');
    }

    public function correct(GradeCorrectRequest $request, ClassSection $classSection, StudentGrade $studentGrade): RedirectResponse
    {
        abort_unless($studentGrade->enrollmentCourse->class_section_id === $classSection->id, 404);

        $this->grades->correct($studentGrade, $request->validated('grade'), $request->validated('reason'), $request->user());

        return redirect()->route('class-sections.grades.show', $classSection)->with('success', 'Grade corrected.');
    }
}
