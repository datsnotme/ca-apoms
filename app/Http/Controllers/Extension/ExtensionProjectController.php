<?php

namespace App\Http\Controllers\Extension;

use App\Enums\ExtensionProjectStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Extension\ExtensionProjectRequest;
use App\Models\Department;
use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExtensionProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ExtensionProject::class);

        $user = $request->user();

        $projects = ExtensionProject::query()
            ->visibleTo($user)
            ->with(['department:id,name', 'createdBy:id,name'])
            ->withCount(['members', 'activities', 'beneficiaries'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (ExtensionProject $project) => [
                ...$project->only(['id', 'title', 'status', 'start_date', 'end_date', 'members_count', 'activities_count', 'beneficiaries_count']),
                'department' => $project->department,
                'created_by' => $project->createdBy,
            ])
            ->withQueryString();

        return Inertia::render('Extension/Index', [
            'projects' => $projects,
            'canCreate' => $user->can('create', ExtensionProject::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', ExtensionProject::class);

        return Inertia::render('Extension/Create', [
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(ExtensionProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request) {
            $project = ExtensionProject::create([...$request->validated(), 'created_by' => $request->user()->id]);

            $project->members()->create([
                'user_id' => $request->user()->id,
                'is_lead' => true,
                'added_by' => $request->user()->id,
            ]);

            return $project;
        });

        return redirect()->route('extension-projects.show', $project)->with('success', 'Extension project created.');
    }

    public function show(Request $request, ExtensionProject $extensionProject): Response
    {
        $this->authorize('view', $extensionProject);

        $user = $request->user();

        $extensionProject->load([
            'department:id,name',
            'createdBy:id,name',
            'members' => fn ($q) => $q->with('user:id,name')->orderByDesc('is_lead')->orderBy('created_at'),
            'activities' => fn ($q) => $q->with('createdBy:id,name')->orderByDesc('activity_date'),
            'beneficiaries' => fn ($q) => $q->with('createdBy:id,name')->orderByDesc('created_at'),
        ]);

        $canManage = $user->can('update', $extensionProject);

        return Inertia::render('Extension/Show', [
            'project' => [
                ...$extensionProject->only(['id', 'title', 'description', 'start_date', 'end_date', 'funding_source']),
                'status' => $extensionProject->status->value,
                'status_label' => $extensionProject->status->label(),
                'department' => $extensionProject->department,
                'created_by' => $extensionProject->createdBy,
                'members' => $extensionProject->members->map(fn ($member) => [
                    ...$member->only(['id', 'is_lead']),
                    'user' => $member->user,
                    'can_delete' => $canManage,
                ]),
                'activities' => $extensionProject->activities->map(fn ($activity) => [
                    ...$activity->only(['id', 'title', 'activity_type', 'description', 'activity_date', 'location']),
                    'created_by' => $activity->createdBy,
                    'can_delete' => $canManage,
                ]),
                'beneficiaries' => $extensionProject->beneficiaries->map(fn ($beneficiary) => [
                    ...$beneficiary->only(['id', 'beneficiary_name', 'beneficiary_type', 'count', 'location', 'notes']),
                    'created_by' => $beneficiary->createdBy,
                    'can_delete' => $canManage,
                ]),
            ],
            'canManage' => $canManage,
            'memberOptions' => $canManage
                ? User::query()->whereNotIn('id', $extensionProject->members->pluck('user_id'))->orderBy('surname')->get(['id', 'name'])
                : [],
        ]);
    }

    public function edit(Request $request, ExtensionProject $extensionProject): Response
    {
        $this->authorize('update', $extensionProject);

        return Inertia::render('Extension/Edit', [
            'project' => [
                ...$extensionProject->only(['id', 'title', 'description', 'department_id', 'start_date', 'end_date', 'funding_source']),
                'status' => $extensionProject->status->value,
                'start_date' => $extensionProject->start_date?->format('Y-m-d'),
                'end_date' => $extensionProject->end_date?->format('Y-m-d'),
            ],
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(ExtensionProjectRequest $request, ExtensionProject $extensionProject): RedirectResponse
    {
        $extensionProject->update($request->validated());

        return redirect()->route('extension-projects.show', $extensionProject)->with('success', 'Extension project updated.');
    }

    public function destroy(ExtensionProject $extensionProject): RedirectResponse
    {
        $this->authorize('delete', $extensionProject);

        $extensionProject->delete();

        return redirect()->route('extension-projects.index')->with('success', 'Extension project removed.');
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
        return collect(ExtensionProjectStatus::cases())
            ->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]);
    }
}
