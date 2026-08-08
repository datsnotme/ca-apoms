<?php

namespace App\Http\Controllers\Import;

use App\Enums\ImportType;
use App\Exports\ImportErrorReportExport;
use App\Exports\TemplateExport;
use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\Import\CoursesImporter;
use App\Services\Import\CurriculumCoursesImporter;
use App\Services\Import\EnrollmentImporter;
use App\Services\Import\GradesImporter;
use App\Services\Import\ImportRunner;
use App\Services\Import\RowImporter;
use App\Services\Import\StudentsImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(private readonly ImportRunner $runner) {}

    public function index(Request $request): Response
    {
        $accessibleTypes = array_map(
            fn (ImportType $t) => $t->value,
            array_filter(ImportType::cases(), fn (ImportType $t) => $request->user()->can($this->permissionFor($t))),
        );

        $batches = ImportBatch::query()
            ->whereIn('type', $accessibleTypes)
            ->with('uploadedBy:id,name')
            ->orderByDesc('id')
            ->paginate(15);

        return Inertia::render('Imports/Index', [
            'batches' => $batches,
            'types' => array_map(fn (ImportType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'can' => $request->user()->can($this->permissionFor($t)),
            ], ImportType::cases()),
        ]);
    }

    public function template(ImportType $type): BinaryFileResponse
    {
        $importer = $this->importerFor($type);

        return Excel::download(
            new TemplateExport($importer->headings(), $importer->sampleRow()),
            strtolower($type->value).'-template.xlsx'
        );
    }

    public function store(Request $request, ImportType $type): RedirectResponse
    {
        abort_unless($request->user()->can($this->permissionFor($type)), 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $batch = $this->runner->run($request->file('file'), $type, $this->importerFor($type), $request->user());

        return redirect()->route('imports.show', $batch)->with('success', 'Import complete.');
    }

    public function show(Request $request, ImportBatch $batch): Response
    {
        abort_unless($request->user()->can($this->permissionFor($batch->type)), 403);

        $batch->load('uploadedBy:id,name');

        return Inertia::render('Imports/Show', [
            'batch' => $batch,
            'errors' => $batch->errors()->orderBy('row_number')->get(),
        ]);
    }

    public function errors(Request $request, ImportBatch $batch): StreamedResponse|BinaryFileResponse
    {
        abort_unless($request->user()->can($this->permissionFor($batch->type)), 403);

        return Excel::download(new ImportErrorReportExport($batch), "import-{$batch->id}-errors.csv");
    }

    private function importerFor(ImportType $type): RowImporter
    {
        return match ($type) {
            ImportType::Students => app(StudentsImporter::class),
            ImportType::Courses => app(CoursesImporter::class),
            ImportType::CurriculumCourses => app(CurriculumCoursesImporter::class),
            ImportType::Enrollment => app(EnrollmentImporter::class),
            ImportType::Grades => app(GradesImporter::class),
        };
    }

    private function permissionFor(ImportType $type): string
    {
        return match ($type) {
            ImportType::Students => 'students.import',
            ImportType::Courses => 'courses.manage',
            ImportType::CurriculumCourses => 'curricula.manage',
            ImportType::Enrollment => 'enrollment.manage',
            ImportType::Grades => 'grades.import',
        };
    }
}
