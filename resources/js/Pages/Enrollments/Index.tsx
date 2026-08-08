import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import BulkDeleteBar from '@/Components/ui/BulkDeleteBar';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import useBulkSelection from '@/hooks/useBulkSelection';
import { Paginated, PageProps } from '@/types';

interface EnrollmentRow {
    id: number;
    status: 'enrolled' | 'withdrawn' | 'completed';
    enrollment_courses_count: number;
    student: { student_number: string; surname: string; first_name: string; middle_name: string | null } | null;
    semester: { term: string; academic_year: { start_year: number; end_year: number } } | null;
}

const STATUS_VARIANT = { enrolled: 'success', withdrawn: 'danger', completed: 'info' } as const;

export default function Index({
    enrollments,
    filters,
    semesters,
}: {
    enrollments: Paginated<EnrollmentRow>;
    filters: { search?: string; semester_id?: string };
    semesters: { id: number; label: string }[];
}) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('enrollment.manage');
    const [search, setSearch] = useState(filters.search ?? '');
    const [semesterId, setSemesterId] = useState(filters.semester_id ?? '');
    const bulk = useBulkSelection(enrollments.data.map((e) => e.id));

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('enrollments.index'), { search, semester_id: semesterId }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Enrollment</h1>}>
            <Head title="Enrollment" />

            <Card>
                <CardHeader
                    title="Student Enrollments"
                    description="Per-semester enrollment records and enrolled courses."
                    actions={
                        canManage ? (
                            <Link href={route('enrollments.create')}>
                                <PrimaryButton>New Enrollment</PrimaryButton>
                            </Link>
                        ) : undefined
                    }
                />

                <div className="flex flex-col gap-3 border-b border-slate-200 px-5 py-3 sm:flex-row sm:items-center">
                    <form onSubmit={submitSearch} className="flex max-w-lg flex-1 gap-3">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by student number or name..."
                            className="w-full"
                        />
                        <select
                            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={semesterId}
                            onChange={(e) => {
                                setSemesterId(e.target.value);
                                router.get(route('enrollments.index'), { search, semester_id: e.target.value }, { preserveState: true });
                            }}
                        >
                            <option value="">All Semesters</option>
                            {semesters.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.label}
                                </option>
                            ))}
                        </select>
                    </form>
                </div>

                {canManage && (
                    <BulkDeleteBar
                        href={route('enrollments.destroyMany')}
                        ids={bulk.selectedIds}
                        itemLabelPlural="enrollments"
                        onDeleted={bulk.clear}
                    />
                )}

                {enrollments.data.length === 0 ? (
                    <EmptyState title="No enrollments found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    {canManage && (
                                        <th scope="col" className="w-10 px-5 py-2.5">
                                            <Checkbox
                                                aria-label="Select all enrollments on this page"
                                                checked={bulk.allOnPageSelected}
                                                onChange={bulk.toggleAllOnPage}
                                            />
                                        </th>
                                    )}
                                    <th scope="col" className="px-5 py-2.5">Student</th>
                                    <th scope="col" className="px-5 py-2.5">Semester</th>
                                    <th scope="col" className="px-5 py-2.5">Courses</th>
                                    <th scope="col" className="px-5 py-2.5">Status</th>
                                    {canManage && <th scope="col" className="px-5 py-2.5 text-right">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {enrollments.data.map((e) => (
                                    <tr key={e.id} className="hover:bg-slate-50">
                                        {canManage && (
                                            <td className="px-5 py-2.5">
                                                <Checkbox
                                                    aria-label={`Select enrollment for ${e.student?.student_number}`}
                                                    checked={bulk.isSelected(e.id)}
                                                    onChange={() => bulk.toggle(e.id)}
                                                />
                                            </td>
                                        )}
                                        <td className="px-5 py-2.5">
                                            <Link href={route('enrollments.edit', e.id)} className="hover:underline">
                                                {e.student?.student_number} — {e.student?.first_name} {e.student?.surname}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-2.5">
                                            {e.semester
                                                ? `${e.semester.academic_year.start_year}-${e.semester.academic_year.end_year} ${e.semester.term}`
                                                : '—'}
                                        </td>
                                        <td className="px-5 py-2.5">{e.enrollment_courses_count}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={STATUS_VARIANT[e.status]}>{e.status}</Badge>
                                        </td>
                                        {canManage && (
                                            <td className="px-5 py-2.5 text-right">
                                                <ConfirmDeleteButton
                                                    href={route('enrollments.destroy', e.id)}
                                                    itemLabel={`this enrollment for ${e.student?.student_number}`}
                                                />
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={enrollments.links} from={enrollments.from} to={enrollments.to} total={enrollments.total} />
            </Card>
        </AppLayout>
    );
}
