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

interface ProgramRow {
    id: number;
    code: string;
    name: string;
    degree_type: string | null;
    status: 'active' | 'inactive';
    department: { name: string } | null;
}

export default function Index({
    programs,
    filters,
}: {
    programs: Paginated<ProgramRow>;
    filters: { search?: string };
}) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('programs.manage');
    const [search, setSearch] = useState(filters.search ?? '');
    const bulk = useBulkSelection(programs.data.map((p) => p.id));

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('programs.index'), { search }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Programs</h1>}>
            <Head title="Programs" />

            <Card>
                <CardHeader
                    title="Degree Programs"
                    description="Academic programs offered by the College of Agriculture."
                    actions={
                        canManage ? (
                            <Link href={route('programs.create')}>
                                <PrimaryButton>Add Program</PrimaryButton>
                            </Link>
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
                        href={route('programs.destroyMany')}
                        ids={bulk.selectedIds}
                        itemLabelPlural="programs"
                        onDeleted={bulk.clear}
                    />
                )}

                {programs.data.length === 0 ? (
                    <EmptyState
                        title="No programs found"
                        description="Try a different search, or add the first program."
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    {canManage && (
                                        <th scope="col" className="w-10 px-5 py-2.5">
                                            <Checkbox
                                                aria-label="Select all programs on this page"
                                                checked={bulk.allOnPageSelected}
                                                onChange={bulk.toggleAllOnPage}
                                            />
                                        </th>
                                    )}
                                    <th className="px-5 py-2.5">Code</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Department</th>
                                    <th className="px-5 py-2.5">Degree</th>
                                    <th className="px-5 py-2.5">Status</th>
                                    {canManage && <th className="px-5 py-2.5 text-right">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {programs.data.map((p) => (
                                    <tr key={p.id} className="hover:bg-slate-50">
                                        {canManage && (
                                            <td className="px-5 py-2.5">
                                                <Checkbox
                                                    aria-label={`Select ${p.name}`}
                                                    checked={bulk.isSelected(p.id)}
                                                    onChange={() => bulk.toggle(p.id)}
                                                />
                                            </td>
                                        )}
                                        <td className="px-5 py-2.5 font-mono text-xs">{p.code}</td>
                                        <td className="px-5 py-2.5">{p.name}</td>
                                        <td className="px-5 py-2.5">{p.department?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{p.degree_type ?? '—'}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={p.status === 'active' ? 'success' : 'neutral'}>
                                                {p.status === 'active' ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </td>
                                        {canManage && (
                                            <td className="px-5 py-2.5 text-right">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route('programs.edit', p.id)}
                                                        className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <ConfirmDeleteButton
                                                        href={route('programs.destroy', p.id)}
                                                        itemLabel={p.name}
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
                    links={programs.links}
                    from={programs.from}
                    to={programs.to}
                    total={programs.total}
                />
            </Card>
        </AppLayout>
    );
}
