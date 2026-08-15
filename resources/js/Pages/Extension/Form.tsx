import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface ExtensionProjectFormValues {
    title: string;
    description: string;
    status: string;
    start_date: string;
    end_date: string;
    funding_source: string;
    department_id: string;
}

export default function ExtensionProjectForm({
    action,
    method,
    initialValues,
    departments,
    isAdmin,
    statuses,
    showStatus,
    submitLabel,
    onCancelHref,
    onCancel,
    onSuccess,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<ExtensionProjectFormValues>;
    departments: { id: number; name: string }[];
    isAdmin: boolean;
    statuses: { value: string; label: string }[];
    showStatus: boolean;
    submitLabel: string;
    onCancelHref?: string;
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<ExtensionProjectFormValues>({
        title: initialValues.title ?? '',
        description: initialValues.description ?? '',
        status: initialValues.status ?? 'proposed',
        start_date: initialValues.start_date ?? '',
        end_date: initialValues.end_date ?? '',
        funding_source: initialValues.funding_source ?? '',
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

            {isAdmin ? (
                <div>
                    <InputLabel htmlFor="department_id" value="Department" />
                    <select
                        id="department_id"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={data.department_id}
                        onChange={(e) => setData('department_id', e.target.value)}
                        required
                    >
                        <option value="">Select a department…</option>
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.department_id} className="mt-2" />
                </div>
            ) : (
                <p className="self-end text-sm text-slate-900">This project belongs to your own department.</p>
            )}

            {showStatus && (
                <div>
                    <InputLabel htmlFor="status" value="Status" />
                    <select
                        id="status"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={data.status}
                        onChange={(e) => setData('status', e.target.value)}
                    >
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.status} className="mt-2" />
                </div>
            )}

            <div>
                <InputLabel htmlFor="start_date" value="Start Date" />
                <TextInput
                    id="start_date"
                    type="date"
                    className="mt-1 block w-full"
                    value={data.start_date}
                    onChange={(e) => setData('start_date', e.target.value)}
                />
                <InputError message={errors.start_date} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="end_date" value="End Date (optional)" />
                <TextInput
                    id="end_date"
                    type="date"
                    className="mt-1 block w-full"
                    value={data.end_date}
                    onChange={(e) => setData('end_date', e.target.value)}
                />
                <InputError message={errors.end_date} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="funding_source" value="Funding Source" />
                <TextInput
                    id="funding_source"
                    className="mt-1 block w-full"
                    value={data.funding_source}
                    onChange={(e) => setData('funding_source', e.target.value)}
                />
                <InputError message={errors.funding_source} className="mt-2" />
            </div>

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
