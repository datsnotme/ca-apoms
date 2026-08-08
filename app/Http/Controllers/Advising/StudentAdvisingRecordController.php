<?php

namespace App\Http\Controllers\Advising;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentAdvisingRecordRequest;
use App\Models\Student;
use App\Models\StudentAdvisingRecord;
use Illuminate\Http\RedirectResponse;

class StudentAdvisingRecordController extends Controller
{
    public function store(StudentAdvisingRecordRequest $request, Student $student): RedirectResponse
    {
        $student->advisingRecords()->create([
            ...$request->validated(),
            'adviser_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Advising record added.');
    }

    public function update(StudentAdvisingRecordRequest $request, Student $student, StudentAdvisingRecord $record): RedirectResponse
    {
        abort_unless($record->student_id === $student->id, 404);

        $record->update($request->validated());

        return back()->with('success', 'Advising record updated.');
    }

    public function destroy(Student $student, StudentAdvisingRecord $record): RedirectResponse
    {
        abort_unless($record->student_id === $student->id, 404);

        $this->authorize('delete', $record);

        $record->delete();

        return back()->with('success', 'Advising record removed.');
    }
}
