<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $showArchived = $request->boolean('archived');

        $users = User::query()
            ->when($showArchived, fn ($q) => $q->onlyTrashed(), fn ($q) => $q)
            ->with(['department:id,name', 'roles:name'])
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%"));
            })
            ->orderBy('surname')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only('search'),
            'showArchived' => $showArchived,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $temporaryPassword = Str::password(12);

        $user = User::create([
            ...$request->safe()->except('role'),
            'password' => $temporaryPassword,
            'must_change_password' => true,
            'created_by' => $request->user()->id,
        ]);

        $user->assignRole($request->validated('role'));

        return redirect()->route('users.index')->with(
            'success',
            "User created. Temporary password: {$temporaryPassword} — share this securely; it will not be shown again."
        );
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        return Inertia::render('Users/Edit', [
            'user' => [
                ...$user->only([
                    'id', 'employee_number', 'surname', 'first_name', 'middle_name', 'suffix',
                    'email', 'username', 'contact_number', 'department_id', 'status',
                ]),
                'role' => $user->getRoleNames()->first(),
            ],
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->safe()->except('role'));
        $user->syncRoles([$request->validated('role')]);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User archived.');
    }

    public function reactivate(Request $request, int $userId): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::onlyTrashed()->findOrFail($userId);
        $user->restore();

        return redirect()->route('users.index')->with('success', 'User restored.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_map(
            fn (RoleName $role) => ['value' => $role->value, 'label' => $role->label()],
            RoleName::cases()
        );
    }
}
