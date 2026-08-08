import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import ResearchProjectForm from './Form';

interface ResearchProjectDetail {
    id: number;
    title: string;
    description: string | null;
    status: string;
    start_date: string | null;
    end_date: string | null;
    funding_source: string | null;
    department_id: number;
}

export default function Edit({
    project,
    departments,
    isAdmin,
    statuses,
}: {
    project: ResearchProjectDetail;
    departments: { id: number; name: string }[];
    isAdmin: boolean;
    statuses: { value: string; label: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Research Project</h1>}>
            <Head title="Edit Research Project" />

            <Card>
                <CardHeader title={project.title} />
                <CardContent>
                    <ResearchProjectForm
                        action={route('research-projects.update', project.id)}
                        method="put"
                        initialValues={{
                            title: project.title,
                            description: project.description ?? '',
                            status: project.status,
                            start_date: project.start_date ?? '',
                            end_date: project.end_date ?? '',
                            funding_source: project.funding_source ?? '',
                            department_id: String(project.department_id),
                        }}
                        departments={departments}
                        isAdmin={isAdmin}
                        statuses={statuses}
                        showStatus
                        submitLabel="Save Changes"
                        onCancelHref={route('research-projects.show', project.id)}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
