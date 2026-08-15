import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface EventFormValues {
    title: string;
    description: string;
    start_at: string;
    end_at: string;
    location: string;
    department_id: string;
}

export default function EventForm({
    action,
    method,
    initialValues,
    departments,
    isAdmin,
    submitLabel,
    onCancelHref,
    onCancel,
    onSuccess,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<EventFormValues>;
    departments: { id: number; name: string }[];
    isAdmin: boolean;
    submitLabel: string;
    onCancelHref?: string;
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<EventFormValues>({
        title: initialValues.title ?? '',
        description: initialValues.description ?? '',
        start_at: initialValues.start_at ?? '',
        end_at: initialValues.end_at ?? '',
        location: initialValues.location ?? '',
        department_id: initialValues.department_id ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const submitFn = method === 'post' ? post : put;
        submitFn(action, {
            onSuccess: () => {
                if (method === 'post') {
                    reset();
                }
                onSuccess?.();
            },
        });
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
                <InputLabel htmlFor="start_at" value="Starts" />
                <TextInput
                    id="start_at"
                    type="datetime-local"
                    className="mt-1 block w-full"
                    value={data.start_at}
                    onChange={(e) => setData('start_at', e.target.value)}
                    required
                />
                <InputError message={errors.start_at} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="end_at" value="Ends (optional)" />
                <TextInput
                    id="end_at"
                    type="datetime-local"
                    className="mt-1 block w-full"
                    value={data.end_at}
                    onChange={(e) => setData('end_at', e.target.value)}
                />
                <InputError message={errors.end_at} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="location" value="Location" />
                <TextInput
                    id="location"
                    className="mt-1 block w-full"
                    value={data.location}
                    onChange={(e) => setData('location', e.target.value)}
                />
                <InputError message={errors.location} className="mt-2" />
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
                <p className="self-end text-sm text-slate-900">This will be posted to your own department only.</p>
            )}

            <div className="sm:col-span-2">
                <InputLabel htmlFor="description" value="Description" />
                <textarea
                    id="description"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    rows={4}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                />
                <InputError message={errors.description} className="mt-2" />
            </div>

            <div className="flex gap-3 sm:col-span-2">
                <PrimaryButton disabled={processing}>{submitLabel}</PrimaryButton>
                <SecondaryButton
                    type="button"
                    onClick={() => (onCancel ? onCancel() : onCancelHref && (window.location.href = onCancelHref))}
                >
                    Cancel
                </SecondaryButton>
            </div>
        </form>
    );
}
