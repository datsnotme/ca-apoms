import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import Modal from '@/Components/Modal';
import { Paginated } from '@/types';
import ResearchProjectForm from './Form';

type Status = 'proposed' | 'ongoing' | 'completed' | 'cancelled';

const STATUS_VARIANT: Record<Status, 'neutral' | 'info' | 'success' | 'danger'> = {
    proposed: 'neutral',
    ongoing: 'info',
    completed: 'success',
    cancelled: 'danger',
};

interface ResearchProjectRow {
    id: number;
    title: string;
    status: Status;
    start_date: string | null;
    end_date: string | null;
    members_count: number;
    outputs_count: number;
    department: { name: string } | null;
    created_by: { name: string } | null;
}

export default function Index({
    projects,
    canCreate,
    departments,
    isAdmin,
    statuses,
}: {
    projects: Paginated<ResearchProjectRow>;
    canCreate: boolean;
    departments?: { id: number; name: string }[];
    isAdmin?: boolean;
    statuses?: { value: string; label: string }[];
}) {
    const [showCreate, setShowCreate] = useState(false);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Research Projects</h1>}>
            <Head title="Research Projects" />

            <Card>
                <CardHeader
                    title="Research Projects"
                    description="Faculty research initiatives, their members, and their outputs."
                    actions={
                        canCreate ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>New Project</PrimaryButton>
                        ) : undefined
                    }
                />

                {projects.data.length === 0 ? (
                    <EmptyState title="No research projects found" />
                ) : (
                    <div className="divide-y divide-slate-100">
                        {projects.data.map((project) => (
                            <Link
                                key={project.id}
                                href={route('research-projects.show', project.id)}
                                className="flex items-start justify-between gap-4 px-5 py-4 hover:bg-slate-50"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-sm font-semibold text-slate-900">{project.title}</h3>
                                        <Badge variant={STATUS_VARIANT[project.status]}>{project.status}</Badge>
                                        {project.department && <Badge variant="neutral">{project.department.name}</Badge>}
                                    </div>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {project.start_date ?? 'No start date'}
                                        {project.end_date ? ` – ${project.end_date}` : ''}
                                    </p>
                                    <p className="mt-2 text-xs text-slate-900">
                                        {project.members_count} member{project.members_count === 1 ? '' : 's'} ·{' '}
                                        {project.outputs_count} output{project.outputs_count === 1 ? '' : 's'} · Created by{' '}
                                        {project.created_by?.name ?? 'Unknown'}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                <Pagination links={projects.links} from={projects.from} to={projects.to} total={projects.total} />
            </Card>

            {canCreate && departments && statuses && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="2xl" variant="form">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">New Research Project</h2>
                        <div className="mt-4">
                            <ResearchProjectForm
                                action={route('research-projects.store')}
                                method="post"
                                initialValues={{}}
                                departments={departments}
                                isAdmin={Boolean(isAdmin)}
                                statuses={statuses}
                                showStatus={false}
                                submitLabel="Create Project"
                                onCancel={() => setShowCreate(false)}
                                onSuccess={() => setShowCreate(false)}
                            />
                        </div>
                    </div>
                </Modal>
            )}
        </AppLayout>
    );
}
