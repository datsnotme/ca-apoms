import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

const CATEGORIES = [
    ['crop_science', 'Crop Science'],
    ['animal_science', 'Animal Science'],
    ['soil_science', 'Soil Science'],
    ['agricultural_economics', 'Agricultural Economics'],
    ['agricultural_engineering', 'Agricultural Engineering'],
    ['agribusiness', 'Agribusiness'],
    ['agricultural_extension', 'Agricultural Extension'],
    ['horticulture', 'Horticulture'],
    ['plant_pathology', 'Plant Pathology'],
    ['entomology', 'Entomology'],
    ['food_science', 'Food Science'],
    ['farm_management', 'Farm Management'],
    ['research', 'Research'],
    ['thesis', 'Thesis'],
    ['practicum', 'Practicum'],
    ['internship', 'Internship'],
    ['general_education', 'General Education'],
    ['nstp_pe', 'NSTP / PE'],
] as const;

const BUCKETS = [
    ['general_education', 'General Education'],
    ['major_subjects', 'Major Subjects'],
    ['required_courses', 'Required Courses'],
    ['physical_education', 'Physical Education'],
    ['non_academic_requirement', 'Non-Academic Requirement'],
] as const;

interface CourseFormValues {
    department_id: string;
    code: string;
    title: string;
    description: string;
    units: string;
    lecture_hours: string;
    laboratory_hours: string;
    category: string;
    bucket: string;
    recommended_year_level: string;
    recommended_semester: string;
    is_active: boolean;
    prerequisite_ids: number[];
    corequisite_ids: number[];
}

export default function CourseForm({
    action,
    method,
    initialValues,
    departments,
    courses,
    submitLabel,
    onCancelHref,
    onCancel,
    onSuccess,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<CourseFormValues>;
    departments: { id: number; name: string }[];
    courses: { id: number; code: string; title: string }[];
    submitLabel: string;
    onCancelHref?: string;
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<CourseFormValues>({
        department_id: initialValues.department_id ?? String(departments[0]?.id ?? ''),
        code: initialValues.code ?? '',
        title: initialValues.title ?? '',
        description: initialValues.description ?? '',
        units: initialValues.units ?? '3',
        lecture_hours: initialValues.lecture_hours ?? '3',
        laboratory_hours: initialValues.laboratory_hours ?? '0',
        category: initialValues.category ?? CATEGORIES[0][0],
        bucket: initialValues.bucket ?? BUCKETS[1][0],
        recommended_year_level: initialValues.recommended_year_level ?? '',
        recommended_semester: initialValues.recommended_semester ?? '',
        is_active: initialValues.is_active ?? true,
        prerequisite_ids: initialValues.prerequisite_ids ?? [],
        corequisite_ids: initialValues.corequisite_ids ?? [],
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

    function toggleId(field: 'prerequisite_ids' | 'corequisite_ids', id: number) {
        setData(field, data[field].includes(id) ? data[field].filter((x) => x !== id) : [...data[field], id]);
    }

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <InputLabel htmlFor="department_id" value="Department" />
                <select
                    id="department_id"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.department_id}
                    onChange={(e) => setData('department_id', e.target.value)}
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
                <InputLabel htmlFor="code" value="Course Code" />
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
                <InputLabel htmlFor="title" value="Course Title" />
                <TextInput
                    id="title"
                    className="mt-1 block w-full"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    required
                />
                <InputError message={errors.title} className="mt-2" />
            </div>

            <div className="sm:col-span-2">
                <InputLabel htmlFor="description" value="Description" />
                <textarea
                    id="description"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    rows={2}
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                />
                <InputError message={errors.description} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="units" value="Units" />
                <TextInput
                    id="units"
                    type="number"
                    step="0.5"
                    className="mt-1 block w-full"
                    value={data.units}
                    onChange={(e) => setData('units', e.target.value)}
                    required
                />
                <InputError message={errors.units} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="category" value="Category" />
                <select
                    id="category"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.category}
                    onChange={(e) => setData('category', e.target.value)}
                >
                    {CATEGORIES.map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.category} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="bucket" value="Evaluation Form Section" />
                <select
                    id="bucket"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.bucket}
                    onChange={(e) => setData('bucket', e.target.value)}
                >
                    {BUCKETS.map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.bucket} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="lecture_hours" value="Lecture Hours" />
                <TextInput
                    id="lecture_hours"
                    type="number"
                    step="0.5"
                    className="mt-1 block w-full"
                    value={data.lecture_hours}
                    onChange={(e) => setData('lecture_hours', e.target.value)}
                />
                <InputError message={errors.lecture_hours} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="laboratory_hours" value="Laboratory Hours" />
                <TextInput
                    id="laboratory_hours"
                    type="number"
                    step="0.5"
                    className="mt-1 block w-full"
                    value={data.laboratory_hours}
                    onChange={(e) => setData('laboratory_hours', e.target.value)}
                />
                <InputError message={errors.laboratory_hours} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="recommended_year_level" value="Recommended Year Level" />
                <TextInput
                    id="recommended_year_level"
                    type="number"
                    min="1"
                    max="6"
                    className="mt-1 block w-full"
                    value={data.recommended_year_level}
                    onChange={(e) => setData('recommended_year_level', e.target.value)}
                />
                <InputError message={errors.recommended_year_level} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="recommended_semester" value="Recommended Semester" />
                <select
                    id="recommended_semester"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.recommended_semester}
                    onChange={(e) => setData('recommended_semester', e.target.value)}
                >
                    <option value="">—</option>
                    <option value="FIRST">1st Semester</option>
                    <option value="SECOND">2nd Semester</option>
                    <option value="SUMMER">Summer</option>
                </select>
                <InputError message={errors.recommended_semester} className="mt-2" />
            </div>

            <div className="sm:col-span-2">
                <label className="flex items-center gap-2">
                    <Checkbox checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} />
                    <span className="text-sm text-slate-700">Active (available for use in curricula)</span>
                </label>
            </div>

            {courses.length > 0 && (
                <>
                    <fieldset>
                        <legend className="block text-sm font-medium text-gray-700">Prerequisites</legend>
                        <div className="mt-1 max-h-40 overflow-y-auto rounded-md border border-slate-200 p-2">
                            {courses.map((c) => (
                                <label key={c.id} className="flex items-center gap-2 py-0.5 text-sm">
                                    <Checkbox
                                        checked={data.prerequisite_ids.includes(c.id)}
                                        onChange={() => toggleId('prerequisite_ids', c.id)}
                                    />
                                    {c.code} — {c.title}
                                </label>
                            ))}
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend className="block text-sm font-medium text-gray-700">Corequisites</legend>
                        <div className="mt-1 max-h-40 overflow-y-auto rounded-md border border-slate-200 p-2">
                            {courses.map((c) => (
                                <label key={c.id} className="flex items-center gap-2 py-0.5 text-sm">
                                    <Checkbox
                                        checked={data.corequisite_ids.includes(c.id)}
                                        onChange={() => toggleId('corequisite_ids', c.id)}
                                    />
                                    {c.code} — {c.title}
                                </label>
                            ))}
                        </div>
                    </fieldset>
                </>
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
