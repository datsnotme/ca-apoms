<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduation\CompetencyCategoryRequest;
use App\Models\CompetencyCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompetencyCategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', CompetencyCategory::class);

        return Inertia::render('CompetencyFramework/Index', [
            'categories' => CompetencyCategory::query()
                ->withCount('indicators')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'canManage' => request()->user()->can('create', CompetencyCategory::class),
        ]);
    }

    public function store(CompetencyCategoryRequest $request): RedirectResponse
    {
        $category = CompetencyCategory::create($request->validated());

        return redirect()->route('competency-categories.edit', $category)->with('success', 'Competency category created. Add indicators below.');
    }

    public function edit(CompetencyCategory $competencyCategory): Response
    {
        $this->authorize('update', $competencyCategory);

        $competencyCategory->load(['indicators' => fn ($q) => $q->orderBy('sort_order')->orderBy('title')]);

        return Inertia::render('CompetencyFramework/Edit', [
            'category' => $competencyCategory,
        ]);
    }

    public function update(CompetencyCategoryRequest $request, CompetencyCategory $competencyCategory): RedirectResponse
    {
        $competencyCategory->update($request->validated());

        return redirect()->route('competency-categories.edit', $competencyCategory)->with('success', 'Competency category updated.');
    }

    public function destroy(CompetencyCategory $competencyCategory): RedirectResponse
    {
        $this->authorize('delete', $competencyCategory);

        $competencyCategory->delete();

        return redirect()->route('competency-categories.index')->with('success', 'Competency category archived.');
    }
}
