import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { Paginated } from '@/types';

interface Conflict {
    id: number;
    entity_table: string;
    entity_uuid: string;
    local_snapshot: Record<string, unknown>;
    remote_snapshot: Record<string, unknown> | null;
    conflicting_fields: string[];
    status: string;
    resolution: string | null;
    created_at: string;
}

const HIDDEN_FIELDS = new Set(['uuid', 'sync_version', 'id', 'created_at', 'updated_at']);

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    return String(value);
}

function ConflictDiff({ conflict }: { conflict: Conflict }) {
    const remoteDeleted = conflict.remote_snapshot?.__deleted__ === true;
    const fields = Array.from(
        new Set([
            ...conflict.conflicting_fields.filter((f) => f !== '__remote_deleted__'),
            ...Object.keys(conflict.local_snapshot).filter((f) => !HIDDEN_FIELDS.has(f)),
        ]),
    );

    if (remoteDeleted) {
        return (
            <p className="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">
                The remote deleted this record while it was edited locally. Local fields are shown below — "Keep
                Local" restores/keeps it, "Take Remote" deletes it here too.
            </p>
        );
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th className="px-3 py-2">Field</th>
                        <th className="px-3 py-2">Local</th>
                        <th className="px-3 py-2">Remote</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {fields.map((field) => {
                        const isConflicting = conflict.conflicting_fields.includes(field);
                        return (
                            <tr key={field} className={isConflicting ? 'bg-red-50' : undefined}>
                                <td className="px-3 py-2 font-mono text-xs text-slate-600">{field}</td>
                                <td className="px-3 py-2">{formatValue(conflict.local_snapshot[field])}</td>
                                <td className="px-3 py-2">{formatValue(conflict.remote_snapshot?.[field])}</td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}

export default function Conflicts({
    conflicts,
    filters,
}: {
    conflicts: Paginated<Conflict>;
    filters: { status?: string };
}) {
    const [takeRemoteTarget, setTakeRemoteTarget] = useState<Conflict | null>(null);
    const [resolvingId, setResolvingId] = useState<number | null>(null);

    function resolve(conflict: Conflict, resolution: 'take_remote' | 'keep_local') {
        setResolvingId(conflict.id);
        router.post(
            route('sync.conflicts.resolve', conflict.id),
            { resolution },
            {
                preserveScroll: true,
                onFinish: () => {
                    setResolvingId(null);
                    setTakeRemoteTarget(null);
                },
            },
        );
    }

    function changeStatusFilter(status: string) {
        router.get(route('sync.conflicts'), status ? { status } : {}, { preserveState: true });
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Sync Conflicts</h1>}>
            <Head title="Sync Conflicts" />

            <Card>
                <CardHeader
                    title="Conflicts"
                    description="Fields changed on both sides since they last agreed — pick which value wins."
                    actions={
                        <select
                            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={filters.status ?? 'pending'}
                            onChange={(e) => changeStatusFilter(e.target.value)}
                        >
                            <option value="pending">Pending</option>
                            <option value="resolved">Resolved</option>
                            <option value="">All</option>
                        </select>
                    }
                />

                {conflicts.data.length === 0 ? (
                    <EmptyState title="No conflicts" description="Nothing here needs your attention right now." />
                ) : (
                    <div className="divide-y divide-slate-200">
                        {conflicts.data.map((conflict) => (
                            <div key={conflict.id} className="px-5 py-4">
                                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p className="text-sm font-medium text-slate-900">
                                            {conflict.entity_table}{' '}
                                            <span className="font-mono text-xs font-normal text-slate-400">
                                                {conflict.entity_uuid}
                                            </span>
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            Detected {new Date(conflict.created_at).toLocaleString()}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        {conflict.status === 'pending' ? (
                                            <Badge variant="danger">Pending</Badge>
                                        ) : (
                                            <Badge variant="success">
                                                Resolved: {conflict.resolution === 'take_remote' ? 'took remote' : 'kept local'}
                                            </Badge>
                                        )}
                                        {conflict.status === 'pending' && (
                                            <>
                                                <button
                                                    type="button"
                                                    disabled={resolvingId === conflict.id}
                                                    onClick={() => resolve(conflict, 'keep_local')}
                                                    className="text-sm font-medium text-brand-700 hover:text-brand-900 disabled:opacity-50"
                                                >
                                                    Keep Local
                                                </button>
                                                <button
                                                    type="button"
                                                    disabled={resolvingId === conflict.id}
                                                    onClick={() => setTakeRemoteTarget(conflict)}
                                                    className="text-sm font-medium text-red-600 hover:text-red-800 disabled:opacity-50"
                                                >
                                                    Take Remote
                                                </button>
                                            </>
                                        )}
                                    </div>
                                </div>

                                <ConflictDiff conflict={conflict} />
                            </div>
                        ))}
                    </div>
                )}

                <Pagination
                    links={conflicts.links}
                    from={conflicts.from}
                    to={conflicts.to}
                    total={conflicts.total}
                />
            </Card>

            <Modal show={takeRemoteTarget !== null} onClose={() => setTakeRemoteTarget(null)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-medium text-slate-900">Overwrite local with the remote's values?</h2>
                    <p className="mt-2 text-sm text-slate-500">
                        This replaces this record's local field values ({takeRemoteTarget?.conflicting_fields.join(', ')})
                        with what the remote sent. Your local edits to those fields will be lost. This cannot be undone.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setTakeRemoteTarget(null)}>Cancel</SecondaryButton>
                        <DangerButton
                            disabled={resolvingId !== null}
                            onClick={() => takeRemoteTarget && resolve(takeRemoteTarget, 'take_remote')}
                        >
                            Take Remote
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
