import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import StudentForm from './Form';

interface Option {
    id: number;
    name: string;
}

export default function Create({
    departments,
    programs,
    curricula,
    yearLevels,
    sections,
    advisers,
    classifications,
    statuses,
}: {
    departments: Option[];
    programs: (Option & { department_id: number })[];
    curricula: (Option & { program_id: number })[];
    yearLevels: { id: number; level: number; label: string }[];
    sections: { id: number; name: string; program_id: number; year_level_id: number }[];
    advisers: Option[];
    classifications: { value: string; label: string }[];
    statuses: { value: string; label: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Register Student</h1>}>
            <Head title="Register Student" />

            <Card>
                <CardHeader title="New Student" />
                <CardContent>
                    <StudentForm
                        action={route('students.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        programs={programs}
                        curricula={curricula}
                        yearLevels={yearLevels}
                        sections={sections}
                        advisers={advisers}
                        classifications={classifications}
                        statuses={statuses}
                        submitLabel="Register Student"
                        onCancelHref={route('students.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
