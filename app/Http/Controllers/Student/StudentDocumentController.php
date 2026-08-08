<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentDocumentRequest;
use App\Http\Requests\Student\StudentDocumentVerifyRequest;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDocumentController extends Controller
{
    public function store(StudentDocumentRequest $request, Student $student): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store("student-documents/{$student->id}", 'local');

        $student->documents()->create([
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

    public function download(Student $student, StudentDocument $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless($document->student_id === $student->id, 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    public function verify(StudentDocumentVerifyRequest $request, Student $student, StudentDocument $document): RedirectResponse
    {
        abort_unless($document->student_id === $student->id, 404);

        $document->update([
            'verification_status' => $request->validated('verification_status'),
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'remarks' => $request->validated('remarks'),
        ]);

        return back()->with('success', 'Document verification updated.');
    }

    public function destroy(Student $student, StudentDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        abort_unless($document->student_id === $student->id, 404);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document removed.');
    }
}
