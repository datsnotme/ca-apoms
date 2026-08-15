import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import AdvisingRecords from './AdvisingRecords';
import InterventionFollowups from './InterventionFollowups';

interface ChecklistRow {
    curriculum_course_id: number;
    course: { id: number; code: string; title: string };
    year_level: number;
    semester: string;
    is_required: boolean;
    units: number;
    status: string;
    is_deficiency: boolean;
    grade: string | null;
}

interface DeficiencyRow {
    id: number;
    deficiency_type: string;
    detected_at: string;
    curriculum_course: { course: { code: string; title: string } };
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    completed: 'success',
    failed: 'danger',
    incomplete: 'warning',
    in_progress: 'info',
    dropped: 'danger',
    not_taken: 'neutral',
    pending: 'neutral',
};

function groupByYearSemester(rows: ChecklistRow[]) {
    const groups = new Map<string, ChecklistRow[]>();
    for (const row of rows) {
        const key = `${row.year_level}|${row.semester}`;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key)!.push(row);
    }
    return [...groups.entries()].sort(([a], [b]) => a.localeCompare(b, undefined, { numeric: true }));
}

const YEAR_LABELS: Record<number, string> = { 1: '1st Year', 2: '2nd Year', 3: '3rd Year', 4: '4th Year', 5: '5th Year', 6: '6th Year' };

interface AdvisingRecordRow {
    id: number;
    session_date: string;
    summary: string;
    recommendations: string | null;
    follow_up_required: boolean;
    adviser: { name: string } | null;
    semester: { term: string; academic_year: { start_year: number; end_year: number } } | null;
}

interface AlertRow {
    id: number;
    alert_type: string;
    severity: 'warning' | 'critical';
    message: string;
    acknowledged_at: string | null;
}

interface FollowupRow {
    id: number;
    description: string;
    status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    due_date: string | null;
    notes: string | null;
    assigned_to: { name: string } | null;
    completed_by: { name: string } | null;
}

export default function Show({
    student,
    checklist,
    gwa,
    completionPercentage,
    deficiencies,
    alerts,
    advisingRecords,
    semesters,
    canManageAdvising,
    interventionFollowups,
    advisers,
    canManageFollowups,
}: {
    student: {
        id: number;
        student_number: string;
        name: string;
        department: { name: string } | null;
        program: { name: string } | null;
        curriculum: { name: string } | null;
        year_level: { label: string } | null;
        adviser: { name: string } | null;
    };
    checklist: ChecklistRow[];
    gwa: number | null;
    completionPercentage: number;
    deficiencies: DeficiencyRow[];
    alerts: AlertRow[];
    advisingRecords: AdvisingRecordRow[];
    semesters: { id: number; label: string }[];
    canManageAdvising: boolean;
    interventionFollowups: FollowupRow[];
    advisers: { id: number; name: string }[];
    canManageFollowups: boolean;
}) {
    const groups = groupByYearSemester(checklist);

    function acknowledge(alertId: number) {
        router.patch(route('students.alerts.acknowledge', [student.id, alertId]), {}, { preserveScroll: true });
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Academic Progress</h1>}>
            <Head title={`Progress — ${student.name}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader
                        title={student.name}
                        description={`${student.student_number} · ${student.program?.name ?? '—'} · ${student.curriculum?.name ?? 'No curriculum assigned'}`}
                        actions={
                            <div className="flex items-center gap-4 print:hidden">
                                <button
                                    type="button"
                                    onClick={() => window.print()}
                                    className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                >
                                    Print Report
                                </button>
                                <Link href={route('students.edit', student.id)} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                                    Back to Profile
                                </Link>
                            </div>
                        }
                    />
                    <div className="grid grid-cols-2 gap-4 px-5 py-4 text-center sm:grid-cols-4">
                        <div>
                            <p className="text-2xl font-semibold text-slate-900">{student.year_level?.label ?? '—'}</p>
                            <p className="text-xs uppercase text-slate-900">Year Level</p>
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-brand-700">{completionPercentage}%</p>
                            <p className="text-xs uppercase text-slate-900">Curriculum Complete</p>
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-slate-900">{gwa ?? '—'}</p>
                            <p className="text-xs uppercase text-slate-900">GWA</p>
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-red-600">{deficiencies.length}</p>
                            <p className="text-xs uppercase text-slate-900">Deficiencies</p>
                        </div>
                    </div>
                    <div className="border-t border-slate-200 px-5 py-3 text-sm text-slate-900">
                        Adviser: {student.adviser?.name ?? 'Unassigned'} · Department: {student.department?.name ?? '—'}
                    </div>
                </Card>

                {alerts.length > 0 && (
                    <Card>
                        <CardHeader title="Active Alerts" description="Automatically evaluated risk indicators." />
                        <CardContent className="flex flex-col gap-3">
                            {alerts.map((a) => (
                                <div key={a.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border border-slate-200 p-3">
                                    <div className="flex items-center gap-2">
                                        <Badge variant={a.severity === 'critical' ? 'danger' : 'warning'}>
                                            {a.alert_type.replace(/_/g, ' ')}
                                        </Badge>
                                        <span className="text-sm text-slate-700">{a.message}</span>
                                    </div>
                                    {a.acknowledged_at ? (
                                        <span className="text-xs text-slate-900">Acknowledged</span>
                                    ) : (
                                        <button
                                            type="button"
                                            onClick={() => acknowledge(a.id)}
                                            className="text-sm font-medium text-brand-700 hover:text-brand-900 print:hidden"
                                        >
                                            Acknowledge
                                        </button>
                                    )}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {deficiencies.length > 0 && (
                    <Card>
                        <CardHeader title="Active Deficiencies" description="Required courses whose expected year level has already passed." />
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                    <tr>
                                        <th className="px-5 py-2.5">Course</th>
                                        <th className="px-5 py-2.5">Type</th>
                                        <th className="px-5 py-2.5">Detected</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {deficiencies.map((d) => (
                                        <tr key={d.id}>
                                            <td className="px-5 py-2.5">
                                                {d.curriculum_course.course.code} — {d.curriculum_course.course.title}
                                            </td>
                                            <td className="px-5 py-2.5">
                                                <Badge variant="danger">{d.deficiency_type.replace(/_/g, ' ')}</Badge>
                                            </td>
                                            <td className="px-5 py-2.5">{d.detected_at.slice(0, 10)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                <Card>
                    <CardHeader title="Curriculum Checklist" />
                    {checklist.length === 0 ? (
                        <EmptyState title="No curriculum assigned or the curriculum has no courses yet" />
                    ) : (
                        <CardContent className="flex flex-col gap-6">
                            {groups.map(([key, rows]) => {
                                const [yearLevel, semester] = key.split('|');
                                return (
                                    <div key={key}>
                                        <h3 className="mb-2 text-sm font-semibold text-slate-700">
                                            {YEAR_LABELS[Number(yearLevel)] ?? `Year ${yearLevel}`} — {semester}
                                        </h3>
                                        <div className="overflow-x-auto">
                                            <table className="w-full text-sm">
                                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                                    <tr>
                                                        <th className="px-5 py-2.5">Course</th>
                                                        <th className="px-5 py-2.5">Units</th>
                                                        <th className="px-5 py-2.5">Required</th>
                                                        <th className="px-5 py-2.5">Grade</th>
                                                        <th className="px-5 py-2.5">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100">
                                                    {rows.map((row) => (
                                                        <tr key={row.curriculum_course_id} className={row.is_deficiency ? 'bg-red-50' : undefined}>
                                                            <td className="px-5 py-2.5">
                                                                {row.course.code} — {row.course.title}
                                                            </td>
                                                            <td className="px-5 py-2.5">{row.units}</td>
                                                            <td className="px-5 py-2.5">{row.is_required ? 'Yes' : 'No'}</td>
                                                            <td className="px-5 py-2.5">{row.grade ?? '—'}</td>
                                                            <td className="px-5 py-2.5">
                                                                <Badge variant={STATUS_VARIANT[row.status] ?? 'neutral'}>
                                                                    {row.status.replace(/_/g, ' ')}
                                                                </Badge>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                );
                            })}
                        </CardContent>
                    )}
                </Card>

                <Card>
                    <CardHeader title="Advising Records" description="Session notes, recommendations, and follow-up flags." />
                    <CardContent>
                        <AdvisingRecords
                            studentId={student.id}
                            records={advisingRecords}
                            semesters={semesters}
                            canManage={canManageAdvising}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Intervention Follow-ups" description="Concrete action items tracked through to completion." />
                    <CardContent>
                        <InterventionFollowups
                            studentId={student.id}
                            followups={interventionFollowups}
                            advisers={advisers}
                            canManage={canManageFollowups}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
