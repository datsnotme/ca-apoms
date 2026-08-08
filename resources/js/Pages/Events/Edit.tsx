import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import EventForm from './Form';

interface EventDetail {
    id: number;
    title: string;
    description: string | null;
    location: string | null;
    start_at: string;
    end_at: string | null;
    department_id: number | null;
}

export default function Edit({
    event,
    departments,
    isAdmin,
}: {
    event: EventDetail;
    departments: { id: number; name: string }[];
    isAdmin: boolean;
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Event</h1>}>
            <Head title="Edit Event" />

            <Card>
                <CardHeader title={event.title} />
                <CardContent>
                    <EventForm
                        action={route('events.update', event.id)}
                        method="put"
                        initialValues={{
                            title: event.title,
                            description: event.description ?? '',
                            start_at: event.start_at,
                            end_at: event.end_at ?? '',
                            location: event.location ?? '',
                            department_id: event.department_id ? String(event.department_id) : '',
                        }}
                        departments={departments}
                        isAdmin={isAdmin}
                        submitLabel="Save Changes"
                        onCancelHref={route('events.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
