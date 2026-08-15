import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface ProgramFormValues {
    department_id: string;
    code: string;
    name: string;
    degree_type: string;
    major: string;
    required_total_units: string;
    duration_years: string;
    status: 'active' | 'inactive';
}

export default function ProgramForm({
    action,
    method,
    initialValues,
    departments,
    submitLabel,
    onCancelHref,
    onCancel,
    onSuccess,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<ProgramFormValues>;
    departments: { id: number; name: string }[];
    submitLabel: string;
    onCancelHref?: string;
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<ProgramFormValues>({
        department_id: initialValues.department_id ?? String(departments[0]?.id ?? ''),
        code: initialValues.code ?? '',
        name: initialValues.name ?? '',
        degree_type: initialValues.degree_type ?? 'Bachelor',
        major: initialValues.major ?? '',
        required_total_units: initialValues.required_total_units ?? '',
        duration_years: initialValues.duration_years ?? '4',
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

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <InputLabel htmlFor="department_id" value="Department" />
                <select
                    id="department_id"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.department_id}
                    onChange={(e) => setData('department_id', e.target.value)}
                    required
                >
                    {departments.map((d) => (
                        <option key={d.id} value={d.id}>
                            {d.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.department_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="code" value="Program Code" />
                <TextInput
                    id="code"
                    className="mt-1 block w-full"
                    value={data.code}
                    onChange={(e) => setData('code', e.target.value.toUpperCase())}
                    required
                />
                <InputError message={errors.code} className="mt-2" />
            </div>

            <div className="sm:col-span-2">
                <InputLabel htmlFor="name" value="Program Name" />
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
                <InputLabel htmlFor="degree_type" value="Degree Type" />
                <TextInput
                    id="degree_type"
                    className="mt-1 block w-full"
                    value={data.degree_type}
                    onChange={(e) => setData('degree_type', e.target.value)}
                />
                <InputError message={errors.degree_type} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="major" value="Major / Specialization" />
                <TextInput
                    id="major"
                    className="mt-1 block w-full"
                    value={data.major}
                    onChange={(e) => setData('major', e.target.value)}
                />
                <InputError message={errors.major} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="required_total_units" value="Required Total Units" />
                <TextInput
                    id="required_total_units"
                    type="number"
                    className="mt-1 block w-full"
                    value={data.required_total_units}
                    onChange={(e) => setData('required_total_units', e.target.value)}
                />
                <InputError message={errors.required_total_units} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="duration_years" value="Duration (years)" />
                <TextInput
                    id="duration_years"
                    type="number"
                    className="mt-1 block w-full"
                    value={data.duration_years}
                    onChange={(e) => setData('duration_years', e.target.value)}
                />
                <InputError message={errors.duration_years} className="mt-2" />
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
