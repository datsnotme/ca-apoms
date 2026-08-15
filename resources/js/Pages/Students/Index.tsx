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
import Modal from '@/Components/Modal';
import useBulkSelection from '@/hooks/useBulkSelection';
import { Paginated, PageProps } from '@/types';
import StudentForm from './Form';

interface Option {
    id: number;
    name: string;
}

interface StudentRow {
    id: number;
    student_number: string;
    name: string;
    classification: string;
    status: string;
    department: { name: string } | null;
    program: { name: string } | null;
    year_level: { label: string } | null;
    can_view_progress: boolean;
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    active: 'success',
    on_leave: 'warning',
    withdrawn: 'danger',
    dropped: 'danger',
    inactive: 'neutral',
    graduated: 'info',
    transferred: 'neutral',
    archived: 'neutral',
};

export default function Index({
    students,
    filters,
    classifications,
    statuses,
    yearLevels,
    departments,
    programs,
    curricula,
    sections,
    advisers,
}: {
    students: Paginated<StudentRow>;
    filters: { search?: string; classification?: string; status?: string; year_level_id?: string };
    classifications: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
    yearLevels: { id: number; level: number; label: string }[];
    departments?: Option[];
    programs?: (Option & { department_id: number })[];
    curricula?: (Option & { program_id: number })[];
    sections?: { id: number; name: string; program_id: number; year_level_id: number }[];
    advisers?: Option[];
}) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('students.manage');
    const [search, setSearch] = useState(filters.search ?? '');
    const [classification, setClassification] = useState(filters.classification ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [yearLevelId, setYearLevelId] = useState(filters.year_level_id ?? '');
    const [showCreate, setShowCreate] = useState(false);
    const bulk = useBulkSelection(students.data.map((s) => s.id));

    const applyFilters = (
        overrides: Partial<{ search: string; classification: string; status: string; year_level_id: string }> = {},
    ) => {
        router.get(
            route('students.index'),
            {
                search: overrides.search ?? search,
                classification: overrides.classification ?? classification,
                status: overrides.status ?? status,
                year_level_id: overrides.year_level_id ?? yearLevelId,
            },
            { preserveState: true },
        );
    };

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters();
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Students</h1>}>
            <Head title="Students" />

            <Card>
                <CardHeader
                    title="Student Records"
                    description="Search, filter, and manage student profiles."
                    actions={
                        canManage ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>Register Student</PrimaryButton>
                        ) : undefined
                    }
                />

                <div className="flex flex-col gap-3 border-b border-slate-200 px-5 py-3 sm:flex-row sm:items-center">
                    <form onSubmit={submitSearch} className="max-w-sm flex-1">
                        <TextInput
                            aria-label="Search students by number or name"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by number or name..."
                            className="w-full"
                        />
                    </form>

                    <select
                        aria-label="Filter by classification"
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={classification}
                        onChange={(e) => {
                            setClassification(e.target.value);
                            applyFilters({ classification: e.target.value });
                        }}
                    >
                        <option value="">All Classifications</option>
                        {classifications.map((c) => (
                            <option key={c.value} value={c.value}>
                                {c.label}
                            </option>
                        ))}
                    </select>

                    <select
                        aria-label="Filter by status"
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={status}
                        onChange={(e) => {
                            setStatus(e.target.value);
                            applyFilters({ status: e.target.value });
                        }}
                    >
                        <option value="">All Statuses</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>

                    <select
                        aria-label="Filter by year level"
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={yearLevelId}
                        onChange={(e) => {
                            setYearLevelId(e.target.value);
                            applyFilters({ year_level_id: e.target.value });
                        }}
                    >
                        <option value="">All Year Levels</option>
                        {yearLevels.map((y) => (
                            <option key={y.id} value={y.id}>
                                {y.label}
                            </option>
                        ))}
                    </select>
                </div>

                {canManage && (
                    <BulkDeleteBar
                        href={route('students.destroyMany')}
                        ids={bulk.selectedIds}
                        itemLabelPlural="students"
                        onDeleted={bulk.clear}
                    />
                )}

                {students.data.length === 0 ? (
                    <EmptyState title="No students found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    {canManage && (
                                        <th scope="col" className="w-10 px-5 py-2.5">
                                            <Checkbox
                                                aria-label="Select all students on this page"
                                                checked={bulk.allOnPageSelected}
                                                onChange={bulk.toggleAllOnPage}
                                            />
                                        </th>
                                    )}
                                    <th scope="col" className="px-5 py-2.5">Student No.</th>
                                    <th scope="col" className="px-5 py-2.5">Name</th>
                                    <th scope="col" className="px-5 py-2.5">Department</th>
                                    <th scope="col" className="px-5 py-2.5">Program</th>
                                    <th scope="col" className="px-5 py-2.5">Year Level</th>
                                    <th scope="col" className="px-5 py-2.5">Classification</th>
                                    <th scope="col" className="px-5 py-2.5">Status</th>
                                    <th scope="col" className="px-5 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {students.data.map((s) => (
                                    <tr key={s.id} className="hover:bg-slate-50">
                                        {canManage && (
                                            <td className="px-5 py-2.5">
                                                <Checkbox
                                                    aria-label={`Select ${s.name}`}
                                                    checked={bulk.isSelected(s.id)}
                                                    onChange={() => bulk.toggle(s.id)}
                                                />
                                            </td>
                                        )}
                                        <td className="px-5 py-2.5 font-mono text-xs">{s.student_number}</td>
                                        <td className="px-5 py-2.5">
                                            {canManage ? (
                                                <Link href={route('students.edit', s.id)} className="hover:underline">
                                                    {s.name}
                                                </Link>
                                            ) : (
                                                s.name
                                            )}
                                        </td>
                                        <td className="px-5 py-2.5">{s.department?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.program?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.year_level?.label ?? '—'}</td>
                                        <td className="px-5 py-2.5 capitalize">{s.classification}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={STATUS_VARIANT[s.status] ?? 'neutral'}>{s.status}</Badge>
                                        </td>
                                        <td className="px-5 py-2.5 text-right">
                                            <div className="flex justify-end gap-3">
                                                {s.can_view_progress && (
                                                    <Link
                                                        href={route('students.progress.show', s.id)}
                                                        className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                    >
                                                        Progress
                                                    </Link>
                                                )}
                                                {canManage && (
                                                    <>
                                                        <Link
                                                            href={route('students.edit', s.id)}
                                                            className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <ConfirmDeleteButton
                                                            href={route('students.destroy', s.id)}
                                                            itemLabel={s.name}
                                                        />
                                                    </>
                                                )}
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

            {canManage && departments && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="4xl">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">Register Student</h2>
                        <div className="mt-4">
                            <StudentForm
                                action={route('students.store')}
                                method="post"
                                initialValues={{}}
                                departments={departments}
                                programs={programs ?? []}
                                curricula={curricula ?? []}
                                yearLevels={yearLevels}
                                sections={sections ?? []}
                                advisers={advisers ?? []}
                                classifications={classifications}
                                statuses={statuses}
                                submitLabel="Register Student"
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
