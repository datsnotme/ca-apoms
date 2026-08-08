<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Http\Requests\Research\ResearchMemberRequest;
use App\Models\ResearchMember;
use App\Models\ResearchProject;
use Illuminate\Http\RedirectResponse;

class ResearchMemberController extends Controller
{
    public function store(ResearchMemberRequest $request, ResearchProject $researchProject): RedirectResponse
    {
        $researchProject->members()->create([
            ...$request->validated(),
            'added_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Member added.');
    }

    public function destroy(ResearchProject $researchProject, ResearchMember $member): RedirectResponse
    {
        abort_unless($member->research_project_id === $researchProject->id, 404);

        $this->authorize('delete', $member);

        $member->delete();

        return back()->with('success', 'Member removed.');
    }
}
