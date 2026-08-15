import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import PrimaryButton from '@/Components/PrimaryButton';
import Modal from '@/Components/Modal';
import { Paginated } from '@/types';
import EquipmentForm from './Form';

type Status = 'available' | 'borrowed' | 'under_maintenance' | 'retired';

const STATUS_VARIANT: Record<Status, 'success' | 'info' | 'neutral' | 'danger'> = {
    available: 'success',
    borrowed: 'info',
    under_maintenance: 'neutral',
    retired: 'danger',
};

interface EquipmentRow {
    id: number;
    name: string;
    type: string;
    serial_number: string | null;
    status: Status;
    status_label: string;
    department: { name: string } | null;
    facility: { name: string } | null;
}

export default function Index({
    equipment,
    canCreate,
    filters,
    departments,
    facilities,
    statuses,
    isAdmin,
}: {
    equipment: Paginated<EquipmentRow>;
    canCreate: boolean;
    filters: { status: string };
    departments?: { id: number; name: string }[];
    facilities?: { id: number; name: string }[];
    statuses?: { value: string; label: string }[];
    isAdmin?: boolean;
}) {
    const [showCreate, setShowCreate] = useState(false);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Equipment</h1>}>
            <Head title="Equipment" />

            <Card>
                <CardHeader
                    title="Equipment"
                    description="Inventory, borrowing, returns, and maintenance."
                    actions={
                        <div className="flex items-center gap-3">
                            <Link href={route('equipment.accountability')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                                Accountability Report
                            </Link>
                            {canCreate && (
                                <PrimaryButton onClick={() => setShowCreate(true)}>Register Equipment</PrimaryButton>
                            )}
                        </div>
                    }
                />

                <div className="flex items-center gap-2 border-b border-slate-200 px-5 py-3">
                    <select
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={filters.status}
                        onChange={(e) => router.get(route('equipment.index'), { status: e.target.value }, { preserveState: true })}
                    >
                        <option value="">All Statuses</option>
                        <option value="available">Available</option>
                        <option value="borrowed">Borrowed</option>
                        <option value="under_maintenance">Under Maintenance</option>
                        <option value="retired">Retired</option>
                    </select>
                </div>

                {equipment.data.length === 0 ? (
                    <EmptyState title="No equipment found" />
                ) : (
                    <div className="divide-y divide-slate-100">
                        {equipment.data.map((item) => (
                            <Link
                                key={item.id}
                                href={route('equipment.show', item.id)}
                                className="flex items-start justify-between gap-4 px-5 py-4 hover:bg-slate-50"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-sm font-semibold text-slate-900">{item.name}</h3>
                                        <Badge variant="info">{item.type}</Badge>
                                        <Badge variant={STATUS_VARIANT[item.status]}>{item.status_label}</Badge>
                                    </div>
                                    <p className="mt-1 text-sm text-slate-600">
                                        {item.department?.name ?? 'Shared / College-wide'}
                                        {item.facility ? ` · ${item.facility.name}` : ''}
                                        {item.serial_number ? ` · SN: ${item.serial_number}` : ''}
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                <Pagination links={equipment.links} from={equipment.from} to={equipment.to} total={equipment.total} />
            </Card>

            {canCreate && departments && facilities && statuses && (
                <Modal show={showCreate} onClose={() => setShowCreate(false)} maxWidth="2xl">
                    <div className="p-6">
                        <h2 className="text-lg font-medium text-slate-900">Register Equipment</h2>
                        <div className="mt-4">
                            <EquipmentForm
                                action={route('equipment.store')}
                                method="post"
                                initialValues={{}}
                                departments={departments}
                                facilities={facilities}
                                statuses={statuses}
                                isAdmin={Boolean(isAdmin)}
                                showStatus={false}
                                submitLabel="Register Equipment"
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
