import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Create({
    categories,
    departments,
    isAdmin,
}: {
    categories: { id: number; name: string }[];
    departments: { id: number; name: string }[];
    isAdmin: boolean;
}) {
    const { data, setData, post, processing, errors } = useForm<{
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
        post(route('documents.store'), { forceFormData: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Upload Document</h1>}>
            <Head title="Upload Document" />

            <Card>
                <CardHeader title="New Document" />
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
                            <p className="self-end text-sm text-slate-500">This will be filed under your own department only.</p>
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
                            <SecondaryButton type="button" onClick={() => (window.location.href = route('documents.index'))}>
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
