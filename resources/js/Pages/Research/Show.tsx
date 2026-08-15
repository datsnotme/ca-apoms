import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import PrimaryButton from '@/Components/PrimaryButton';
import MemberList from './MemberList';
import OutputList from './OutputList';

type Status = 'proposed' | 'ongoing' | 'completed' | 'cancelled';

const STATUS_VARIANT: Record<Status, 'neutral' | 'info' | 'success' | 'danger'> = {
    proposed: 'neutral',
    ongoing: 'info',
    completed: 'success',
    cancelled: 'danger',
};

interface MemberRow {
    id: number;
    is_lead: boolean;
    user: { id: number; name: string };
    can_delete: boolean;
}

interface OutputRow {
    id: number;
    title: string;
    type: string;
    description: string | null;
    output_date: string | null;
    reference_url: string | null;
    created_by: { id: number; name: string } | null;
    can_delete: boolean;
}

interface ResearchProjectDetail {
    id: number;
    title: string;
    description: string | null;
    status: Status;
    status_label: string;
    start_date: string | null;
    end_date: string | null;
    funding_source: string | null;
    department: { name: string } | null;
    created_by: { name: string } | null;
    members: MemberRow[];
    outputs: OutputRow[];
}

export default function Show({
    project,
    canManage,
    memberOptions,
}: {
    project: ResearchProjectDetail;
    canManage: boolean;
    memberOptions: { id: number; name: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Research Project</h1>}>
            <Head title={project.title} />

            <div className="flex flex-col gap-6">
                <Link href={route('research-projects.index')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                    ← Back to Research Projects
                </Link>

                <Card>
                    <CardHeader
                        title={project.title}
                        description={`${project.start_date ?? 'No start date'}${project.end_date ? ` – ${project.end_date}` : ''}`}
                        actions={
                            canManage ? (
                                <div className="flex items-center gap-3">
                                    <Link href={route('research-projects.edit', project.id)}>
                                        <PrimaryButton>Edit</PrimaryButton>
                                    </Link>
                                    <ConfirmDeleteButton href={route('research-projects.destroy', project.id)} itemLabel={project.title} />
                                </div>
                            ) : undefined
                        }
                    />
                    <CardContent>
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant={STATUS_VARIANT[project.status]}>{project.status_label}</Badge>
                            {project.department && <Badge variant="neutral">{project.department.name}</Badge>}
                            {project.funding_source && <Badge variant="info">{project.funding_source}</Badge>}
                            <span className="text-xs text-slate-900">Created by {project.created_by?.name ?? 'Unknown'}</span>
                        </div>
                        {project.description && <p className="mt-3 whitespace-pre-wrap text-sm text-slate-600">{project.description}</p>}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Members" description="Researchers on this project. Exactly one lead per project." />
                    <CardContent>
                        <MemberList projectId={project.id} members={project.members} memberOptions={memberOptions} canManage={canManage} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Outputs" description="Publications, presentations, patents, and other project outputs." />
                    <CardContent>
                        <OutputList projectId={project.id} outputs={project.outputs} canManage={canManage} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
