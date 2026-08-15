import { Head, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import TextInput from '@/Components/TextInput';
import { Paginated } from '@/types';

interface ActivityRow {
    id: number;
    log_name: string | null;
    description: string;
    event: string | null;
    subject_type: string | null;
    subject_id: number | null;
    causer: { name: string } | null;
    created_at: string;
}

const EVENT_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    created: 'success',
    updated: 'warning',
    deleted: 'danger',
};

export default function Index({
    activities,
    filters,
    logNames,
}: {
    activities: Paginated<ActivityRow>;
    filters: { search?: string; log_name?: string };
    logNames: string[];
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [logName, setLogName] = useState(filters.log_name ?? '');

    const submitFilters: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('audit-logs.index'), { search, log_name: logName || undefined }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Audit Logs</h1>}>
            <Head title="Audit Logs" />

            <Card>
                <CardHeader title="Activity History" description="System-recorded actions, most recent first." />

                <div className="flex flex-wrap items-end gap-3 border-b border-slate-200 px-5 py-3">
                    <form onSubmit={submitFilters} className="flex flex-wrap items-end gap-3">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search description..."
                        />
                        <select
                            className="rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={logName}
                            onChange={(e) => setLogName(e.target.value)}
                        >
                            <option value="">All modules</option>
                            {logNames.map((name) => (
                                <option key={name} value={name}>
                                    {name}
                                </option>
                            ))}
                        </select>
                        <button
                            type="submit"
                            className="rounded-md bg-brand-700 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800"
                        >
                            Filter
                        </button>
                    </form>
                </div>

                {activities.data.length === 0 ? (
                    <EmptyState title="No activity recorded yet" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    <th className="px-5 py-2.5">When</th>
                                    <th className="px-5 py-2.5">User</th>
                                    <th className="px-5 py-2.5">Module</th>
                                    <th className="px-5 py-2.5">Event</th>
                                    <th className="px-5 py-2.5">Description</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {activities.data.map((a) => (
                                    <tr key={a.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5 whitespace-nowrap text-slate-900">
                                            {new Date(a.created_at).toLocaleString()}
                                        </td>
                                        <td className="px-5 py-2.5">{a.causer?.name ?? 'System'}</td>
                                        <td className="px-5 py-2.5">{a.log_name ?? '—'}</td>
                                        <td className="px-5 py-2.5">
                                            {a.event && (
                                                <Badge variant={EVENT_VARIANT[a.event] ?? 'neutral'}>{a.event}</Badge>
                                            )}
                                        </td>
                                        <td className="px-5 py-2.5">{a.description}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination
                    links={activities.links}
                    from={activities.from}
                    to={activities.to}
                    total={activities.total}
                />
            </Card>
        </AppLayout>
    );
}
