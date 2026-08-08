import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import PrimaryButton from '@/Components/PrimaryButton';
import { PageProps } from '@/types';

interface TemplateRow {
    id: number;
    title: string;
    description: string | null;
    is_required: boolean;
    sort_order: number;
    program: { name: string } | null;
}

export default function Index({ templates }: { templates: TemplateRow[] }) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('graduation.manage');

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Graduation Requirements</h1>}>
            <Head title="Graduation Requirements" />

            <Card>
                <CardHeader
                    title="Requirement Checklist Templates"
                    description="Institutional requirements a candidate must satisfy or have waived before recommendation."
                    actions={
                        canManage ? (
                            <Link href={route('graduation-requirement-templates.create')}>
                                <PrimaryButton>Add Requirement</PrimaryButton>
                            </Link>
                        ) : undefined
                    }
                />

                {templates.length === 0 ? (
                    <EmptyState title="No requirements defined yet" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-5 py-2.5">Title</th>
                                    <th className="px-5 py-2.5">Program</th>
                                    <th className="px-5 py-2.5">Required</th>
                                    {canManage && <th className="px-5 py-2.5 text-right">Actions</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {templates.map((t) => (
                                    <tr key={t.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5">
                                            {t.title}
                                            {t.description && <p className="text-xs text-slate-400">{t.description}</p>}
                                        </td>
                                        <td className="px-5 py-2.5">{t.program?.name ?? 'All Programs'}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={t.is_required ? 'success' : 'neutral'}>
                                                {t.is_required ? 'Required' : 'Optional'}
                                            </Badge>
                                        </td>
                                        {canManage && (
                                            <td className="px-5 py-2.5 text-right">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={route('graduation-requirement-templates.edit', t.id)}
                                                        className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                    >
                                                        Edit
                                                    </Link>
                                                    <ConfirmDeleteButton
                                                        href={route('graduation-requirement-templates.destroy', t.id)}
                                                        itemLabel={t.title}
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
