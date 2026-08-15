<?php

namespace App\Http\Controllers\Progress;

use App\Http\Controllers\Controller;
use App\Models\College;
use App\Models\Student;
use App\Services\StudentEvaluationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentEvaluationController extends Controller
{
    public function __construct(private readonly StudentEvaluationService $evaluation) {}

    public function show(Request $request, Student $student): Response
    {
        $this->authorize('viewProgress', $student);

        $student->load(['department:id,name', 'program:id,name', 'curriculum:id,name', 'yearLevel:id,level,label']);

        $data = $this->evaluation->evaluate($student);

        $pdf = Pdf::loadView('pdf.student-evaluation', [
            'student' => $student,
            'college' => College::query()->first(),
            'buckets' => $data['buckets'],
            'gwa' => $data['gwa'],
            'completionPercentage' => $data['completion_percentage'],
            'flaggedCourses' => $data['flagged_courses'],
            'suggestedClassification' => $data['suggested_classification'],
            'generatedBy' => $request->user(),
        ]);

        return $pdf->stream("student-evaluation-{$student->student_number}.pdf");
    }
}
