<?php

namespace App\Http\Middleware;

use App\Models\College;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_number' => $user->employee_number,
                    'profile_photo_url' => $user->profile_photo_url,
                    'department' => $user->department?->only(['id', 'name', 'code']),
                    'role' => $user->getRoleNames()->first(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Named distinctly from the `notifications` page prop that
            // Notifications/Index itself renders (a paginated list) — using
            // the same key would let the page prop silently overwrite this
            // shared one wherever both appear together.
            'notificationCenter' => $user ? [
                'unread_count' => fn () => $user->unreadNotifications()->count(),
                'recent' => fn () => $user->notifications()->latest()->limit(8)->get()->map(fn ($n) => [
                    'id' => $n->id,
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at,
                    ...$n->data,
                ]),
            ] : null,
            // Deliberately unauthenticated-visible (unlike auth/
            // notificationCenter above) — the login page (GuestLayout)
            // needs it too. A single cheap lookup, not cached, matching
            // this project's "compute on demand" convention elsewhere.
            'systemLogoUrl' => fn () => College::query()->first()?->logo_url,
        ];
    }
}
