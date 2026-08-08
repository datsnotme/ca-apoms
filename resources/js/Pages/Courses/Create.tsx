import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import CourseForm from './Form';

export default function Create({
    departments,
    courses,
}: {
    departments: { id: number; name: string }[];
    courses: { id: number; code: string; title: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Course</h1>}>
            <Head title="Add Course" />

            <Card>
                <CardHeader title="New Course" />
                <CardContent>
                    <CourseForm
                        action={route('courses.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        courses={courses}
                        submitLabel="Create Course"
                        onCancelHref={route('courses.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
