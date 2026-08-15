import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import StudentForm from './Form';
import Documents from './Documents';
import { PageProps } from '@/types';

interface Option {
    id: number;
    name: string;
}

interface StatusHistoryRow {
    id: number;
    from_status: string | null;
    to_status: string;
    reason: string | null;
    effective_date: string;
    changed_by: { name: string } | null;
}

interface StudentDetail {
    id: number;
    student_number: string;
    surname: string;
    first_name: string;
    middle_name: string | null;
    suffix: string | null;
    sex: string | null;
    birth_date: string | null;
    civil_status: string | null;
    citizenship: string | null;
    contact_number: string | null;
    email: string | null;
    department_id: number;
    program_id: number;
    curriculum_id: number;
    year_level_id: number;
    section_id: number | null;
    adviser_id: number | null;
    admission_type: string | null;
    date_admitted: string | null;
    expected_graduation_date: string | null;
    scholarship_status: string | null;
    classification: string;
    status: string;
    name: string;
    guardian: { name: string; relationship: string | null; contact_number: string | null; address: string | null } | null;
    emergency: { name: string; relationship: string | null; contact_number: string | null; address: string | null } | null;
    permanent_address: string | null;
    current_address: string | null;
    status_histories: StatusHistoryRow[];
    can_view_progress: boolean;
    documents: {
        id: number;
        category: string;
        title: string;
        original_filename: string;
        file_size: number;
        uploaded_at: string;
        verification_status: 'pending' | 'verified' | 'rejected';
        remarks: string | null;
        uploaded_by: { name: string } | null;
        verified_by: { name: string } | null;
    }[];
}

export default function Edit({
    student,
    departments,
    programs,
    curricula,
    yearLevels,
    sections,
    advisers,
    classifications,
    statuses,
    documentCategories,
}: {
    student: StudentDetail;
    departments: Option[];
    programs: (Option & { department_id: number })[];
    curricula: (Option & { program_id: number })[];
    yearLevels: { id: number; level: number; label: string }[];
    sections: { id: number; name: string; program_id: number; year_level_id: number }[];
    advisers: Option[];
    classifications: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
    documentCategories: { value: string; label: string }[];
}) {
    const { auth } = usePage<PageProps>().props;
    const canManageDocuments = auth.user.permissions.includes('student-documents.manage');
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Student</h1>}>
            <Head title={`Edit ${student.name}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader
                        title={student.name}
                        description={student.student_number}
                        actions={
                            student.can_view_progress ? (
                                <Link
                                    href={route('students.progress.show', student.id)}
                                    className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                >
                                    View Progress
                                </Link>
                            ) : undefined
                        }
                    />
                    <CardContent>
                        <StudentForm
                            action={route('students.update', student.id)}
                            method="put"
                            showStatusReason
                            initialValues={{
                                student_number: student.student_number,
                                surname: student.surname,
                                first_name: student.first_name,
                                middle_name: student.middle_name ?? '',
                                suffix: student.suffix ?? '',
                                sex: student.sex ?? '',
                                birth_date: student.birth_date ?? '',
                                civil_status: student.civil_status ?? '',
                                citizenship: student.citizenship ?? '',
                                contact_number: student.contact_number ?? '',
                                email: student.email ?? '',
                                department_id: String(student.department_id),
                                program_id: String(student.program_id),
                                curriculum_id: String(student.curriculum_id),
                                year_level_id: String(student.year_level_id),
                                section_id: student.section_id ? String(student.section_id) : '',
                                adviser_id: student.adviser_id ? String(student.adviser_id) : '',
                                admission_type: student.admission_type ?? '',
                                date_admitted: student.date_admitted ?? '',
                                expected_graduation_date: student.expected_graduation_date ?? '',
                                scholarship_status: student.scholarship_status ?? '',
                                classification: student.classification,
                                status: student.status,
                                guardian_name: student.guardian?.name ?? '',
                                guardian_relationship: student.guardian?.relationship ?? '',
                                guardian_contact_number: student.guardian?.contact_number ?? '',
                                guardian_address: student.guardian?.address ?? '',
                                emergency_name: student.emergency?.name ?? '',
                                emergency_relationship: student.emergency?.relationship ?? '',
                                emergency_contact_number: student.emergency?.contact_number ?? '',
                                emergency_address: student.emergency?.address ?? '',
                                permanent_address: student.permanent_address ?? '',
                                current_address: student.current_address ?? '',
                            }}
                            departments={departments}
                            programs={programs}
                            curricula={curricula}
                            yearLevels={yearLevels}
                            sections={sections}
                            advisers={advisers}
                            classifications={classifications}
                            statuses={statuses}
                            submitLabel="Save Changes"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Status History" description="Automatically recorded on every status change." />
                    {student.status_histories.length === 0 ? (
                        <CardContent>
                            <p className="text-sm text-slate-900">No status changes recorded yet.</p>
                        </CardContent>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                    <tr>
                                        <th className="px-5 py-2.5">Date</th>
                                        <th className="px-5 py-2.5">From</th>
                                        <th className="px-5 py-2.5">To</th>
                                        <th className="px-5 py-2.5">Reason</th>
                                        <th className="px-5 py-2.5">Changed By</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {student.status_histories.map((h) => (
                                        <tr key={h.id}>
                                            <td className="px-5 py-2.5">{h.effective_date}</td>
                                            <td className="px-5 py-2.5">
                                                {h.from_status ? <Badge variant="neutral">{h.from_status}</Badge> : '—'}
                                            </td>
                                            <td className="px-5 py-2.5">
                                                <Badge variant="info">{h.to_status}</Badge>
                                            </td>
                                            <td className="px-5 py-2.5">{h.reason ?? '—'}</td>
                                            <td className="px-5 py-2.5">{h.changed_by?.name ?? '—'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>

                <Card>
                    <CardHeader title="Documents" description="Uploaded credentials and their verification status." />
                    <CardContent>
                        <Documents
                            studentId={student.id}
                            documents={student.documents}
                            categories={documentCategories}
                            canManage={canManageDocuments}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
