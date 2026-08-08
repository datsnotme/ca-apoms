<?php

namespace App\Http\Controllers\Advising;

use App\Enums\InterventionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentInterventionFollowupRequest;
use App\Models\Student;
use App\Models\StudentInterventionFollowup;
use Illuminate\Http\RedirectResponse;

class StudentInterventionFollowupController extends Controller
{
    public function store(StudentInterventionFollowupRequest $request, Student $student): RedirectResponse
    {
        $student->interventionFollowups()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Follow-up added.');
    }

    public function update(StudentInterventionFollowupRequest $request, Student $student, StudentInterventionFollowup $followup): RedirectResponse
    {
        abort_unless($followup->student_id === $student->id, 404);

        $data = $request->validated();

        if (($data['status'] ?? null) === InterventionStatus::Completed->value && $followup->status !== InterventionStatus::Completed) {
            $data['completed_by'] = $request->user()->id;
            $data['completed_at'] = now();
        } elseif (isset($data['status']) && $data['status'] !== InterventionStatus::Completed->value) {
            $data['completed_by'] = null;
            $data['completed_at'] = null;
        }

        $followup->update($data);

        return back()->with('success', 'Follow-up updated.');
    }

    public function destroy(Student $student, StudentInterventionFollowup $followup): RedirectResponse
    {
        abort_unless($followup->student_id === $student->id, 404);

        $this->authorize('delete', $followup);

        $followup->delete();

        return back()->with('success', 'Follow-up removed.');
    }
}
