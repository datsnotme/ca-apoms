<?php

namespace App\Http\Controllers\Graduation;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\College;
use App\Models\Department;
use App\Models\GraduationCandidate;
use App\Models\Semester;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GraduationReportController extends Controller
{
    public function show(Request $request, GraduationCandidate $graduationCandidate): Response
    {
        $this->authorize('view', $graduationCandidate);

        $graduationCandidate->load([
            'student:id,student_number,surname,first_name,middle_name,department_id,program_id',
            'student.department:id,name', 'student.program:id,name',
            'academicYear:id,start_year,end_year', 'semester:id,term',
            'recommendedBy:id,name', 'decidedBy:id,name',
            'requirements.template', 'requirements.satisfiedBy:id,name',
            'competencyEvaluators.evaluator:id,name',
            'competencyEvaluators.ratings.indicator',
        ]);

        $pdf = Pdf::loadView('pdf.graduation-candidate-report', [
            'candidate' => $graduationCandidate,
            'college' => College::query()->first(),
            'generatedBy' => $request->user(),
        ]);

        return $pdf->stream("graduation-report-{$graduationCandidate->student->student_number}.pdf");
    }

    public function batch(Request $request): Response
    {
        $this->authorize('viewAny', GraduationCandidate::class);

        $data = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
        ]);

        $academicYear = AcademicYear::findOrFail($data['academic_year_id']);
        $semester = Semester::findOrFail($data['semester_id']);
        $user = $request->user();

        $department = $user->hasRole(RoleName::DepartmentHead->value)
            ? Department::find($user->department_id)
            : null;

        $candidates = GraduationCandidate::query()
            ->visibleTo($user)
            ->where('academic_year_id', $academicYear->id)
            ->where('semester_id', $semester->id)
            ->whereIn('status', ['approved', 'graduated'])
            ->with(['student:id,student_number,surname,first_name,middle_name,department_id,program_id', 'student.department:id,name', 'student.program:id,name'])
            ->orderBy('student_id')
            ->get();

        $pdf = Pdf::loadView('pdf.graduation-batch-report', [
            'candidates' => $candidates,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'college' => College::query()->first(),
            'department' => $department,
            'generatedBy' => $user,
        ]);

        return $pdf->download("graduation-list-{$academicYear->start_year}-{$academicYear->end_year}-{$semester->term->value}.pdf");
    }
}
