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
import DepartmentForm from './Form';

interface DepartmentRow {
    id: number;
    code: string;
    name: string;
    status: 'active' | 'inactive';
    programs_count: number;
    college: { name: string } | null;
    head: { name: string } | null;
}

export default function Index({
    departments,
    filters,
    potentialHeads,
}: {
    departments: Paginated<DepartmentRow>;
    filters: { search?: string };
    potentialHeads?: { id: number; name: string }[];
}) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('departments.manage');
    const [search, setSearch] = useState(filters.search ?? '');
    const [showCreate, setShowCreate] = useState(false);
    const bulk = useBulkSelection(departments.data.map((d) => d.id));

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('departments.index'), { search }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Departments</h1>}>
            <Head title="Departments" />

            <Card>
                <CardHeader
                    title="Departments"
                    description="Departments within the College of Agriculture."
                    actions={
                        canManage ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>Add Department</PrimaryButton>
                        ) : undefined
                    }
                />

                <div className="border-b border-slate-200 px-5 py-3">
                    <form onSubmit={submitSearch} className="max-w-sm">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by name or code..."
                            className="w-full"
                        />
                    </form>
                </div>

                {canManage && (
                    <BulkDeleteBar
                        href={route('departments.destroyMany')}
                        ids={bulk.selectedIds}
                        itemLabelPlural="departments"
                        onDeleted={bulk.clear}
                    />
                )}

                {departments.data.length === 0 ? (
                    <EmptyState
                        title="No departments found"
                        description="Try a different search, or add the first department."
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    {canManage && (
                                        <th scope="col" className="w-10 px-5 py-2.5">
                                            <Checkbox
                                                aria-label="Select all departments on this page"
                                                checked={bulk.allOnPageSelected}
                                                onChange={bulk.toggleAllOnPage}
                                            />
                                        </th>
                                    )}
                                    <th className="px-5 py-2.5">Code</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Head</th>
                                    <th className="px-5 py-2.5">Programs</th>
                                    <th className="px-5 py-2.5">Status</th>
                                    {canManage && <th className="px-5 py-2.5 text-right">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {departments.data.map((d) => (
                                    <tr key={d.id} className="hover:bg-slate-50">
                                        {canManage && (
                                            <td className="px-5 py-2.5">
                                                <Checkbox
                                                    aria-label={`Select ${d.name}`}
                                                    checked={bulk.isSelected(d.id)}
                                                    onChange={() => bulk.toggle(d.id)}
                                                />
                                            </td>
                                        )}
                                        <td className="px-5 py-2.5 font-mono text-xs">{d.code}</td>
                                        <td className="px-5 py-2.5">{d.name}</td>
                                        <td className="px-5 py-2.5">{d.head?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{d.programs_count}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={d.status === 'active' ? 'success' : 'neutral'}>
                                                {d.status === 'active' ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </td>
                                        {canManage && (
                                            <td className="px-5 py-2.5 text-right">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route('departments.edit', d.id)}
                                                        className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <ConfirmDeleteButton
                                                        href={route('departments.destroy', d.id)}
                                                        itemLabel={d.name}
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

                <Pagination
                    links={departments.links}
                    from={departments.from}
                    to={departments.to}
                    total={departments.total}
                />
            </Card>

            {canManage && potentialHeads && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="2xl">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">Add Department</h2>
                        <div className="mt-4">
                            <DepartmentForm
                                action={route('departments.store')}
                                method="post"
                                initialValues={{}}
                                potentialHeads={potentialHeads}
                                submitLabel="Add Department"
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
