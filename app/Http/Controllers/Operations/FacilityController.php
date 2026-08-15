<?php

namespace App\Http\Controllers\Operations;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\FacilityRequest;
use App\Models\Department;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FacilityController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Facility::class);

        $user = $request->user();

        $facilities = Facility::query()
            ->visibleTo($user)
            ->with('department:id,name')
            ->orderBy('name')
            ->paginate(15)
            ->through(fn (Facility $facility) => [
                ...$facility->only(['id', 'name', 'type', 'location', 'capacity', 'is_active']),
                'department' => $facility->department,
                'can_manage' => $user->can('update', $facility),
            ])
            ->withQueryString();

        $canCreate = $user->can('create', Facility::class);

        return Inertia::render('Facilities/Index', [
            'facilities' => $facilities,
            'canCreate' => $canCreate,
            ...($canCreate ? [
                'departments' => $this->departmentOptions($request),
                'isAdmin' => $user->hasRole(RoleName::Administrator->value),
            ] : []),
        ]);
    }

    public function store(FacilityRequest $request): RedirectResponse
    {
        Facility::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('facilities.index')->with('success', 'Facility registered.');
    }

    public function edit(Request $request, Facility $facility): Response
    {
        $this->authorize('update', $facility);

        return Inertia::render('Facilities/Edit', [
            'facility' => $facility->only(['id', 'name', 'type', 'department_id', 'location', 'capacity', 'description', 'is_active']),
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
        ]);
    }

    public function update(FacilityRequest $request, Facility $facility): RedirectResponse
    {
        $facility->update($request->validated());

        return redirect()->route('facilities.index')->with('success', 'Facility updated.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $this->authorize('delete', $facility);

        $facility->delete();

        return redirect()->route('facilities.index')->with('success', 'Facility removed.');
    }

    private function departmentOptions(Request $request)
    {
        if (! $request->user()->hasRole(RoleName::Administrator->value)) {
            return [];
        }

        return Department::query()->orderBy('name')->get(['id', 'name']);
    }
}
