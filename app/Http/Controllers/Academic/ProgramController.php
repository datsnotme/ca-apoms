<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\ProgramRequest;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Program::class);

        $programs = Program::query()
            ->visibleTo($request->user())
            ->with('department:id,name')
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Programs/Index', [
            'programs' => $programs,
            'filters' => $request->only('search'),
            ...($request->user()->can('create', Program::class) ? [
                'departments' => Department::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name']),
            ] : []),
        ]);
    }

    public function store(ProgramRequest $request): RedirectResponse
    {
        Program::create($request->validated());

        return redirect()->route('programs.index')->with('success', 'Program created.');
    }

    public function edit(Request $request, Program $program): Response
    {
        $this->authorize('update', $program);

        return Inertia::render('Programs/Edit', [
            'program' => $program,
            'departments' => Department::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(ProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        return redirect()->route('programs.index')->with('success', 'Program updated.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $this->authorize('delete', $program);

        $program->delete();

        return redirect()->route('programs.index')->with('success', 'Program archived.');
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
            'ids.*' => ['integer', 'exists:programs,id'],
        ]);

        $programs = Program::whereIn('id', $validated['ids'])->get();

        foreach ($programs as $program) {
            $this->authorize('delete', $program);
        }

        foreach ($programs as $program) {
            $program->delete();
        }

        return redirect()->route('programs.index')->with('success', $programs->count().' program(s) archived.');
    }
}
