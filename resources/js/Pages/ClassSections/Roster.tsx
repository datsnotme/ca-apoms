import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';

interface RosterRow {
    id: number;
    status: string;
    student: {
        id: number;
        student_number: string;
        surname: string;
        first_name: string;
        middle_name: string | null;
    };
}

export default function Roster({
    classSection,
    roster,
}: {
    classSection: {
        id: number;
        section_label: string;
        max_students: number;
        course: { code: string; title: string };
        semester: { term: string; academic_year: { start_year: number; end_year: number } };
    };
    roster: RosterRow[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Class Roster</h1>}>
            <Head title={`Roster — ${classSection.course.code} ${classSection.section_label}`} />

            <Card>
                <CardHeader
                    title={`${classSection.course.code} — ${classSection.course.title}`}
                    description={`Section ${classSection.section_label} · ${classSection.semester.academic_year.start_year}-${classSection.semester.academic_year.end_year} ${classSection.semester.term} · ${roster.length} / ${classSection.max_students} enrolled`}
                    actions={
                        <Link href={route('class-sections.edit', classSection.id)} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                            Back to Section
                        </Link>
                    }
                />

                {roster.length === 0 ? (
                    <EmptyState title="No students enrolled in this section yet" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    <th className="px-5 py-2.5">Student No.</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {roster.map((r) => (
                                    <tr key={r.id}>
                                        <td className="px-5 py-2.5 font-mono text-xs">{r.student.student_number}</td>
                                        <td className="px-5 py-2.5">
                                            {r.student.first_name} {r.student.middle_name} {r.student.surname}
                                        </td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant="neutral">{r.status}</Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
        </AppLayout>
    );
}
