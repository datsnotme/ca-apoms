<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Http\Requests\Faculty\FacultyDocumentRequest;
use App\Http\Requests\Faculty\FacultyDocumentVerifyRequest;
use App\Models\FacultyDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FacultyDocumentController extends Controller
{
    public function store(FacultyDocumentRequest $request, User $user): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store("faculty-documents/{$user->id}", 'local');

        $user->facultyDocuments()->create([
            'category' => $request->validated('category'),
            'title' => $request->validated('title'),
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function download(User $user, FacultyDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless($document->user_id === $user->id, 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    public function verify(FacultyDocumentVerifyRequest $request, User $user, FacultyDocument $document): RedirectResponse
    {
        abort_unless($document->user_id === $user->id, 404);

        $document->update([
            'verification_status' => $request->validated('verification_status'),
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'remarks' => $request->validated('remarks'),
        ]);

        return back()->with('success', 'Document verification updated.');
    }

    public function destroy(User $user, FacultyDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        abort_unless($document->user_id === $user->id, 404);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }
}
