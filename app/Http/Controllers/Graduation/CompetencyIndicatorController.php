<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduation\CompetencyIndicatorRequest;
use App\Models\CompetencyCategory;
use App\Models\CompetencyIndicator;
use Illuminate\Http\RedirectResponse;

class CompetencyIndicatorController extends Controller
{
    public function store(CompetencyIndicatorRequest $request, CompetencyCategory $competencyCategory): RedirectResponse
    {
        $competencyCategory->indicators()->create($request->validated());

        return redirect()->route('competency-categories.edit', $competencyCategory)->with('success', 'Indicator added.');
    }

    public function update(CompetencyIndicatorRequest $request, CompetencyCategory $competencyCategory, CompetencyIndicator $indicator): RedirectResponse
    {
        $indicator->update($request->validated());

        return redirect()->route('competency-categories.edit', $competencyCategory)->with('success', 'Indicator updated.');
    }

    public function destroy(CompetencyCategory $competencyCategory, CompetencyIndicator $indicator): RedirectResponse
    {
        $this->authorize('delete', $indicator);

        $indicator->delete();

        return redirect()->route('competency-categories.edit', $competencyCategory)->with('success', 'Indicator removed.');
    }
}
