import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import BulkDeleteBar from '@/Components/ui/BulkDeleteBar';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import Modal from '@/Components/Modal';
import useBulkSelection from '@/hooks/useBulkSelection';
import { Paginated } from '@/types';
import AnnouncementForm from './Form';

interface AnnouncementRow {
    id: number;
    title: string;
    body: string;
    created_at: string;
    department: { name: string } | null;
    created_by: { name: string } | null;
    can_manage: boolean;
}

export default function Index({
    announcements,
    canCreate,
    departments,
    isAdmin,
}: {
    announcements: Paginated<AnnouncementRow>;
    canCreate: boolean;
    departments?: { id: number; name: string }[];
    isAdmin?: boolean;
}) {
    const manageableIds = announcements.data.filter((a) => a.can_manage).map((a) => a.id);
    const bulk = useBulkSelection(manageableIds);
    const [showCreate, setShowCreate] = useState(false);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Announcements</h1>}>
            <Head title="Announcements" />

            <Card>
                <CardHeader
                    title="Announcements"
                    description="College-wide and department bulletins."
                    actions={
                        canCreate ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>Post Announcement</PrimaryButton>
                        ) : undefined
                    }
                />

                <BulkDeleteBar
                    href={route('announcements.destroyMany')}
                    ids={bulk.selectedIds}
                    itemLabelPlural="announcements"
                    onDeleted={bulk.clear}
                />

                {announcements.data.length === 0 ? (
                    <EmptyState title="No announcements yet" />
                ) : (
                    <div className="divide-y divide-slate-100">
                        {announcements.data.map((a) => (
                            <div key={a.id} className="flex items-start justify-between gap-4 px-5 py-4">
                                <div className="flex min-w-0 flex-1 items-start gap-3">
                                    {a.can_manage && (
                                        <Checkbox
                                            aria-label={`Select ${a.title}`}
                                            className="mt-1"
                                            checked={bulk.isSelected(a.id)}
                                            onChange={() => bulk.toggle(a.id)}
                                        />
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="text-sm font-semibold text-slate-900">{a.title}</h3>
                                            <Badge variant={a.department ? 'info' : 'neutral'}>{a.department?.name ?? 'Entire College'}</Badge>
                                        </div>
                                        <p className="mt-1 whitespace-pre-wrap text-sm text-slate-600">{a.body}</p>
                                        <p className="mt-2 text-xs text-slate-900">
                                            {a.created_by?.name ?? 'Unknown'} · {a.created_at.slice(0, 10)}
                                        </p>
                                    </div>
                                </div>
                                {a.can_manage && (
                                    <div className="flex shrink-0 items-center gap-3">
                                        <Link
                                            href={route('announcements.edit', a.id)}
                                            className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                        >
                                            Edit
                                        </Link>
                                        <ConfirmDeleteButton href={route('announcements.destroy', a.id)} itemLabel={a.title} />
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                <Pagination links={announcements.links} from={announcements.from} to={announcements.to} total={announcements.total} />
            </Card>

            {canCreate && departments && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="lg" variant="form">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">Post Announcement</h2>
                        <div className="mt-4">
                            <AnnouncementForm
                                action={route('announcements.store')}
                                method="post"
                                initialValues={{}}
                                departments={departments}
                                isAdmin={Boolean(isAdmin)}
                                submitLabel="Post Announcement"
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
