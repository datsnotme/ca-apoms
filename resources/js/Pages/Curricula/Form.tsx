import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface CurriculumFormValues {
    program_id: string;
    effective_academic_year_id: string;
    code: string;
    name: string;
    required_total_units: string;
    status: 'active' | 'inactive';
    notes: string;
}

export default function CurriculumForm({
    action,
    method,
    initialValues,
    programs,
    academicYears,
    submitLabel,
    onCancelHref,
    onCancel,
    onSuccess,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<CurriculumFormValues>;
    programs: { id: number; name: string }[];
    academicYears: { id: number; start_year: number; end_year: number }[];
    submitLabel: string;
    onCancelHref?: string;
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<CurriculumFormValues>({
        program_id: initialValues.program_id ?? String(programs[0]?.id ?? ''),
        effective_academic_year_id: initialValues.effective_academic_year_id ?? String(academicYears[0]?.id ?? ''),
        code: initialValues.code ?? '',
        name: initialValues.name ?? '',
        required_total_units: initialValues.required_total_units ?? '',
        status: initialValues.status ?? 'active',
        notes: initialValues.notes ?? '',
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
                <InputLabel htmlFor="program_id" value="Program" />
                <select
                    id="program_id"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.program_id}
                    onChange={(e) => setData('program_id', e.target.value)}
                >
                    {programs.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.program_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="effective_academic_year_id" value="Effective Academic Year" />
                <select
                    id="effective_academic_year_id"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.effective_academic_year_id}
                    onChange={(e) => setData('effective_academic_year_id', e.target.value)}
                >
                    {academicYears.map((y) => (
                        <option key={y.id} value={y.id}>
                            {y.start_year}-{y.end_year}
                        </option>
                    ))}
                </select>
                <InputError message={errors.effective_academic_year_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="code" value="Curriculum Code" />
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
                <InputLabel htmlFor="name" value="Curriculum Name" />
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

            <div className="sm:col-span-2">
                <InputLabel htmlFor="notes" value="Notes" />
                <textarea
                    id="notes"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    rows={2}
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                />
                <InputError message={errors.notes} className="mt-2" />
            </div>

            <div className="flex gap-3 sm:col-span-2">
                <PrimaryButton disabled={processing}>{submitLabel}</PrimaryButton>
                {(onCancel || onCancelHref) && (
                    <SecondaryButton
                        type="button"
                        onClick={() =>
                            onCancel ? onCancel() : onCancelHref && (window.location.href = onCancelHref)
                        }
                    >
                        Cancel
                    </SecondaryButton>
                )}
            </div>
        </form>
    );
}
