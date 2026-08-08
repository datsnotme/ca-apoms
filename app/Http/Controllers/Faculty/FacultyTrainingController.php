<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\FacultyTrainingRequest;
use App\Models\FacultyTraining;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class FacultyTrainingController extends Controller
{
    public function store(FacultyTrainingRequest $request, User $user): RedirectResponse
    {
        $user->trainings()->create($request->validated());

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Training added.');
    }

    public function update(FacultyTrainingRequest $request, User $user, FacultyTraining $training): RedirectResponse
    {
        abort_unless($training->user_id === $user->id, 404);

        $training->update($request->validated());

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Training updated.');
    }

    public function destroy(User $user, FacultyTraining $training): RedirectResponse
    {
        $this->authorize('delete', $training);
        abort_unless($training->user_id === $user->id, 404);

        $training->delete();

        return redirect()->route('faculty-profiles.show', $user)->with('success', 'Training removed.');
    }
}
