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
import useBulkSelection from '@/hooks/useBulkSelection';
import { Paginated } from '@/types';

type Status = 'pending' | 'in_progress' | 'completed' | 'cancelled';

interface TaskRow {
    id: number;
    title: string;
    due_date: string | null;
    status: Status;
    assigned_to: { id: number; name: string } | null;
    created_by: { id: number; name: string } | null;
    can_update: boolean;
    can_manage: boolean;
}

const STATUS_VARIANT: Record<Status, 'neutral' | 'info' | 'success' | 'danger'> = {
    pending: 'neutral',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'danger',
};

function StatusControl({ task, statuses }: { task: TaskRow; statuses: { value: string; label: string }[] }) {
    const [processing, setProcessing] = useState(false);

    function submit(status: string) {
        setProcessing(true);
        router.put(route('tasks.update', task.id), { status }, { preserveScroll: true, onFinish: () => setProcessing(false) });
    }

    if (!task.can_update) {
        return <Badge variant={STATUS_VARIANT[task.status]}>{statuses.find((s) => s.value === task.status)?.label ?? task.status}</Badge>;
    }

    return (
        <select
            className="rounded-md border-gray-300 text-xs shadow-sm focus:border-brand-600 focus:ring-brand-600"
            value={task.status}
            disabled={processing}
            onChange={(e) => submit(e.target.value)}
        >
            {statuses.map((s) => (
                <option key={s.value} value={s.value}>
                    {s.label}
                </option>
            ))}
        </select>
    );
}

export default function Index({
    tasks,
    statuses,
    filters,
}: {
    tasks: Paginated<TaskRow>;
    statuses: { value: string; label: string }[];
    filters: { status: string };
}) {
    const manageableIds = tasks.data.filter((t) => t.can_manage).map((t) => t.id);
    const bulk = useBulkSelection(manageableIds);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Tasks</h1>}>
            <Head title="Tasks" />

            <Card>
                <CardHeader
                    title="Tasks"
                    description="To-dos you created or were assigned."
                    actions={
                        <Link href={route('tasks.create')}>
                            <PrimaryButton>Add Task</PrimaryButton>
                        </Link>
                    }
                />

                <div className="flex items-center gap-2 border-b border-slate-200 px-5 py-3">
                    <select
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={filters.status}
                        onChange={(e) => router.get(route('tasks.index'), { status: e.target.value }, { preserveState: true })}
                    >
                        <option value="">All Statuses</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                </div>

                <BulkDeleteBar
                    href={route('tasks.destroyMany')}
                    ids={bulk.selectedIds}
                    itemLabelPlural="tasks"
                    onDeleted={bulk.clear}
                />

                {tasks.data.length === 0 ? (
                    <EmptyState title="No tasks found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="w-10 px-5 py-2.5">
                                        {manageableIds.length > 0 && (
                                            <Checkbox
                                                aria-label="Select all tasks"
                                                checked={bulk.allOnPageSelected}
                                                onChange={() => bulk.toggleAllOnPage()}
                                            />
                                        )}
                                    </th>
                                    <th className="px-5 py-2.5">Title</th>
                                    <th className="px-5 py-2.5">Assigned To</th>
                                    <th className="px-5 py-2.5">Created By</th>
                                    <th className="px-5 py-2.5">Due</th>
                                    <th className="px-5 py-2.5">Status</th>
                                    <th className="px-5 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {tasks.data.map((task) => (
                                    <tr key={task.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5">
                                            {task.can_manage && (
                                                <Checkbox
                                                    aria-label={`Select ${task.title}`}
                                                    checked={bulk.isSelected(task.id)}
                                                    onChange={() => bulk.toggle(task.id)}
                                                />
                                            )}
                                        </td>
                                        <td className="px-5 py-2.5">{task.title}</td>
                                        <td className="px-5 py-2.5">{task.assigned_to?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{task.created_by?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{task.due_date ?? '—'}</td>
                                        <td className="px-5 py-2.5">
                                            <StatusControl task={task} statuses={statuses} />
                                        </td>
                                        <td className="px-5 py-2.5 text-right">
                                            {task.can_manage && (
                                                <div className="flex justify-end gap-3">
                                                    <Link href={route('tasks.edit', task.id)} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                                                        Edit
                                                    </Link>
                                                    <ConfirmDeleteButton href={route('tasks.destroy', task.id)} itemLabel={task.title} />
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={tasks.links} from={tasks.from} to={tasks.to} total={tasks.total} />
            </Card>
        </AppLayout>
    );
}
