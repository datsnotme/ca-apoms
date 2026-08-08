import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import MeetingForm from './Form';

interface MeetingDetail {
    id: number;
    title: string;
    description: string | null;
    location: string | null;
    start_at: string;
    end_at: string | null;
    department_id: number | null;
}

export default function Edit({
    meeting,
    departments,
    isAdmin,
}: {
    meeting: MeetingDetail;
    departments: { id: number; name: string }[];
    isAdmin: boolean;
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Meeting</h1>}>
            <Head title="Edit Meeting" />

            <Card>
                <CardHeader title={meeting.title} />
                <CardContent>
                    <MeetingForm
                        action={route('meetings.update', meeting.id)}
                        method="put"
                        initialValues={{
                            title: meeting.title,
                            description: meeting.description ?? '',
                            start_at: meeting.start_at,
                            end_at: meeting.end_at ?? '',
                            location: meeting.location ?? '',
                            department_id: meeting.department_id ? String(meeting.department_id) : '',
                        }}
                        departments={departments}
                        isAdmin={isAdmin}
                        submitLabel="Save Changes"
                        onCancelHref={route('meetings.show', meeting.id)}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
