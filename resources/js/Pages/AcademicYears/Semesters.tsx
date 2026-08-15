import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import PrimaryButton from '@/Components/PrimaryButton';
import Modal from '@/Components/Modal';
import { PageProps } from '@/types';
import SemesterFields from './SemesterFields';

const TERM_LABELS: Record<string, string> = {
    FIRST: '1st Semester',
    SECOND: '2nd Semester',
    SUMMER: 'Summer',
};

interface SemesterRow {
    id: number;
    term: string;
    start_date: string | null;
    end_date: string | null;
    is_current: boolean;
    academic_year: { start_year: number; end_year: number };
}

export default function Semesters({
    semesters,
    academicYears,
}: {
    semesters: SemesterRow[];
    academicYears: { id: number; start_year: number; end_year: number }[];
}) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('academic-terms.manage');
    const [showCreate, setShowCreate] = useState(false);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Semesters</h1>}>
            <Head title="Semesters" />

            <Card>
                <CardHeader
                    title="All Semesters"
                    description="Every semester across all academic years."
                    actions={
                        canManage ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>Add Semester</PrimaryButton>
                        ) : undefined
                    }
                />

                {semesters.length === 0 ? (
                    <EmptyState title="No semesters yet" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    <th className="px-5 py-2.5">Academic Year</th>
                                    <th className="px-5 py-2.5">Term</th>
                                    <th className="px-5 py-2.5">Dates</th>
                                    <th className="px-5 py-2.5">Status</th>
                                    {canManage && <th className="px-5 py-2.5 text-right">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {semesters.map((s) => (
                                    <tr key={s.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5">
                                            {s.academic_year.start_year}-{s.academic_year.end_year}
                                        </td>
                                        <td className="px-5 py-2.5">{TERM_LABELS[s.term] ?? s.term}</td>
                                        <td className="px-5 py-2.5 text-slate-900">
                                            {s.start_date ?? '—'} to {s.end_date ?? '—'}
                                        </td>
                                        <td className="px-5 py-2.5">
                                            {s.is_current && <Badge variant="success">Current</Badge>}
                                        </td>
                                        {canManage && (
                                            <td className="px-5 py-2.5 text-right">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route('semesters.edit', s.id)}
                                                        className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <ConfirmDeleteButton
                                                        href={route('semesters.destroy', s.id)}
                                                        itemLabel={`${TERM_LABELS[s.term]} ${s.academic_year.start_year}-${s.academic_year.end_year}`}
                                                    />
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>

            {canManage && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="lg">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">Add Semester</h2>
                        <div className="mt-4">
                            <SemesterFields
                                academicYears={academicYears}
                                onCancel={() => setShowCreate(false)}
                                onSuccess={() => setShowCreate(false)}
                            />
                        </div>
                    </div>
                </Modal>
            )}
        </AppLayout>
    );
}
