import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import Modal from '@/Components/Modal';
import { Paginated, PageProps } from '@/types';
import ClassSectionForm from './Form';

interface ClassSectionRow {
    id: number;
    section_label: string;
    max_students: number;
    enrolled_count: number;
    status: 'open' | 'closed';
    course: { code: string; title: string } | null;
    semester: { term: string; academic_year: { start_year: number; end_year: number } } | null;
}

export default function Index({
    classSections,
    filters,
    semesters,
    courses,
    faculty,
    statuses,
}: {
    classSections: Paginated<ClassSectionRow>;
    filters: { search?: string; semester_id?: string };
    semesters: { id: number; label: string }[];
    courses?: { id: number; code: string; title: string }[];
    faculty?: { id: number; name: string }[];
    statuses?: { value: string; label: string }[];
}) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('enrollment.manage');
    const [search, setSearch] = useState(filters.search ?? '');
    const [semesterId, setSemesterId] = useState(filters.semester_id ?? '');
    const [showCreate, setShowCreate] = useState(false);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('class-sections.index'), { search, semester_id: semesterId }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Class Sections</h1>}>
            <Head title="Class Sections" />

            <Card>
                <CardHeader
                    title="Class Sections"
                    description="Course offerings per semester, with faculty and schedule."
                    actions={
                        canManage ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>Add Class Section</PrimaryButton>
                        ) : undefined
                    }
                />

                <div className="flex flex-col gap-3 border-b border-slate-200 px-5 py-3 sm:flex-row sm:items-center">
                    <form onSubmit={submitSearch} className="flex max-w-lg flex-1 gap-3">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by course code or title..."
                            className="w-full"
                        />
                        <select
                            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={semesterId}
                            onChange={(e) => {
                                setSemesterId(e.target.value);
                                router.get(route('class-sections.index'), { search, semester_id: e.target.value }, { preserveState: true });
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
                                    <th className="px-5 py-2.5">Enrolled</th>
                                    <th className="px-5 py-2.5">Status</th>
                                    <th className="px-5 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {classSections.data.map((cs) => (
                                    <tr key={cs.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5">
                                            <Link href={route('class-sections.edit', cs.id)} className="hover:underline">
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
                                            {cs.enrolled_count} / {cs.max_students}
                                        </td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={cs.status === 'open' ? 'success' : 'neutral'}>{cs.status}</Badge>
                                        </td>
                                        <td className="px-5 py-2.5 text-right">
                                            <div className="flex justify-end gap-3">
                                                <Link
                                                    href={route('class-sections.roster', cs.id)}
                                                    className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                >
                                                    Roster
                                                </Link>
                                                {canManage && (
                                                    <>
                                                        <Link
                                                            href={route('class-sections.edit', cs.id)}
                                                            className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <ConfirmDeleteButton
                                                            href={route('class-sections.destroy', cs.id)}
                                                            itemLabel={`${cs.course?.code} ${cs.section_label}`}
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

                <Pagination links={classSections.links} from={classSections.from} to={classSections.to} total={classSections.total} />
            </Card>

            {canManage && courses && faculty && statuses && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="2xl" variant="form">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">Add Class Section</h2>
                        <div className="mt-4">
                            <ClassSectionForm
                                action={route('class-sections.store')}
                                method="post"
                                initialValues={{}}
                                courses={courses}
                                semesters={semesters}
                                faculty={faculty}
                                statuses={statuses}
                                submitLabel="Add Class Section"
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
