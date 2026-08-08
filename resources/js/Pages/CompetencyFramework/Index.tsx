import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import PrimaryButton from '@/Components/PrimaryButton';

interface CategoryRow {
    id: number;
    name: string;
    description: string | null;
    sort_order: number;
    indicators_count: number;
}

export default function Index({ categories, canManage }: { categories: CategoryRow[]; canManage: boolean }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Competency Framework</h1>}>
            <Head title="Competency Framework" />

            <Card>
                <CardHeader
                    title="Competency Categories"
                    description="The rating framework evaluators use when assessing a graduating candidate."
                    actions={
                        canManage ? (
                            <Link href={route('competency-categories.create')}>
                                <PrimaryButton>Add Category</PrimaryButton>
                            </Link>
                        ) : undefined
                    }
                />

                {categories.length === 0 ? (
                    <EmptyState title="No competency categories defined yet" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-5 py-2.5">Category</th>
                                    <th className="px-5 py-2.5">Indicators</th>
                                    {canManage && <th className="px-5 py-2.5 text-right">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {categories.map((c) => (
                                    <tr key={c.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5">
                                            {canManage ? (
                                                <Link
                                                    href={route('competency-categories.edit', c.id)}
                                                    className="font-medium text-brand-700 hover:text-brand-900"
                                                >
                                                    {c.name}
                                                </Link>
                                            ) : (
                                                c.name
                                            )}
                                            {c.description && <p className="text-xs text-slate-400">{c.description}</p>}
                                        </td>
                                        <td className="px-5 py-2.5">{c.indicators_count}</td>
                                        {canManage && (
                                            <td className="px-5 py-2.5 text-right">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route('competency-categories.edit', c.id)}
                                                        className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                    >
                                                        Manage
                                                    </Link>
                                                    <ConfirmDeleteButton
                                                        href={route('competency-categories.destroy', c.id)}
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
            </Card>
        </AppLayout>
    );
}
