import { FormEventHandler } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface VersionRow {
    id: number;
    version_number: number;
    original_filename: string;
    file_size: number;
    notes: string | null;
    uploaded_at: string;
    uploaded_by: { id: number; name: string } | null;
}

interface DocumentDetail {
    id: number;
    title: string;
    description: string | null;
    category: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    uploaded_by: { id: number; name: string } | null;
    versions: VersionRow[];
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function Show({
    document,
    canManage,
    canUploadVersion,
}: {
    document: DocumentDetail;
    canManage: boolean;
    canUploadVersion: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{ file: File | null; notes: string }>({
        file: null,
        notes: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('documents.versions.store', document.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Document</h1>}>
            <Head title={document.title} />

            <div className="flex flex-col gap-6">
                <Link href={route('documents.index')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                    ← Back to Documents
                </Link>

                <Card>
                    <CardHeader
                        title={document.title}
                        description={document.category?.name}
                        actions={
                            canManage ? (
                                <div className="flex items-center gap-3">
                                    <Link href={route('documents.edit', document.id)}>
                                        <PrimaryButton>Edit</PrimaryButton>
                                    </Link>
                                    <ConfirmDeleteButton href={route('documents.destroy', document.id)} itemLabel={document.title} />
                                </div>
                            ) : undefined
                        }
                    />
                    <CardContent>
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant={document.department ? 'info' : 'neutral'}>{document.department?.name ?? 'Entire College'}</Badge>
                            <span className="text-xs text-slate-900">Filed by {document.uploaded_by?.name ?? 'Unknown'}</span>
                        </div>
                        {document.description && <p className="mt-3 whitespace-pre-wrap text-sm text-slate-600">{document.description}</p>}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Version History" description="Every uploaded version, newest first." />
                    <CardContent>
                        <div className="flex flex-col gap-4">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                        <tr>
                                            <th className="px-3 py-2">Version</th>
                                            <th className="px-3 py-2">File</th>
                                            <th className="px-3 py-2">Notes</th>
                                            <th className="px-3 py-2">Uploaded</th>
                                            {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {document.versions.map((v) => (
                                            <tr key={v.id}>
                                                <td className="px-3 py-2">v{v.version_number}</td>
                                                <td className="px-3 py-2">
                                                    <a
                                                        href={route('documents.versions.download', [document.id, v.id])}
                                                        className="text-brand-700 hover:underline"
                                                    >
                                                        {v.original_filename}
                                                    </a>
                                                    <span className="ml-1 text-xs text-slate-900">({formatBytes(v.file_size)})</span>
                                                </td>
                                                <td className="px-3 py-2 text-slate-900">{v.notes ?? '—'}</td>
                                                <td className="px-3 py-2">
                                                    {v.uploaded_at.slice(0, 10)}
                                                    {v.uploaded_by && <div className="text-xs text-slate-900">by {v.uploaded_by.name}</div>}
                                                </td>
                                                {canManage && (
                                                    <td className="px-3 py-2 text-right">
                                                        <SecondaryButton
                                                            type="button"
                                                            onClick={() =>
                                                                router.delete(route('documents.versions.destroy', [document.id, v.id]), {
                                                                    preserveScroll: true,
                                                                })
                                                            }
                                                        >
                                                            Remove
                                                        </SecondaryButton>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {canUploadVersion && (
                                <form onSubmit={submit} className="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-3">
                                    <div>
                                        <InputLabel value="New Version File" />
                                        <input
                                            type="file"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                            className="mt-1 block w-full text-sm"
                                            onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                                            required
                                        />
                                        <InputError message={errors.file} className="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel value="Notes (optional)" />
                                        <input
                                            type="text"
                                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                            placeholder="What changed?"
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                        />
                                        <InputError message={errors.notes} className="mt-1" />
                                    </div>
                                    <div className="flex items-end">
                                        <PrimaryButton disabled={processing}>Upload New Version</PrimaryButton>
                                    </div>
                                </form>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
