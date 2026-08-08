<?php

namespace App\Services;

use App\Enums\ReportType;
use App\Enums\RoleName;
use App\Enums\StudentGradeStatus;
use App\Models\Department;
use App\Models\GraduationCandidate;
use App\Models\ProgressAlert;
use App\Models\Semester;
use App\Models\StudentEnrollment;
use App\Models\StudentGrade;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * One method per canned report (Phase 8B). Every query is computed on
 * demand — no report-caching table — the same "compute on demand"
 * discipline already applied to dashboards (Phase 8A), faculty workload
 * (Phase 5D), and equipment accountability (Phase 7D). Both the Inertia
 * preview and the PDF/Excel export call the same method, so what a user
 * sees on screen and what they download can never drift apart.
 *
 * @phpstan-type ReportPayload array{headings: array<int, string>, rows: array<int, array<int, string>>}
 */
class ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return ReportPayload
     */
    public function generate(ReportType $type, User $user, array $filters): array
    {
        return match ($type) {
            ReportType::Enrollment => $this->enrollment($user, $filters),
            ReportType::Grades => $this->grades($user, $filters),
            ReportType::AtRisk => $this->atRisk($user),
            ReportType::FacultyWorkload => $this->facultyWorkload($user, $filters),
            ReportType::GraduationPipeline => $this->graduationPipeline($user, $filters),
        };
    }

    private function departmentIdFor(User $user, array $filters): ?int
    {
        if ($user->hasRole(RoleName::DepartmentHead->value)) {
            return $user->department_id;
        }

        return isset($filters['department_id']) && $filters['department_id'] !== null
            ? (int) $filters['department_id']
            : null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function enrollment(User $user, array $filters): array
    {
        $departmentId = $this->departmentIdFor($user, $filters);
        $semesterId = (int) ($filters['semester_id'] ?? Semester::where('is_current', true)->value('id'));

        $enrollments = StudentEnrollment::query()
            ->where('semester_id', $semesterId)
            ->whereHas('student', fn (Builder $q) => $q->when($departmentId, fn (Builder $q2) => $q2->where('department_id', $departmentId)))
            ->with(['student.program:id,name,department_id', 'student.program.department:id,name'])
            ->get();

        $rows = $enrollments
            ->groupBy(fn ($e) => $e->student->program?->id ?? 0)
            ->map(function (Collection $group) {
                $program = $group->first()->student->program;

                return [
                    $program?->name ?? 'Unassigned',
                    $program?->department?->name ?? '—',
                    (string) $group->count(),
                ];
            })
            ->sortBy(fn ($row) => $row[0])
            ->values()
            ->all();

        return ['headings' => ['Program', 'Department', 'Enrolled Students'], 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function grades(User $user, array $filters): array
    {
        $departmentId = $this->departmentIdFor($user, $filters);
        $semesterId = (int) ($filters['semester_id'] ?? Semester::where('is_current', true)->value('id'));

        $counts = StudentGrade::query()
            ->whereIn('status', [StudentGradeStatus::Finalized->value, StudentGradeStatus::Locked->value])
            ->whereNotNull('grade')
            ->whereHas(
                'enrollmentCourse.studentEnrollment',
                fn (Builder $q) => $q->where('semester_id', $semesterId)
                    ->when($departmentId, fn (Builder $q2) => $q2->whereHas('student', fn (Builder $q3) => $q3->where('department_id', $departmentId)))
            )
            ->selectRaw('grade, count(*) as aggregate')
            ->groupBy('grade')
            ->orderBy('grade')
            ->pluck('aggregate', 'grade');

        $total = $counts->sum();

        $rows = $counts->map(fn ($count, $grade) => [
            $grade,
            (string) $count,
            $total > 0 ? number_format($count / $total * 100, 1).'%' : '0.0%',
        ])->values()->all();

        return ['headings' => ['Grade', 'Count', 'Share'], 'rows' => $rows];
    }

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function atRisk(User $user): array
    {
        $departmentId = $this->departmentIdFor($user, []);

        $alerts = ProgressAlert::query()
            ->whereNull('resolved_at')
            ->when($departmentId, fn (Builder $q) => $q->whereHas('student', fn (Builder $s) => $s->where('department_id', $departmentId)))
            ->with(['student:id,student_number,surname,first_name,middle_name,department_id', 'student.department:id,name'])
            ->orderByDesc('created_at')
            ->get();

        $rows = $alerts->map(fn (ProgressAlert $alert) => [
            $alert->student->student_number,
            $alert->student->name,
            $alert->student->department?->name ?? '—',
            $alert->alert_type->label(),
            $alert->severity->label(),
            $alert->created_at->format('Y-m-d'),
            $alert->acknowledged_at ? 'Yes' : 'No',
        ])->all();

        return ['headings' => ['Student No.', 'Name', 'Department', 'Alert Type', 'Severity', 'Raised On', 'Acknowledged'], 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function facultyWorkload(User $user, array $filters): array
    {
        $departmentId = $this->departmentIdFor($user, $filters);
        $semesterId = (int) ($filters['semester_id'] ?? Semester::where('is_current', true)->value('id'));

        $facultyMembers = User::role(RoleName::Faculty->value)
            ->when($departmentId, fn (Builder $q) => $q->where('department_id', $departmentId))
            ->with('department:id,name')
            ->orderBy('surname')
            ->get();

        $summary = app(FacultyWorkloadService::class)->summaryFor($facultyMembers, $semesterId);

        $rows = $summary->map(fn (array $row) => [
            $row['name'],
            $row['department'] ?? '—',
            (string) $row['section_count'],
            number_format($row['total_units'], 2),
        ])->all();

        return ['headings' => ['Faculty', 'Department', 'Sections', 'Total Units'], 'rows' => $rows];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{headings: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function graduationPipeline(User $user, array $filters): array
    {
        $semesterId = (int) ($filters['semester_id'] ?? Semester::where('is_current', true)->value('id'));
        $semester = Semester::find($semesterId);

        $candidates = GraduationCandidate::query()
            ->visibleTo($user)
            ->where('academic_year_id', $semester?->academic_year_id)
            ->where('semester_id', $semesterId)
            ->with(['student:id,student_number,surname,first_name,middle_name,department_id,program_id', 'student.department:id,name', 'student.program:id,name'])
            ->orderBy('status')
            ->get();

        $rows = $candidates->map(fn (GraduationCandidate $candidate) => [
            $candidate->student->student_number,
            $candidate->student->name,
            $candidate->student->department?->name ?? '—',
            $candidate->student->program?->name ?? '—',
            $candidate->status->label(),
            $candidate->gwa_snapshot ?? '—',
        ])->all();

        return ['headings' => ['Student No.', 'Name', 'Department', 'Program', 'Status', 'GWA'], 'rows' => $rows];
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    public function departmentOptions(User $user): Collection
    {
        if (! $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])) {
            return collect();
        }

        return Department::query()->orderBy('name')->get(['id', 'name']);
    }

    public function scopeDescription(User $user, ?int $filterDepartmentId): string
    {
        $departmentId = $this->departmentIdFor($user, ['department_id' => $filterDepartmentId]);

        if ($departmentId === null) {
            return 'College-wide';
        }

        return Department::find($departmentId)?->name ?? 'Unknown department';
    }
}
