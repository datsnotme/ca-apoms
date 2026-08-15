<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\SemesterRequest;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SemesterController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Semester::class);

        return Inertia::render('AcademicYears/Semesters', [
            'semesters' => Semester::query()
                ->with('academicYear')
                ->orderByDesc('id')
                ->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(['id', 'start_year', 'end_year']),
        ]);
    }

    public function store(SemesterRequest $request): RedirectResponse
    {
        $semester = Semester::create($request->validated());

        if ($semester->is_current) {
            Semester::where('id', '!=', $semester->id)->update(['is_current' => false]);
        }

        return redirect()->route('semesters.index')->with('success', 'Semester created.');
    }

    public function edit(Semester $semester): Response
    {
        $this->authorize('update', $semester);

        return Inertia::render('AcademicYears/SemesterForm', [
            'semester' => $semester,
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(['id', 'start_year', 'end_year']),
        ]);
    }

    public function update(SemesterRequest $request, Semester $semester): RedirectResponse
    {
        $semester->update($request->validated());

        if ($semester->is_current) {
            Semester::where('id', '!=', $semester->id)->update(['is_current' => false]);
        }

        return redirect()->route('semesters.index')->with('success', 'Semester updated.');
    }

    public function destroy(Semester $semester): RedirectResponse
    {
        $this->authorize('delete', $semester);

        $semester->delete();

        return redirect()->route('semesters.index')->with('success', 'Semester archived.');
    }
}
