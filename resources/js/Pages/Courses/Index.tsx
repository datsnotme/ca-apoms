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

interface CourseRow {
    id: number;
    code: string;
    title: string;
    units: string;
    category: string;
    is_active: boolean;
    department: { name: string } | null;
}

export default function Index({
    courses,
    filters,
}: {
    courses: Paginated<CourseRow>;
    filters: { search?: string };
}) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('courses.manage');
    const [search, setSearch] = useState(filters.search ?? '');
    const bulk = useBulkSelection(courses.data.map((c) => c.id));

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('courses.index'), { search }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Courses</h1>}>
            <Head title="Courses" />

            <Card>
                <CardHeader
                    title="Course Catalog"
                    description="All courses offered across the College of Agriculture."
                    actions={
                        canManage ? (
                            <Link href={route('courses.create')}>
                                <PrimaryButton>Add Course</PrimaryButton>
                            </Link>
                        ) : undefined
                    }
                />

                <div className="border-b border-slate-200 px-5 py-3">
                    <form onSubmit={submitSearch} className="max-w-sm">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by code or title..."
                            className="w-full"
                        />
                    </form>
                </div>

                {canManage && (
                    <BulkDeleteBar
                        href={route('courses.destroyMany')}
                        ids={bulk.selectedIds}
                        itemLabelPlural="courses"
                        onDeleted={bulk.clear}
                    />
                )}

                {courses.data.length === 0 ? (
                    <EmptyState title="No courses found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    {canManage && (
                                        <th scope="col" className="w-10 px-5 py-2.5">
                                            <Checkbox
                                                aria-label="Select all courses on this page"
                                                checked={bulk.allOnPageSelected}
                                                onChange={bulk.toggleAllOnPage}
                                            />
                                        </th>
                                    )}
                                    <th className="px-5 py-2.5">Code</th>
                                    <th className="px-5 py-2.5">Title</th>
                                    <th className="px-5 py-2.5">Department</th>
                                    <th className="px-5 py-2.5">Units</th>
                                    <th className="px-5 py-2.5">Status</th>
                                    {canManage && <th className="px-5 py-2.5 text-right">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {courses.data.map((c) => (
                                    <tr key={c.id} className="hover:bg-slate-50">
                                        {canManage && (
                                            <td className="px-5 py-2.5">
                                                <Checkbox
                                                    aria-label={`Select ${c.title}`}
                                                    checked={bulk.isSelected(c.id)}
                                                    onChange={() => bulk.toggle(c.id)}
                                                />
                                            </td>
                                        )}
                                        <td className="px-5 py-2.5 font-mono text-xs">{c.code}</td>
                                        <td className="px-5 py-2.5">{c.title}</td>
                                        <td className="px-5 py-2.5">{c.department?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{c.units}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={c.is_active ? 'success' : 'neutral'}>
                                                {c.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </td>
                                        {canManage && (
                                            <td className="px-5 py-2.5 text-right">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route('courses.edit', c.id)}
                                                        className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <ConfirmDeleteButton
                                                        href={route('courses.destroy', c.id)}
                                                        itemLabel={c.title}
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

                <Pagination links={courses.links} from={courses.from} to={courses.to} total={courses.total} />
            </Card>
        </AppLayout>
    );
}
