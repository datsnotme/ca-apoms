<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduation\GraduationRequirementTemplateRequest;
use App\Models\GraduationRequirementTemplate;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GraduationRequirementTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', GraduationRequirementTemplate::class);

        $canManage = $request->user()->can('create', GraduationRequirementTemplate::class);

        return Inertia::render('GraduationRequirements/Index', [
            'templates' => GraduationRequirementTemplate::query()
                ->with('program:id,name')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(),
            ...($canManage ? [
                'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            ] : []),
        ]);
    }

    public function store(GraduationRequirementTemplateRequest $request): RedirectResponse
    {
        GraduationRequirementTemplate::create($request->validated());

        return redirect()->route('graduation-requirement-templates.index')->with('success', 'Requirement added.');
    }

    public function edit(GraduationRequirementTemplate $graduationRequirementTemplate): Response
    {
        $this->authorize('update', $graduationRequirementTemplate);

        return Inertia::render('GraduationRequirements/Edit', [
            'template' => $graduationRequirementTemplate,
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(GraduationRequirementTemplateRequest $request, GraduationRequirementTemplate $graduationRequirementTemplate): RedirectResponse
    {
        $graduationRequirementTemplate->update($request->validated());

        return redirect()->route('graduation-requirement-templates.index')->with('success', 'Requirement updated.');
    }

    public function destroy(GraduationRequirementTemplate $graduationRequirementTemplate): RedirectResponse
    {
        $this->authorize('delete', $graduationRequirementTemplate);

        $graduationRequirementTemplate->delete();

        return redirect()->route('graduation-requirement-templates.index')->with('success', 'Requirement archived.');
    }
}
