import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import PrimaryButton from '@/Components/PrimaryButton';
import AttendeeList from './AttendeeList';
import ActionItemList from './ActionItemList';

interface AttendeeRow {
    id: number;
    attended: boolean;
    attended_at: string | null;
    user: { id: number; name: string };
}

interface ActionItemRow {
    id: number;
    description: string;
    due_date: string | null;
    status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    notes: string | null;
    assigned_to: { id: number; name: string } | null;
    completed_by: { id: number; name: string } | null;
    completed_at: string | null;
    can_update: boolean;
    can_delete: boolean;
}

interface MeetingDetail {
    id: number;
    title: string;
    description: string | null;
    location: string | null;
    start_at: string;
    end_at: string | null;
    department: { name: string } | null;
    created_by: { name: string } | null;
    attendees: AttendeeRow[];
    action_items: ActionItemRow[];
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

export default function Show({
    meeting,
    canManage,
    attendeeOptions,
    assigneeOptions,
    statuses,
}: {
    meeting: MeetingDetail;
    canManage: boolean;
    attendeeOptions: { id: number; name: string }[];
    assigneeOptions: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Meeting</h1>}>
            <Head title={meeting.title} />

            <div className="flex flex-col gap-6">
                <Link href={route('meetings.index')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                    ← Back to Meetings
                </Link>

                <Card>
                    <CardHeader
                        title={meeting.title}
                        description={`${formatDateTime(meeting.start_at)}${meeting.end_at ? ` – ${formatDateTime(meeting.end_at)}` : ''}${
                            meeting.location ? ` · ${meeting.location}` : ''
                        }`}
                        actions={
                            canManage ? (
                                <div className="flex items-center gap-3">
                                    <Link href={route('meetings.edit', meeting.id)}>
                                        <PrimaryButton>Edit</PrimaryButton>
                                    </Link>
                                    <ConfirmDeleteButton href={route('meetings.destroy', meeting.id)} itemLabel={meeting.title} />
                                </div>
                            ) : undefined
                        }
                    />
                    <CardContent>
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant={meeting.department ? 'info' : 'neutral'}>{meeting.department?.name ?? 'Entire College'}</Badge>
                            <span className="text-xs text-slate-900">Organized by {meeting.created_by?.name ?? 'Unknown'}</span>
                        </div>
                        {meeting.description && <p className="mt-3 whitespace-pre-wrap text-sm text-slate-600">{meeting.description}</p>}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Attendees" description="Who was invited, and whether they attended." />
                    <CardContent>
                        <AttendeeList
                            meetingId={meeting.id}
                            attendees={meeting.attendees}
                            attendeeOptions={attendeeOptions}
                            canManage={canManage}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Action Items" description="Follow-up tasks assigned as a result of this meeting." />
                    <CardContent>
                        <ActionItemList
                            meetingId={meeting.id}
                            actionItems={meeting.action_items}
                            assigneeOptions={assigneeOptions}
                            statuses={statuses}
                            canManage={canManage}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
