<?php

namespace App\Http\Controllers\Operations;

use App\Enums\ActionItemStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\TaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();

        $tasks = Task::query()
            ->visibleTo($user)
            ->with(['assignedTo:id,name', 'createdBy:id,name'])
            ->when($request->string('status')->trim()->isNotEmpty(), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByRaw('due_date is null, due_date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (Task $task) => [
                ...$task->only(['id', 'title', 'due_date', 'status']),
                'assigned_to' => $task->assignedTo,
                'created_by' => $task->createdBy,
                'can_update' => $user->can('update', $task),
                'can_manage' => $user->hasRole(RoleName::Administrator->value) || $task->created_by === $user->id,
            ])
            ->withQueryString();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'statuses' => collect(ActionItemStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'filters' => ['status' => $request->string('status')->toString()],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Task::class);

        return Inertia::render('Tasks/Create', [
            'assigneeOptions' => User::query()->orderBy('surname')->get(['id', 'name']),
        ]);
    }

    public function store(TaskRequest $request): RedirectResponse
    {
        $assignedTo = $request->validated('assigned_to') ?? $request->user()->id;

        $task = Task::create([
            ...$request->validated(),
            'assigned_to' => $assignedTo,
            'created_by' => $request->user()->id,
        ]);

        if ($assignedTo !== $request->user()->id) {
            User::find($assignedTo)?->notify(new TaskAssignedNotification($task));
        }

        return redirect()->route('tasks.index')->with('success', 'Task added.');
    }

    public function edit(Request $request, Task $task): Response
    {
        $this->authorize('update', $task);

        // The full edit form is for the creator/Admin only — a plain
        // assignee's only allowed change (status/notes) happens inline on
        // the Index list, not through this page. See TaskRequest.
        abort_unless(
            $request->user()->hasRole(RoleName::Administrator->value) || $task->created_by === $request->user()->id,
            403
        );

        return Inertia::render('Tasks/Edit', [
            'task' => $task->only(['id', 'title', 'description', 'assigned_to', 'due_date']),
            'assigneeOptions' => User::query()->orderBy('surname')->get(['id', 'name']),
        ]);
    }

    public function update(TaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();

        if (($data['status'] ?? null) === ActionItemStatus::Completed->value && $task->status !== ActionItemStatus::Completed) {
            $data['completed_by'] = $request->user()->id;
            $data['completed_at'] = now();
        } elseif (isset($data['status']) && $data['status'] !== ActionItemStatus::Completed->value) {
            $data['completed_by'] = null;
            $data['completed_at'] = null;
        }

        $reassigned = isset($data['assigned_to']) && $data['assigned_to'] !== $task->assigned_to;

        $task->update($data);

        if ($reassigned && $task->assigned_to && $task->assigned_to !== $request->user()->id) {
            User::find($task->assigned_to)?->notify(new TaskAssignedNotification($task));
        }

        return back()->with('success', 'Task updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task removed.');
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
            'ids.*' => ['integer', 'exists:tasks,id'],
        ]);

        $tasks = Task::whereIn('id', $validated['ids'])->get();

        foreach ($tasks as $task) {
            $this->authorize('delete', $task);
        }

        foreach ($tasks as $task) {
            $task->delete();
        }

        return redirect()->route('tasks.index')->with('success', $tasks->count().' task(s) removed.');
    }
}
