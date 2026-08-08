<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CourseRequest;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Course::class);

        $courses = Course::query()
            ->visibleTo($request->user())
            ->with('department:id,name')
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($request->string('category')->trim()->isNotEmpty(), fn ($q) => $q->where('category', $request->string('category')))
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'filters' => $request->only('search', 'category'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Course::class);

        return Inertia::render('Courses/Create', [
            'departments' => Department::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name']),
            'courses' => Course::query()->orderBy('code')->get(['id', 'code', 'title']),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $course = Course::create($request->safe()->except(['prerequisite_ids', 'corequisite_ids']));
            $course->prerequisites()->sync($request->validated('prerequisite_ids', []));
            $course->corequisites()->sync($request->validated('corequisite_ids', []));
        });

        return redirect()->route('courses.index')->with('success', 'Course created.');
    }

    public function edit(Request $request, Course $course): Response
    {
        $this->authorize('update', $course);

        return Inertia::render('Courses/Edit', [
            'course' => [
                ...$course->only([
                    'id', 'department_id', 'code', 'title', 'description', 'units',
                    'lecture_hours', 'laboratory_hours', 'category', 'recommended_year_level',
                    'recommended_semester', 'is_active',
                ]),
                'prerequisite_ids' => $course->prerequisites()->pluck('courses.id'),
                'corequisite_ids' => $course->corequisites()->pluck('courses.id'),
            ],
            'departments' => Department::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name']),
            'courses' => Course::query()->where('id', '!=', $course->id)->orderBy('code')->get(['id', 'code', 'title']),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        DB::transaction(function () use ($request, $course) {
            $course->update($request->safe()->except(['prerequisite_ids', 'corequisite_ids']));
            $course->prerequisites()->sync($request->validated('prerequisite_ids', []));
            $course->corequisites()->sync($request->validated('corequisite_ids', []));
        });

        return redirect()->route('courses.index')->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Course archived.');
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
            'ids.*' => ['integer', 'exists:courses,id'],
        ]);

        $courses = Course::whereIn('id', $validated['ids'])->get();

        foreach ($courses as $course) {
            $this->authorize('delete', $course);
        }

        foreach ($courses as $course) {
            $course->delete();
        }

        return redirect()->route('courses.index')->with('success', $courses->count().' course(s) archived.');
    }
}
