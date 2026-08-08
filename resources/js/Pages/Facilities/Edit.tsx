import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import FacilityForm from './Form';

interface FacilityDetail {
    id: number;
    name: string;
    type: string;
    department_id: number | null;
    location: string | null;
    capacity: number | null;
    description: string | null;
    is_active: boolean;
}

export default function Edit({
    facility,
    departments,
    isAdmin,
}: {
    facility: FacilityDetail;
    departments: { id: number; name: string }[];
    isAdmin: boolean;
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Facility</h1>}>
            <Head title="Edit Facility" />

            <Card>
                <CardHeader title={facility.name} />
                <CardContent>
                    <FacilityForm
                        action={route('facilities.update', facility.id)}
                        method="put"
                        initialValues={{
                            name: facility.name,
                            type: facility.type,
                            department_id: facility.department_id ? String(facility.department_id) : '',
                            location: facility.location ?? '',
                            capacity: facility.capacity ? String(facility.capacity) : '',
                            description: facility.description ?? '',
                            is_active: facility.is_active,
                        }}
                        departments={departments}
                        isAdmin={isAdmin}
                        submitLabel="Save Changes"
                        onCancelHref={route('facilities.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
