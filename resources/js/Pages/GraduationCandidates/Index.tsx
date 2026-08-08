import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import { Paginated } from '@/types';

interface CandidateRow {
    id: number;
    status: string;
    gwa_snapshot: string | null;
    completion_percentage_snapshot: string | null;
    student: {
        student_number: string;
        surname: string;
        first_name: string;
        middle_name: string | null;
        department: { name: string } | null;
        program: { name: string } | null;
    };
    academic_year: { start_year: number; end_year: number };
    semester: { term: string };
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    nominated: 'neutral',
    under_evaluation: 'info',
    recommended: 'warning',
    approved: 'success',
    rejected: 'danger',
    graduated: 'success',
};

export default function Index({
    candidates,
    filters,
    canManage,
    academicYears,
    semesters,
}: {
    candidates: Paginated<CandidateRow>;
    filters: { status?: string };
    canManage: boolean;
    academicYears: { id: number; start_year: number; end_year: number }[];
    semesters: { id: number; label: string }[];
}) {
    const [reportAcademicYearId, setReportAcademicYearId] = useState(String(academicYears[0]?.id ?? ''));
    const [reportSemesterId, setReportSemesterId] = useState(String(semesters[0]?.id ?? ''));

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Graduating Evaluation</h1>}>
            <Head title="Graduating Evaluation" />

            <Card>
                <CardHeader
                    title="Graduation Candidates"
                    description="Students nominated for graduation, tracked from nomination through approval."
                    actions={
                        canManage ? (
                            <Link href={route('graduation-candidates.create')}>
                                <PrimaryButton>Nominate Student</PrimaryButton>
                            </Link>
                        ) : undefined
                    }
                />

                <div className="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-3">
                    <select
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={filters.status ?? ''}
                        onChange={(e) => router.get(route('graduation-candidates.index'), { status: e.target.value }, { preserveState: true })}
                    >
                        <option value="">All Statuses</option>
                        <option value="nominated">Nominated</option>
                        <option value="under_evaluation">Under Evaluation</option>
                        <option value="recommended">Recommended</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="graduated">Graduated</option>
                    </select>

                    {academicYears.length > 0 && semesters.length > 0 && (
                        <div className="flex items-center gap-2">
                            <select
                                className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={reportAcademicYearId}
                                onChange={(e) => setReportAcademicYearId(e.target.value)}
                            >
                                {academicYears.map((y) => (
                                    <option key={y.id} value={y.id}>
                                        {y.start_year}-{y.end_year}
                                    </option>
                                ))}
                            </select>
                            <select
                                className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={reportSemesterId}
                                onChange={(e) => setReportSemesterId(e.target.value)}
                            >
                                {semesters.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.label}
                                    </option>
                                ))}
                            </select>
                            <a
                                href={route('graduation-candidates.report.batch', {
                                    academic_year_id: reportAcademicYearId,
                                    semester_id: reportSemesterId,
                                })}
                                className="text-sm font-medium text-brand-700 hover:text-brand-900"
                            >
                                Download Graduation List (PDF)
                            </a>
                        </div>
                    )}
                </div>

                {candidates.data.length === 0 ? (
                    <EmptyState title="No graduation candidates found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-5 py-2.5">Student No.</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Program</th>
                                    <th className="px-5 py-2.5">Target Term</th>
                                    <th className="px-5 py-2.5">GWA</th>
                                    <th className="px-5 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {candidates.data.map((c) => (
                                    <tr key={c.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5 font-mono text-xs">{c.student.student_number}</td>
                                        <td className="px-5 py-2.5">
                                            <Link href={route('graduation-candidates.show', c.id)} className="hover:underline">
                                                {c.student.first_name} {c.student.middle_name} {c.student.surname}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-2.5">{c.student.program?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">
                                            {c.academic_year.start_year}-{c.academic_year.end_year} {c.semester.term}
                                        </td>
                                        <td className="px-5 py-2.5">{c.gwa_snapshot ?? '—'}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={STATUS_VARIANT[c.status] ?? 'neutral'}>{c.status.replace(/_/g, ' ')}</Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={candidates.links} from={candidates.from} to={candidates.to} total={candidates.total} />
            </Card>
        </AppLayout>
    );
}
