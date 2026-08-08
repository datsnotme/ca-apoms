<?php

namespace App\Http\Controllers\Progress;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\ProgressAlert;
use App\Models\Student;
use App\Services\ProgressAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AtRiskController extends Controller
{
    public function __construct(private readonly ProgressAlertService $alerts) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('progress.view'), 403);

        $user = $request->user();
        $isFaculty = $user->hasRole(RoleName::Faculty->value);
        $departmentId = ! $isFaculty && ! $user->hasRole([RoleName::Administrator->value, RoleName::Dean->value])
            ? $user->department_id
            : null;
        $adviserId = $isFaculty ? $user->id : null;

        // Re-evaluated on every visit rather than on a schedule — bounded by
        // the scope above (a Faculty user's advisee list, a department, or
        // the whole college for Admin/Dean). Would move to a queued nightly
        // job if the student count grew by an order of magnitude — see
        // ASSUMPTIONS.md. Shared with DashboardController::activeAlertCount()/
        // atRiskByDepartment() via ProgressAlertService::syncAlertsForScope()
        // so the Dashboard and this page can never disagree about who's
        // currently at risk.
        $this->alerts->syncAlertsForScope($departmentId, $adviserId);

        $students = Student::query()
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->when($adviserId, fn ($q) => $q->where('adviser_id', $adviserId))
            ->whereHas('alerts', fn ($q) => $q->whereNull('resolved_at'))
            ->with(['department:id,name', 'program:id,name', 'adviser:id,name'])
            ->with(['alerts' => fn ($q) => $q->whereNull('resolved_at')->orderByDesc('severity')])
            ->orderBy('surname')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('AcademicProgress/Index', [
            'students' => $students,
        ]);
    }

    public function acknowledge(Request $request, Student $student, ProgressAlert $alert): RedirectResponse
    {
        $this->authorize('viewProgress', $student);

        abort_unless($alert->student_id === $student->id, 404);

        $alert->update(['acknowledged_by' => $request->user()->id, 'acknowledged_at' => now()]);

        return back()->with('success', 'Alert acknowledged.');
    }
}
