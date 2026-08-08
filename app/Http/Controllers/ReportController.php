<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Exports\ReportExport;
use App\Models\College;
use App\Models\Semester;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as PdfResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reports/Index', [
            'reportTypes' => collect(ReportType::cases())->map(fn (ReportType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ]),
        ]);
    }

    public function show(Request $request, ReportType $type, ReportService $reports): Response
    {
        $user = $request->user();
        $filters = $this->filtersFrom($request);

        $data = $reports->generate($type, $user, $filters);

        return Inertia::render('Reports/Show', [
            'type' => $type->value,
            'title' => $type->label(),
            'description' => $type->description(),
            'availableFilters' => $type->availableFilters(),
            'filters' => $filters,
            'filterOptions' => [
                'semesters' => $this->semesterOptions(),
                'departments' => $reports->departmentOptions($user),
            ],
            'scopeDescription' => $reports->scopeDescription($user, $filters['department_id'] ?? null),
            'headings' => $data['headings'],
            'rows' => $data['rows'],
        ]);
    }

    public function pdf(Request $request, ReportType $type, ReportService $reports): PdfResponse
    {
        $user = $request->user();
        $filters = $this->filtersFrom($request);
        $data = $reports->generate($type, $user, $filters);

        $pdf = Pdf::loadView('pdf.report', [
            'title' => $type->label(),
            'headings' => $data['headings'],
            'rows' => $data['rows'],
            'college' => College::query()->first(),
            'scopeDescription' => $reports->scopeDescription($user, $filters['department_id'] ?? null),
            'generatedBy' => $user,
        ]);

        return $pdf->download("{$type->value}-report.pdf");
    }

    public function excel(Request $request, ReportType $type, ReportService $reports): BinaryFileResponse
    {
        $user = $request->user();
        $filters = $this->filtersFrom($request);
        $data = $reports->generate($type, $user, $filters);

        return Excel::download(new ReportExport($data['headings'], $data['rows']), "{$type->value}-report.xlsx");
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFrom(Request $request): array
    {
        return [
            'semester_id' => $request->integer('semester_id') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
        ];
    }

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    private function semesterOptions()
    {
        return Semester::query()
            ->with('academicYear:id,start_year,end_year')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Semester $semester) => [
                'id' => $semester->id,
                'label' => "{$semester->academicYear->start_year}-{$semester->academicYear->end_year} {$semester->term->label()}",
            ]);
    }
}
