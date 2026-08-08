<?php

namespace App\Http\Controllers\Operations;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\EventRequest;
use App\Models\Department;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Event::class);

        $user = $request->user();

        $events = Event::query()
            ->visibleTo($user)
            ->with(['department:id,name', 'createdBy:id,name'])
            ->when(! $request->boolean('include_past'), fn ($q) => $q->upcoming())
            ->orderBy('start_at')
            ->paginate(15)
            ->through(fn (Event $event) => [
                ...$event->only(['id', 'title', 'description', 'start_at', 'end_at', 'location']),
                'department' => $event->department,
                'created_by' => $event->createdBy,
                'can_manage' => $user->can('update', $event),
            ])
            ->withQueryString();

        return Inertia::render('Events/Index', [
            'events' => $events,
            'canCreate' => $user->can('create', Event::class),
            'filters' => ['include_past' => $request->boolean('include_past')],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Event::class);

        return Inertia::render('Events/Create', [
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
        ]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        Event::create([...$request->validated(), 'created_by' => $request->user()->id]);

        return redirect()->route('events.index')->with('success', 'Event added.');
    }

    public function edit(Request $request, Event $event): Response
    {
        $this->authorize('update', $event);

        return Inertia::render('Events/Edit', [
            'event' => [
                ...$event->only(['id', 'title', 'description', 'location', 'department_id']),
                'start_at' => $event->start_at->format('Y-m-d\TH:i'),
                'end_at' => $event->end_at?->format('Y-m-d\TH:i'),
            ],
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
        ]);
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

        return redirect()->route('events.index')->with('success', 'Event updated.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index')->with('success', 'Event removed.');
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
            'ids.*' => ['integer', 'exists:events,id'],
        ]);

        $events = Event::whereIn('id', $validated['ids'])->get();

        foreach ($events as $event) {
            $this->authorize('delete', $event);
        }

        foreach ($events as $event) {
            $event->delete();
        }

        return redirect()->route('events.index')->with('success', $events->count().' event(s) removed.');
    }

    private function departmentOptions(Request $request)
    {
        if (! $request->user()->hasRole(RoleName::Administrator->value)) {
            return [];
        }

        return Department::query()->orderBy('name')->get(['id', 'name']);
    }
}
