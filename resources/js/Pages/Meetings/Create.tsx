import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import MeetingForm from './Form';

export default function Create({ departments, isAdmin }: { departments: { id: number; name: string }[]; isAdmin: boolean }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Schedule Meeting</h1>}>
            <Head title="Schedule Meeting" />

            <Card>
                <CardHeader title="New Meeting" />
                <CardContent>
                    <MeetingForm
                        action={route('meetings.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        isAdmin={isAdmin}
                        submitLabel="Schedule Meeting"
                        onCancelHref={route('meetings.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
