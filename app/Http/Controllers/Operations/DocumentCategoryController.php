<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\DocumentCategoryRequest;
use App\Models\DocumentCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DocumentCategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', DocumentCategory::class);

        return Inertia::render('DocumentCategories/Index', [
            'categories' => DocumentCategory::query()->withCount('documents')->orderBy('name')->get(),
        ]);
    }

    public function store(DocumentCategoryRequest $request): RedirectResponse
    {
        DocumentCategory::create($request->validated());

        return back()->with('success', 'Category added.');
    }

    public function destroy(DocumentCategory $documentCategory): RedirectResponse
    {
        $this->authorize('delete', $documentCategory);

        if ($documentCategory->documents()->exists()) {
            return back()->with('error', 'This category is still in use by one or more documents and cannot be removed.');
        }

        $documentCategory->delete();

        return back()->with('success', 'Category removed.');
    }
}
