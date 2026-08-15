import { Head, Link, router } from '@inertiajs/react';
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
import EventForm from './Form';

interface EventRow {
    id: number;
    title: string;
    description: string | null;
    start_at: string;
    end_at: string | null;
    location: string | null;
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
    events,
    canCreate,
    filters,
    departments,
    isAdmin,
}: {
    events: Paginated<EventRow>;
    canCreate: boolean;
    filters: { include_past: boolean };
    departments?: { id: number; name: string }[];
    isAdmin?: boolean;
}) {
    const manageableIds = events.data.filter((e) => e.can_manage).map((e) => e.id);
    const bulk = useBulkSelection(manageableIds);
    const [showCreate, setShowCreate] = useState(false);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Events</h1>}>
            <Head title="Events" />

            <Card>
                <CardHeader
                    title="Events"
                    description="College-wide and department calendar."
                    actions={
                        canCreate ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>Add Event</PrimaryButton>
                        ) : undefined
                    }
                />

                <div className="flex items-center gap-2 border-b border-slate-200 px-5 py-3">
                    <label className="flex items-center gap-2 text-sm text-slate-600">
                        <input
                            type="checkbox"
                            checked={filters.include_past}
                            onChange={(e) => router.get(route('events.index'), { include_past: e.target.checked ? '1' : '' }, { preserveState: true })}
                        />
                        Include past events
                    </label>
                </div>

                <BulkDeleteBar
                    href={route('events.destroyMany')}
                    ids={bulk.selectedIds}
                    itemLabelPlural="events"
                    onDeleted={bulk.clear}
                />

                {events.data.length === 0 ? (
                    <EmptyState title="No events found" />
                ) : (
                    <div className="divide-y divide-slate-100">
                        {events.data.map((event) => (
                            <div key={event.id} className="flex items-start justify-between gap-4 px-5 py-4">
                                <div className="flex min-w-0 flex-1 items-start gap-3">
                                    {event.can_manage && (
                                        <Checkbox
                                            aria-label={`Select ${event.title}`}
                                            className="mt-1"
                                            checked={bulk.isSelected(event.id)}
                                            onChange={() => bulk.toggle(event.id)}
                                        />
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="text-sm font-semibold text-slate-900">{event.title}</h3>
                                            <Badge variant={event.department ? 'info' : 'neutral'}>{event.department?.name ?? 'Entire College'}</Badge>
                                        </div>
                                        <p className="mt-1 text-sm text-slate-600">
                                            {formatDateTime(event.start_at)}
                                            {event.end_at ? ` – ${formatDateTime(event.end_at)}` : ''}
                                            {event.location ? ` · ${event.location}` : ''}
                                        </p>
                                        {event.description && <p className="mt-1 whitespace-pre-wrap text-sm text-slate-600">{event.description}</p>}
                                        <p className="mt-2 text-xs text-slate-900">Posted by {event.created_by?.name ?? 'Unknown'}</p>
                                    </div>
                                </div>
                                {event.can_manage && (
                                    <div className="flex shrink-0 items-center gap-3">
                                        <Link href={route('events.edit', event.id)} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                                            Edit
                                        </Link>
                                        <ConfirmDeleteButton href={route('events.destroy', event.id)} itemLabel={event.title} />
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                <Pagination links={events.links} from={events.from} to={events.to} total={events.total} />
            </Card>

            {canCreate && departments && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="2xl" variant="form">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">Add Event</h2>
                        <div className="mt-4">
                            <EventForm
                                action={route('events.store')}
                                method="post"
                                initialValues={{}}
                                departments={departments}
                                isAdmin={Boolean(isAdmin)}
                                submitLabel="Add Event"
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
