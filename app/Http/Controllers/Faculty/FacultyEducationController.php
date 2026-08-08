<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\FacultyEducationRequest;
use App\Models\FacultyEducation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class FacultyEducationController extends Controller
{
    public function store(FacultyEducationRequest $request, User $user): RedirectResponse
    {
        $user->education()->create($request->validated());

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Education record added.');
    }

    public function update(FacultyEducationRequest $request, User $user, FacultyEducation $education): RedirectResponse
    {
        abort_unless($education->user_id === $user->id, 404);

        $education->update($request->validated());

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Education record updated.');
    }

    public function destroy(User $user, FacultyEducation $education): RedirectResponse
    {
        $this->authorize('delete', $education);
        abort_unless($education->user_id === $user->id, 404);

        $education->delete();

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Education record removed.');
    }
}
