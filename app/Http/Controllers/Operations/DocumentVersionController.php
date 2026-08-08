<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Operations\DocumentVersionRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVersionController extends Controller
{
    public function store(DocumentVersionRequest $request, Document $document): RedirectResponse
    {
        $file = $request->file('file');
        $nextVersion = ($document->versions()->max('version_number') ?? 0) + 1;
        $path = $file->store("documents/{$document->id}", 'local');

        $document->versions()->create([
            'version_number' => $nextVersion,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'notes' => $request->validated('notes'),
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'New version uploaded.');
    }

    public function download(Document $document, DocumentVersion $version): StreamedResponse
    {
        $this->authorize('view', $document);
        abort_unless($version->document_id === $document->id, 404);

        return Storage::disk('local')->download($version->file_path, $version->original_filename);
    }

    public function destroy(Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->authorize('delete', $version);
        abort_unless($version->document_id === $document->id, 404);

        if ($document->versions()->count() <= 1) {
            return back()->with('error', 'A document must keep at least one version — delete the whole document instead.');
        }

        Storage::disk('local')->delete($version->file_path);
        $version->delete();

        return back()->with('success', 'Version removed.');
    }
}
