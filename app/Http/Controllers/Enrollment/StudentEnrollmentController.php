<?php

namespace App\Http\Controllers\Enrollment;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\StudentEnrollmentRequest;
use App\Models\ClassSection;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentEnrollmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', StudentEnrollment::class);

        $enrollments = StudentEnrollment::query()
            ->visibleTo($request->user())
            ->with(['student:id,student_number,surname,first_name,middle_name,department_id', 'semester:id,academic_year_id,term', 'semester.academicYear:id,start_year,end_year'])
            ->withCount('enrollmentCourses')
            ->when($request->string('semester_id')->trim()->isNotEmpty(), fn ($q) => $q->where('semester_id', $request->integer('semester_id')))
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->whereHas('student', fn ($q) => $q->where('student_number', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%"));
            })
            ->orderByDesc('semester_id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Enrollments/Index', [
            'enrollments' => $enrollments,
            'filters' => $request->only('search', 'semester_id'),
            'semesters' => $this->semesterOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', StudentEnrollment::class);

        return Inertia::render('Enrollments/Create', [
            'students' => Student::query()->visibleTo($request->user())->orderBy('surname')->get(['id', 'student_number', 'surname', 'first_name', 'middle_name']),
            'semesters' => $this->semesterOptions(),
        ]);
    }

    public function store(StudentEnrollmentRequest $request): RedirectResponse
    {
        $enrollment = StudentEnrollment::create([
            ...$request->validated(),
            'enrolled_by' => $request->user()->id,
        ]);

        return redirect()->route('enrollments.edit', $enrollment)->with('success', 'Enrollment created.');
    }

    public function edit(Request $request, StudentEnrollment $enrollment): Response
    {
        $this->authorize('update', $enrollment);

        $enrollment->load([
            'student:id,student_number,surname,first_name,middle_name',
            'semester:id,academic_year_id,term', 'semester.academicYear:id,start_year,end_year',
            'enrollmentCourses.classSection.course:id,code,title,units',
        ]);

        $availableSections = ClassSection::query()
            ->where('semester_id', $enrollment->semester_id)
            ->where('status', 'open')
            ->with('course:id,code,title,units')
            ->withCount(['enrollmentCourses as enrolled_count' => fn ($q) => $q->whereIn('status', ['Enrolled', 'Added', 'Repeated'])])
            ->orderBy('section_label')
            ->get();

        return Inertia::render('Enrollments/Edit', [
            'enrollment' => [
                ...$enrollment->only(['id', 'student_id', 'semester_id', 'status']),
                'student' => $enrollment->student,
                'semester' => $enrollment->semester,
                'enrollment_courses' => $enrollment->enrollmentCourses,
            ],
            'availableSections' => $availableSections,
            'statuses' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], EnrollmentStatus::cases()),
        ]);
    }

    public function update(Request $request, StudentEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('update', $enrollment);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(EnrollmentStatus::class)],
        ]);

        $enrollment->update($validated);

        return redirect()->route('enrollments.edit', $enrollment)->with('success', 'Enrollment status updated.');
    }

    public function destroy(StudentEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('delete', $enrollment);

        $enrollment->delete();

        return redirect()->route('enrollments.index')->with('success', 'Enrollment archived.');
    }

    /**
     * Same authorization and soft-delete behavior as destroy(), applied to a
     * batch — see StudentController::destroyMany() for the pattern this
     * mirrors across every bulk-delete endpoint in the app.
     */
    public function destroyMany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:student_enrollments,id'],
        ]);

        $enrollments = StudentEnrollment::whereIn('id', $validated['ids'])->get();

        foreach ($enrollments as $enrollment) {
            $this->authorize('delete', $enrollment);
        }

        foreach ($enrollments as $enrollment) {
            $enrollment->delete();
        }

        return redirect()->route('enrollments.index')->with('success', $enrollments->count().' enrollment(s) archived.');
    }

    private function semesterOptions()
    {
        return Semester::query()
            ->with('academicYear:id,start_year,end_year')
            ->orderByDesc('id')
            ->get(['id', 'academic_year_id', 'term'])
            ->map(fn ($s) => ['id' => $s->id, 'label' => "{$s->academicYear->start_year}-{$s->academicYear->end_year} {$s->term->label()}"]);
    }
}
