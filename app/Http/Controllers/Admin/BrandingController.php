<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandingRequest;
use App\Models\College;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages the system logo shown in the sidebar (AppLayout) and the login
 * page (GuestLayout) — see HandleInertiaRequests for how it's shared
 * globally. Single-college install (see ASSUMPTIONS.md), so this always
 * operates on the one College row rather than taking a route parameter.
 */
class BrandingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Branding/Edit', [
            'logoUrl' => College::query()->first()?->logo_url,
        ]);
    }

    public function update(BrandingRequest $request): RedirectResponse
    {
        $college = College::query()->firstOrFail();

        if ($college->logo_path) {
            Storage::disk('public')->delete($college->logo_path);
        }

        $path = $request->file('logo')->store('branding', 'public');

        $college->update(['logo_path' => $path]);

        return back()->with('success', 'System logo updated.');
    }

    public function destroy(): RedirectResponse
    {
        $college = College::query()->firstOrFail();

        if ($college->logo_path) {
            Storage::disk('public')->delete($college->logo_path);
            $college->update(['logo_path' => null]);
        }

        return back()->with('success', 'System logo removed.');
    }
}
