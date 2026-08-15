import { FormEventHandler, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import Badge from '@/Components/ui/Badge';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

type Status = 'pending' | 'in_progress' | 'completed' | 'cancelled';

interface ActionItemRow {
    id: number;
    description: string;
    due_date: string | null;
    status: Status;
    notes: string | null;
    assigned_to: { id: number; name: string } | null;
    completed_by: { id: number; name: string } | null;
    completed_at: string | null;
    can_update: boolean;
    can_delete: boolean;
}

const STATUS_VARIANT: Record<Status, 'neutral' | 'info' | 'success' | 'danger'> = {
    pending: 'neutral',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'danger',
};

function StatusControl({
    meetingId,
    item,
    statuses,
}: {
    meetingId: number;
    item: ActionItemRow;
    statuses: { value: string; label: string }[];
}) {
    const [processing, setProcessing] = useState(false);

    function submit(status: string) {
        setProcessing(true);
        router.put(
            route('meetings.action-items.update', [meetingId, item.id]),
            { status },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    if (!item.can_update) {
        return <Badge variant={STATUS_VARIANT[item.status]}>{statuses.find((s) => s.value === item.status)?.label ?? item.status}</Badge>;
    }

    return (
        <select
            className="rounded-md border-gray-300 text-xs shadow-sm focus:border-brand-600 focus:ring-brand-600"
            value={item.status}
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

export default function ActionItemList({
    meetingId,
    actionItems,
    assigneeOptions,
    statuses,
    canManage,
}: {
    meetingId: number;
    actionItems: ActionItemRow[];
    assigneeOptions: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
    canManage: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        description: string;
        assigned_to: string;
        due_date: string;
    }>({
        description: '',
        assigned_to: '',
        due_date: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('meetings.action-items.store', meetingId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="flex flex-col gap-4">
            {actionItems.length === 0 ? (
                <p className="text-sm text-slate-900">No action items yet.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                            <tr>
                                <th className="px-3 py-2">Description</th>
                                <th className="px-3 py-2">Assigned To</th>
                                <th className="px-3 py-2">Due</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {actionItems.map((item) => (
                                <tr key={item.id}>
                                    <td className="px-3 py-2">
                                        {item.description}
                                        {item.completed_by && (
                                            <div className="text-xs text-slate-900">
                                                Completed by {item.completed_by.name}
                                                {item.completed_at ? ` on ${item.completed_at.slice(0, 10)}` : ''}
                                            </div>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">{item.assigned_to?.name ?? '—'}</td>
                                    <td className="px-3 py-2">{item.due_date ?? '—'}</td>
                                    <td className="px-3 py-2">
                                        <StatusControl meetingId={meetingId} item={item} statuses={statuses} />
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        {item.can_delete && (
                                            <SecondaryButton
                                                type="button"
                                                onClick={() =>
                                                    router.delete(route('meetings.action-items.destroy', [meetingId, item.id]), {
                                                        preserveScroll: true,
                                                    })
                                                }
                                            >
                                                Remove
                                            </SecondaryButton>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {canManage && (
                <form onSubmit={submit} className="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-4">
                    <div className="sm:col-span-2">
                        <TextInput
                            className="mt-1 block w-full"
                            placeholder="Action item description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            required
                        />
                        <InputError message={errors.description} className="mt-1" />
                    </div>

                    <div>
                        <select
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={data.assigned_to}
                            onChange={(e) => setData('assigned_to', e.target.value)}
                        >
                            <option value="">Unassigned</option>
                            {assigneeOptions.map((u) => (
                                <option key={u.id} value={u.id}>
                                    {u.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.assigned_to} className="mt-1" />
                    </div>

                    <div className="flex items-start gap-2">
                        <TextInput
                            type="date"
                            className="mt-1 block w-full"
                            value={data.due_date}
                            onChange={(e) => setData('due_date', e.target.value)}
                        />
                        <PrimaryButton className="mt-1" disabled={processing}>
                            Add
                        </PrimaryButton>
                    </div>
                </form>
            )}
        </div>
    );
}
