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
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Program::class);

        return Inertia::render('Programs/Create', [
            'departments' => Department::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name']),
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
}
