import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import AcademicYearForm from './Form';

export default function Create() {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Academic Year</h1>}>
            <Head title="Add Academic Year" />

            <Card>
                <CardHeader title="New Academic Year" />
                <CardContent>
                    <AcademicYearForm
                        action={route('academic-years.store')}
                        method="post"
                        initialValues={{}}
                        submitLabel="Create Academic Year"
                        onCancelHref={route('academic-years.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
