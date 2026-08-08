import { FormEventHandler, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

interface RosterRow {
    enrollment_course_id: number;
    student: { id: number; student_number: string; surname: string; first_name: string; middle_name: string | null };
    grade: string | null;
    status: string | null;
    student_grade_id: number | null;
}

interface GradeSubmission {
    id: number;
    status: 'draft' | 'submitted' | 'returned' | 'reviewed' | 'finalized';
    submitted_at: string | null;
    reviewed_at: string | null;
    finalized_at: string | null;
    review_remarks: string | null;
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    draft: 'neutral',
    submitted: 'warning',
    returned: 'danger',
    reviewed: 'info',
    finalized: 'success',
};

function GradeCell({
    classSectionId,
    row,
    gradingScaleValues,
    canEncode,
    submissionStatus,
}: {
    classSectionId: number;
    row: RosterRow;
    gradingScaleValues: { value: string; label: string }[];
    canEncode: boolean;
    submissionStatus: string;
}) {
    const [grade, setGrade] = useState(row.grade ?? '');
    const [processing, setProcessing] = useState(false);
    const editable = canEncode && ['draft', 'returned'].includes(submissionStatus);

    function save(value: string) {
        setGrade(value);
        setProcessing(true);
        router.put(
            route('class-sections.grades.update', [classSectionId, row.enrollment_course_id]),
            { grade: value || null },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    if (!editable) {
        return <span>{row.grade ?? '—'}</span>;
    }

    return (
        <select
            className="rounded-md border-gray-300 text-xs shadow-sm focus:border-brand-600 focus:ring-brand-600"
            value={grade}
            disabled={processing}
            onChange={(e) => save(e.target.value)}
        >
            <option value="">— Ungraded —</option>
            {gradingScaleValues.map((v) => (
                <option key={v.value} value={v.value}>
                    {v.value} — {v.label}
                </option>
            ))}
        </select>
    );
}

function CorrectGradeForm({
    classSectionId,
    row,
    gradingScaleValues,
}: {
    classSectionId: number;
    row: RosterRow;
    gradingScaleValues: { value: string; label: string }[];
}) {
    const [open, setOpen] = useState(false);
    const [grade, setGrade] = useState(row.grade ?? '');
    const [reason, setReason] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.patch(
            route('class-sections.grades.correct', [classSectionId, row.student_grade_id as number]),
            { grade, reason },
            {
                preserveScroll: true,
                onError: (err) => setErrors(err as Record<string, string>),
                onSuccess: () => setOpen(false),
                onFinish: () => setProcessing(false),
            },
        );
    };

    if (!open) {
        return (
            <button type="button" onClick={() => setOpen(true)} className="text-xs font-medium text-brand-700 hover:text-brand-900">
                Correct
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="flex flex-col gap-1">
            <select
                className="rounded-md border-gray-300 text-xs shadow-sm focus:border-brand-600 focus:ring-brand-600"
                value={grade}
                onChange={(e) => setGrade(e.target.value)}
            >
                {gradingScaleValues.map((v) => (
                    <option key={v.value} value={v.value}>
                        {v.value} — {v.label}
                    </option>
                ))}
            </select>
            <InputError message={errors.grade} />
            <TextInput
                className="text-xs"
                placeholder="Reason for correction"
                value={reason}
                onChange={(e) => setReason(e.target.value)}
            />
            <InputError message={errors.reason} />
            <div className="flex gap-2">
                <button type="submit" disabled={processing} className="text-xs font-medium text-brand-700 hover:text-brand-900">
                    Save
                </button>
                <button type="button" onClick={() => setOpen(false)} className="text-xs text-slate-500 hover:text-slate-700">
                    Cancel
                </button>
            </div>
        </form>
    );
}

export default function Show({
    classSection,
    submission,
    roster,
    gradingScaleValues,
    can,
}: {
    classSection: {
        id: number;
        section_label: string;
        max_students: number;
        course: { code: string; title: string; units: string };
        semester: { term: string; academic_year: { start_year: number; end_year: number } };
    };
    submission: GradeSubmission;
    roster: RosterRow[];
    gradingScaleValues: { value: string; label: string }[];
    can: { encode: boolean; review: boolean };
}) {
    const [remarks, setRemarks] = useState('');
    const [processing, setProcessing] = useState(false);

    function submitForReview() {
        setProcessing(true);
        router.post(route('class-sections.grades.submit', classSection.id), {}, { preserveScroll: true, onFinish: () => setProcessing(false) });
    }

    function approve() {
        setProcessing(true);
        router.post(route('class-sections.grades.approve', classSection.id), {}, { preserveScroll: true, onFinish: () => setProcessing(false) });
    }

    function returnForCorrection() {
        setProcessing(true);
        router.post(
            route('class-sections.grades.return', classSection.id),
            { remarks },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    function finalize() {
        setProcessing(true);
        router.post(route('class-sections.grades.finalize', classSection.id), {}, { preserveScroll: true, onFinish: () => setProcessing(false) });
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Grades</h1>}>
            <Head title={`Grades — ${classSection.course.code} ${classSection.section_label}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader
                        title={`${classSection.course.code} — ${classSection.course.title}`}
                        description={`Section ${classSection.section_label} · ${classSection.semester.academic_year.start_year}-${classSection.semester.academic_year.end_year} ${classSection.semester.term}`}
                        actions={<Badge variant={STATUS_VARIANT[submission.status]}>{submission.status}</Badge>}
                    />
                    <CardContent className="flex flex-wrap items-center gap-3">
                        {submission.status === 'returned' && submission.review_remarks && (
                            <p className="w-full text-sm text-red-600">Returned: {submission.review_remarks}</p>
                        )}

                        {can.encode && ['draft', 'returned'].includes(submission.status) && (
                            <PrimaryButton disabled={processing} onClick={submitForReview}>
                                Submit for Review
                            </PrimaryButton>
                        )}

                        {can.review && submission.status === 'submitted' && (
                            <>
                                <PrimaryButton disabled={processing} onClick={approve}>
                                    Approve
                                </PrimaryButton>
                                <TextInput
                                    value={remarks}
                                    onChange={(e) => setRemarks(e.target.value)}
                                    placeholder="Reason for returning"
                                    className="w-56"
                                />
                                <DangerButton disabled={processing || !remarks} onClick={returnForCorrection}>
                                    Return for Correction
                                </DangerButton>
                            </>
                        )}

                        {can.review && submission.status === 'reviewed' && (
                            <PrimaryButton disabled={processing} onClick={finalize}>
                                Finalize
                            </PrimaryButton>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Grade Sheet" description={`${roster.length} / ${classSection.max_students} enrolled`} />
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-5 py-2.5">Student No.</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Grade</th>
                                    {can.review && submission.status === 'finalized' && <th className="px-5 py-2.5">Correction</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {roster.map((row) => (
                                    <tr key={row.enrollment_course_id}>
                                        <td className="px-5 py-2.5 font-mono text-xs">{row.student.student_number}</td>
                                        <td className="px-5 py-2.5">
                                            {row.student.first_name} {row.student.middle_name} {row.student.surname}
                                        </td>
                                        <td className="px-5 py-2.5">
                                            <GradeCell
                                                classSectionId={classSection.id}
                                                row={row}
                                                gradingScaleValues={gradingScaleValues}
                                                canEncode={can.encode}
                                                submissionStatus={submission.status}
                                            />
                                        </td>
                                        {can.review && submission.status === 'finalized' && (
                                            <td className="px-5 py-2.5">
                                                {row.student_grade_id && (
                                                    <CorrectGradeForm
                                                        classSectionId={classSection.id}
                                                        row={row}
                                                        gradingScaleValues={gradingScaleValues}
                                                    />
                                                )}
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
