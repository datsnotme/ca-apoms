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

interface CurriculumRow {
    id: number;
    code: string;
    name: string;
    status: 'active' | 'inactive';
    curriculum_courses_count: number;
    program: { name: string } | null;
    effective_academic_year: { start_year: number; end_year: number } | null;
}

export default function Index({
    curricula,
    filters,
}: {
    curricula: Paginated<CurriculumRow>;
    filters: { search?: string };
}) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('curricula.manage');
    const [search, setSearch] = useState(filters.search ?? '');
    const bulk = useBulkSelection(curricula.data.map((c) => c.id));

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('curricula.index'), { search }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Curricula</h1>}>
            <Head title="Curricula" />

            <Card>
                <CardHeader
                    title="Curriculum Versions"
                    description="Multiple curriculum versions per program are supported."
                    actions={
                        canManage ? (
                            <Link href={route('curricula.create')}>
                                <PrimaryButton>Add Curriculum</PrimaryButton>
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
                        href={route('curricula.destroyMany')}
                        ids={bulk.selectedIds}
                        itemLabelPlural="curricula"
                        onDeleted={bulk.clear}
                    />
                )}

                {curricula.data.length === 0 ? (
                    <EmptyState title="No curricula found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    {canManage && (
                                        <th scope="col" className="w-10 px-5 py-2.5">
                                            <Checkbox
                                                aria-label="Select all curricula on this page"
                                                checked={bulk.allOnPageSelected}
                                                onChange={bulk.toggleAllOnPage}
                                            />
                                        </th>
                                    )}
                                    <th className="px-5 py-2.5">Code</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Program</th>
                                    <th className="px-5 py-2.5">Effective A.Y.</th>
                                    <th className="px-5 py-2.5">Courses</th>
                                    <th className="px-5 py-2.5">Status</th>
                                    {canManage && <th className="px-5 py-2.5 text-right">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {curricula.data.map((c) => (
                                    <tr key={c.id} className="hover:bg-slate-50">
                                        {canManage && (
                                            <td className="px-5 py-2.5">
                                                <Checkbox
                                                    aria-label={`Select ${c.name}`}
                                                    checked={bulk.isSelected(c.id)}
                                                    onChange={() => bulk.toggle(c.id)}
                                                />
                                            </td>
                                        )}
                                        <td className="px-5 py-2.5 font-mono text-xs">{c.code}</td>
                                        <td className="px-5 py-2.5">
                                            <Link href={route('curricula.edit', c.id)} className="hover:underline">
                                                {c.name}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-2.5">{c.program?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">
                                            {c.effective_academic_year
                                                ? `${c.effective_academic_year.start_year}-${c.effective_academic_year.end_year}`
                                                : '—'}
                                        </td>
                                        <td className="px-5 py-2.5">{c.curriculum_courses_count}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={c.status === 'active' ? 'success' : 'neutral'}>
                                                {c.status === 'active' ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </td>
                                        {canManage && (
                                            <td className="px-5 py-2.5 text-right">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route('curricula.edit', c.id)}
                                                        className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <ConfirmDeleteButton
                                                        href={route('curricula.destroy', c.id)}
                                                        itemLabel={c.name}
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

                <Pagination links={curricula.links} from={curricula.from} to={curricula.to} total={curricula.total} />
            </Card>
        </AppLayout>
    );
}
