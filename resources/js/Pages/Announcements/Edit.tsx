import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import AnnouncementForm from './Form';

interface AnnouncementDetail {
    id: number;
    title: string;
    body: string;
    department_id: number | null;
}

export default function Edit({
    announcement,
    departments,
    isAdmin,
}: {
    announcement: AnnouncementDetail;
    departments: { id: number; name: string }[];
    isAdmin: boolean;
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Announcement</h1>}>
            <Head title="Edit Announcement" />

            <Card>
                <CardHeader title={announcement.title} />
                <CardContent>
                    <AnnouncementForm
                        action={route('announcements.update', announcement.id)}
                        method="put"
                        initialValues={{
                            title: announcement.title,
                            body: announcement.body,
                            department_id: announcement.department_id ? String(announcement.department_id) : '',
                        }}
                        departments={departments}
                        isAdmin={isAdmin}
                        submitLabel="Save Changes"
                        onCancelHref={route('announcements.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
