import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface TemplateFormValues {
    program_id: string;
    title: string;
    description: string;
    is_required: boolean;
    sort_order: string;
}

export default function GraduationRequirementForm({
    action,
    method,
    initialValues,
    programs,
    submitLabel,
    onCancelHref,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<TemplateFormValues>;
    programs: { id: number; name: string }[];
    submitLabel: string;
    onCancelHref?: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<TemplateFormValues>({
        program_id: initialValues.program_id ?? '',
        title: initialValues.title ?? '',
        description: initialValues.description ?? '',
        is_required: initialValues.is_required ?? true,
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
                <InputLabel htmlFor="program_id" value="Program" />
                <select
                    id="program_id"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.program_id}
                    onChange={(e) => setData('program_id', e.target.value)}
                >
                    <option value="">— Applies to all programs —</option>
                    {programs.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.program_id} className="mt-2" />
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

            <div className="sm:col-span-2">
                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        checked={data.is_required}
                        onChange={(e) => setData('is_required', e.target.checked)}
                        className="rounded border-gray-300 text-brand-700 focus:ring-brand-600"
                    />
                    Required for graduation
                </label>
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
