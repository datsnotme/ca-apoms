import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import CompetencyCategoryForm from './Form';

export default function Create() {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Competency Category</h1>}>
            <Head title="Add Competency Category" />

            <Card>
                <CardHeader title="New Category" />
                <CardContent>
                    <CompetencyCategoryForm
                        action={route('competency-categories.store')}
                        method="post"
                        initialValues={{}}
                        submitLabel="Create Category"
                        onCancelHref={route('competency-categories.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
