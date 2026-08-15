import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import { PageProps } from '@/types';
import AcademicYearForm from './Form';
import SemesterFields from './SemesterFields';

const TERM_LABELS: Record<string, string> = {
    FIRST: '1st Semester',
    SECOND: '2nd Semester',
    SUMMER: 'Summer',
};

interface SemesterRow {
    id: number;
    term: string;
    is_current: boolean;
}

interface AcademicYearRow {
    id: number;
    start_year: number;
    end_year: number;
    is_current: boolean;
    semesters_count: number;
    semesters: SemesterRow[];
}

export default function Index({ academicYears }: { academicYears: AcademicYearRow[] }) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('academic-terms.manage');
    const [showAddSemester, setShowAddSemester] = useState(false);
    const [showAddYear, setShowAddYear] = useState(false);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Academic Years</h1>}>
            <Head title="Academic Years" />

            <div className="flex items-center justify-end gap-2">
                {canManage && (
                    <>
                        <SecondaryButton onClick={() => setShowAddSemester(true)}>Add Semester</SecondaryButton>
                        <PrimaryButton onClick={() => setShowAddYear(true)}>Add Academic Year</PrimaryButton>
                    </>
                )}
            </div>

            <div className="mt-4 flex flex-col gap-4">
                {academicYears.length === 0 && (
                    <Card>
                        <EmptyState
                            title="No academic years yet"
                            description="Add the first academic year to get started."
                        />
                    </Card>
                )}

                {academicYears.map((year) => (
                    <Card key={year.id}>
                        <CardHeader
                            title={`${year.start_year}-${year.end_year}`}
                            description={`${year.semesters_count} semester(s) on file`}
                            actions={
                                <div className="flex items-center gap-3">
                                    {year.is_current && <Badge variant="success">Current</Badge>}
                                    {canManage && (
                                        <>
                                            <Link
                                                href={route('academic-years.edit', year.id)}
                                                className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                            >
                                                Edit
                                            </Link>
                                            <ConfirmDeleteButton
                                                href={route('academic-years.destroy', year.id)}
                                                itemLabel={`${year.start_year}-${year.end_year}`}
                                            />
                                        </>
                                    )}
                                </div>
                            }
                        />
                        <div className="flex flex-wrap gap-2 px-5 py-4">
                            {year.semesters.map((s) => (
                                <Link
                                    key={s.id}
                                    href={route('semesters.edit', s.id)}
                                    className="rounded-md border border-slate-200 px-3 py-1.5 text-sm text-slate-700 hover:border-brand-400"
                                >
                                    {TERM_LABELS[s.term] ?? s.term}
                                    {s.is_current && <span className="ml-1.5 text-xs text-brand-700">•current</span>}
                                </Link>
                            ))}
                            {year.semesters.length === 0 && (
                                <p className="text-sm text-slate-900">No semesters added yet.</p>
                            )}
                        </div>
                    </Card>
                ))}
            </div>

            {canManage && (
                <>
                    <Modal show={showAddYear} onClose={() => setShowAddYear(false)} maxWidth="lg">
                        <div className="p-6">
                            <h2 className="text-lg font-medium text-slate-900">Add Academic Year</h2>
                            <div className="mt-4">
                                <AcademicYearForm
                                    action={route('academic-years.store')}
                                    method="post"
                                    initialValues={{}}
                                    submitLabel="Add Academic Year"
                                    onCancel={() => setShowAddYear(false)}
                                    onSuccess={() => setShowAddYear(false)}
                                />
                            </div>
                        </div>
                    </Modal>

                    <Modal show={showAddSemester} onClose={() => setShowAddSemester(false)} maxWidth="lg">
                        <div className="p-6">
                            <h2 className="text-lg font-medium text-slate-900">Add Semester</h2>
                            <div className="mt-4">
                                <SemesterFields
                                    academicYears={academicYears}
                                    onCancel={() => setShowAddSemester(false)}
                                    onSuccess={() => setShowAddSemester(false)}
                                />
                            </div>
                        </div>
                    </Modal>
                </>
            )}
        </AppLayout>
    );
}
