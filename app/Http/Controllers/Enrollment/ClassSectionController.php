<?php

namespace App\Http\Controllers\Enrollment;

use App\Enums\ClassSectionStatus;
use App\Enums\DayOfWeek;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrollment\ClassSectionRequest;
use App\Models\ClassSection;
use App\Models\Course;
use App\Models\Facility;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClassSectionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ClassSection::class);

        $classSections = ClassSection::query()
            ->visibleTo($request->user())
            ->with(['course:id,code,title,department_id', 'semester:id,academic_year_id,term', 'semester.academicYear:id,start_year,end_year'])
            ->withCount(['enrollmentCourses as enrolled_count' => fn ($q) => $q->whereIn('status', ['Enrolled', 'Added', 'Repeated'])])
            ->when($request->string('semester_id')->trim()->isNotEmpty(), fn ($q) => $q->where('semester_id', $request->integer('semester_id')))
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->whereHas('course', fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"));
            })
            ->orderByDesc('semester_id')
            ->orderBy('section_label')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('ClassSections/Index', [
            'classSections' => $classSections,
            'filters' => $request->only('search', 'semester_id'),
            'semesters' => $this->semesterOptions(),
            ...($request->user()->can('create', ClassSection::class) ? [
                'courses' => Course::query()->visibleTo($request->user())->orderBy('code')->get(['id', 'code', 'title']),
                'faculty' => User::query()
                    ->role([RoleName::Faculty->value, RoleName::DepartmentHead->value])
                    ->orderBy('surname')->get(['id', 'name']),
                'statuses' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], ClassSectionStatus::cases()),
            ] : []),
        ]);
    }

    public function store(ClassSectionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $classSection = ClassSection::create($request->safe()->except('faculty_id'));
            $this->syncFaculty($classSection, $request->validated('faculty_id'));
        });

        return redirect()->route('class-sections.index')->with('success', 'Class section created.');
    }

    public function edit(Request $request, ClassSection $classSection): Response
    {
        $this->authorize('update', $classSection);

        $classSection->load(['schedules.facility:id,name', 'facultyAssignments.faculty:id,name']);

        return Inertia::render('ClassSections/Edit', [
            'classSection' => [
                ...$classSection->only(['id', 'course_id', 'semester_id', 'section_label', 'max_students', 'status']),
                'faculty_id' => $classSection->facultyAssignments->first()?->faculty_id,
                'schedules' => $classSection->schedules->map(fn ($s) => [
                    ...$s->only(['id', 'day_of_week', 'start_time', 'end_time']),
                    'facility' => $s->facility,
                ]),
                'enrolled_count' => $classSection->enrolledCount(),
            ],
            'dayOptions' => array_map(fn ($d) => ['value' => $d->value, 'label' => $d->label()], DayOfWeek::cases()),
            'facilityOptions' => Facility::query()->visibleTo($request->user())->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            ...$this->formOptions($request),
        ]);
    }

    public function update(ClassSectionRequest $request, ClassSection $classSection): RedirectResponse
    {
        DB::transaction(function () use ($request, $classSection) {
            $classSection->update($request->safe()->except('faculty_id'));
            $this->syncFaculty($classSection, $request->validated('faculty_id'));
        });

        return redirect()->route('class-sections.edit', $classSection)->with('success', 'Class section updated.');
    }

    public function destroy(ClassSection $classSection): RedirectResponse
    {
        $this->authorize('delete', $classSection);

        $classSection->delete();

        return redirect()->route('class-sections.index')->with('success', 'Class section archived.');
    }

    public function roster(Request $request, ClassSection $classSection): Response
    {
        $this->authorize('view', $classSection);

        $classSection->load([
            'course:id,code,title',
            'semester:id,academic_year_id,term', 'semester.academicYear:id,start_year,end_year',
            'enrollmentCourses' => fn ($q) => $q->whereIn('status', ['Enrolled', 'Added', 'Repeated', 'Completed', 'Failed', 'Incomplete'])
                ->with('studentEnrollment.student:id,student_number,surname,first_name,middle_name'),
        ]);

        return Inertia::render('ClassSections/Roster', [
            'classSection' => [
                ...$classSection->only(['id', 'section_label', 'max_students']),
                'course' => $classSection->course,
                'semester' => $classSection->semester,
            ],
            'roster' => $classSection->enrollmentCourses->map(fn ($ec) => [
                'id' => $ec->id,
                'status' => $ec->status,
                'student' => $ec->studentEnrollment->student,
            ]),
        ]);
    }

    private function syncFaculty(ClassSection $classSection, ?int $facultyId): void
    {
        if ($facultyId) {
            $classSection->facultyAssignments()->updateOrCreate(
                ['role' => 'primary'],
                ['faculty_id' => $facultyId]
            );
        } else {
            $classSection->facultyAssignments()->where('role', 'primary')->delete();
        }
    }

    private function semesterOptions()
    {
        return Semester::query()
            ->with('academicYear:id,start_year,end_year')
            ->orderByDesc('id')
            ->get(['id', 'academic_year_id', 'term'])
            ->map(fn ($s) => ['id' => $s->id, 'label' => "{$s->academicYear->start_year}-{$s->academicYear->end_year} {$s->term->label()}"]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        return [
            'courses' => Course::query()->visibleTo($request->user())->orderBy('code')->get(['id', 'code', 'title']),
            'semesters' => $this->semesterOptions(),
            'faculty' => User::query()
                ->role([RoleName::Faculty->value, RoleName::DepartmentHead->value])
                ->orderBy('surname')->get(['id', 'name']),
            'statuses' => array_map(fn ($s) => ['value' => $s->value, 'label' => $s->label()], ClassSectionStatus::cases()),
        ];
    }
}
