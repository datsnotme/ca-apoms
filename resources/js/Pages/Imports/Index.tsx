import { FormEventHandler, useRef, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { Paginated } from '@/types';

interface ImportTypeOption {
    value: string;
    label: string;
    can: boolean;
}

interface BatchRow {
    id: number;
    type: string;
    file_name: string;
    status: 'processing' | 'completed' | 'failed';
    total_rows: number;
    success_rows: number;
    error_rows: number;
    uploaded_by: { name: string } | null;
    created_at: string;
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    processing: 'warning',
    completed: 'success',
    failed: 'danger',
};

function ImportCard({ type }: { type: ImportTypeOption }) {
    const fileInput = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm<{ file: File | null }>({ file: null });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('imports.store', type.value), {
            forceFormData: true,
            onSuccess: () => {
                reset();
                if (fileInput.current) fileInput.current.value = '';
            },
        });
    };

    return (
        <div className="flex flex-col gap-2 rounded-md border border-slate-200 p-4">
            <h3 className="text-sm font-semibold text-slate-900">{type.label}</h3>
            <a href={route('imports.template', type.value)} className="text-xs font-medium text-brand-700 hover:text-brand-900">
                Download Template
            </a>
            {type.can ? (
                <form onSubmit={submit} className="flex flex-col gap-2">
                    <input
                        ref={fileInput}
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        className="text-xs"
                        onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                    />
                    <InputError message={errors.file} />
                    <PrimaryButton disabled={processing || !data.file} className="self-start">
                        Upload
                    </PrimaryButton>
                </form>
            ) : (
                <p className="text-xs text-slate-900">You don&apos;t have permission to import this.</p>
            )}
        </div>
    );
}

export default function Index({ batches, types }: { batches: Paginated<BatchRow>; types: ImportTypeOption[] }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Excel Imports</h1>}>
            <Head title="Imports" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader title="Import Data" description="Download a template, fill it in, then upload it here." />
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {types.map((type) => (
                                <ImportCard key={type.value} type={type} />
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Import History" />
                    {batches.data.length === 0 ? (
                        <EmptyState title="No imports yet" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                    <tr>
                                        <th className="px-5 py-2.5">Type</th>
                                        <th className="px-5 py-2.5">File</th>
                                        <th className="px-5 py-2.5">Uploaded By</th>
                                        <th className="px-5 py-2.5">Rows</th>
                                        <th className="px-5 py-2.5">Status</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {batches.data.map((batch) => (
                                        <tr key={batch.id} className="hover:bg-slate-50">
                                            <td className="px-5 py-2.5">{batch.type}</td>
                                            <td className="px-5 py-2.5">
                                                <Link href={route('imports.show', batch.id)} className="hover:underline">
                                                    {batch.file_name}
                                                </Link>
                                            </td>
                                            <td className="px-5 py-2.5">{batch.uploaded_by?.name ?? '—'}</td>
                                            <td className="px-5 py-2.5">
                                                {batch.success_rows} / {batch.total_rows} ({batch.error_rows} errors)
                                            </td>
                                            <td className="px-5 py-2.5">
                                                <Badge variant={STATUS_VARIANT[batch.status]}>{batch.status}</Badge>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <Pagination links={batches.links} from={batches.from} to={batches.to} total={batches.total} />
                </Card>
            </div>
        </AppLayout>
    );
}
