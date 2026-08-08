import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';

interface CategoryRow {
    id: number;
    name: string;
    description: string | null;
    documents_count: number;
}

export default function Index({ categories }: { categories: CategoryRow[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', description: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('document-categories.store'), { onSuccess: () => reset() });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Document Categories</h1>}>
            <Head title="Document Categories" />

            <Card>
                <CardHeader
                    title="Document Categories"
                    description="Categories used to organize the document repository."
                />

                {categories.length === 0 ? (
                    <EmptyState title="No categories defined yet" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Description</th>
                                    <th className="px-5 py-2.5">Documents</th>
                                    <th className="px-5 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {categories.map((c) => (
                                    <tr key={c.id}>
                                        <td className="px-5 py-2.5">{c.name}</td>
                                        <td className="px-5 py-2.5 text-slate-500">{c.description ?? '—'}</td>
                                        <td className="px-5 py-2.5">{c.documents_count}</td>
                                        <td className="px-5 py-2.5 text-right">
                                            <ConfirmDeleteButton
                                                href={route('document-categories.destroy', c.id)}
                                                itemLabel={c.name}
                                            />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <CardContent>
                    <form onSubmit={submit} className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <InputLabel htmlFor="name" value="New Category Name" />
                            <TextInput
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="description" value="Description (optional)" />
                            <TextInput
                                id="description"
                                className="mt-1 block w-full"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                            />
                            <InputError message={errors.description} className="mt-1" />
                        </div>
                        <div className="flex items-end">
                            <PrimaryButton disabled={processing}>Add Category</PrimaryButton>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
