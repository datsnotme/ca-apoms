<?php

namespace App\Http\Controllers\Faculty;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\User;
use App\Services\FacultyWorkloadService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FacultyWorkloadController extends Controller
{
    public function __construct(private readonly FacultyWorkloadService $workload) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->can('faculty-profiles.view'), 403);

        $semesterId = $this->resolveSemesterId($request);

        if ($user->hasRole(RoleName::Faculty->value)) {
            $sections = $this->workload->sectionsFor($user, $semesterId);

            return Inertia::render('FacultyWorkload/Index', [
                'mode' => 'own',
                'faculty' => $user->only(['id', 'name']),
                'sections' => $sections,
                'totalUnits' => $this->workload->totalUnits($sections),
                'semesters' => $this->semesterOptions(),
                'filters' => ['semester_id' => $semesterId],
            ]);
        }

        $facultyMembers = User::query()
            ->role([RoleName::Faculty->value, RoleName::DepartmentHead->value])
            ->when(
                $user->hasRole(RoleName::DepartmentHead->value),
                fn ($q) => $q->where('department_id', $user->department_id)
            )
            ->with('department:id,name')
            ->orderBy('surname')
            ->get();

        return Inertia::render('FacultyWorkload/Index', [
            'mode' => 'dashboard',
            'workloads' => $this->workload->summaryFor($facultyMembers, $semesterId),
            'semesters' => $this->semesterOptions(),
            'filters' => ['semester_id' => $semesterId],
        ]);
    }

    public function show(Request $request, User $user): Response
    {
        abort_unless($user->hasRole([RoleName::Faculty->value, RoleName::DepartmentHead->value]), 404);

        $viewer = $request->user();
        abort_unless($viewer->can('faculty-profiles.view'), 403);

        if ($viewer->hasRole(RoleName::Faculty->value) && $viewer->id !== $user->id) {
            abort(403);
        }

        if ($viewer->hasRole(RoleName::DepartmentHead->value) && $viewer->department_id !== $user->department_id) {
            abort(403);
        }

        $semesterId = $this->resolveSemesterId($request);
        $sections = $this->workload->sectionsFor($user, $semesterId);

        return Inertia::render('FacultyWorkload/Show', [
            'faculty' => $user->only(['id', 'name']),
            'sections' => $sections,
            'totalUnits' => $this->workload->totalUnits($sections),
            'semesters' => $this->semesterOptions(),
            'filters' => ['semester_id' => $semesterId],
        ]);
    }

    private function resolveSemesterId(Request $request): ?int
    {
        if ($request->filled('semester_id')) {
            return $request->integer('semester_id');
        }

        return Semester::where('is_current', true)->value('id')
            ?? Semester::query()->orderByDesc('id')->value('id');
    }

    private function semesterOptions()
    {
        return Semester::query()
            ->with('academicYear:id,start_year,end_year')
            ->orderByDesc('id')
            ->get(['id', 'academic_year_id', 'term'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'label' => "{$s->academicYear->start_year}-{$s->academicYear->end_year} {$s->term->label()}",
            ]);
    }
}
