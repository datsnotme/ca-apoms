import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import AnnouncementForm from './Form';

export default function Create({ departments, isAdmin }: { departments: { id: number; name: string }[]; isAdmin: boolean }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Post Announcement</h1>}>
            <Head title="Post Announcement" />

            <Card>
                <CardHeader title="New Announcement" />
                <CardContent>
                    <AnnouncementForm
                        action={route('announcements.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        isAdmin={isAdmin}
                        submitLabel="Post Announcement"
                        onCancelHref={route('announcements.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
