import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import GraduationRequirementForm from './Form';

export default function Create({ programs }: { programs: { id: number; name: string }[] }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Graduation Requirement</h1>}>
            <Head title="Add Graduation Requirement" />

            <Card>
                <CardHeader title="New Requirement" />
                <CardContent>
                    <GraduationRequirementForm
                        action={route('graduation-requirement-templates.store')}
                        method="post"
                        initialValues={{}}
                        programs={programs}
                        submitLabel="Create Requirement"
                        onCancelHref={route('graduation-requirement-templates.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
