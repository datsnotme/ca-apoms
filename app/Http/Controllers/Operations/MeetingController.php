<?php

namespace App\Http\Controllers\Operations;

use App\Enums\ActionItemStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\MeetingRequest;
use App\Models\Department;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Meeting::class);

        $user = $request->user();

        $meetings = Meeting::query()
            ->visibleTo($user)
            ->with(['department:id,name', 'createdBy:id,name'])
            ->withCount(['attendees', 'actionItems'])
            ->when(! $request->boolean('include_past'), fn ($q) => $q->upcoming())
            ->orderBy('start_at')
            ->paginate(15)
            ->through(fn (Meeting $meeting) => [
                ...$meeting->only(['id', 'title', 'start_at', 'end_at', 'location', 'attendees_count', 'action_items_count']),
                'department' => $meeting->department,
                'created_by' => $meeting->createdBy,
                'can_manage' => $user->can('update', $meeting),
            ])
            ->withQueryString();

        $canCreate = $user->can('create', Meeting::class);

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings,
            'canCreate' => $canCreate,
            'filters' => ['include_past' => $request->boolean('include_past')],
            ...($canCreate ? [
                'departments' => $this->departmentOptions($request),
                'isAdmin' => $user->hasRole(RoleName::Administrator->value),
            ] : []),
        ]);
    }

    public function store(MeetingRequest $request): RedirectResponse
    {
        $meeting = Meeting::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Meeting scheduled.');
    }

    public function show(Request $request, Meeting $meeting): Response
    {
        $this->authorize('view', $meeting);

        $user = $request->user();

        $meeting->load([
            'department:id,name',
            'createdBy:id,name',
            'attendees' => fn ($q) => $q->with('user:id,name')->orderBy('created_at'),
            'actionItems' => fn ($q) => $q->with(['assignedTo:id,name', 'completedBy:id,name'])->orderByDesc('created_at'),
        ]);

        $canManage = $user->can('update', $meeting);

        return Inertia::render('Meetings/Show', [
            'meeting' => [
                ...$meeting->only(['id', 'title', 'description', 'location', 'start_at', 'end_at']),
                'department' => $meeting->department,
                'created_by' => $meeting->createdBy,
                'attendees' => $meeting->attendees->map(fn ($attendee) => [
                    ...$attendee->only(['id', 'attended', 'attended_at']),
                    'user' => $attendee->user,
                ]),
                'action_items' => $meeting->actionItems->map(fn ($item) => [
                    ...$item->only(['id', 'description', 'due_date', 'status', 'notes']),
                    'assigned_to' => $item->assignedTo,
                    'completed_by' => $item->completedBy,
                    'completed_at' => $item->completed_at,
                    'can_update' => $user->can('update', $item),
                    'can_delete' => $user->can('delete', $item),
                ]),
            ],
            'canManage' => $canManage,
            'attendeeOptions' => $canManage
                ? User::query()->whereNotIn('id', $meeting->attendees->pluck('user_id'))->orderBy('surname')->get(['id', 'name'])
                : [],
            'assigneeOptions' => $canManage
                ? User::query()->orderBy('surname')->get(['id', 'name'])
                : [],
            'statuses' => collect(ActionItemStatus::cases())
                ->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    public function edit(Request $request, Meeting $meeting): Response
    {
        $this->authorize('update', $meeting);

        return Inertia::render('Meetings/Edit', [
            'meeting' => [
                ...$meeting->only(['id', 'title', 'description', 'location', 'department_id']),
                'start_at' => $meeting->start_at->format('Y-m-d\TH:i'),
                'end_at' => $meeting->end_at?->format('Y-m-d\TH:i'),
            ],
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
        ]);
    }

    public function update(MeetingRequest $request, Meeting $meeting): RedirectResponse
    {
        $meeting->update($request->validated());

        return redirect()->route('meetings.show', $meeting)->with('success', 'Meeting updated.');
    }

    public function destroy(Meeting $meeting): RedirectResponse
    {
        $this->authorize('delete', $meeting);

        $meeting->delete();

        return redirect()->route('meetings.index')->with('success', 'Meeting removed.');
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
            'ids.*' => ['integer', 'exists:meetings,id'],
        ]);

        $meetings = Meeting::whereIn('id', $validated['ids'])->get();

        foreach ($meetings as $meeting) {
            $this->authorize('delete', $meeting);
        }

        foreach ($meetings as $meeting) {
            $meeting->delete();
        }

        return redirect()->route('meetings.index')->with('success', $meetings->count().' meeting(s) removed.');
    }

    private function departmentOptions(Request $request)
    {
        if (! $request->user()->hasRole(RoleName::Administrator->value)) {
            return [];
        }

        return Department::query()->orderBy('name')->get(['id', 'name']);
    }
}
