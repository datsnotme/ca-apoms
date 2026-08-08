import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import EquipmentForm from './Form';

interface EquipmentDetail {
    id: number;
    name: string;
    type: string;
    department_id: number | null;
    facility_id: number | null;
    serial_number: string | null;
    status: string;
    description: string | null;
}

export default function Edit({
    equipment,
    departments,
    facilities,
    statuses,
    isAdmin,
}: {
    equipment: EquipmentDetail;
    departments: { id: number; name: string }[];
    facilities: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
    isAdmin: boolean;
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Equipment</h1>}>
            <Head title="Edit Equipment" />

            <Card>
                <CardHeader title={equipment.name} />
                <CardContent>
                    <EquipmentForm
                        action={route('equipment.update', equipment.id)}
                        method="put"
                        initialValues={{
                            name: equipment.name,
                            type: equipment.type,
                            department_id: equipment.department_id ? String(equipment.department_id) : '',
                            facility_id: equipment.facility_id ? String(equipment.facility_id) : '',
                            serial_number: equipment.serial_number ?? '',
                            status: equipment.status,
                            description: equipment.description ?? '',
                        }}
                        departments={departments}
                        facilities={facilities}
                        statuses={statuses}
                        isAdmin={isAdmin}
                        showStatus
                        submitLabel="Save Changes"
                        onCancelHref={route('equipment.show', equipment.id)}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
