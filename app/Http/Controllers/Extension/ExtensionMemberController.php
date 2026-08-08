<?php

namespace App\Http\Controllers\Extension;

use App\Http\Controllers\Controller;
use App\Http\Requests\Extension\ExtensionMemberRequest;
use App\Models\ExtensionMember;
use App\Models\ExtensionProject;
use Illuminate\Http\RedirectResponse;

class ExtensionMemberController extends Controller
{
    public function store(ExtensionMemberRequest $request, ExtensionProject $extensionProject): RedirectResponse
    {
        $extensionProject->members()->create([
            ...$request->validated(),
            'added_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Member added.');
    }

    public function destroy(ExtensionProject $extensionProject, ExtensionMember $member): RedirectResponse
    {
        abort_unless($member->extension_project_id === $extensionProject->id, 404);

        $this->authorize('delete', $member);

        $member->delete();

        return back()->with('success', 'Member removed.');
    }
}
