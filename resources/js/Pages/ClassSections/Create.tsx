import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import ClassSectionForm from './Form';

export default function Create({
    courses,
    semesters,
    faculty,
    statuses,
}: {
    courses: { id: number; code: string; title: string }[];
    semesters: { id: number; label: string }[];
    faculty: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Class Section</h1>}>
            <Head title="Add Class Section" />

            <Card>
                <CardHeader title="New Class Section" />
                <CardContent>
                    <ClassSectionForm
                        action={route('class-sections.store')}
                        method="post"
                        initialValues={{}}
                        courses={courses}
                        semesters={semesters}
                        faculty={faculty}
                        statuses={statuses}
                        submitLabel="Create Class Section"
                        onCancelHref={route('class-sections.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
