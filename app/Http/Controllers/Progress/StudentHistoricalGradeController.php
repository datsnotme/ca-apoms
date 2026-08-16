<?php

namespace App\Http\Controllers\Progress;

use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Import\StudentHistoricalGradeImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentHistoricalGradeController extends Controller
{
    private const HEADINGS = [
        'student_number', 'student_name', 'academic_year', 'semester', 'program',
        'course_code', 'course_title', 'lecture_hours', 'laboratory_hours', 'units', 'grade',
    ];

    public function __construct(private readonly StudentHistoricalGradeImportService $importer) {}

    public function template(Request $request, Student $student): BinaryFileResponse
    {
        abort_unless($request->user()->can('grades.import'), 403);

        $sampleRow = [
            $student->student_number, $student->name, '2022-2023', 'First Semester', 'BSBIO',
            'GEC101', 'Understanding the Self', 3, 0, 3, '2.50',
        ];

        return Excel::download(
            new TemplateExport(self::HEADINGS, $sampleRow),
            "historical-grades-{$student->student_number}-template.xlsx"
        );
    }

    public function store(Request $request, Student $student): RedirectResponse
    {
        abort_unless($request->user()->can('grades.import'), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = $this->importer->import($student, $request->file('file'), $request->user());

        return back()->with('success', "Imported {$result['imported']} historical grade row(s) for {$student->name}.");
    }
}
