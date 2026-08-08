import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import { Paginated } from '@/types';

interface AdviseeRow {
    id: number;
    student_number: string;
    name: string;
    department: { name: string } | null;
    program: { name: string } | null;
    adviser: { name: string } | null;
    latest_advising_record: { session_date: string; follow_up_required: boolean } | null;
}

export default function Index({ students, canManage }: { students: Paginated<AdviseeRow>; canManage: boolean }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Advising</h1>}>
            <Head title="Advising" />

            <Card>
                <CardHeader
                    title="My Advisees"
                    description="Students assigned to you, with their most recent advising session."
                />

                {students.data.length === 0 ? (
                    <EmptyState title="No advisees found" description="Students are assigned an adviser from their profile page." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-5 py-2.5">Student No.</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Department</th>
                                    <th className="px-5 py-2.5">Program</th>
                                    <th className="px-5 py-2.5">Adviser</th>
                                    <th className="px-5 py-2.5">Last Session</th>
                                    <th className="px-5 py-2.5"></th>
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
                                        <td className="px-5 py-2.5">{s.program?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.adviser?.name ?? 'Unassigned'}</td>
                                        <td className="px-5 py-2.5">
                                            {s.latest_advising_record ? (
                                                <>
                                                    {s.latest_advising_record.session_date.slice(0, 10)}
                                                    {s.latest_advising_record.follow_up_required && (
                                                        <Badge variant="warning">Follow-up</Badge>
                                                    )}
                                                </>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-5 py-2.5 text-right">
                                            <Link
                                                href={route('students.progress.show', s.id)}
                                                className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                            >
                                                {canManage ? 'Log Session' : 'View'}
                                            </Link>
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
