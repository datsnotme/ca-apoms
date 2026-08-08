<?php

namespace App\Http\Controllers\Operations;

use App\Enums\ActiveStatus;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\AnnouncementRequest;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Notifications\NewAnnouncementNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Announcement::class);

        $user = $request->user();

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->with(['department:id,name', 'createdBy:id,name'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (Announcement $announcement) => [
                ...$announcement->only(['id', 'title', 'body', 'created_at']),
                'department' => $announcement->department,
                'created_by' => $announcement->createdBy,
                'can_manage' => $user->can('update', $announcement),
            ])
            ->withQueryString();

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements,
            'canCreate' => $user->can('create', Announcement::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Announcement::class);

        return Inertia::render('Announcements/Create', [
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
        ]);
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        $announcement = Announcement::create([...$request->validated(), 'created_by' => $request->user()->id]);

        Notification::send($this->recipients($announcement, $request->user()), new NewAnnouncementNotification($announcement));

        return redirect()->route('announcements.index')->with('success', 'Announcement posted.');
    }

    public function edit(Request $request, Announcement $announcement): Response
    {
        $this->authorize('update', $announcement);

        return Inertia::render('Announcements/Edit', [
            'announcement' => $announcement->only(['id', 'title', 'body', 'department_id']),
            'departments' => $this->departmentOptions($request),
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
        ]);
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($request->validated());

        return redirect()->route('announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return redirect()->route('announcements.index')->with('success', 'Announcement removed.');
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
            'ids.*' => ['integer', 'exists:announcements,id'],
        ]);

        $announcements = Announcement::whereIn('id', $validated['ids'])->get();

        foreach ($announcements as $announcement) {
            $this->authorize('delete', $announcement);
        }

        foreach ($announcements as $announcement) {
            $announcement->delete();
        }

        return redirect()->route('announcements.index')->with('success', $announcements->count().' announcement(s) removed.');
    }

    private function departmentOptions(Request $request)
    {
        if (! $request->user()->hasRole(RoleName::Administrator->value)) {
            return [];
        }

        return Department::query()->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Everyone within the announcement's audience, excluding the poster.
     */
    private function recipients(Announcement $announcement, User $poster)
    {
        return User::query()
            ->where('status', ActiveStatus::Active->value)
            ->where('id', '!=', $poster->id)
            ->when($announcement->department_id, fn ($q) => $q->where('department_id', $announcement->department_id))
            ->get();
    }
}
