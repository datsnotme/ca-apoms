import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface DepartmentFormValues {
    code: string;
    name: string;
    description: string;
    department_head_id: string;
    office_location: string;
    contact_info: string;
    status: 'active' | 'inactive';
}

export default function DepartmentForm({
    action,
    method,
    initialValues,
    potentialHeads,
    submitLabel,
    onCancelHref,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<DepartmentFormValues>;
    potentialHeads: { id: number; name: string }[];
    submitLabel: string;
    onCancelHref: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<DepartmentFormValues>({
        code: initialValues.code ?? '',
        name: initialValues.name ?? '',
        description: initialValues.description ?? '',
        department_head_id: initialValues.department_head_id ?? '',
        office_location: initialValues.office_location ?? '',
        contact_info: initialValues.contact_info ?? '',
        status: initialValues.status ?? 'active',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const submitFn = method === 'post' ? post : put;
        submitFn(action);
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <InputLabel htmlFor="code" value="Department Code" />
                <TextInput
                    id="code"
                    className="mt-1 block w-full"
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value.toUpperCase())}
                    required
                />
                <InputError message={errors.code} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="status" value="Status" />
                <select
                    id="status"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.status}
                    onChange={(e) => setData('status', e.target.value as 'active' | 'inactive')}
                >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <InputError message={errors.status} className="mt-2" />
            </div>

            <div className="sm:col-span-2">
                <InputLabel htmlFor="name" value="Department Name" />
                <TextInput
                    id="name"
                    className="mt-1 block w-full"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    required
                />
                <InputError message={errors.name} className="mt-2" />
            </div>

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

            <div>
                <InputLabel htmlFor="department_head_id" value="Department Head" />
                <select
                    id="department_head_id"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.department_head_id}
                    onChange={(e) => setData('department_head_id', e.target.value)}
                >
                    <option value="">Unassigned</option>
                    {potentialHeads.map((u) => (
                        <option key={u.id} value={u.id}>
                            {u.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.department_head_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="office_location" value="Office Location" />
                <TextInput
                    id="office_location"
                    className="mt-1 block w-full"
                    value={data.office_location}
                    onChange={(e) => setData('office_location', e.target.value)}
                />
                <InputError message={errors.office_location} className="mt-2" />
            </div>

            <div className="sm:col-span-2">
                <InputLabel htmlFor="contact_info" value="Contact Info" />
                <TextInput
                    id="contact_info"
                    className="mt-1 block w-full"
                    value={data.contact_info}
                    onChange={(e) => setData('contact_info', e.target.value)}
                />
                <InputError message={errors.contact_info} className="mt-2" />
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
