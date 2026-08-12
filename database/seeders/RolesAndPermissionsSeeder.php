<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Every permission in the system, across all phases built so far. Mirrors
     * ROLE_PERMISSIONS.md, which is the source of truth for intent. The
     * Administrator role always gets the full list (see run()).
     */
    private const PERMISSIONS = [
        // Phase 1
        'users.manage',
        'departments.manage',
        'departments.view',
        'programs.manage',
        'programs.view',
        'academic-terms.manage',
        'academic-terms.view',
        'audit-logs.view',
        // Phase 2
        'courses.manage',
        'courses.view',
        'curricula.manage',
        'curricula.view',
        'students.manage',
        'students.view',
        'students.import',
        'student-documents.manage',
        'student-documents.view',
        'enrollment.manage',
        'enrollment.view',
        'grades.encode',
        'grades.review',
        'grades.view',
        'grades.import',
        // Phase 3
        'progress.view',
        'advising.manage',
        'advising.view',
        // Phase 4
        'graduation.manage',
        'graduation.view',
        'graduation.evaluate',
        // Phase 5
        'faculty-profiles.manage',
        'faculty-profiles.view',
        // Phase 6
        'operations.manage',
        'operations.view',
        // Phase 7 — shared by Research (7A) and Extension (7B): the spec
        // gives both an identical permission row ("Submit research/
        // extension records"), so one pair covers both rather than minting
        // near-duplicate pairs per sub-phase.
        'research-extension.manage',
        'research-extension.view',
        // Phase 8B — "Generate authorized college reports": Admin/Dean get
        // college-level access, Department Head gets department-level
        // (enforced by ReportService's query scoping, not a second
        // permission tier), Faculty gets none at all, per the spec's
        // explicit permission row.
        'reports.view',
        // Phase 8C — "Perform backup and restore": Admin-only per the spec's
        // explicit permission row (⛔ for every other role). Not added to any
        // entry in ROLE_PERMISSIONS below — same pattern as `users.manage`.
        'backups.manage',
        // Post-launch: system logo upload — Admin-only, same shape and
        // reasoning as `backups.manage` (no per-record ownership to
        // adjudicate, so no dedicated Policy either — see
        // BrandingController).
        'branding.manage',
        // Post-launch: hybrid online/LAN/offline sync (Phase 1 — Foundation).
        // Admin-only, same shape as `backups.manage`/`branding.manage` — the
        // Administrator's PC is the LAN sync hub, and device registration/
        // Sync Center access has no per-record ownership to adjudicate.
        'sync.manage',
    ];

    /**
     * Non-administrator role grants. Administrator always receives every
     * permission in PERMISSIONS (see run()), so it is not repeated here.
     */
    private const ROLE_PERMISSIONS = [
        RoleName::Dean->value => [
            'departments.view', 'programs.view', 'academic-terms.view', 'audit-logs.view',
            'courses.view', 'curricula.view', 'students.view', 'enrollment.view', 'grades.view',
            'progress.view', 'advising.view', 'graduation.view',
            'faculty-profiles.view',
            'operations.view',
            'research-extension.view',
            'reports.view',
        ],
        RoleName::DepartmentHead->value => [
            'departments.view', 'programs.view', 'academic-terms.view',
            'courses.view', 'curricula.view',
            'students.manage', 'students.view',
            'student-documents.view',
            'enrollment.manage', 'enrollment.view',
            'grades.review', 'grades.view',
            'progress.view', 'advising.manage', 'advising.view',
            'graduation.view',
            'faculty-profiles.view',
            'operations.manage', 'operations.view',
            // View-only per the spec row (👁, own dept.) — unlike
            // operations.manage above, Department Head does NOT get
            // research-extension.manage. See ResearchProjectPolicy.
            'research-extension.view',
            // Department-level only — ReportService scopes every query to
            // the Department Head's own department. Faculty gets none.
            'reports.view',
        ],
        RoleName::Faculty->value => [
            'departments.view', 'programs.view', 'academic-terms.view',
            'courses.view', 'curricula.view',
            'students.view',
            'enrollment.view',
            'grades.encode', 'grades.view', 'grades.import',
            'progress.view', 'advising.manage', 'advising.view',
            'graduation.view', 'graduation.evaluate',
            'faculty-profiles.view',
            'operations.view',
            // Faculty's manage-own (🟡) capability is granted at the Policy
            // layer via project leadership, not a blanket .manage grant —
            // see ResearchProjectPolicy::create()/update().
            'research-extension.view',
        ],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $administrator = Role::firstOrCreate(['name' => RoleName::Administrator->value, 'guard_name' => 'web']);
        $administrator->syncPermissions(self::PERMISSIONS);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
