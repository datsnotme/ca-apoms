<?php

namespace App\Http\Controllers\Progress;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Student;
use App\Services\StudentEvaluationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class StudentEvaluationController extends Controller
{
    public function __construct(private readonly StudentEvaluationService $evaluation) {}

    /**
     * Lists every student within the acting user's viewProgress scope —
     * same scoping AtRiskController uses — so an evaluator can reach any
     * enrolled student's evaluation from the sidebar instead of only
     * finding it after already opening that student's Progress page.
     */
    public function index(Request $request): InertiaResponse
    {
        abort_unless($request->user()->can('progress.view'), 403);

        $user = $request->user();
        $isFaculty = $user->hasRole(RoleName::Faculty->value);
        $departmentId = ! $isFaculty && ! $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            ? $user->department_id
            : null;
        $adviserId = $isFaculty ? $user->id : null;

        $students = Student::query()
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($adviserId, fn ($q) => $q->where('adviser_id', $adviserId))
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(fn ($q) => $q->where('student_number', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%"));
            })
            ->with(['department:id,name', 'program:id,name', 'yearLevel:id,level,label', 'adviser:id,name'])
            ->orderBy('surname')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Progress/EvaluationIndex', [
            'students' => $students,
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Student $student): Response
    {
        $this->authorize('viewProgress', $student);

        $student->load(['department:id,name', 'program:id,name', 'curriculum:id,name', 'yearLevel:id,level,label', 'historicalGrades']);

        $data = $this->evaluation->evaluate($student);

        $pdf = Pdf::loadView('pdf.student-evaluation', [
            'student' => $student,
            'college' => College::query()->first(),
            'years' => $data['years'],
            'bucketSummary' => $data['bucket_summary'],
            'priorAcademicRecord' => $data['prior_academic_record'],
            'gwa' => $data['gwa'],
            'completionPercentage' => $data['completion_percentage'],
            'flaggedCourses' => $data['flagged_courses'],
            'suggestedClassification' => $data['suggested_classification'],
            'summary' => $data['summary'],
        ]);

        return $pdf->stream("student-evaluation-{$student->student_number}.pdf");
    }
}
