<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\AcademicYearRequest;
use App\Models\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AcademicYear::class);

        return Inertia::render('AcademicYears/Index', [
            'academicYears' => AcademicYear::query()
                ->withCount('semesters')
                ->with(['semesters' => fn ($q) => $q->orderBy('term')])
                ->orderByDesc('start_year')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', AcademicYear::class);

        return Inertia::render('AcademicYears/Create');
    }

    public function store(AcademicYearRequest $request): RedirectResponse
    {
        $academicYear = AcademicYear::create($request->validated());

        if ($academicYear->is_current) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_current' => false]);
        }

        return redirect()->route('academic-years.index')->with('success', 'Academic year created.');
    }

    public function edit(AcademicYear $academicYear): Response
    {
        $this->authorize('update', $academicYear);

        return Inertia::render('AcademicYears/Edit', ['academicYear' => $academicYear]);
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $academicYear->update($request->validated());

        if ($academicYear->is_current) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_current' => false]);
        }

        return redirect()->route('academic-years.index')->with('success', 'Academic year updated.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('delete', $academicYear);

        $academicYear->delete();

        return redirect()->route('academic-years.index')->with('success', 'Academic year archived.');
    }
}
