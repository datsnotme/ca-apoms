import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import DepartmentForm from './Form';

export default function Create({ potentialHeads }: { potentialHeads: { id: number; name: string }[] }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Department</h1>}>
            <Head title="Add Department" />

            <Card>
                <CardHeader title="New Department" />
                <CardContent>
                    <DepartmentForm
                        action={route('departments.store')}
                        method="post"
                        initialValues={{}}
                        potentialHeads={potentialHeads}
                        submitLabel="Create Department"
                        onCancelHref={route('departments.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
