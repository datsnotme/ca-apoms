import { FormEventHandler, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import Badge from '@/Components/ui/Badge';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface FollowupRow {
    id: number;
    description: string;
    status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    due_date: string | null;
    notes: string | null;
    assigned_to: { name: string } | null;
    completed_by: { name: string } | null;
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    pending: 'neutral',
    in_progress: 'info',
    completed: 'success',
    cancelled: 'danger',
};

function StatusControl({ studentId, followup }: { studentId: number; followup: FollowupRow }) {
    const [processing, setProcessing] = useState(false);

    function setStatus(status: string) {
        setProcessing(true);
        router.put(
            route('students.followups.update', [studentId, followup.id]),
            { status },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    if (followup.status === 'completed' || followup.status === 'cancelled') {
        return null;
    }

    return (
        <div className="flex gap-2">
            {followup.status === 'pending' && (
                <button
                    type="button"
                    disabled={processing}
                    onClick={() => setStatus('in_progress')}
                    className="text-xs font-medium text-brand-700 hover:text-brand-900"
                >
                    Start
                </button>
            )}
            <button
                type="button"
                disabled={processing}
                onClick={() => setStatus('completed')}
                className="text-xs font-medium text-brand-700 hover:text-brand-900"
            >
                Complete
            </button>
            <button
                type="button"
                disabled={processing}
                onClick={() => setStatus('cancelled')}
                className="text-xs font-medium text-red-600 hover:text-red-800"
            >
                Cancel
            </button>
        </div>
    );
}

export default function InterventionFollowups({
    studentId,
    followups,
    advisers,
    canManage,
}: {
    studentId: number;
    followups: FollowupRow[];
    advisers: { id: number; name: string }[];
    canManage: boolean;
}) {
    const [showForm, setShowForm] = useState(false);
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
        post(route('students.followups.store', studentId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    };

    return (
        <div className="flex flex-col gap-4">
            {followups.length === 0 ? (
                <p className="text-sm text-slate-500">No intervention follow-ups yet.</p>
            ) : (
                <div className="flex flex-col gap-3">
                    {followups.map((f) => (
                        <div key={f.id} className="rounded-md border border-slate-200 p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <Badge variant={STATUS_VARIANT[f.status]}>{f.status.replace(/_/g, ' ')}</Badge>
                                    {f.due_date && <span className="text-xs text-slate-400">Due {f.due_date.slice(0, 10)}</span>}
                                    {f.assigned_to && <span className="text-xs text-slate-400">Assigned to {f.assigned_to.name}</span>}
                                </div>
                                {canManage && <StatusControl studentId={studentId} followup={f} />}
                            </div>
                            <p className="mt-2 text-sm text-slate-700">{f.description}</p>
                            {f.status === 'completed' && f.completed_by && (
                                <p className="mt-1 text-xs text-slate-400">Completed by {f.completed_by.name}</p>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {canManage && (
                <>
                    {!showForm ? (
                        <PrimaryButton type="button" className="self-start" onClick={() => setShowForm(true)}>
                            Add Follow-up
                        </PrimaryButton>
                    ) : (
                        <form onSubmit={submit} className="flex flex-col gap-3 rounded-md border border-slate-200 p-4">
                            <div>
                                <InputLabel value="Description" />
                                <textarea
                                    className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                    rows={2}
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                <InputError message={errors.description} className="mt-1" />
                            </div>

                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <InputLabel value="Assign To" />
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                        value={data.assigned_to}
                                        onChange={(e) => setData('assigned_to', e.target.value)}
                                    >
                                        <option value="">— Unassigned —</option>
                                        {advisers.map((a) => (
                                            <option key={a.id} value={a.id}>
                                                {a.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.assigned_to} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Due Date" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.due_date}
                                        onChange={(e) => setData('due_date', e.target.value)}
                                    />
                                    <InputError message={errors.due_date} className="mt-1" />
                                </div>
                            </div>

                            <div className="flex gap-3">
                                <PrimaryButton disabled={processing}>Save Follow-up</PrimaryButton>
                                <SecondaryButton
                                    type="button"
                                    onClick={() => {
                                        setShowForm(false);
                                        reset();
                                    }}
                                >
                                    Cancel
                                </SecondaryButton>
                            </div>
                        </form>
                    )}
                </>
            )}
        </div>
    );
}
