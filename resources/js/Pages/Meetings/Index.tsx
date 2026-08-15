import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import BulkDeleteBar from '@/Components/ui/BulkDeleteBar';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import Modal from '@/Components/Modal';
import useBulkSelection from '@/hooks/useBulkSelection';
import { Paginated } from '@/types';
import MeetingForm from './Form';

interface MeetingRow {
    id: number;
    title: string;
    start_at: string;
    end_at: string | null;
    location: string | null;
    attendees_count: number;
    action_items_count: number;
    department: { name: string } | null;
    created_by: { name: string } | null;
    can_manage: boolean;
}

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export default function Index({
    meetings,
    canCreate,
    filters,
    departments,
    isAdmin,
}: {
    meetings: Paginated<MeetingRow>;
    canCreate: boolean;
    filters: { include_past: boolean };
    departments?: { id: number; name: string }[];
    isAdmin?: boolean;
}) {
    const manageableIds = meetings.data.filter((m) => m.can_manage).map((m) => m.id);
    const bulk = useBulkSelection(manageableIds);
    const [showCreate, setShowCreate] = useState(false);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Meetings</h1>}>
            <Head title="Meetings" />

            <Card>
                <CardHeader
                    title="Meetings"
                    description="College-wide and department meetings, with attendees and action items."
                    actions={
                        canCreate ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>Schedule Meeting</PrimaryButton>
                        ) : undefined
                    }
                />

                <div className="flex items-center gap-2 border-b border-slate-200 px-5 py-3">
                    <label className="flex items-center gap-2 text-sm text-slate-600">
                        <input
                            type="checkbox"
                            checked={filters.include_past}
                            onChange={(e) => router.get(route('meetings.index'), { include_past: e.target.checked ? '1' : '' }, { preserveState: true })}
                        />
                        Include past meetings
                    </label>
                </div>

                <BulkDeleteBar
                    href={route('meetings.destroyMany')}
                    ids={bulk.selectedIds}
                    itemLabelPlural="meetings"
                    onDeleted={bulk.clear}
                />

                {meetings.data.length === 0 ? (
                    <EmptyState title="No meetings found" />
                ) : (
                    <div className="divide-y divide-slate-100">
                        {meetings.data.map((meeting) => (
                            <div key={meeting.id} className="flex items-start gap-3 px-5 py-4 hover:bg-slate-50">
                                {meeting.can_manage && (
                                    <Checkbox
                                        aria-label={`Select ${meeting.title}`}
                                        className="mt-1"
                                        checked={bulk.isSelected(meeting.id)}
                                        onChange={() => bulk.toggle(meeting.id)}
                                    />
                                )}
                                <Link href={route('meetings.show', meeting.id)} className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-sm font-semibold text-slate-900">{meeting.title}</h3>
                                        <Badge variant={meeting.department ? 'info' : 'neutral'}>{meeting.department?.name ?? 'Entire College'}</Badge>
                                    </div>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {formatDateTime(meeting.start_at)}
                                        {meeting.end_at ? ` – ${formatDateTime(meeting.end_at)}` : ''}
                                        {meeting.location ? ` · ${meeting.location}` : ''}
                                    </p>
                                    <p className="mt-2 text-xs text-slate-900">
                                        {meeting.attendees_count} attendee{meeting.attendees_count === 1 ? '' : 's'} ·{' '}
                                        {meeting.action_items_count} action item{meeting.action_items_count === 1 ? '' : 's'} · Posted by{' '}
                                        {meeting.created_by?.name ?? 'Unknown'}
                                    </p>
                                </Link>
                            </div>
                        ))}
                    </div>
                )}

                <Pagination links={meetings.links} from={meetings.from} to={meetings.to} total={meetings.total} />
            </Card>

            {canCreate && departments && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="2xl" variant="form">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">Schedule Meeting</h2>
                        <div className="mt-4">
                            <MeetingForm
                                action={route('meetings.store')}
                                method="post"
                                initialValues={{}}
                                departments={departments}
                                isAdmin={Boolean(isAdmin)}
                                submitLabel="Schedule Meeting"
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
