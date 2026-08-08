import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface TaskFormValues {
    title: string;
    description: string;
    assigned_to: string;
    due_date: string;
}

export default function TaskForm({
    action,
    method,
    initialValues,
    assigneeOptions,
    submitLabel,
    onCancelHref,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<TaskFormValues>;
    assigneeOptions: { id: number; name: string }[];
    submitLabel: string;
    onCancelHref: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<TaskFormValues>({
        title: initialValues.title ?? '',
        description: initialValues.description ?? '',
        assigned_to: initialValues.assigned_to ?? '',
        due_date: initialValues.due_date ?? '',
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
                <InputLabel htmlFor="assigned_to" value="Assigned To" />
                <select
                    id="assigned_to"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.assigned_to}
                    onChange={(e) => setData('assigned_to', e.target.value)}
                >
                    <option value="">Myself</option>
                    {assigneeOptions.map((u) => (
                        <option key={u.id} value={u.id}>
                            {u.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.assigned_to} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="due_date" value="Due Date" />
                <TextInput
                    id="due_date"
                    type="date"
                    className="mt-1 block w-full"
                    value={data.due_date}
                    onChange={(e) => setData('due_date', e.target.value)}
                />
                <InputError message={errors.due_date} className="mt-2" />
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
                <SecondaryButton type="button" onClick={() => (window.location.href = onCancelHref)}>
                    Cancel
                </SecondaryButton>
            </div>
        </form>
    );
}
