import { FormEventHandler, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import Badge from '@/Components/ui/Badge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface AttendeeRow {
    id: number;
    attended: boolean;
    attended_at: string | null;
    user: { id: number; name: string };
}

export default function AttendeeList({
    meetingId,
    attendees,
    attendeeOptions,
    canManage,
}: {
    meetingId: number;
    attendees: AttendeeRow[];
    attendeeOptions: { id: number; name: string }[];
    canManage: boolean;
}) {
    const [processingId, setProcessingId] = useState<number | null>(null);
    const { data, setData, post, processing, reset } = useForm<{ user_id: string }>({ user_id: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('meetings.attendees.store', meetingId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    function toggleAttended(attendee: AttendeeRow) {
        setProcessingId(attendee.id);
        router.patch(
            route('meetings.attendees.update', [meetingId, attendee.id]),
            { attended: !attendee.attended },
            { preserveScroll: true, onFinish: () => setProcessingId(null) },
        );
    }

    return (
        <div className="flex flex-col gap-4">
            {attendees.length === 0 ? (
                <p className="text-sm text-slate-900">No attendees invited yet.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                            <tr>
                                <th className="px-3 py-2">Attendee</th>
                                <th className="px-3 py-2">Attendance</th>
                                {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {attendees.map((attendee) => (
                                <tr key={attendee.id}>
                                    <td className="px-3 py-2">{attendee.user.name}</td>
                                    <td className="px-3 py-2">
                                        {canManage ? (
                                            <button
                                                type="button"
                                                disabled={processingId === attendee.id}
                                                onClick={() => toggleAttended(attendee)}
                                                className="disabled:opacity-50"
                                            >
                                                <Badge variant={attendee.attended ? 'success' : 'neutral'}>
                                                    {attendee.attended ? 'Attended' : 'Mark Attended'}
                                                </Badge>
                                            </button>
                                        ) : (
                                            <Badge variant={attendee.attended ? 'success' : 'neutral'}>
                                                {attendee.attended ? 'Attended' : 'Invited'}
                                            </Badge>
                                        )}
                                    </td>
                                    {canManage && (
                                        <td className="px-3 py-2 text-right">
                                            <SecondaryButton
                                                type="button"
                                                onClick={() =>
                                                    router.delete(route('meetings.attendees.destroy', [meetingId, attendee.id]), {
                                                        preserveScroll: true,
                                                    })
                                                }
                                            >
                                                Remove
                                            </SecondaryButton>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {canManage && (
                <form onSubmit={submit} className="flex flex-wrap items-end gap-3 rounded-md border border-slate-200 p-4">
                    <div className="min-w-[16rem]">
                        <select
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={data.user_id}
                            onChange={(e) => setData('user_id', e.target.value)}
                            required
                        >
                            <option value="">Select a user to invite…</option>
                            {attendeeOptions.map((u) => (
                                <option key={u.id} value={u.id}>
                                    {u.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <PrimaryButton disabled={processing || !data.user_id}>Invite</PrimaryButton>
                </form>
            )}
        </div>
    );
}
