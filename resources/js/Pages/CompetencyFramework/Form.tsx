import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface CategoryFormValues {
    name: string;
    description: string;
    sort_order: string;
}

export default function CompetencyCategoryForm({
    action,
    method,
    initialValues,
    submitLabel,
    onCancelHref,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<CategoryFormValues>;
    submitLabel: string;
    onCancelHref?: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<CategoryFormValues>({
        name: initialValues.name ?? '',
        description: initialValues.description ?? '',
        sort_order: initialValues.sort_order ?? '0',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const submitFn = method === 'post' ? post : put;
        submitFn(action);
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
                <InputLabel htmlFor="name" value="Category Name" />
                <TextInput
                    id="name"
                    className="mt-1 block w-full"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                <InputError message={errors.name} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="sort_order" value="Sort Order" />
                <TextInput
                    id="sort_order"
                    type="number"
                    className="mt-1 block w-full"
                    value={data.sort_order}
                    onChange={(e) => setData('sort_order', e.target.value)}
                />
                <InputError message={errors.sort_order} className="mt-2" />
            </div>

            <div className="sm:col-span-2">
                <InputLabel htmlFor="description" value="Description" />
                <textarea
                    id="description"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    rows={2}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                />
                <InputError message={errors.description} className="mt-2" />
            </div>

            <div className="flex gap-3 sm:col-span-2">
                <PrimaryButton disabled={processing}>{submitLabel}</PrimaryButton>
                {onCancelHref && (
                    <SecondaryButton type="button" onClick={() => (window.location.href = onCancelHref)}>
                        Cancel
                    </SecondaryButton>
                )}
            </div>
        </form>
    );
}
