<?php

namespace App\Http\Controllers\Graduation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Graduation\StudentGraduationRequirementRequest;
use App\Models\GraduationCandidate;
use App\Models\StudentGraduationRequirement;
use Illuminate\Http\RedirectResponse;

class StudentGraduationRequirementController extends Controller
{
    public function update(StudentGraduationRequirementRequest $request, GraduationCandidate $graduationCandidate, StudentGraduationRequirement $requirement): RedirectResponse
    {
        abort_unless($requirement->graduation_candidate_id === $graduationCandidate->id, 404);

        $data = $request->validated();

        if ($data['status'] === 'pending') {
            $data['satisfied_by'] = null;
            $data['satisfied_at'] = null;
        } else {
            $data['satisfied_by'] = $request->user()->id;
            $data['satisfied_at'] = now();
        }

        $requirement->update($data);

        return back()->with('success', 'Requirement updated.');
    }
}
