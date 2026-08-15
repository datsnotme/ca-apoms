import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface AcademicYearFormValues {
    start_year: string;
    end_year: string;
    is_current: boolean;
}

export default function AcademicYearForm({
    action,
    method,
    initialValues,
    submitLabel,
    onCancelHref,
    onCancel,
    onSuccess,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<AcademicYearFormValues>;
    submitLabel: string;
    onCancelHref?: string;
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<AcademicYearFormValues>({
        start_year: initialValues.start_year ?? '',
        end_year: initialValues.end_year ?? '',
        is_current: initialValues.is_current ?? false,
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

    function onStartYearChange(value: string) {
        setData('start_year', value);
        const numeric = Number(value);
        if (!Number.isNaN(numeric) && value !== '') {
            setData('end_year', String(numeric + 1));
        }
    }

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <InputLabel htmlFor="start_year" value="Start Year" />
                <TextInput
                    id="start_year"
                    type="number"
                    className="mt-1 block w-full"
                    value={data.start_year}
                    onChange={(e) => onStartYearChange(e.target.value)}
                    required
                />
                <InputError message={errors.start_year} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="end_year" value="End Year" />
                <TextInput
                    id="end_year"
                    type="number"
                    className="mt-1 block w-full"
                    value={data.end_year}
                    onChange={(e) => setData('end_year', e.target.value)}
                    required
                />
                <InputError message={errors.end_year} className="mt-2" />
            </div>

            <div className="sm:col-span-2">
                <label className="flex items-center gap-2">
                    <Checkbox
                        checked={data.is_current}
                        onChange={(e) => setData('is_current', e.target.checked)}
                    />
                    <span className="text-sm text-slate-700">
                        Mark as the current academic year (unsets any other year marked current)
                    </span>
                </label>
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
