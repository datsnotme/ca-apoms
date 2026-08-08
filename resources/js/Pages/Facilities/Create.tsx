import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import FacilityForm from './Form';

export default function Create({ departments, isAdmin }: { departments: { id: number; name: string }[]; isAdmin: boolean }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Register Facility</h1>}>
            <Head title="Register Facility" />

            <Card>
                <CardHeader title="New Facility" />
                <CardContent>
                    <FacilityForm
                        action={route('facilities.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        isAdmin={isAdmin}
                        submitLabel="Register Facility"
                        onCancelHref={route('facilities.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
