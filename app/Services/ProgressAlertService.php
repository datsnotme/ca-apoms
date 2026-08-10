<?php

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Enums\StudentStatus;
use App\Models\ProgressAlert;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Evaluates a fixed set of at-risk rules against a student's current
 * progress and keeps progress_alerts in sync — same "computed on demand,
 * persisted only where a workflow needs it" shape as
 * ProgressComputationService::syncDeficiencies(). An alert needs a
 * persisted row because it can be acknowledged.
 */
class ProgressAlertService
{
    /** Two or more unresolved deficiencies triggers a warning; 4+ is critical. */
    private const DEFICIENCY_WARNING_THRESHOLD = 2;

    private const DEFICIENCY_CRITICAL_THRESHOLD = 4;

    /**
     * GWA is lower-is-better (Philippine 1.00-5.00 scale). 3.00 is the last
     * passing value on the seeded scale; 4.00+ is failing range.
     */
    private const GWA_WARNING_THRESHOLD = 3.00;

    private const GWA_CRITICAL_THRESHOLD = 4.00;

    /** Statuses that warrant an enrollment-status alert, and their severity. */
    private const ENROLLMENT_STATUS_SEVERITY = [
        'on_leave' => AlertSeverity::Warning,
        'inactive' => AlertSeverity::Warning,
        'withdrawn' => AlertSeverity::Critical,
    ];

    public function __construct(private readonly ProgressComputationService $progress) {}

    /**
     * @return array<int, array{type: AlertType, severity: AlertSeverity, message: string}>
     */
    public function evaluate(Student $student): array
    {
        $alerts = [];

        // Uses the eager-loaded count from syncAlertsForScope()'s withCount()
        // when available (the common, batch-evaluated path); falls back to a
        // direct query for a lone evaluate() call outside that scope, e.g.
        // StudentProgressController::show()'s single-student sync. Cast
        // explicitly since withCount()'s aggregate can come back as a
        // numeric string depending on DB driver.
        $deficiencyCount = (int) ($student->unresolved_deficiency_count
            ?? $student->deficiencies()->whereNull('resolved_at')->count());

        if ($deficiencyCount >= self::DEFICIENCY_WARNING_THRESHOLD) {
            $alerts[] = [
                'type' => AlertType::MultipleDeficiencies,
                'severity' => $deficiencyCount >= self::DEFICIENCY_CRITICAL_THRESHOLD ? AlertSeverity::Critical : AlertSeverity::Warning,
                'message' => "{$deficiencyCount} unresolved deficiencies.",
            ];
        }

        $gwa = $this->progress->gwa($student);

        if ($gwa !== null && $gwa >= self::GWA_WARNING_THRESHOLD) {
            $alerts[] = [
                'type' => AlertType::LowGwa,
                'severity' => $gwa >= self::GWA_CRITICAL_THRESHOLD ? AlertSeverity::Critical : AlertSeverity::Warning,
                'message' => "GWA of {$gwa} is at or below the passing threshold.",
            ];
        }

        $statusValue = $student->status instanceof StudentStatus ? $student->status->value : $student->status;

        if (isset(self::ENROLLMENT_STATUS_SEVERITY[$statusValue])) {
            $alerts[] = [
                'type' => AlertType::EnrollmentStatus,
                'severity' => self::ENROLLMENT_STATUS_SEVERITY[$statusValue],
                'message' => 'Student status is '.str_replace('_', ' ', $statusValue).'.',
            ];
        }

        return $alerts;
    }

    public function syncAlerts(Student $student): void
    {
        $triggered = $this->evaluate($student);
        $triggeredTypes = array_map(fn ($a) => $a['type']->value, $triggered);

        DB::transaction(function () use ($student, $triggered, $triggeredTypes) {
            ProgressAlert::where('student_id', $student->id)
                ->whereNotIn('alert_type', $triggeredTypes)
                ->whereNull('resolved_at')
                ->update(['resolved_at' => now()]);

            foreach ($triggered as $alert) {
                $existing = ProgressAlert::where('student_id', $student->id)->where('alert_type', $alert['type']->value)->first();

                // A previously-resolved alert re-triggering is a fresh
                // episode — it needs to be acknowledged again, not inherit
                // the acknowledgment from whatever caused it last time.
                $reopening = $existing && $existing->resolved_at !== null;

                ProgressAlert::updateOrCreate(
                    ['student_id' => $student->id, 'alert_type' => $alert['type']->value],
                    [
                        'severity' => $alert['severity']->value,
                        'message' => $alert['message'],
                        'triggered_at' => now(),
                        'resolved_at' => null,
                        ...($reopening ? ['acknowledged_by' => null, 'acknowledged_at' => null] : []),
                    ]
                );
            }
        });
    }

    /**
     * Re-evaluates every student in the given scope and syncs their alerts,
     * so a subsequent `progress_alerts` query (a Dashboard stat card, the
     * At-Risk page) reflects each student's *current* state rather than
     * whatever was true whenever an alert row was last written. Both
     * `DashboardController::activeAlertCount()` and `AtRiskController::
     * index()` call this with identical scope arguments so the two can never
     * disagree — the discrepancy this method exists to close was a real bug:
     * the Dashboard counted stale unresolved rows while only the At-Risk
     * page actually re-evaluated them on visit.
     */
    public function syncAlertsForScope(?int $departmentId = null, ?int $adviserId = null): void
    {
        $students = Student::query()
            ->when($departmentId, fn (Builder $q) => $q->where('department_id', $departmentId))
            ->when($adviserId, fn (Builder $q) => $q->where('adviser_id', $adviserId))
            ->withCount(['deficiencies as unresolved_deficiency_count' => fn ($q) => $q->whereNull('resolved_at')])
            ->get();

        $this->progress->preloadForStudents($students);

        $students->each(fn (Student $student) => $this->syncAlerts($student));
    }
}
