<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePhotoRequest;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'user' => $request->user()->only([
                'surname', 'first_name', 'middle_name', 'suffix', 'contact_number',
                'employee_number', 'email', 'username', 'profile_photo_url',
            ]),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated())->save();

        return redirect()->route('profile.edit')->with('success', 'Profile updated.');
    }

    public function updatePhoto(ProfilePhotoRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store("profile-photos/{$user->id}", 'public');

        $user->update(['profile_photo_path' => $path]);

        return back()->with('success', 'Profile photo updated.');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->update(['profile_photo_path' => null]);
        }

        return back()->with('success', 'Profile photo removed.');
    }
}
