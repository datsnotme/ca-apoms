import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import EquipmentForm from './Form';

export default function Create({
    departments,
    facilities,
    statuses,
    isAdmin,
}: {
    departments: { id: number; name: string }[];
    facilities: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
    isAdmin: boolean;
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Register Equipment</h1>}>
            <Head title="Register Equipment" />

            <Card>
                <CardHeader title="New Equipment" />
                <CardContent>
                    <EquipmentForm
                        action={route('equipment.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        facilities={facilities}
                        statuses={statuses}
                        isAdmin={isAdmin}
                        showStatus={false}
                        submitLabel="Register Equipment"
                        onCancelHref={route('equipment.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
