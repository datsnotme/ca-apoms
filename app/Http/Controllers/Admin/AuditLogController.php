<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    /**
     * Admin sees every logged action. The Dean's "audit-logs.view" grant is
     * intentionally curated: account/role management entries (log_name
     * "users") are excluded, matching ROLE_PERMISSIONS.md's "the Dean must
     * not automatically receive unrestricted technical system settings"
     * constraint.
     */
    public function __invoke(Request $request): Response
    {
        $isAdmin = $request->user()->hasRole(RoleName::Administrator->value);

        $activities = Activity::query()
            ->with('causer:id,name')
            ->when(! $isAdmin, fn ($q) => $q->where('log_name', '!=', 'users'))
            ->when($request->string('log_name')->trim()->isNotEmpty(), fn ($q) => $q->where('log_name', $request->string('log_name')))
            ->when($request->string('search')->trim()->isNotEmpty(), function ($q) use ($request) {
                $search = $request->string('search')->trim();
                $q->where('description', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('AuditLogs/Index', [
            'activities' => $activities,
            'filters' => $request->only('search', 'log_name'),
            'logNames' => Activity::query()->distinct()->pluck('log_name')->filter()->values(),
        ]);
    }
}
