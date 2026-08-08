import { FormEventHandler, useRef, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import Badge from '@/Components/ui/Badge';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface DocumentRow {
    id: number;
    category: string;
    title: string;
    original_filename: string;
    file_size: number;
    uploaded_at: string;
    verification_status: 'pending' | 'verified' | 'rejected';
    remarks: string | null;
    uploaded_by: { name: string } | null;
    verified_by: { name: string } | null;
}

const STATUS_VARIANT = {
    pending: 'warning',
    verified: 'success',
    rejected: 'danger',
} as const;

function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function VerifyControls({ facultyId, document }: { facultyId: number; document: DocumentRow }) {
    const [remarks, setRemarks] = useState('');
    const [processing, setProcessing] = useState(false);

    function submit(status: 'verified' | 'rejected') {
        setProcessing(true);
        router.patch(
            route('faculty-profiles.documents.verify', [facultyId, document.id]),
            { verification_status: status, remarks: remarks || null },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    if (document.verification_status !== 'pending') {
        return null;
    }

    return (
        <div className="mt-2 flex flex-wrap items-center gap-2">
            <TextInput
                value={remarks}
                onChange={(e) => setRemarks(e.target.value)}
                placeholder="Remarks (optional)"
                className="w-48 text-xs"
            />
            <button
                type="button"
                disabled={processing}
                onClick={() => submit('verified')}
                className="text-xs font-medium text-brand-700 hover:text-brand-900 disabled:opacity-50"
            >
                Verify
            </button>
            <button
                type="button"
                disabled={processing}
                onClick={() => submit('rejected')}
                className="text-xs font-medium text-red-600 hover:text-red-800 disabled:opacity-50"
            >
                Reject
            </button>
        </div>
    );
}

export default function DocumentList({
    facultyId,
    documents,
    categories,
    canUpload,
    canManage,
}: {
    facultyId: number;
    documents: DocumentRow[];
    categories: { value: string; label: string }[];
    canUpload: boolean;
    canManage: boolean;
}) {
    const fileInput = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm<{
        category: string;
        title: string;
        file: File | null;
    }>({
        category: categories[0]?.value ?? '',
        title: '',
        file: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('faculty-profiles.documents.store', facultyId), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                if (fileInput.current) fileInput.current.value = '';
            },
        });
    };

    return (
        <div className="flex flex-col gap-4">
            {documents.length === 0 ? (
                <p className="text-sm text-slate-500">No documents uploaded yet.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Category</th>
                                <th className="px-3 py-2">Title</th>
                                <th className="px-3 py-2">File</th>
                                <th className="px-3 py-2">Uploaded</th>
                                <th className="px-3 py-2">Status</th>
                                {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {documents.map((doc) => (
                                <tr key={doc.id}>
                                    <td className="px-3 py-2 capitalize">{doc.category.replace(/_/g, ' ')}</td>
                                    <td className="px-3 py-2">{doc.title}</td>
                                    <td className="px-3 py-2">
                                        <a
                                            href={route('faculty-profiles.documents.download', [facultyId, doc.id])}
                                            className="text-brand-700 hover:underline"
                                        >
                                            {doc.original_filename}
                                        </a>
                                        <span className="ml-1 text-xs text-slate-400">({formatBytes(doc.file_size)})</span>
                                    </td>
                                    <td className="px-3 py-2">
                                        {doc.uploaded_at?.slice(0, 10)}
                                        {doc.uploaded_by && <div className="text-xs text-slate-400">by {doc.uploaded_by.name}</div>}
                                    </td>
                                    <td className="px-3 py-2">
                                        <Badge variant={STATUS_VARIANT[doc.verification_status]}>{doc.verification_status}</Badge>
                                        {doc.remarks && <div className="mt-1 text-xs text-slate-400">{doc.remarks}</div>}
                                        {doc.verified_by && <div className="text-xs text-slate-400">by {doc.verified_by.name}</div>}
                                        {canManage && <VerifyControls facultyId={facultyId} document={doc} />}
                                    </td>
                                    {canManage && (
                                        <td className="px-3 py-2 text-right">
                                            <SecondaryButton
                                                type="button"
                                                onClick={() =>
                                                    router.delete(route('faculty-profiles.documents.destroy', [facultyId, doc.id]), {
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
            )}

            {canUpload && (
                <form onSubmit={submit} className="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-4">
                    <div>
                        <InputLabel value="Category" />
                        <select
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={data.category}
                            onChange={(e) => setData('category', e.target.value)}
                        >
                            {categories.map((c) => (
                                <option key={c.value} value={c.value}>
                                    {c.label}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.category} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="Title" />
                        <TextInput className="mt-1 block w-full" value={data.title} onChange={(e) => setData('title', e.target.value)} required />
                        <InputError message={errors.title} className="mt-1" />
                    </div>

                    <div>
                        <InputLabel value="File" />
                        <input
                            ref={fileInput}
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            className="mt-1 block w-full text-sm"
                            onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                        />
                        <InputError message={errors.file} className="mt-1" />
                    </div>

                    <div className="flex items-end">
                        <PrimaryButton disabled={processing}>Upload</PrimaryButton>
                    </div>
                </form>
            )}
        </div>
    );
}
