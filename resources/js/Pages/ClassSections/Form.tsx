import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface ClassSectionFormValues {
    course_id: string;
    semester_id: string;
    section_label: string;
    max_students: string;
    status: 'open' | 'closed';
    faculty_id: string;
}

const selectClass =
    'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600';

export default function ClassSectionForm({
    action,
    method,
    initialValues,
    courses,
    semesters,
    faculty,
    statuses,
    submitLabel,
    onCancelHref,
    onCancel,
    onSuccess,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<ClassSectionFormValues>;
    courses: { id: number; code: string; title: string }[];
    semesters: { id: number; label: string }[];
    faculty: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
    submitLabel: string;
    onCancelHref?: string;
    onCancel?: () => void;
    onSuccess?: () => void;
}) {
    const { data, setData, post, put, processing, errors, reset } = useForm<ClassSectionFormValues>({
        course_id: initialValues.course_id ?? String(courses[0]?.id ?? ''),
        semester_id: initialValues.semester_id ?? String(semesters[0]?.id ?? ''),
        section_label: initialValues.section_label ?? '',
        max_students: initialValues.max_students ?? '40',
        status: initialValues.status ?? 'open',
        faculty_id: initialValues.faculty_id ?? '',
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
                <InputLabel htmlFor="course_id" value="Course" />
                <select id="course_id" className={selectClass} value={data.course_id} onChange={(e) => setData('course_id', e.target.value)}>
                    {courses.map((c) => (
                        <option key={c.id} value={c.id}>
                            {c.code} — {c.title}
                        </option>
                    ))}
                </select>
                <InputError message={errors.course_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="semester_id" value="Semester" />
                <select id="semester_id" className={selectClass} value={data.semester_id} onChange={(e) => setData('semester_id', e.target.value)}>
                    {semesters.map((s) => (
                        <option key={s.id} value={s.id}>
                            {s.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.semester_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="section_label" value="Section Label" />
                <TextInput
                    id="section_label"
                    className="mt-1 block w-full"
                    value={data.section_label}
                    onChange={(e) => setData('section_label', e.target.value.toUpperCase())}
                    placeholder="e.g. A, 1"
                    required
                />
                <InputError message={errors.section_label} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="max_students" value="Max Students" />
                <TextInput
                    id="max_students"
                    type="number"
                    className="mt-1 block w-full"
                    value={data.max_students}
                    onChange={(e) => setData('max_students', e.target.value)}
                    required
                />
                <InputError message={errors.max_students} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="faculty_id" value="Primary Faculty" />
                <select id="faculty_id" className={selectClass} value={data.faculty_id} onChange={(e) => setData('faculty_id', e.target.value)}>
                    <option value="">— Unassigned —</option>
                    {faculty.map((f) => (
                        <option key={f.id} value={f.id}>
                            {f.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.faculty_id} className="mt-2" />
            </div>

            <div>
                <InputLabel htmlFor="status" value="Status" />
                <select
                    id="status"
                    className={selectClass}
                    value={data.status}
                    onChange={(e) => setData('status', e.target.value as 'open' | 'closed')}
                >
                    {statuses.map((s) => (
                        <option key={s.value} value={s.value}>
                            {s.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.status} className="mt-2" />
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
