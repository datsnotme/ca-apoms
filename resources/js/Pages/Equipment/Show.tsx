import { FormEventHandler, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

type Status = 'available' | 'borrowed' | 'under_maintenance' | 'retired';

const STATUS_VARIANT: Record<Status, 'success' | 'info' | 'neutral' | 'danger'> = {
    available: 'success',
    borrowed: 'info',
    under_maintenance: 'neutral',
    retired: 'danger',
};

interface PersonRef {
    id: number;
    name: string;
}

interface ReturnDetail {
    id: number;
    returned_at: string;
    condition_on_return: string | null;
    notes: string | null;
    recorded_by: PersonRef | null;
}

interface BorrowingRow {
    id: number;
    borrowed_at: string;
    expected_return_at: string | null;
    purpose: string | null;
    borrowed_by: PersonRef | null;
    return: ReturnDetail | null;
}

interface MaintenanceRow {
    id: number;
    description: string;
    started_at: string;
    completed_at: string | null;
    performed_by: string | null;
    notes: string | null;
    recorded_by: PersonRef | null;
    can_complete: boolean;
}

interface EquipmentDetail {
    id: number;
    name: string;
    type: string;
    serial_number: string | null;
    description: string | null;
    status: Status;
    status_label: string;
    department: { name: string } | null;
    facility: { name: string } | null;
    created_by: PersonRef | null;
    active_borrowing: (Omit<BorrowingRow, 'return'>) | null;
    borrowings: BorrowingRow[];
    maintenance_records: MaintenanceRow[];
}

function BorrowForm({ equipmentId, userOptions }: { equipmentId: number; userOptions: PersonRef[] }) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        borrowed_by: string;
        expected_return_at: string;
        purpose: string;
    }>({ borrowed_by: '', expected_return_at: '', purpose: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('equipment.borrowings.store', equipmentId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-4">
            <div className="sm:col-span-2">
                <InputLabel value="Borrower" />
                <select
                    className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={data.borrowed_by}
                    onChange={(e) => setData('borrowed_by', e.target.value)}
                    required
                >
                    <option value="">Select a borrower…</option>
                    {userOptions.map((u) => (
                        <option key={u.id} value={u.id}>
                            {u.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.borrowed_by} className="mt-1" />
            </div>
            <div>
                <InputLabel value="Expected Return" />
                <TextInput
                    type="date"
                    className="mt-1 block w-full"
                    value={data.expected_return_at}
                    onChange={(e) => setData('expected_return_at', e.target.value)}
                />
                <InputError message={errors.expected_return_at} className="mt-1" />
            </div>
            <div className="flex items-end">
                <PrimaryButton disabled={processing}>Record Borrowing</PrimaryButton>
            </div>
            <div className="sm:col-span-4">
                <InputLabel value="Purpose (optional)" />
                <TextInput className="mt-1 block w-full" value={data.purpose} onChange={(e) => setData('purpose', e.target.value)} />
                <InputError message={errors.purpose} className="mt-1" />
            </div>
        </form>
    );
}

function ReturnForm({ equipmentId, borrowingId }: { equipmentId: number; borrowingId: number }) {
    const { data, setData, post, processing, errors } = useForm<{
        condition_on_return: string;
        notes: string;
    }>({ condition_on_return: '', notes: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('equipment.borrowings.return', [equipmentId, borrowingId]), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-3">
            <div>
                <InputLabel value="Condition on Return" />
                <TextInput
                    className="mt-1 block w-full"
                    value={data.condition_on_return}
                    onChange={(e) => setData('condition_on_return', e.target.value)}
                />
                <InputError message={errors.condition_on_return} className="mt-1" />
            </div>
            <div>
                <InputLabel value="Notes" />
                <TextInput className="mt-1 block w-full" value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                <InputError message={errors.notes} className="mt-1" />
            </div>
            <div className="flex items-end">
                <PrimaryButton disabled={processing}>Record Return</PrimaryButton>
            </div>
        </form>
    );
}

function MaintenanceForm({ equipmentId }: { equipmentId: number }) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        description: string;
        started_at: string;
        performed_by: string;
    }>({ description: '', started_at: '', performed_by: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('equipment.maintenance.store', equipmentId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-4">
            <div className="sm:col-span-2">
                <InputLabel value="Issue / Maintenance Needed" />
                <TextInput className="mt-1 block w-full" value={data.description} onChange={(e) => setData('description', e.target.value)} required />
                <InputError message={errors.description} className="mt-1" />
            </div>
            <div>
                <InputLabel value="Performed By (optional)" />
                <TextInput className="mt-1 block w-full" value={data.performed_by} onChange={(e) => setData('performed_by', e.target.value)} />
                <InputError message={errors.performed_by} className="mt-1" />
            </div>
            <div className="flex items-end">
                <PrimaryButton disabled={processing}>Report Maintenance</PrimaryButton>
            </div>
        </form>
    );
}

function CompleteMaintenanceButton({ equipmentId, maintenanceId }: { equipmentId: number; maintenanceId: number }) {
    const [processing, setProcessing] = useState(false);

    function complete() {
        setProcessing(true);
        router.patch(
            route('equipment.maintenance.complete', [equipmentId, maintenanceId]),
            {},
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    return (
        <SecondaryButton type="button" disabled={processing} onClick={complete}>
            Mark Complete
        </SecondaryButton>
    );
}

export default function Show({
    equipment,
    canManage,
    userOptions,
}: {
    equipment: EquipmentDetail;
    canManage: boolean;
    userOptions: PersonRef[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Equipment</h1>}>
            <Head title={equipment.name} />

            <div className="flex flex-col gap-6">
                <Link href={route('equipment.index')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                    ← Back to Equipment
                </Link>

                <Card>
                    <CardHeader
                        title={equipment.name}
                        description={equipment.serial_number ? `Serial: ${equipment.serial_number}` : undefined}
                        actions={
                            canManage ? (
                                <div className="flex items-center gap-3">
                                    <Link href={route('equipment.edit', equipment.id)}>
                                        <PrimaryButton>Edit</PrimaryButton>
                                    </Link>
                                    <ConfirmDeleteButton href={route('equipment.destroy', equipment.id)} itemLabel={equipment.name} />
                                </div>
                            ) : undefined
                        }
                    />
                    <CardContent>
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="info">{equipment.type}</Badge>
                            <Badge variant={STATUS_VARIANT[equipment.status]}>{equipment.status_label}</Badge>
                            <Badge variant={equipment.department ? 'neutral' : 'success'}>
                                {equipment.department?.name ?? 'Shared / College-wide'}
                            </Badge>
                            {equipment.facility && <Badge variant="neutral">{equipment.facility.name}</Badge>}
                        </div>
                        {equipment.description && <p className="mt-3 whitespace-pre-wrap text-sm text-slate-600">{equipment.description}</p>}
                        {equipment.active_borrowing && (
                            <p className="mt-3 text-sm text-slate-600">
                                Currently borrowed by <strong>{equipment.active_borrowing.borrowed_by?.name ?? 'Unknown'}</strong> since{' '}
                                {equipment.active_borrowing.borrowed_at.slice(0, 10)}
                                {equipment.active_borrowing.expected_return_at
                                    ? ` (expected back ${equipment.active_borrowing.expected_return_at.slice(0, 10)})`
                                    : ''}
                                .
                            </p>
                        )}
                    </CardContent>
                </Card>

                {canManage && equipment.status === 'available' && (
                    <Card>
                        <CardHeader title="Record Borrowing" />
                        <CardContent>
                            <BorrowForm equipmentId={equipment.id} userOptions={userOptions} />
                        </CardContent>
                    </Card>
                )}

                {canManage && equipment.status === 'borrowed' && equipment.active_borrowing && (
                    <Card>
                        <CardHeader title="Record Return" />
                        <CardContent>
                            <ReturnForm equipmentId={equipment.id} borrowingId={equipment.active_borrowing.id} />
                        </CardContent>
                    </Card>
                )}

                {canManage && equipment.status === 'available' && (
                    <Card>
                        <CardHeader title="Report Maintenance" />
                        <CardContent>
                            <MaintenanceForm equipmentId={equipment.id} />
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader title="Borrowing History" />
                    <CardContent>
                        {equipment.borrowings.length === 0 ? (
                            <p className="text-sm text-slate-900">No borrowing history yet.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                        <tr>
                                            <th className="px-3 py-2">Borrower</th>
                                            <th className="px-3 py-2">Borrowed</th>
                                            <th className="px-3 py-2">Expected Return</th>
                                            <th className="px-3 py-2">Returned</th>
                                            <th className="px-3 py-2">Condition</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {equipment.borrowings.map((b) => (
                                            <tr key={b.id}>
                                                <td className="px-3 py-2">{b.borrowed_by?.name ?? 'Unknown'}</td>
                                                <td className="px-3 py-2">{b.borrowed_at.slice(0, 10)}</td>
                                                <td className="px-3 py-2">{b.expected_return_at?.slice(0, 10) ?? '—'}</td>
                                                <td className="px-3 py-2">
                                                    {b.return ? b.return.returned_at.slice(0, 10) : <Badge variant="info">Outstanding</Badge>}
                                                </td>
                                                <td className="px-3 py-2">{b.return?.condition_on_return ?? '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Maintenance History" />
                    <CardContent>
                        {equipment.maintenance_records.length === 0 ? (
                            <p className="text-sm text-slate-900">No maintenance history yet.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                        <tr>
                                            <th className="px-3 py-2">Description</th>
                                            <th className="px-3 py-2">Started</th>
                                            <th className="px-3 py-2">Completed</th>
                                            <th className="px-3 py-2">Performed By</th>
                                            <th className="px-3 py-2 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {equipment.maintenance_records.map((m) => (
                                            <tr key={m.id}>
                                                <td className="px-3 py-2">{m.description}</td>
                                                <td className="px-3 py-2">{m.started_at.slice(0, 10)}</td>
                                                <td className="px-3 py-2">
                                                    {m.completed_at ? m.completed_at.slice(0, 10) : <Badge variant="neutral">Ongoing</Badge>}
                                                </td>
                                                <td className="px-3 py-2">{m.performed_by ?? '—'}</td>
                                                <td className="px-3 py-2 text-right">
                                                    {m.can_complete && <CompleteMaintenanceButton equipmentId={equipment.id} maintenanceId={m.id} />}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
