import { FormEventHandler, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import BulkDeleteBar from '@/Components/ui/BulkDeleteBar';
import Checkbox from '@/Components/Checkbox';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import useBulkSelection from '@/hooks/useBulkSelection';
import { Paginated } from '@/types';

interface DocumentRow {
    id: number;
    title: string;
    description: string | null;
    category: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    uploaded_by: { id: number; name: string } | null;
    latest_version: { version_number: number; original_filename: string; file_size: number; uploaded_at: string } | null;
    can_manage: boolean;
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function Index({
    documents,
    categories,
    canCreate,
    filters,
    departments,
    isAdmin,
}: {
    documents: Paginated<DocumentRow>;
    categories: { id: number; name: string }[];
    canCreate: boolean;
    filters: { search?: string; document_category_id?: string };
    departments?: { id: number; name: string }[];
    isAdmin?: boolean;
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const manageableIds = documents.data.filter((d) => d.can_manage).map((d) => d.id);
    const bulk = useBulkSelection(manageableIds);
    const [showCreate, setShowCreate] = useState(false);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('documents.index'), { search, document_category_id: filters.document_category_id }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Documents</h1>}>
            <Head title="Documents" />

            <Card>
                <CardHeader
                    title="Document Repository"
                    description="College-wide and department reference documents."
                    actions={
                        canCreate ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>Upload Document</PrimaryButton>
                        ) : undefined
                    }
                />

                <div className="flex flex-col gap-3 border-b border-slate-200 px-5 py-3 sm:flex-row sm:items-center">
                    <form onSubmit={submitSearch} className="flex max-w-lg flex-1 gap-3">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by title..."
                            className="w-full"
                        />
                    </form>
                    <select
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={filters.document_category_id ?? ''}
                        onChange={(e) =>
                            router.get(route('documents.index'), { search, document_category_id: e.target.value }, { preserveState: true })
                        }
                    >
                        <option value="">All Categories</option>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.name}
                            </option>
                        ))}
                    </select>
                </div>

                <BulkDeleteBar
                    href={route('documents.destroyMany')}
                    ids={bulk.selectedIds}
                    itemLabelPlural="documents"
                    description="This archives the selected documents and permanently deletes their uploaded files. This cannot be undone."
                    onDeleted={bulk.clear}
                />

                {documents.data.length === 0 ? (
                    <EmptyState title="No documents found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    <th className="w-10 px-5 py-2.5">
                                        {manageableIds.length > 0 && (
                                            <Checkbox
                                                aria-label="Select all documents"
                                                checked={bulk.allOnPageSelected}
                                                onChange={() => bulk.toggleAllOnPage()}
                                            />
                                        )}
                                    </th>
                                    <th className="px-5 py-2.5">Title</th>
                                    <th className="px-5 py-2.5">Category</th>
                                    <th className="px-5 py-2.5">Audience</th>
                                    <th className="px-5 py-2.5">Latest Version</th>
                                    <th className="px-5 py-2.5">Uploaded By</th>
                                    <th className="px-5 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {documents.data.map((doc) => (
                                    <tr key={doc.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5">
                                            {doc.can_manage && (
                                                <Checkbox
                                                    aria-label={`Select ${doc.title}`}
                                                    checked={bulk.isSelected(doc.id)}
                                                    onChange={() => bulk.toggle(doc.id)}
                                                />
                                            )}
                                        </td>
                                        <td className="px-5 py-2.5">
                                            <Link href={route('documents.show', doc.id)} className="font-medium text-brand-700 hover:underline">
                                                {doc.title}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-2.5">{doc.category?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={doc.department ? 'info' : 'neutral'}>{doc.department?.name ?? 'Entire College'}</Badge>
                                        </td>
                                        <td className="px-5 py-2.5">
                                            {doc.latest_version ? (
                                                <>
                                                    v{doc.latest_version.version_number} · {doc.latest_version.original_filename}
                                                    <span className="ml-1 text-xs text-slate-900">
                                                        ({formatBytes(doc.latest_version.file_size)})
                                                    </span>
                                                </>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-5 py-2.5">{doc.uploaded_by?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5 text-right">
                                            {doc.can_manage && (
                                                <ConfirmDeleteButton href={route('documents.destroy', doc.id)} itemLabel={doc.title} />
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={documents.links} from={documents.from} to={documents.to} total={documents.total} />
            </Card>

            {canCreate && (
                <UploadDocumentModal
                    show={showCreate}
                    categories={categories}
                    departments={departments ?? []}
                    isAdmin={Boolean(isAdmin)}
                    onClose={() => setShowCreate(false)}
                />
            )}
        </AppLayout>
    );
}

function UploadDocumentModal({
    show,
    categories,
    departments,
    isAdmin,
    onClose,
}: {
    show: boolean;
    categories: { id: number; name: string }[];
    departments: { id: number; name: string }[];
    isAdmin: boolean;
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        document_category_id: string;
        title: string;
        description: string;
        department_id: string;
        file: File | null;
    }>({
        document_category_id: categories[0]?.id ? String(categories[0].id) : '',
        title: '',
        description: '',
        department_id: '',
        file: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('documents.store'), {
            forceFormData: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="2xl">
            <div className="p-6">
                <h2 className="text-lg font-medium text-slate-900">Upload Document</h2>
                <form onSubmit={submit} className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="title" value="Title" />
                        <TextInput
                            id="title"
                            className="mt-1 block w-full"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                        />
                        <InputError message={errors.title} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="document_category_id" value="Category" />
                        <select
                            id="document_category_id"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={data.document_category_id}
                            onChange={(e) => setData('document_category_id', e.target.value)}
                        >
                            {categories.map((c) => (
                                <option key={c.id} value={c.id}>
                                    {c.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.document_category_id} className="mt-2" />
                    </div>

                    {isAdmin ? (
                        <div>
                            <InputLabel htmlFor="department_id" value="Audience" />
                            <select
                                id="department_id"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.department_id}
                                onChange={(e) => setData('department_id', e.target.value)}
                            >
                                <option value="">Entire College</option>
                                {departments.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.department_id} className="mt-2" />
                        </div>
                    ) : (
                        <p className="self-end text-sm text-slate-900">This will be filed under your own department only.</p>
                    )}

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="description" value="Description" />
                        <textarea
                            id="description"
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            rows={3}
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                        />
                        <InputError message={errors.description} className="mt-2" />
                    </div>

                    <div className="sm:col-span-2">
                        <InputLabel htmlFor="file" value="File" />
                        <input
                            id="file"
                            type="file"
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                            className="mt-1 block w-full text-sm"
                            onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                            required
                        />
                        <InputError message={errors.file} className="mt-2" />
                    </div>

                    <div className="flex gap-3 sm:col-span-2">
                        <PrimaryButton disabled={processing}>Upload Document</PrimaryButton>
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
