import { FormEventHandler, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface EnrollmentCourseRow {
    id: number;
    status: string;
    class_section: {
        id: number;
        section_label: string;
        course: { code: string; title: string; units: string };
    };
}

interface AvailableSection {
    id: number;
    section_label: string;
    max_students: number;
    enrolled_count: number;
    course: { id: number; code: string; title: string; units: string };
}

const COURSE_STATUS_OPTIONS = ['Enrolled', 'Added', 'Dropped', 'Withdrawn', 'Completed', 'Failed', 'Incomplete', 'Credited', 'Repeated'];

function CourseRow({ enrollmentId, row }: { enrollmentId: number; row: EnrollmentCourseRow }) {
    const [status, setStatus] = useState(row.status);
    const [processing, setProcessing] = useState(false);

    function changeStatus(next: string) {
        setStatus(next);
        setProcessing(true);
        router.patch(
            route('enrollments.courses.update', [enrollmentId, row.id]),
            { status: next },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    return (
        <tr>
            <td className="px-5 py-2.5">
                {row.class_section.course.code} — {row.class_section.course.title}
            </td>
            <td className="px-5 py-2.5 font-mono text-xs">{row.class_section.section_label}</td>
            <td className="px-5 py-2.5">{row.class_section.course.units}</td>
            <td className="px-5 py-2.5">
                <select
                    className="rounded-md border-gray-300 text-xs shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={status}
                    disabled={processing}
                    onChange={(e) => changeStatus(e.target.value)}
                >
                    {COURSE_STATUS_OPTIONS.map((s) => (
                        <option key={s} value={s}>
                            {s}
                        </option>
                    ))}
                </select>
            </td>
            <td className="px-5 py-2.5 text-right">
                <SecondaryButton
                    type="button"
                    onClick={() => router.delete(route('enrollments.courses.destroy', [enrollmentId, row.id]), { preserveScroll: true })}
                >
                    Remove
                </SecondaryButton>
            </td>
        </tr>
    );
}

function AddCourseForm({ enrollmentId, availableSections }: { enrollmentId: number; availableSections: AvailableSection[] }) {
    const [classSectionId, setClassSectionId] = useState(String(availableSections[0]?.id ?? ''));
    const [allowRepeat, setAllowRepeat] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(
            route('enrollments.courses.store', enrollmentId),
            { class_section_id: classSectionId, allow_repeat: allowRepeat },
            {
                preserveScroll: true,
                onError: (err) => setErrors(err as Record<string, string>),
                onSuccess: () => setAllowRepeat(false),
                onFinish: () => setProcessing(false),
            },
        );
    };

    if (availableSections.length === 0) {
        return <p className="text-sm text-slate-900">No open class sections are available for this semester.</p>;
    }

    return (
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3 rounded-md border border-slate-200 p-4">
            <div className="min-w-64">
                <InputLabel value="Class Section" />
                <select
                    className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={classSectionId}
                    onChange={(e) => setClassSectionId(e.target.value)}
                >
                    {availableSections.map((s) => (
                        <option key={s.id} value={s.id} disabled={s.enrolled_count >= s.max_students}>
                            {s.course.code} ({s.section_label}) — {s.enrolled_count}/{s.max_students}
                            {s.enrolled_count >= s.max_students ? ' — FULL' : ''}
                        </option>
                    ))}
                </select>
                <InputError message={errors.class_section_id} className="mt-1" />
            </div>

            <label className="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" checked={allowRepeat} onChange={(e) => setAllowRepeat(e.target.checked)} />
                Mark as repeat
            </label>

            <PrimaryButton disabled={processing}>Add Course</PrimaryButton>
        </form>
    );
}

export default function Edit({
    enrollment,
    availableSections,
    statuses,
}: {
    enrollment: {
        id: number;
        status: 'enrolled' | 'withdrawn' | 'completed';
        student: { student_number: string; surname: string; first_name: string; middle_name: string | null };
        semester: { term: string; academic_year: { start_year: number; end_year: number } };
        enrollment_courses: EnrollmentCourseRow[];
    };
    availableSections: AvailableSection[];
    statuses: { value: string; label: string }[];
}) {
    const [status, setStatus] = useState(enrollment.status);
    const [processing, setProcessing] = useState(false);

    function changeEnrollmentStatus(next: string) {
        setStatus(next as typeof status);
        setProcessing(true);
        router.put(
            route('enrollments.update', enrollment.id),
            { status: next },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Enrollment</h1>}>
            <Head title={`Enrollment — ${enrollment.student.student_number}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader
                        title={`${enrollment.student.first_name} ${enrollment.student.surname} (${enrollment.student.student_number})`}
                        description={`${enrollment.semester.academic_year.start_year}-${enrollment.semester.academic_year.end_year} ${enrollment.semester.term}`}
                    />
                    <CardContent className="flex items-center gap-3">
                        <InputLabel value="Enrollment Status" />
                        <select
                            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={status}
                            disabled={processing}
                            onChange={(e) => changeEnrollmentStatus(e.target.value)}
                        >
                            {statuses.map((s) => (
                                <option key={s.value} value={s.value}>
                                    {s.label}
                                </option>
                            ))}
                        </select>
                        <Badge variant={status === 'enrolled' ? 'success' : status === 'withdrawn' ? 'danger' : 'info'}>{status}</Badge>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Enrolled Courses" description="Add, drop, or update status of individual course enrollments." />
                    <CardContent className="flex flex-col gap-4">
                        {enrollment.enrollment_courses.length === 0 ? (
                            <p className="text-sm text-slate-900">No courses added yet.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                        <tr>
                                            <th className="px-5 py-2.5">Course</th>
                                            <th className="px-5 py-2.5">Section</th>
                                            <th className="px-5 py-2.5">Units</th>
                                            <th className="px-5 py-2.5">Status</th>
                                            <th className="px-5 py-2.5 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {enrollment.enrollment_courses.map((row) => (
                                            <CourseRow key={row.id} enrollmentId={enrollment.id} row={row} />
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        <AddCourseForm enrollmentId={enrollment.id} availableSections={availableSections} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
