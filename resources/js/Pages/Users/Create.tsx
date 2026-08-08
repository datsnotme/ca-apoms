import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import UserForm from './Form';

export default function Create({
    departments,
    roles,
}: {
    departments: { id: number; name: string }[];
    roles: { value: string; label: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add User</h1>}>
            <Head title="Add User" />

            <Card>
                <CardHeader
                    title="New User Account"
                    description="A temporary password is generated automatically and shown once after creation. The user must change it on first login."
                />
                <CardContent>
                    <UserForm
                        action={route('users.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        roles={roles}
                        submitLabel="Create User"
                        onCancelHref={route('users.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
