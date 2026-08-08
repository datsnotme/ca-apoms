import { cloneElement, FormEventHandler, isValidElement, useId, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface StudentFormValues {
    student_number: string;
    surname: string;
    first_name: string;
    middle_name: string;
    suffix: string;
    sex: string;
    birth_date: string;
    civil_status: string;
    citizenship: string;
    contact_number: string;
    email: string;

    department_id: string;
    program_id: string;
    curriculum_id: string;
    year_level_id: string;
    section_id: string;
    adviser_id: string;

    admission_type: string;
    date_admitted: string;
    expected_graduation_date: string;
    scholarship_status: string;
    classification: string;
    status: string;
    status_reason: string;

    guardian_name: string;
    guardian_relationship: string;
    guardian_contact_number: string;
    guardian_address: string;

    emergency_name: string;
    emergency_relationship: string;
    emergency_contact_number: string;
    emergency_address: string;

    permanent_address: string;
    current_address: string;
}

interface Option {
    id: number;
    name: string;
}

const selectClass =
    'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600';

function Field({
    label,
    error,
    children,
    span,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
    span?: boolean;
}) {
    const id = useId();

    return (
        <div className={span ? 'sm:col-span-2' : undefined}>
            <InputLabel htmlFor={id} value={label} />
            {isValidElement(children) ? cloneElement(children as React.ReactElement<{ id?: string }>, { id }) : children}
            <InputError message={error} className="mt-2" />
        </div>
    );
}

function SectionHeading({ title, description }: { title: string; description?: string }) {
    return (
        <div className="sm:col-span-2">
            <h3 className="text-sm font-semibold text-slate-900">{title}</h3>
            {description && <p className="mt-0.5 text-xs text-slate-500">{description}</p>}
        </div>
    );
}

export default function StudentForm({
    action,
    method,
    initialValues,
    departments,
    programs,
    curricula,
    yearLevels,
    sections,
    advisers,
    classifications,
    statuses,
    submitLabel,
    onCancelHref,
    showStatusReason,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<StudentFormValues>;
    departments: Option[];
    programs: (Option & { department_id: number })[];
    curricula: (Option & { program_id: number })[];
    yearLevels: { id: number; level: number; label: string }[];
    sections: { id: number; name: string; program_id: number; year_level_id: number }[];
    advisers: Option[];
    classifications: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
    submitLabel: string;
    onCancelHref?: string;
    showStatusReason?: boolean;
}) {
    const { data, setData, post, put, processing, errors } = useForm<StudentFormValues>({
        student_number: initialValues.student_number ?? '',
        surname: initialValues.surname ?? '',
        first_name: initialValues.first_name ?? '',
        middle_name: initialValues.middle_name ?? '',
        suffix: initialValues.suffix ?? '',
        sex: initialValues.sex ?? '',
        birth_date: initialValues.birth_date ?? '',
        civil_status: initialValues.civil_status ?? '',
        citizenship: initialValues.citizenship ?? '',
        contact_number: initialValues.contact_number ?? '',
        email: initialValues.email ?? '',

        department_id: initialValues.department_id ?? String(departments[0]?.id ?? ''),
        program_id: initialValues.program_id ?? '',
        curriculum_id: initialValues.curriculum_id ?? '',
        year_level_id: initialValues.year_level_id ?? String(yearLevels[0]?.id ?? ''),
        section_id: initialValues.section_id ?? '',
        adviser_id: initialValues.adviser_id ?? '',

        admission_type: initialValues.admission_type ?? '',
        date_admitted: initialValues.date_admitted ?? '',
        expected_graduation_date: initialValues.expected_graduation_date ?? '',
        scholarship_status: initialValues.scholarship_status ?? '',
        classification: initialValues.classification ?? classifications[0]?.value ?? 'regular',
        status: initialValues.status ?? statuses[0]?.value ?? 'active',
        status_reason: initialValues.status_reason ?? '',

        guardian_name: initialValues.guardian_name ?? '',
        guardian_relationship: initialValues.guardian_relationship ?? '',
        guardian_contact_number: initialValues.guardian_contact_number ?? '',
        guardian_address: initialValues.guardian_address ?? '',

        emergency_name: initialValues.emergency_name ?? '',
        emergency_relationship: initialValues.emergency_relationship ?? '',
        emergency_contact_number: initialValues.emergency_contact_number ?? '',
        emergency_address: initialValues.emergency_address ?? '',

        permanent_address: initialValues.permanent_address ?? '',
        current_address: initialValues.current_address ?? '',
    });

    const filteredPrograms = useMemo(
        () => programs.filter((p) => String(p.department_id) === data.department_id),
        [programs, data.department_id],
    );

    const filteredCurricula = useMemo(
        () => curricula.filter((c) => String(c.program_id) === data.program_id),
        [curricula, data.program_id],
    );

    const filteredSections = useMemo(
        () =>
            sections.filter(
                (s) => String(s.program_id) === data.program_id && String(s.year_level_id) === data.year_level_id,
            ),
        [sections, data.program_id, data.year_level_id],
    );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const submitFn = method === 'post' ? post : put;
        submitFn(action);
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <SectionHeading title="Personal Information" />

            <Field label="Student Number" error={errors.student_number}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.student_number}
                    onChange={(e) => setData('student_number', e.target.value)}
                    required
                />
            </Field>

            <Field label="Sex" error={errors.sex}>
                <select className={selectClass} value={data.sex} onChange={(e) => setData('sex', e.target.value)}>
                    <option value="">— Select —</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </Field>

            <Field label="Surname" error={errors.surname}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.surname}
                    onChange={(e) => setData('surname', e.target.value)}
                    required
                />
            </Field>

            <Field label="First Name" error={errors.first_name}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.first_name}
                    onChange={(e) => setData('first_name', e.target.value)}
                    required
                />
            </Field>

            <Field label="Middle Name" error={errors.middle_name}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.middle_name}
                    onChange={(e) => setData('middle_name', e.target.value)}
                />
            </Field>

            <Field label="Suffix" error={errors.suffix}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.suffix}
                    onChange={(e) => setData('suffix', e.target.value)}
                />
            </Field>

            <Field label="Birth Date" error={errors.birth_date}>
                <TextInput
                    type="date"
                    className="mt-1 block w-full"
                    value={data.birth_date}
                    onChange={(e) => setData('birth_date', e.target.value)}
                />
            </Field>

            <Field label="Civil Status" error={errors.civil_status}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.civil_status}
                    onChange={(e) => setData('civil_status', e.target.value)}
                />
            </Field>

            <Field label="Citizenship" error={errors.citizenship}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.citizenship}
                    onChange={(e) => setData('citizenship', e.target.value)}
                />
            </Field>

            <Field label="Contact Number" error={errors.contact_number}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.contact_number}
                    onChange={(e) => setData('contact_number', e.target.value)}
                />
            </Field>

            <Field label="Email" error={errors.email} span>
                <TextInput
                    type="email"
                    className="mt-1 block w-full"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                />
            </Field>

            <SectionHeading title="Academic Placement" />

            <Field label="Department" error={errors.department_id}>
                <select
                    className={selectClass}
                    value={data.department_id}
                    onChange={(e) => {
                        setData('department_id', e.target.value);
                        setData('program_id', '');
                        setData('curriculum_id', '');
                    }}
                >
                    {departments.map((d) => (
                        <option key={d.id} value={d.id}>
                            {d.name}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Program" error={errors.program_id}>
                <select
                    className={selectClass}
                    value={data.program_id}
                    onChange={(e) => {
                        setData('program_id', e.target.value);
                        setData('curriculum_id', '');
                        setData('section_id', '');
                    }}
                >
                    <option value="">— Select —</option>
                    {filteredPrograms.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.name}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Curriculum" error={errors.curriculum_id}>
                <select
                    className={selectClass}
                    value={data.curriculum_id}
                    onChange={(e) => setData('curriculum_id', e.target.value)}
                >
                    <option value="">— Select —</option>
                    {filteredCurricula.map((c) => (
                        <option key={c.id} value={c.id}>
                            {c.name}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Year Level" error={errors.year_level_id}>
                <select
                    className={selectClass}
                    value={data.year_level_id}
                    onChange={(e) => {
                        setData('year_level_id', e.target.value);
                        setData('section_id', '');
                    }}
                >
                    {yearLevels.map((y) => (
                        <option key={y.id} value={y.id}>
                            {y.label}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Section" error={errors.section_id}>
                <select
                    className={selectClass}
                    value={data.section_id}
                    onChange={(e) => setData('section_id', e.target.value)}
                >
                    <option value="">— Unassigned —</option>
                    {filteredSections.map((s) => (
                        <option key={s.id} value={s.id}>
                            {s.name}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Adviser" error={errors.adviser_id}>
                <select
                    className={selectClass}
                    value={data.adviser_id}
                    onChange={(e) => setData('adviser_id', e.target.value)}
                >
                    <option value="">— Unassigned —</option>
                    {advisers.map((a) => (
                        <option key={a.id} value={a.id}>
                            {a.name}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Classification" error={errors.classification}>
                <select
                    className={selectClass}
                    value={data.classification}
                    onChange={(e) => setData('classification', e.target.value)}
                >
                    {classifications.map((c) => (
                        <option key={c.value} value={c.value}>
                            {c.label}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Status" error={errors.status}>
                <select
                    className={selectClass}
                    value={data.status}
                    onChange={(e) => setData('status', e.target.value)}
                >
                    {statuses.map((s) => (
                        <option key={s.value} value={s.value}>
                            {s.label}
                        </option>
                    ))}
                </select>
            </Field>

            {showStatusReason && (
                <Field label="Reason for Status Change" error={errors.status_reason} span>
                    <TextInput
                        className="mt-1 block w-full"
                        value={data.status_reason}
                        onChange={(e) => setData('status_reason', e.target.value)}
                        placeholder="Only needed if the status above is being changed"
                    />
                </Field>
            )}

            <Field label="Admission Type" error={errors.admission_type}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.admission_type}
                    onChange={(e) => setData('admission_type', e.target.value)}
                    placeholder="e.g. Freshman, Transferee"
                />
            </Field>

            <Field label="Date Admitted" error={errors.date_admitted}>
                <TextInput
                    type="date"
                    className="mt-1 block w-full"
                    value={data.date_admitted}
                    onChange={(e) => setData('date_admitted', e.target.value)}
                />
            </Field>

            <Field label="Expected Graduation Date" error={errors.expected_graduation_date}>
                <TextInput
                    type="date"
                    className="mt-1 block w-full"
                    value={data.expected_graduation_date}
                    onChange={(e) => setData('expected_graduation_date', e.target.value)}
                />
            </Field>

            <Field label="Scholarship Status" error={errors.scholarship_status}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.scholarship_status}
                    onChange={(e) => setData('scholarship_status', e.target.value)}
                />
            </Field>

            <SectionHeading title="Guardian" description="Optional — leave blank if not applicable." />

            <Field label="Guardian Name" error={errors.guardian_name}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.guardian_name}
                    onChange={(e) => setData('guardian_name', e.target.value)}
                />
            </Field>

            <Field label="Relationship" error={errors.guardian_relationship}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.guardian_relationship}
                    onChange={(e) => setData('guardian_relationship', e.target.value)}
                />
            </Field>

            <Field label="Contact Number" error={errors.guardian_contact_number}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.guardian_contact_number}
                    onChange={(e) => setData('guardian_contact_number', e.target.value)}
                />
            </Field>

            <Field label="Address" error={errors.guardian_address}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.guardian_address}
                    onChange={(e) => setData('guardian_address', e.target.value)}
                />
            </Field>

            <SectionHeading title="Emergency Contact" description="Optional — leave blank if not applicable." />

            <Field label="Name" error={errors.emergency_name}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.emergency_name}
                    onChange={(e) => setData('emergency_name', e.target.value)}
                />
            </Field>

            <Field label="Relationship" error={errors.emergency_relationship}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.emergency_relationship}
                    onChange={(e) => setData('emergency_relationship', e.target.value)}
                />
            </Field>

            <Field label="Contact Number" error={errors.emergency_contact_number}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.emergency_contact_number}
                    onChange={(e) => setData('emergency_contact_number', e.target.value)}
                />
            </Field>

            <Field label="Address" error={errors.emergency_address}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.emergency_address}
                    onChange={(e) => setData('emergency_address', e.target.value)}
                />
            </Field>

            <SectionHeading title="Addresses" />

            <Field label="Permanent Address" error={errors.permanent_address}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.permanent_address}
                    onChange={(e) => setData('permanent_address', e.target.value)}
                />
            </Field>

            <Field label="Current Address" error={errors.current_address}>
                <TextInput
                    className="mt-1 block w-full"
                    value={data.current_address}
                    onChange={(e) => setData('current_address', e.target.value)}
                />
            </Field>

            <div className="flex gap-3 sm:col-span-2">
                <PrimaryButton disabled={processing}>{submitLabel}</PrimaryButton>
                {onCancelHref && (
                    <SecondaryButton type="button" onClick={() => (window.location.href = onCancelHref)}>
                        Cancel
                    </SecondaryButton>
                )}
            </div>
        </form>
    );
}
