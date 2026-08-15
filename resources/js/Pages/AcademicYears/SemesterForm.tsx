import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import SemesterFields from './SemesterFields';

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

export default function SemesterForm({
    semester,
    academicYears,
}: {
    semester: SemesterDetail;
    academicYears: AcademicYearOption[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Semester</h1>}>
            <Head title="Edit Semester" />

            <Card>
                <CardHeader title="Edit Semester" />
                <CardContent>
                    <SemesterFields
                        semester={semester}
                        academicYears={academicYears}
                        onCancel={() => (window.location.href = route('semesters.index'))}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
