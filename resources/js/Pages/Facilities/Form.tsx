import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface FacilityFormValues {
    name: string;
    type: string;
    department_id: string;
    location: string;
    capacity: string;
    description: string;
    is_active: boolean;
}

export default function FacilityForm({
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
    initialValues: Partial<FacilityFormValues>;
    departments: { id: number; name: string }[];
    isAdmin: boolean;
    submitLabel: string;
    onCancelHref?: string;
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<FacilityFormValues>({
        name: initialValues.name ?? '',
        type: initialValues.type ?? '',
        department_id: initialValues.department_id ?? '',
        location: initialValues.location ?? '',
        capacity: initialValues.capacity ?? '',
        description: initialValues.description ?? '',
        is_active: initialValues.is_active ?? true,
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
                <InputLabel htmlFor="name" value="Name" />
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
                <InputLabel htmlFor="type" value="Type" />
                <TextInput
                    id="type"
                    className="mt-1 block w-full"
                    placeholder="e.g. Laboratory, Farm, Classroom"
                    value={data.type}
                    onChange={(e) => setData('type', e.target.value)}
                    required
                />
                <InputError message={errors.type} className="mt-2" />
            </div>

            {isAdmin ? (
                <div>
                    <InputLabel htmlFor="department_id" value="Department" />
                    <select
                        id="department_id"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={data.department_id}
                        onChange={(e) => setData('department_id', e.target.value)}
                    >
                        <option value="">Shared / College-wide</option>
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.department_id} className="mt-2" />
                </div>
            ) : (
                <p className="self-end text-sm text-slate-900">This facility belongs to your own department.</p>
            )}

            <div>
                <InputLabel htmlFor="location" value="Location" />
                <TextInput
                    id="location"
                    className="mt-1 block w-full"
                    placeholder="e.g. Building A, 2nd Floor"
                    value={data.location}
                    onChange={(e) => setData('location', e.target.value)}
                />
                <InputError message={errors.location} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="capacity" value="Capacity" />
                <TextInput
                    id="capacity"
                    type="number"
                    min={0}
                    className="mt-1 block w-full"
                    value={data.capacity}
                    onChange={(e) => setData('capacity', e.target.value)}
                />
                <InputError message={errors.capacity} className="mt-2" />
            </div>

            <div className="flex items-end">
                <label className="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        type="checkbox"
                        checked={data.is_active}
                        onChange={(e) => setData('is_active', e.target.checked)}
                    />
                    Active
                </label>
                <InputError message={errors.is_active} className="ml-2" />
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
