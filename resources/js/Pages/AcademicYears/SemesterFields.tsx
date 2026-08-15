import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface AcademicYearOption {
    id: number;
    start_year: number;
    end_year: number;
}

interface SemesterDetail {
    id: number;
    academic_year_id: number;
    term: string;
    start_date: string | null;
    end_date: string | null;
    is_current: boolean;
}

export default function SemesterFields({
    semester,
    academicYears,
    onCancel,
    onSuccess,
}: {
    semester?: SemesterDetail;
    academicYears: AcademicYearOption[];
    onCancel: () => void;
    onSuccess?: () => void;
}) {
    const isEdit = Boolean(semester);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        academic_year_id: semester ? String(semester.academic_year_id) : String(academicYears[0]?.id ?? ''),
        term: semester?.term ?? 'FIRST',
        start_date: semester?.start_date ?? '',
        end_date: semester?.end_date ?? '',
        is_current: semester?.is_current ?? false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = {
            onSuccess: () => {
                if (!isEdit) {
                    reset();
                }
                onSuccess?.();
            },
        };
        if (isEdit && semester) {
            put(route('semesters.update', semester.id), options);
        } else {
            post(route('semesters.store'), options);
        }
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <InputLabel htmlFor="academic_year_id" value="Academic Year" />
                <select
                    id="academic_year_id"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.academic_year_id}
                    onChange={(e) => setData('academic_year_id', e.target.value)}
                    required
                >
                    {academicYears.map((y) => (
                        <option key={y.id} value={y.id}>
                            {y.start_year}-{y.end_year}
                        </option>
                    ))}
                </select>
                <InputError message={errors.academic_year_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="term" value="Term" />
                <select
                    id="term"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.term}
                    onChange={(e) => setData('term', e.target.value)}
                >
                    <option value="FIRST">1st Semester</option>
                    <option value="SECOND">2nd Semester</option>
                    <option value="SUMMER">Summer</option>
                </select>
                <InputError message={errors.term} className="mt-2" />
            </div>

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
                <InputLabel htmlFor="end_date" value="End Date" />
                <TextInput
                    id="end_date"
                    type="date"
                    className="mt-1 block w-full"
                    value={data.end_date}
                    onChange={(e) => setData('end_date', e.target.value)}
                />
                <InputError message={errors.end_date} className="mt-2" />
            </div>

            <div className="sm:col-span-2">
                <label className="flex items-center gap-2">
                    <Checkbox checked={data.is_current} onChange={(e) => setData('is_current', e.target.checked)} />
                    <span className="text-sm text-slate-700">
                        Mark as the current semester (unsets any other semester marked current)
                    </span>
                </label>
            </div>

            <div className="flex gap-3 sm:col-span-2">
                <PrimaryButton disabled={processing}>{isEdit ? 'Save Changes' : 'Create Semester'}</PrimaryButton>
                <SecondaryButton type="button" onClick={onCancel}>
                    Cancel
                </SecondaryButton>
            </div>
        </form>
    );
}
