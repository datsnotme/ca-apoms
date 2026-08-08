import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import UserForm from './Form';

interface UserDetail {
    id: number;
    employee_number: string;
    surname: string;
    first_name: string;
    middle_name: string | null;
    suffix: string | null;
    email: string;
    username: string;
    contact_number: string | null;
    department_id: number | null;
    status: 'active' | 'inactive';
    role: string | null;
}

export default function Edit({
    user,
    departments,
    roles,
}: {
    user: UserDetail;
    departments: { id: number; name: string }[];
    roles: { value: string; label: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit User</h1>}>
            <Head title={`Edit ${user.first_name} ${user.surname}`} />

            <Card>
                <CardHeader title={`${user.first_name} ${user.surname}`} />
                <CardContent>
                    <UserForm
                        action={route('users.update', user.id)}
                        method="put"
                        initialValues={{
                            employee_number: user.employee_number,
                            surname: user.surname,
                            first_name: user.first_name,
                            middle_name: user.middle_name ?? '',
                            suffix: user.suffix ?? '',
                            email: user.email,
                            username: user.username,
                            contact_number: user.contact_number ?? '',
                            department_id: user.department_id ? String(user.department_id) : '',
                            role: user.role ?? undefined,
                            status: user.status,
                        }}
                        departments={departments}
                        roles={roles}
                        submitLabel="Save Changes"
                        onCancelHref={route('users.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
