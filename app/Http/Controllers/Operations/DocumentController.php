<?php

namespace App\Http\Controllers\Operations;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\DocumentRequest;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\DocumentVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Document::class);

        $user = $request->user();

        $documents = Document::query()
            ->visibleTo($user)
            ->with(['category:id,name', 'department:id,name', 'uploadedBy:id,name', 'latestVersion'])
            ->when($request->string('document_category_id')->trim()->isNotEmpty(), fn ($q) => $q->where('document_category_id', $request->integer('document_category_id')))
            ->when($request->string('search')->trim()->isNotEmpty(), function ($q) use ($request) {
                $search = $request->string('search')->trim();
                $q->where('title', 'like', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->through(fn (Document $document) => [
                ...$document->only(['id', 'title', 'description']),
                'category' => $document->category,
                'department' => $document->department,
                'uploaded_by' => $document->uploadedBy,
                'latest_version' => $document->latestVersion,
                'can_manage' => $user->can('update', $document),
            ])
            ->withQueryString();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'categories' => DocumentCategory::query()->orderBy('name')->get(['id', 'name']),
            'canCreate' => $user->can('create', Document::class),
            'filters' => $request->only('search', 'document_category_id'),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Document::class);

        return Inertia::render('Documents/Create', $this->formOptions($request));
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $file = $request->file('file');

        $document = DB::transaction(function () use ($request, $file) {
            $document = Document::create([
                ...$request->safe()->except('file'),
                'uploaded_by' => $request->user()->id,
            ]);

            $path = $file->store("documents/{$document->id}", 'local');

            $document->versions()->create([
                'version_number' => 1,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
                'uploaded_at' => now(),
            ]);

            return $document;
        });

        return redirect()->route('documents.show', $document)->with('success', 'Document uploaded.');
    }

    public function show(Request $request, Document $document): Response
    {
        $this->authorize('view', $document);

        $user = $request->user();

        $document->load([
            'category:id,name',
            'department:id,name',
            'uploadedBy:id,name',
            'versions' => fn ($q) => $q->with('uploadedBy:id,name')->orderByDesc('version_number'),
        ]);

        return Inertia::render('Documents/Show', [
            'document' => $document,
            'canManage' => $user->can('update', $document),
            'canUploadVersion' => $user->can('create', [DocumentVersion::class, $document]),
        ]);
    }

    public function edit(Request $request, Document $document): Response
    {
        $this->authorize('update', $document);

        return Inertia::render('Documents/Edit', [
            'document' => $document->only(['id', 'title', 'description', 'document_category_id', 'department_id']),
            ...$this->formOptions($request),
        ]);
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        $document->update($request->safe()->except('file'));

        return redirect()->route('documents.show', $document)->with('success', 'Document updated.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        foreach ($document->versions as $version) {
            Storage::disk('local')->delete($version->file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document removed.');
    }

    /**
     * Same authorization and file-cleanup behavior as destroy(), applied to a
     * batch — see StudentController::destroyMany() for the authorization
     * pattern this mirrors across every bulk-delete endpoint in the app.
     */
    public function destroyMany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:documents,id'],
        ]);

        $documents = Document::whereIn('id', $validated['ids'])->with('versions')->get();

        foreach ($documents as $document) {
            $this->authorize('delete', $document);
        }

        foreach ($documents as $document) {
            foreach ($document->versions as $version) {
                Storage::disk('local')->delete($version->file_path);
            }

            $document->delete();
        }

        return redirect()->route('documents.index')->with('success', $documents->count().' document(s) removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        return [
            'categories' => DocumentCategory::query()->orderBy('name')->get(['id', 'name']),
            'departments' => $request->user()->hasRole(RoleName::Administrator->value)
                ? Department::query()->orderBy('name')->get(['id', 'name'])
                : [],
            'isAdmin' => $request->user()->hasRole(RoleName::Administrator->value),
        ];
    }
}
