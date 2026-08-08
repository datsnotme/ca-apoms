import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface EquipmentFormValues {
    name: string;
    type: string;
    department_id: string;
    facility_id: string;
    serial_number: string;
    status: string;
    description: string;
}

export default function EquipmentForm({
    action,
    method,
    initialValues,
    departments,
    facilities,
    statuses,
    isAdmin,
    showStatus,
    submitLabel,
    onCancelHref,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<EquipmentFormValues>;
    departments: { id: number; name: string }[];
    facilities: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
    isAdmin: boolean;
    showStatus: boolean;
    submitLabel: string;
    onCancelHref: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<EquipmentFormValues>({
        name: initialValues.name ?? '',
        type: initialValues.type ?? '',
        department_id: initialValues.department_id ?? '',
        facility_id: initialValues.facility_id ?? '',
        serial_number: initialValues.serial_number ?? '',
        status: initialValues.status ?? 'available',
        description: initialValues.description ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const submitFn = method === 'post' ? post : put;
        submitFn(action);
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
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
                    placeholder="e.g. Microscope, Tractor, Laptop"
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
                <p className="self-end text-sm text-slate-500">This equipment belongs to your own department.</p>
            )}

            <div>
                <InputLabel htmlFor="facility_id" value="Facility (optional)" />
                <select
                    id="facility_id"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.facility_id}
                    onChange={(e) => setData('facility_id', e.target.value)}
                >
                    <option value="">No fixed location</option>
                    {facilities.map((f) => (
                        <option key={f.id} value={f.id}>
                            {f.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.facility_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="serial_number" value="Serial Number (optional)" />
                <TextInput
                    id="serial_number"
                    className="mt-1 block w-full"
                    value={data.serial_number}
                    onChange={(e) => setData('serial_number', e.target.value)}
                />
                <InputError message={errors.serial_number} className="mt-2" />
            </div>

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
                    <p className="mt-1 text-xs text-slate-500">
                        Normally set automatically by borrowing/return/maintenance actions — change this manually only to correct a mistake.
                    </p>
                </div>
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
                <SecondaryButton type="button" onClick={() => (window.location.href = onCancelHref)}>
                    Cancel
                </SecondaryButton>
            </div>
        </form>
    );
}
