<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CurriculumRequest;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Curriculum;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurriculumController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Curriculum::class);

        $curricula = Curriculum::query()
            ->visibleTo($request->user())
            ->with(['program:id,name,department_id', 'effectiveAcademicYear:id,start_year,end_year'])
            ->withCount('curriculumCourses')
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Curricula/Index', [
            'curricula' => $curricula,
            'filters' => $request->only('search'),
            ...($request->user()->can('create', Curriculum::class) ? [
                'programs' => Program::query()
                    ->whereHas('department', fn ($q) => $q->visibleTo($request->user()))
                    ->orderBy('name')->get(['id', 'name']),
                'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(['id', 'start_year', 'end_year']),
            ] : []),
        ]);
    }

    public function store(CurriculumRequest $request): RedirectResponse
    {
        $curriculum = Curriculum::create($request->validated());

        return redirect()->route('curricula.edit', $curriculum)->with('success', 'Curriculum created. Add courses below.');
    }

    public function edit(Request $request, Curriculum $curriculum): Response
    {
        $this->authorize('update', $curriculum);

        $curriculum->load([
            'program:id,name',
            'effectiveAcademicYear:id,start_year,end_year',
            'curriculumCourses.course:id,code,title,units,department_id',
        ]);

        return Inertia::render('Curricula/Edit', [
            'curriculum' => $curriculum,
            'programs' => Program::query()
                ->whereHas('department', fn ($q) => $q->visibleTo($request->user()))
                ->orderBy('name')->get(['id', 'name']),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(['id', 'start_year', 'end_year']),
            'availableCourses' => Course::query()
                ->visibleTo($request->user())
                ->whereNotIn('id', $curriculum->curriculumCourses->pluck('course_id'))
                ->orderBy('code')
                ->get(['id', 'code', 'title', 'units']),
        ]);
    }

    public function update(CurriculumRequest $request, Curriculum $curriculum): RedirectResponse
    {
        $curriculum->update($request->validated());

        return redirect()->route('curricula.edit', $curriculum)->with('success', 'Curriculum updated.');
    }

    public function destroy(Curriculum $curriculum): RedirectResponse
    {
        $this->authorize('delete', $curriculum);

        $curriculum->delete();

        return redirect()->route('curricula.index')->with('success', 'Curriculum archived.');
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
            'ids.*' => ['integer', 'exists:curricula,id'],
        ]);

        $curricula = Curriculum::whereIn('id', $validated['ids'])->get();

        foreach ($curricula as $curriculum) {
            $this->authorize('delete', $curriculum);
        }

        foreach ($curricula as $curriculum) {
            $curriculum->delete();
        }

        return redirect()->route('curricula.index')->with('success', $curricula->count().' curriculum(s) archived.');
    }
}
