import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import PrimaryButton from '@/Components/PrimaryButton';
import Modal from '@/Components/Modal';
import { Paginated } from '@/types';
import FacilityForm from './Form';

interface FacilityRow {
    id: number;
    name: string;
    type: string;
    location: string | null;
    capacity: number | null;
    is_active: boolean;
    department: { name: string } | null;
    can_manage: boolean;
}

export default function Index({
    facilities,
    canCreate,
    departments,
    isAdmin,
}: {
    facilities: Paginated<FacilityRow>;
    canCreate: boolean;
    departments?: { id: number; name: string }[];
    isAdmin?: boolean;
}) {
    const [showCreate, setShowCreate] = useState(false);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Facilities</h1>}>
            <Head title="Facilities" />

            <Card>
                <CardHeader
                    title="Facilities"
                    description="Laboratories, farms, greenhouses, field locations, classrooms, and other college spaces."
                    actions={
                        canCreate ? (
                            <PrimaryButton onClick={() => setShowCreate(true)}>Register Facility</PrimaryButton>
                        ) : undefined
                    }
                />

                {facilities.data.length === 0 ? (
                    <EmptyState title="No facilities registered yet" />
                ) : (
                    <div className="divide-y divide-slate-100">
                        {facilities.data.map((facility) => (
                            <div key={facility.id} className="flex items-start justify-between gap-4 px-5 py-4">
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-sm font-semibold text-slate-900">{facility.name}</h3>
                                        <Badge variant="info">{facility.type}</Badge>
                                        <Badge variant={facility.department ? 'neutral' : 'success'}>
                                            {facility.department?.name ?? 'Shared / College-wide'}
                                        </Badge>
                                        {!facility.is_active && <Badge variant="danger">Inactive</Badge>}
                                    </div>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {facility.location ?? 'No location on file'}
                                        {facility.capacity ? ` · Capacity ${facility.capacity}` : ''}
                                    </p>
                                </div>
                                {facility.can_manage && (
                                    <div className="flex shrink-0 items-center gap-3">
                                        <Link
                                            href={route('facilities.edit', facility.id)}
                                            className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                        >
                                            Edit
                                        </Link>
                                        <ConfirmDeleteButton href={route('facilities.destroy', facility.id)} itemLabel={facility.name} />
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                <Pagination links={facilities.links} from={facilities.from} to={facilities.to} total={facilities.total} />
            </Card>

            {canCreate && departments && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="2xl" variant="form">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">Register Facility</h2>
                        <div className="mt-4">
                            <FacilityForm
                                action={route('facilities.store')}
                                method="post"
                                initialValues={{}}
                                departments={departments}
                                isAdmin={Boolean(isAdmin)}
                                submitLabel="Register Facility"
                                onCancel={() => setShowCreate(false)}
                                onSuccess={() => setShowCreate(false)}
                            />
                        </div>
                    </div>
                </Modal>
            )}
        </AppLayout>
    );
}
