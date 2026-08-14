<?php

namespace App\Http\Controllers;

use App\Enums\EquipmentStatus;
use App\Enums\ExtensionProjectStatus;
use App\Enums\GraduationCandidateStatus;
use App\Enums\InternalRequestStatus;
use App\Enums\ResearchProjectStatus;
use App\Enums\RoleName;
use App\Enums\StudentClassification;
use App\Enums\StudentStatus;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentBorrowing;
use App\Models\ExtensionProject;
use App\Models\GraduationCandidate;
use App\Models\InternalRequest;
use App\Models\Meeting;
use App\Models\Program;
use App\Models\ProgressAlert;
use App\Models\ResearchProject;
use App\Models\Semester;
use App\Models\Student;
use App\Models\Task;
use App\Models\User;
use App\Services\ProgressAlertService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Role-specific analytics (Phase 8A). Every number here is computed live
 * from data the earlier phases already produce — no new tables, no
 * persisted snapshots. See ASSUMPTIONS.md for why: a GWA-trend-over-time
 * chart would need point-in-time history and was deferred for exactly that
 * reason back in Phase 3; this sub-phase's charts are current-state
 * distributions, which don't need it.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly ProgressAlertService $alerts) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return match (true) {
            $user->hasRole(RoleName::Administrator->value) => $this->administrator(),
            $user->hasRole(RoleName::Dean->value) => $this->dean(),
            $user->hasRole(RoleName::DepartmentHead->value) => $this->departmentHead($user),
            default => $this->faculty($user),
        };
    }

    private function administrator(): Response
    {
        // Sync once, college-wide, before either the stat card or the chart
        // below reads progress_alerts — otherwise the two can each see a
        // different snapshot depending on read order, and both can drift
        // from what AtRiskController::index() shows on the At-Risk page.
        $this->alerts->syncAlertsForScope();

        return Inertia::render('Dashboard', [
            'role' => 'administrator',
            'departmentName' => null,
            'statCards' => [
                ['label' => 'Departments', 'value' => Department::count(), 'href' => route('departments.index')],
                ['label' => 'Programs', 'value' => Program::count(), 'href' => route('programs.index')],
                ['label' => 'Active Users', 'value' => User::where('status', 'active')->count(), 'href' => route('users.index')],
                ['label' => 'Active Students', 'value' => Student::where('status', StudentStatus::Active->value)->count(), 'href' => route('students.index')],
                ['label' => 'Active Alerts', 'value' => $this->activeAlertCount(), 'href' => route('academic-progress.index')],
                ['label' => 'Pending Requests', 'value' => InternalRequest::where('status', InternalRequestStatus::Pending->value)->count(), 'href' => route('internal-requests.index')],
                ['label' => 'Upcoming Meetings', 'value' => Meeting::upcoming()->count(), 'href' => route('meetings.index')],
                ['label' => 'Equipment Outstanding', 'value' => EquipmentBorrowing::whereDoesntHave('return')->count(), 'href' => route('equipment.accountability')],
            ],
            'charts' => [
                'studentsByStatus' => $this->statusBreakdown(Student::query(), StudentStatus::class, 'status'),
                'studentsByClassification' => $this->classificationBreakdown(Student::query()),
                'graduationPipeline' => $this->statusBreakdown(GraduationCandidate::query(), GraduationCandidateStatus::class, 'status'),
                'atRiskByDepartment' => $this->atRiskByDepartment(),
                'equipmentByStatus' => $this->statusBreakdown(Equipment::query(), EquipmentStatus::class, 'status'),
            ],
        ]);
    }

    private function dean(): Response
    {
        $this->alerts->syncAlertsForScope();

        return Inertia::render('Dashboard', [
            'role' => 'dean',
            'departmentName' => null,
            'statCards' => [
                ['label' => 'Active Students', 'value' => Student::where('status', StudentStatus::Active->value)->count(), 'href' => route('students.index')],
                ['label' => 'Active Alerts', 'value' => $this->activeAlertCount(), 'href' => route('academic-progress.index')],
                ['label' => 'Graduation Candidates', 'value' => GraduationCandidate::whereNotIn('status', [GraduationCandidateStatus::Graduated->value, GraduationCandidateStatus::Rejected->value])->count(), 'href' => route('graduation-candidates.index')],
                ['label' => 'Upcoming Meetings', 'value' => Meeting::upcoming()->count(), 'href' => route('meetings.index')],
            ],
            'charts' => [
                'studentsByStatus' => $this->statusBreakdown(Student::query(), StudentStatus::class, 'status'),
                'studentsByClassification' => $this->classificationBreakdown(Student::query()),
                'graduationPipeline' => $this->statusBreakdown(GraduationCandidate::query(), GraduationCandidateStatus::class, 'status'),
                'atRiskByDepartment' => $this->atRiskByDepartment(),
            ],
        ]);
    }

    private function departmentHead(User $user): Response
    {
        $departmentId = $user->department_id;
        $this->alerts->syncAlertsForScope($departmentId);

        return Inertia::render('Dashboard', [
            'role' => 'department-head',
            'departmentName' => $user->department?->name,
            'statCards' => [
                ['label' => 'Active Students', 'value' => Student::where('department_id', $departmentId)->where('status', StudentStatus::Active->value)->count(), 'href' => route('students.index')],
                ['label' => 'Active Alerts', 'value' => $this->activeAlertCount($departmentId), 'href' => route('academic-progress.index')],
                ['label' => 'Graduation Candidates', 'value' => GraduationCandidate::visibleTo($user)->whereNotIn('status', [GraduationCandidateStatus::Graduated->value, GraduationCandidateStatus::Rejected->value])->count(), 'href' => route('graduation-candidates.index')],
                ['label' => 'Pending Requests', 'value' => InternalRequest::where('department_id', $departmentId)->where('status', InternalRequestStatus::Pending->value)->count(), 'href' => route('internal-requests.index')],
                ['label' => 'Upcoming Meetings', 'value' => Meeting::upcoming()->visibleTo($user)->count(), 'href' => route('meetings.index')],
                ['label' => 'Research & Extension', 'value' => $this->activeProjectCount($departmentId), 'href' => route('research-projects.index')],
                ['label' => 'Equipment Outstanding', 'value' => EquipmentBorrowing::whereDoesntHave('return')->whereHas('equipment', fn (Builder $q) => $q->visibleTo($user)->where('department_id', $departmentId))->count(), 'href' => route('equipment.accountability')],
            ],
            'charts' => [
                'studentsByStatus' => $this->statusBreakdown(Student::where('department_id', $departmentId), StudentStatus::class, 'status'),
                'studentsByClassification' => $this->classificationBreakdown(Student::where('department_id', $departmentId)),
                'graduationPipeline' => $this->statusBreakdown(GraduationCandidate::visibleTo($user), GraduationCandidateStatus::class, 'status'),
            ],
        ]);
    }

    private function faculty(User $user): Response
    {
        $this->alerts->syncAlertsForScope(null, $user->id);

        $currentSemesterId = Semester::where('is_current', true)->value('id');

        $mySectionsCount = $currentSemesterId
            ? $user->facultyAssignments()->whereHas('classSection', fn (Builder $q) => $q->where('semester_id', $currentSemesterId))->count()
            : 0;

        return Inertia::render('Dashboard', [
            'role' => 'faculty',
            'departmentName' => $user->department?->name,
            'statCards' => [
                ['label' => 'My Class Sections (this semester)', 'value' => $mySectionsCount, 'href' => route('class-sections.index')],
                ['label' => 'My Advisees', 'value' => Student::where('adviser_id', $user->id)->count(), 'href' => route('advising.index')],
                ['label' => 'My Advisees At Risk', 'value' => $this->activeAlertCount(null, $user->id), 'href' => route('academic-progress.index')],
                ['label' => 'My Open Tasks', 'value' => Task::where('assigned_to', $user->id)->whereNotIn('status', ['completed', 'cancelled'])->count(), 'href' => route('tasks.index')],
                ['label' => 'My Research & Extension', 'value' => $this->myProjectCount($user), 'href' => route('research-projects.index')],
                ['label' => 'Upcoming Meetings', 'value' => Meeting::upcoming()->visibleTo($user)->count(), 'href' => route('meetings.index')],
            ],
            'charts' => [],
        ]);
    }

    /**
     * Groups a query by a status column and labels every enum case,
     * including zero-count ones, so charts don't silently drop categories.
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function statusBreakdown(Builder $query, string $enumClass, string $column): array
    {
        $counts = (clone $query)->selectRaw("{$column}, count(*) as aggregate")->groupBy($column)->pluck('aggregate', $column);

        $labels = [];
        $values = [];

        foreach ($enumClass::cases() as $case) {
            $labels[] = $case->label();
            $values[] = (int) ($counts[$case->value] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Regular and Irregular each get their own slice; every other
     * classification (Transferee, Shiftee, Returning, Graduating) buckets
     * into "Others" — a 3-slice summary for the dashboard pie chart,
     * unlike statusBreakdown()'s one-slice-per-enum-case approach.
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function classificationBreakdown(Builder $query): array
    {
        $counts = (clone $query)
            ->selectRaw('classification, count(*) as aggregate')
            ->groupBy('classification')
            ->pluck('aggregate', 'classification');

        $others = 0;
        foreach (StudentClassification::cases() as $case) {
            if (! in_array($case, [StudentClassification::Regular, StudentClassification::Irregular], true)) {
                $others += (int) ($counts[$case->value] ?? 0);
            }
        }

        return [
            'labels' => ['Regular', 'Irregular', 'Others'],
            'values' => [
                (int) ($counts[StudentClassification::Regular->value] ?? 0),
                (int) ($counts[StudentClassification::Irregular->value] ?? 0),
                $others,
            ],
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function atRiskByDepartment(): array
    {
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);

        $atRiskCounts = Student::query()
            ->select('department_id', DB::raw('count(distinct students.id) as aggregate'))
            ->whereHas('alerts', fn (Builder $q) => $q->whereNull('resolved_at'))
            ->groupBy('department_id')
            ->pluck('aggregate', 'department_id');

        return [
            'labels' => $departments->pluck('name')->all(),
            'values' => $departments->map(fn ($d) => (int) ($atRiskCounts[$d->id] ?? 0))->all(),
        ];
    }

    private function activeAlertCount(?int $departmentId = null, ?int $adviserId = null): int
    {
        // Always join through the student relation, even with no
        // department/adviser filter to apply — Student's default query
        // scope (SoftDeletes) then excludes archived students automatically,
        // matching AtRiskController::index()'s listing (which queries from
        // the Student side and inherits that scope for free). Counting
        // ProgressAlert rows directly, unjoined, was the actual bug: an
        // archived student's old alert rows are never revisited by any sync
        // loop (they're excluded from every "students in scope" query too),
        // so they sat unresolved forever and inflated this count while the
        // At-Risk listing correctly never showed them.
        return ProgressAlert::query()
            ->whereNull('resolved_at')
            ->whereHas('student', fn (Builder $s) => $s
                ->when($departmentId, fn (Builder $s2) => $s2->where('department_id', $departmentId))
                ->when($adviserId, fn (Builder $s2) => $s2->where('adviser_id', $adviserId)))
            ->count();
    }

    private function activeProjectCount(int $departmentId): int
    {
        $research = ResearchProject::where('department_id', $departmentId)->where('status', ResearchProjectStatus::Ongoing->value)->count();
        $extension = ExtensionProject::where('department_id', $departmentId)->where('status', ExtensionProjectStatus::Ongoing->value)->count();

        return $research + $extension;
    }

    private function myProjectCount(User $user): int
    {
        $research = ResearchProject::whereHas('members', fn (Builder $q) => $q->where('user_id', $user->id))->count();
        $extension = ExtensionProject::whereHas('members', fn (Builder $q) => $q->where('user_id', $user->id))->count();

        return $research + $extension;
    }
}
