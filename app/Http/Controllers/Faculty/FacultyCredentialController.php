<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\FacultyCredentialRequest;
use App\Models\FacultyCredential;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class FacultyCredentialController extends Controller
{
    public function store(FacultyCredentialRequest $request, User $user): RedirectResponse
    {
        $user->credentials()->create($request->validated());

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Credential added.');
    }

    public function update(FacultyCredentialRequest $request, User $user, FacultyCredential $credential): RedirectResponse
    {
        abort_unless($credential->user_id === $user->id, 404);

        $credential->update($request->validated());

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Credential updated.');
    }

    public function destroy(User $user, FacultyCredential $credential): RedirectResponse
    {
        $this->authorize('delete', $credential);
        abort_unless($credential->user_id === $user->id, 404);

        $credential->delete();

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Credential removed.');
    }
}
