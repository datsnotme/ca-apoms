<?php

namespace App\Http\Controllers\Research;

use App\Enums\ResearchProjectStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Research\ResearchProjectRequest;
use App\Models\Department;
use App\Models\ResearchProject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ResearchProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ResearchProject::class);

        $user = $request->user();

        $projects = ResearchProject::query()
            ->visibleTo($user)
            ->with(['department:id,name', 'createdBy:id,name'])
            ->withCount(['members', 'outputs'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (ResearchProject $project) => [
                ...$project->only(['id', 'title', 'status', 'start_date', 'end_date', 'members_count', 'outputs_count']),
                'department' => $project->department,
                'created_by' => $project->createdBy,
            ])
            ->withQueryString();

        return Inertia::render('Research/Index', [
            'projects' => $projects,
            'canCreate' => $user->can('create', ResearchProject::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ResearchProject::class);

        return Inertia::render('Research/Create', [
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(ResearchProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request) {
            $project = ResearchProject::create([...$request->validated(), 'created_by' => $request->user()->id]);

            $project->members()->create([
                'user_id' => $request->user()->id,
                'is_lead' => true,
                'added_by' => $request->user()->id,
            ]);

            return $project;
        });

        return redirect()->route('research-projects.show', $project)->with('success', 'Research project created.');
    }

    public function show(Request $request, ResearchProject $researchProject): Response
    {
        $this->authorize('view', $researchProject);

        $user = $request->user();

        $researchProject->load([
            'department:id,name',
            'createdBy:id,name',
            'members' => fn ($q) => $q->with('user:id,name')->orderByDesc('is_lead')->orderBy('created_at'),
            'outputs' => fn ($q) => $q->with('createdBy:id,name')->orderByDesc('output_date'),
        ]);

        $canManage = $user->can('update', $researchProject);

        return Inertia::render('Research/Show', [
            'project' => [
                ...$researchProject->only(['id', 'title', 'description', 'start_date', 'end_date', 'funding_source']),
                'status' => $researchProject->status->value,
                'status_label' => $researchProject->status->label(),
                'department' => $researchProject->department,
                'created_by' => $researchProject->createdBy,
                'members' => $researchProject->members->map(fn ($member) => [
                    ...$member->only(['id', 'is_lead']),
                    'user' => $member->user,
                    'can_delete' => $canManage,
                ]),
                'outputs' => $researchProject->outputs->map(fn ($output) => [
                    ...$output->only(['id', 'title', 'type', 'description', 'output_date', 'reference_url']),
                    'created_by' => $output->createdBy,
                    'can_delete' => $canManage,
                ]),
            ],
            'canManage' => $canManage,
            'memberOptions' => $canManage
                ? User::query()->whereNotIn('id', $researchProject->members->pluck('user_id'))->orderBy('surname')->get(['id', 'name'])
                : [],
        ]);
    }

    public function edit(Request $request, ResearchProject $researchProject): Response
    {
        $this->authorize('update', $researchProject);

        return Inertia::render('Research/Edit', [
            'project' => [
                ...$researchProject->only(['id', 'title', 'description', 'department_id', 'start_date', 'end_date', 'funding_source']),
                'status' => $researchProject->status->value,
                'start_date' => $researchProject->start_date?->format('Y-m-d'),
                'end_date' => $researchProject->end_date?->format('Y-m-d'),
            ],
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(ResearchProjectRequest $request, ResearchProject $researchProject): RedirectResponse
    {
        $researchProject->update($request->validated());

        return redirect()->route('research-projects.show', $researchProject)->with('success', 'Research project updated.');
    }

    public function destroy(ResearchProject $researchProject): RedirectResponse
    {
        $this->authorize('delete', $researchProject);

        $researchProject->delete();

        return redirect()->route('research-projects.index')->with('success', 'Research project removed.');
    }

    private function departmentOptions(Request $request)
    {
        if (! $request->user()->hasRole(RoleName::Administrator->value)) {
            return [];
        }

        return Department::query()->orderBy('name')->get(['id', 'name']);
    }

    private function statusOptions()
    {
        return collect(ResearchProjectStatus::cases())
            ->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]);
    }
}
