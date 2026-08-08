<?php

namespace App\Http\Controllers\Research;

use App\Http\Controllers\Controller;
use App\Http\Requests\Research\ResearchOutputRequest;
use App\Models\ResearchOutput;
use App\Models\ResearchProject;
use Illuminate\Http\RedirectResponse;

class ResearchOutputController extends Controller
{
    public function store(ResearchOutputRequest $request, ResearchProject $researchProject): RedirectResponse
    {
        $researchProject->outputs()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Output added.');
    }

    public function destroy(ResearchProject $researchProject, ResearchOutput $output): RedirectResponse
    {
        abort_unless($output->research_project_id === $researchProject->id, 404);

        $this->authorize('delete', $output);

        $output->delete();

        return back()->with('success', 'Output removed.');
    }
}
