import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import { Paginated } from '@/types';

interface ClassSectionRow {
    id: number;
    section_label: string;
    course: { code: string; title: string } | null;
    semester: { term: string; academic_year: { start_year: number; end_year: number } } | null;
    grade_submission: { status: string } | null;
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    draft: 'neutral',
    submitted: 'warning',
    returned: 'danger',
    reviewed: 'info',
    finalized: 'success',
};

export default function Index({
    classSections,
    filters,
    semesters,
}: {
    classSections: Paginated<ClassSectionRow>;
    filters: { semester_id?: string };
    semesters: { id: number; label: string }[];
}) {
    const [semesterId, setSemesterId] = useState(filters.semester_id ?? '');

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Grades</h1>}>
            <Head title="Grades" />

            <Card>
                <CardHeader title="Grade Submissions" description="Grade sheets by class section and their workflow status." />

                <div className="border-b border-slate-200 px-5 py-3">
                    <select
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={semesterId}
                        onChange={(e) => {
                            setSemesterId(e.target.value);
                            router.get(route('grades.index'), { semester_id: e.target.value }, { preserveState: true });
                        }}
                    >
                        <option value="">All Semesters</option>
                        {semesters.map((s) => (
                            <option key={s.id} value={s.id}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                </div>

                {classSections.data.length === 0 ? (
                    <EmptyState title="No class sections found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    <th className="px-5 py-2.5">Course</th>
                                    <th className="px-5 py-2.5">Section</th>
                                    <th className="px-5 py-2.5">Semester</th>
                                    <th className="px-5 py-2.5">Submission Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {classSections.data.map((cs) => {
                                    const status = cs.grade_submission?.status ?? 'draft';
                                    return (
                                        <tr key={cs.id} className="hover:bg-slate-50">
                                            <td className="px-5 py-2.5">
                                                <Link href={route('class-sections.grades.show', cs.id)} className="hover:underline">
                                                    {cs.course?.code} — {cs.course?.title}
                                                </Link>
                                            </td>
                                            <td className="px-5 py-2.5 font-mono text-xs">{cs.section_label}</td>
                                            <td className="px-5 py-2.5">
                                                {cs.semester
                                                    ? `${cs.semester.academic_year.start_year}-${cs.semester.academic_year.end_year} ${cs.semester.term}`
                                                    : '—'}
                                            </td>
                                            <td className="px-5 py-2.5">
                                                <Badge variant={STATUS_VARIANT[status] ?? 'neutral'}>{status}</Badge>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={classSections.links} from={classSections.from} to={classSections.to} total={classSections.total} />
            </Card>
        </AppLayout>
    );
}
