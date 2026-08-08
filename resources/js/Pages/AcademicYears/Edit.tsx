import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import AcademicYearForm from './Form';

interface AcademicYearDetail {
    id: number;
    start_year: number;
    end_year: number;
    is_current: boolean;
}

export default function Edit({ academicYear }: { academicYear: AcademicYearDetail }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Academic Year</h1>}>
            <Head title={`Edit ${academicYear.start_year}-${academicYear.end_year}`} />

            <Card>
                <CardHeader title={`${academicYear.start_year}-${academicYear.end_year}`} />
                <CardContent>
                    <AcademicYearForm
                        action={route('academic-years.update', academicYear.id)}
                        method="put"
                        initialValues={{
                            start_year: String(academicYear.start_year),
                            end_year: String(academicYear.end_year),
                            is_current: academicYear.is_current,
                        }}
                        submitLabel="Save Changes"
                        onCancelHref={route('academic-years.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
