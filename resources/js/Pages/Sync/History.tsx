import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import { Paginated } from '@/types';

interface Run {
    id: number;
    direction: 'pull' | 'push';
    target: string;
    status: string;
    uploaded_count: number;
    downloaded_count: number;
    created_count: number;
    updated_count: number;
    deleted_count: number;
    conflict_count: number;
    started_at: string;
    finished_at: string | null;
    error_message: string | null;
    device: { id: number; name: string } | null;
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    success: 'success',
    running: 'warning',
    failed: 'danger',
};

export default function History({ runs }: { runs: Paginated<Run> }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Sync History</h1>}>
            <Head title="Sync History" />

            <Card>
                <CardHeader title="Sync Runs" description="Every pull and push attempt, most recent first." />

                {runs.data.length === 0 ? (
                    <EmptyState title="No sync runs yet" description="Trigger a sync from the Sync Center overview." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    <th className="px-5 py-2.5">When</th>
                                    <th className="px-5 py-2.5">Device</th>
                                    <th className="px-5 py-2.5">Direction</th>
                                    <th className="px-5 py-2.5">Target</th>
                                    <th className="px-5 py-2.5">Created</th>
                                    <th className="px-5 py-2.5">Updated</th>
                                    <th className="px-5 py-2.5">Deleted</th>
                                    <th className="px-5 py-2.5">Conflicts</th>
                                    <th className="px-5 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {runs.data.map((run) => (
                                    <tr key={run.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5 whitespace-nowrap text-slate-900">
                                            {new Date(run.started_at).toLocaleString()}
                                        </td>
                                        <td className="px-5 py-2.5">{run.device?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5 capitalize">{run.direction}</td>
                                        <td className="px-5 py-2.5">{run.target}</td>
                                        <td className="px-5 py-2.5">{run.created_count}</td>
                                        <td className="px-5 py-2.5">{run.updated_count}</td>
                                        <td className="px-5 py-2.5">{run.deleted_count}</td>
                                        <td className="px-5 py-2.5">
                                            {run.conflict_count > 0 ? (
                                                <span className="font-medium text-red-600">{run.conflict_count}</span>
                                            ) : (
                                                0
                                            )}
                                        </td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={STATUS_VARIANT[run.status] ?? 'neutral'}>{run.status}</Badge>
                                            {run.error_message && (
                                                <p className="mt-1 max-w-xs text-xs text-red-600">{run.error_message}</p>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={runs.links} from={runs.from} to={runs.to} total={runs.total} />
            </Card>
        </AppLayout>
    );
}
