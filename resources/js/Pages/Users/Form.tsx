import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface UserFormValues {
    employee_number: string;
    surname: string;
    first_name: string;
    middle_name: string;
    suffix: string;
    email: string;
    username: string;
    contact_number: string;
    department_id: string;
    role: string;
    status: 'active' | 'inactive';
}

export default function UserForm({
    action,
    method,
    initialValues,
    departments,
    roles,
    submitLabel,
    onCancelHref,
    onCancel,
    onSuccess,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<UserFormValues>;
    departments: { id: number; name: string }[];
    roles: { value: string; label: string }[];
    submitLabel: string;
    onCancelHref?: string;
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<UserFormValues>({
        employee_number: initialValues.employee_number ?? '',
        surname: initialValues.surname ?? '',
        first_name: initialValues.first_name ?? '',
        middle_name: initialValues.middle_name ?? '',
        suffix: initialValues.suffix ?? '',
        email: initialValues.email ?? '',
        username: initialValues.username ?? '',
        contact_number: initialValues.contact_number ?? '',
        department_id: initialValues.department_id ?? '',
        role: initialValues.role ?? roles[0]?.value ?? '',
        status: initialValues.status ?? 'active',
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

    const needsDepartment = data.role === 'department-head' || data.role === 'faculty-member';

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <InputLabel htmlFor="employee_number" value="Employee Number" />
                <TextInput
                    id="employee_number"
                    className="mt-1 block w-full"
                    value={data.employee_number}
                    onChange={(e) => setData('employee_number', e.target.value)}
                    required
                />
                <InputError message={errors.employee_number} className="mt-2" />
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

            <div>
                <InputLabel htmlFor="surname" value="Surname" />
                <TextInput
                    id="surname"
                    className="mt-1 block w-full"
                    value={data.surname}
                    onChange={(e) => setData('surname', e.target.value)}
                    required
                />
                <InputError message={errors.surname} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="first_name" value="First Name" />
                <TextInput
                    id="first_name"
                    className="mt-1 block w-full"
                    value={data.first_name}
                    onChange={(e) => setData('first_name', e.target.value)}
                    required
                />
                <InputError message={errors.first_name} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="middle_name" value="Middle Name" />
                <TextInput
                    id="middle_name"
                    className="mt-1 block w-full"
                    value={data.middle_name}
                    onChange={(e) => setData('middle_name', e.target.value)}
                />
                <InputError message={errors.middle_name} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="suffix" value="Suffix" />
                <TextInput
                    id="suffix"
                    className="mt-1 block w-full"
                    value={data.suffix}
                    onChange={(e) => setData('suffix', e.target.value)}
                />
                <InputError message={errors.suffix} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    className="mt-1 block w-full"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    required
                />
                <InputError message={errors.email} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="username" value="Username" />
                <TextInput
                    id="username"
                    className="mt-1 block w-full"
                    value={data.username}
                    onChange={(e) => setData('username', e.target.value)}
                    required
                />
                <InputError message={errors.username} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="contact_number" value="Contact Number" />
                <TextInput
                    id="contact_number"
                    className="mt-1 block w-full"
                    value={data.contact_number}
                    onChange={(e) => setData('contact_number', e.target.value)}
                />
                <InputError message={errors.contact_number} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="role" value="Role" />
                <select
                    id="role"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.role}
                    onChange={(e) => setData('role', e.target.value)}
                >
                    {roles.map((r) => (
                        <option key={r.value} value={r.value}>
                            {r.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.role} className="mt-2" />
            </div>

            {needsDepartment && (
                <div className="sm:col-span-2">
                    <InputLabel htmlFor="department_id" value="Department" />
                    <select
                        id="department_id"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={data.department_id}
                        onChange={(e) => setData('department_id', e.target.value)}
                    >
                        <option value="">Select department&hellip;</option>
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.department_id} className="mt-2" />
                </div>
            )}

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
