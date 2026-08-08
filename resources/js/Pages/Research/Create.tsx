import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import ResearchProjectForm from './Form';

export default function Create({
    departments,
    isAdmin,
    statuses,
}: {
    departments: { id: number; name: string }[];
    isAdmin: boolean;
    statuses: { value: string; label: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">New Research Project</h1>}>
            <Head title="New Research Project" />

            <Card>
                <CardHeader title="New Research Project" />
                <CardContent>
                    <ResearchProjectForm
                        action={route('research-projects.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        isAdmin={isAdmin}
                        statuses={statuses}
                        showStatus={false}
                        submitLabel="Create Project"
                        onCancelHref={route('research-projects.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
