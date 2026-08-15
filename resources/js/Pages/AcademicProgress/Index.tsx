import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import { Paginated } from '@/types';

interface AlertRow {
    id: number;
    alert_type: string;
    severity: 'warning' | 'critical';
    message: string;
    acknowledged_at: string | null;
}

interface AtRiskStudentRow {
    id: number;
    student_number: string;
    name: string;
    department: { name: string } | null;
    program: { name: string } | null;
    adviser: { name: string } | null;
    alerts: AlertRow[];
}

export default function Index({ students }: { students: Paginated<AtRiskStudentRow> }) {
    function acknowledge(studentId: number, alertId: number) {
        router.patch(route('students.alerts.acknowledge', [studentId, alertId]), {}, { preserveScroll: true });
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Academic Progress</h1>}>
            <Head title="Academic Progress" />

            <Card>
                <CardHeader
                    title="At-Risk Students"
                    description="Students with multiple deficiencies, a low GWA, or a concerning enrollment status."
                />

                {students.data.length === 0 ? (
                    <EmptyState title="No at-risk students" description="Nobody in your scope currently has an active alert." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    <th className="px-5 py-2.5">Student No.</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Department</th>
                                    <th className="px-5 py-2.5">Adviser</th>
                                    <th className="px-5 py-2.5">Active Alerts</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {students.data.map((s) => (
                                    <tr key={s.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5 font-mono text-xs">{s.student_number}</td>
                                        <td className="px-5 py-2.5">
                                            <Link href={route('students.progress.show', s.id)} className="hover:underline">
                                                {s.name}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-2.5">{s.department?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.adviser?.name ?? 'Unassigned'}</td>
                                        <td className="px-5 py-2.5">
                                            <div className="flex flex-col gap-1.5">
                                                {s.alerts.map((a) => (
                                                    <div key={a.id} className="flex items-center gap-2">
                                                        <Badge variant={a.severity === 'critical' ? 'danger' : 'warning'}>
                                                            {a.alert_type.replace(/_/g, ' ')}
                                                        </Badge>
                                                        <span className="text-xs text-slate-900">{a.message}</span>
                                                        {a.acknowledged_at ? (
                                                            <span className="text-xs text-slate-900">Acknowledged</span>
                                                        ) : (
                                                            <button
                                                                type="button"
                                                                onClick={() => acknowledge(s.id, a.id)}
                                                                className="text-xs font-medium text-brand-700 hover:text-brand-900"
                                                            >
                                                                Acknowledge
                                                            </button>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={students.links} from={students.from} to={students.to} total={students.total} />
            </Card>
        </AppLayout>
    );
}
