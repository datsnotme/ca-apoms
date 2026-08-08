<?php

namespace App\Http\Controllers\Extension;

use App\Http\Controllers\Controller;
use App\Http\Requests\Extension\ExtensionActivityRequest;
use App\Models\ExtensionActivity;
use App\Models\ExtensionProject;
use Illuminate\Http\RedirectResponse;

class ExtensionActivityController extends Controller
{
    public function store(ExtensionActivityRequest $request, ExtensionProject $extensionProject): RedirectResponse
    {
        $extensionProject->activities()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Activity added.');
    }

    public function destroy(ExtensionProject $extensionProject, ExtensionActivity $activity): RedirectResponse
    {
        abort_unless($activity->extension_project_id === $extensionProject->id, 404);

        $this->authorize('delete', $activity);

        $activity->delete();

        return back()->with('success', 'Activity removed.');
    }
}
