export interface NavItem {
    label: string;
    routeName?: string;
    permission?: string;
    children?: NavItem[];
    /** Phase this module ships in; undefined means it's built. */
    plannedPhase?: number;
}

/**
 * Full module list per the UI spec (section 8), in the specified order.
 * Items without a `routeName` are not built yet and render as disabled
 * (not clickable) rather than 404ing — see ASSUMPTIONS.md. Every item that
 * *is* clickable is also enforced server-side; this list only controls what
 * is rendered, never what is authorized.
 */
export const NAVIGATION: NavItem[] = [
    { label: 'Dashboard', routeName: 'dashboard' },
    { label: 'Students', routeName: 'students.index', permission: 'students.view' },
    { label: 'Academic Progress', routeName: 'academic-progress.index', permission: 'progress.view' },
    {
        label: 'Curricula and Courses',
        permission: 'courses.view',
        children: [
            { label: 'Curricula', routeName: 'curricula.index', permission: 'curricula.view' },
            { label: 'Courses', routeName: 'courses.index', permission: 'courses.view' },
        ],
    },
    {
        label: 'Enrollment',
        permission: 'enrollment.view',
        children: [
            { label: 'Class Sections', routeName: 'class-sections.index', permission: 'enrollment.view' },
            { label: 'Student Enrollment', routeName: 'enrollments.index', permission: 'enrollment.view' },
        ],
    },
    { label: 'Grades', routeName: 'grades.index', permission: 'grades.view' },
    { label: 'Data Import', routeName: 'imports.index' },
    { label: 'Advising', routeName: 'advising.index', permission: 'advising.view' },
    { label: 'Evaluate Student', routeName: 'students.evaluation.index', permission: 'progress.view' },
    { label: 'Graduating Evaluation', routeName: 'graduation-candidates.index', permission: 'graduation.view' },
    { label: 'Latin Honors Prospects', routeName: 'latin-honors.index', permission: 'graduation.view' },
    { label: 'Faculty Profiles', routeName: 'faculty-profiles.index', permission: 'faculty-profiles.view' },
    { label: 'Faculty Workload', routeName: 'faculty-workload.index', permission: 'faculty-profiles.view' },
    { label: 'Scheduling', plannedPhase: 5 },
    { label: 'Research', routeName: 'research-projects.index', permission: 'research-extension.view' },
    { label: 'Extension Services', routeName: 'extension-projects.index', permission: 'research-extension.view' },
    {
        label: 'College Operations',
        permission: 'operations.view',
        children: [
            { label: 'Announcements', routeName: 'announcements.index', permission: 'operations.view' },
            { label: 'Events', routeName: 'events.index', permission: 'operations.view' },
            { label: 'Meetings', routeName: 'meetings.index', permission: 'operations.view' },
            { label: 'Tasks', routeName: 'tasks.index' },
            { label: 'Internal Requests', routeName: 'internal-requests.index', permission: 'operations.view' },
        ],
    },
    { label: 'Documents', routeName: 'documents.index', permission: 'operations.view' },
    {
        label: 'Facilities and Equipment',
        permission: 'operations.view',
        children: [
            { label: 'Facilities', routeName: 'facilities.index', permission: 'operations.view' },
            { label: 'Equipment', routeName: 'equipment.index', permission: 'operations.view' },
        ],
    },
    { label: 'Reports', routeName: 'reports.index', permission: 'reports.view' },
    { label: 'Notifications', routeName: 'notifications.index' },
    {
        label: 'User Management',
        routeName: 'users.index',
        permission: 'users.manage',
    },
    {
        label: 'Settings',
        permission: 'departments.view',
        children: [
            { label: 'Departments', routeName: 'departments.index', permission: 'departments.view' },
            { label: 'Programs', routeName: 'programs.index', permission: 'programs.view' },
            { label: 'Academic Years', routeName: 'academic-years.index', permission: 'academic-terms.view' },
            { label: 'Graduation Requirements', routeName: 'graduation-requirement-templates.index', permission: 'graduation.view' },
            { label: 'Competency Framework', routeName: 'competency-categories.index', permission: 'graduation.view' },
            { label: 'Document Categories', routeName: 'document-categories.index', permission: 'operations.view' },
            { label: 'System Logo', routeName: 'branding.edit', permission: 'branding.manage' },
        ],
    },
    {
        label: 'Audit Logs',
        routeName: 'audit-logs.index',
        permission: 'audit-logs.view',
    },
    { label: 'Backup and Restore', routeName: 'backups.index', permission: 'backups.manage' },
    {
        label: 'Sync Center',
        routeName: 'sync.index',
        permission: 'sync.manage',
        children: [
            { label: 'Overview', routeName: 'sync.index', permission: 'sync.manage' },
            { label: 'Devices', routeName: 'sync.devices.index', permission: 'sync.manage' },
            { label: 'History', routeName: 'sync.history', permission: 'sync.manage' },
            { label: 'Conflicts', routeName: 'sync.conflicts', permission: 'sync.manage' },
        ],
    },
];
