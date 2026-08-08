import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import DepartmentForm from './Form';

interface DepartmentDetail {
    id: number;
    code: string;
    name: string;
    description: string | null;
    department_head_id: number | null;
    office_location: string | null;
    contact_info: string | null;
    status: 'active' | 'inactive';
}

export default function Edit({
    department,
    potentialHeads,
}: {
    department: DepartmentDetail;
    potentialHeads: { id: number; name: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Department</h1>}>
            <Head title={`Edit ${department.name}`} />

            <Card>
                <CardHeader title={department.name} />
                <CardContent>
                    <DepartmentForm
                        action={route('departments.update', department.id)}
                        method="put"
                        initialValues={{
                            code: department.code,
                            name: department.name,
                            description: department.description ?? '',
                            department_head_id: department.department_head_id
                                ? String(department.department_head_id)
                                : '',
                            office_location: department.office_location ?? '',
                            contact_info: department.contact_info ?? '',
                            status: department.status,
                        }}
                        potentialHeads={potentialHeads}
                        submitLabel="Save Changes"
                        onCancelHref={route('departments.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
