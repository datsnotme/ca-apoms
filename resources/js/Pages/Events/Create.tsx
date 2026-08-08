import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import EventForm from './Form';

export default function Create({ departments, isAdmin }: { departments: { id: number; name: string }[]; isAdmin: boolean }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Event</h1>}>
            <Head title="Add Event" />

            <Card>
                <CardHeader title="New Event" />
                <CardContent>
                    <EventForm
                        action={route('events.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        isAdmin={isAdmin}
                        submitLabel="Add Event"
                        onCancelHref={route('events.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
