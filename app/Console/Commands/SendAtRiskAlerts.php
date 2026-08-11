<?php

namespace App\Console\Commands;

use App\Models\ProgressAlert;
use App\Notifications\AtRiskAlertNotification;
use App\Services\ProgressAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Proof-of-concept for the "proactive alerts" idea in ASSUMPTIONS.md /
 * PROJECT_PLAN.md: turns the existing at-risk alert data (computed on
 * page-visit only, per ProgressAlertService) into something that reaches an
 * adviser or department head without anyone having to open the app.
 *
 * Notifies once per open alert episode, not once per run — see the
 * notified_at column/migration and the reopening branch in
 * ProgressAlertService::syncAlerts() for how that's enforced.
 */
class SendAtRiskAlerts extends Command
{
    protected $signature = 'alerts:at-risk';

    protected $description = "Sync at-risk progress alerts college-wide and notify each flagged student's adviser and department head, once per open alert episode.";

    public function __construct(private readonly ProgressAlertService $alerts)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->alerts->syncAlertsForScope();

        // whereHas('student') excludes alerts left behind by a since-archived
        // (soft-deleted) student — Student's default query scope hides them
        // from the belongsTo, so $alert->student would otherwise be null.
        // Same fix as DashboardController::activeAlertCount() applies here.
        $pending = ProgressAlert::query()
            ->whereNull('resolved_at')
            ->whereNull('notified_at')
            ->whereHas('student')
            ->with(['student.adviser', 'student.department.head'])
            ->get();

        foreach ($pending as $alert) {
            $recipients = collect([$alert->student->adviser, $alert->student->department?->head])
                ->filter()
                ->unique('id');

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new AtRiskAlertNotification($alert));
            }

            // Marked notified even with zero recipients — this alert's
            // notification obligation for this episode is discharged
            // either way; without this, an unassigned student's alert
            // would be re-evaluated as "pending" on every single run.
            $alert->update(['notified_at' => now()]);
        }

        $this->info("Notified for {$pending->count()} at-risk alert(s).");

        return self::SUCCESS;
    }
}
