<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\FacultyAwardRequest;
use App\Models\FacultyAward;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class FacultyAwardController extends Controller
{
    public function store(FacultyAwardRequest $request, User $user): RedirectResponse
    {
        $user->awards()->create($request->validated());

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Award added.');
    }

    public function update(FacultyAwardRequest $request, User $user, FacultyAward $award): RedirectResponse
    {
        abort_unless($award->user_id === $user->id, 404);

        $award->update($request->validated());

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Award updated.');
    }

    public function destroy(User $user, FacultyAward $award): RedirectResponse
    {
        $this->authorize('delete', $award);
        abort_unless($award->user_id === $user->id, 404);

        $award->delete();

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Award removed.');
    }
}
