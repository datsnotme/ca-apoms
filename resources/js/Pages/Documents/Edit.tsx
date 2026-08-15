import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface DocumentDetail {
    id: number;
    title: string;
    description: string | null;
    document_category_id: number;
    department_id: number | null;
}

export default function Edit({
    document,
    categories,
    departments,
    isAdmin,
}: {
    document: DocumentDetail;
    categories: { id: number; name: string }[];
    departments: { id: number; name: string }[];
    isAdmin: boolean;
}) {
    const { data, setData, put, processing, errors } = useForm({
        document_category_id: String(document.document_category_id),
        title: document.title,
        description: document.description ?? '',
        department_id: document.department_id ? String(document.department_id) : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('documents.update', document.id));
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Document</h1>}>
            <Head title="Edit Document" />

            <Card>
                <CardHeader title={document.title} />
                <CardContent>
                    <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                            <p className="self-end text-sm text-slate-900">This document stays filed under your own department.</p>
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

                        <div className="flex gap-3 sm:col-span-2">
                            <PrimaryButton disabled={processing}>Save Changes</PrimaryButton>
                            <SecondaryButton type="button" onClick={() => (window.location.href = route('documents.show', document.id))}>
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
