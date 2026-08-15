import { FormEventHandler, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

interface Device {
    id: number;
    name: string;
    device_code: string;
    status: string;
    last_sync_at: string | null;
    is_local: boolean;
}

interface Remote {
    id: number;
    name: string;
    base_url: string;
    created_at: string;
}

interface Run {
    id: number;
    direction: 'pull' | 'push';
    target: string;
    status: string;
    created_count: number;
    updated_count: number;
    conflict_count: number;
    started_at: string;
    device: { id: number; name: string } | null;
}

const RUN_STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral'> = {
    success: 'success',
    running: 'warning',
    failed: 'danger',
};

export default function Index({
    localDevice,
    devices,
    remotes,
    pendingConflictCount,
    recentRuns,
}: {
    localDevice: Device | null;
    devices: Device[];
    remotes: Remote[];
    pendingConflictCount: number;
    recentRuns: Run[];
}) {
    const [remoteModal, setRemoteModal] = useState<'closed' | 'add' | Remote>('closed');
    const [deleteTarget, setDeleteTarget] = useState<Remote | null>(null);
    const [syncingId, setSyncingId] = useState<number | null>(null);

    function syncNow(remote: Remote) {
        setSyncingId(remote.id);
        router.post(
            route('sync.remotes.sync', remote.id),
            {},
            { preserveScroll: true, onFinish: () => setSyncingId(null) },
        );
    }

    function setLocalDevice(device: Device) {
        router.post(route('sync.devices.set-local', device.id), {}, { preserveScroll: true });
    }

    function confirmDelete() {
        if (!deleteTarget) return;
        router.delete(route('sync.remotes.destroy', deleteTarget.id), {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Sync Center</h1>}>
            <Head title="Sync Center" />

            <div className="flex flex-col gap-6">
                {pendingConflictCount > 0 && (
                    <Link
                        href={route('sync.conflicts')}
                        className="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 hover:bg-red-100"
                    >
                        <span>
                            {pendingConflictCount} unresolved sync {pendingConflictCount === 1 ? 'conflict needs' : 'conflicts need'} your attention.
                        </span>
                        <span className="font-medium">Review conflicts →</span>
                    </Link>
                )}

                <Card>
                    <CardHeader
                        title="This Instance"
                        description="The device identity this running instance uses when syncing — set once per installation."
                    />
                    <CardContent>
                        {localDevice ? (
                            <p className="text-sm text-slate-700">
                                Syncing as <span className="font-medium">{localDevice.name}</span>{' '}
                                <span className="font-mono text-xs text-slate-900">({localDevice.device_code})</span>
                            </p>
                        ) : (
                            <p className="text-sm text-amber-700">
                                No device is set as this instance yet — pick one from the Devices list below before
                                using Sync Now. Register a device via <span className="font-mono text-xs">php artisan sync:register-device</span>.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        title="Remotes"
                        description="Other CA-APOMS instances this one can pull from and push to."
                        actions={<PrimaryButton onClick={() => setRemoteModal('add')}>Add Remote</PrimaryButton>}
                    />
                    <CardContent>
                        {remotes.length === 0 ? (
                            <EmptyState
                                title="No remotes configured"
                                description="Add a remote instance's URL and a device token issued by it to start syncing."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                        <tr>
                                            <th className="px-3 py-2">Name</th>
                                            <th className="px-3 py-2">Base URL</th>
                                            <th className="px-3 py-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {remotes.map((remote) => (
                                            <tr key={remote.id}>
                                                <td className="px-3 py-2 font-medium text-slate-900">{remote.name}</td>
                                                <td className="px-3 py-2 font-mono text-xs">{remote.base_url}</td>
                                                <td className="px-3 py-2">
                                                    <div className="flex items-center gap-4">
                                                        <button
                                                            type="button"
                                                            disabled={syncingId === remote.id}
                                                            onClick={() => syncNow(remote)}
                                                            className="text-sm font-medium text-brand-700 hover:text-brand-900 disabled:opacity-50"
                                                        >
                                                            {syncingId === remote.id ? 'Syncing…' : 'Sync Now'}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setRemoteModal(remote)}
                                                            className="text-sm font-medium text-slate-600 hover:text-slate-900"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => setDeleteTarget(remote)}
                                                            className="text-sm font-medium text-red-600 hover:text-red-800"
                                                        >
                                                            Remove
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        title="Devices"
                        description="Every device registered against this database."
                        actions={
                            <Link href={route('sync.devices.index')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                                Manage Devices →
                            </Link>
                        }
                    />
                    <CardContent>
                        {devices.length === 0 ? (
                            <EmptyState
                                title="No devices registered yet"
                                action={
                                    <Link href={route('sync.devices.index')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                                        Register a device →
                                    </Link>
                                }
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                        <tr>
                                            <th className="px-3 py-2">Name</th>
                                            <th className="px-3 py-2">Code</th>
                                            <th className="px-3 py-2">Status</th>
                                            <th className="px-3 py-2">Last Synced</th>
                                            <th className="px-3 py-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {devices.map((device) => (
                                            <tr key={device.id}>
                                                <td className="px-3 py-2 font-medium text-slate-900">
                                                    {device.name}
                                                    {device.is_local && (
                                                        <span className="ml-2">
                                                            <Badge variant="info">This instance</Badge>
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2 font-mono text-xs">{device.device_code}</td>
                                                <td className="px-3 py-2">
                                                    <Badge variant={device.status === 'active' ? 'success' : 'neutral'}>
                                                        {device.status}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-2">
                                                    {device.last_sync_at ? new Date(device.last_sync_at).toLocaleString() : 'Never'}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {!device.is_local && (
                                                        <button
                                                            type="button"
                                                            onClick={() => setLocalDevice(device)}
                                                            className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                        >
                                                            Set as this instance
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        title="Recent Activity"
                        description="The last 5 sync runs across every device."
                        actions={
                            <Link href={route('sync.history')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                                View full history →
                            </Link>
                        }
                    />
                    <CardContent>
                        {recentRuns.length === 0 ? (
                            <EmptyState title="No sync runs yet" />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                        <tr>
                                            <th className="px-3 py-2">When</th>
                                            <th className="px-3 py-2">Device</th>
                                            <th className="px-3 py-2">Direction</th>
                                            <th className="px-3 py-2">Target</th>
                                            <th className="px-3 py-2">Result</th>
                                            <th className="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {recentRuns.map((run) => (
                                            <tr key={run.id}>
                                                <td className="px-3 py-2 whitespace-nowrap">
                                                    {new Date(run.started_at).toLocaleString()}
                                                </td>
                                                <td className="px-3 py-2">{run.device?.name ?? '—'}</td>
                                                <td className="px-3 py-2 capitalize">{run.direction}</td>
                                                <td className="px-3 py-2">{run.target}</td>
                                                <td className="px-3 py-2 text-xs text-slate-600">
                                                    {run.created_count} created, {run.updated_count} updated
                                                    {run.conflict_count > 0 && `, ${run.conflict_count} conflicts`}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <Badge variant={RUN_STATUS_VARIANT[run.status] ?? 'neutral'}>{run.status}</Badge>
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

            <RemoteFormModal target={remoteModal} onClose={() => setRemoteModal('closed')} />

            <Modal show={deleteTarget !== null} onClose={() => setDeleteTarget(null)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-medium text-slate-900">Remove this remote?</h2>
                    <p className="mt-2 text-sm text-slate-900">
                        <span className="font-medium">{deleteTarget?.name}</span> will no longer be synced with. This
                        does not delete any data already synced from it.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeleteTarget(null)}>Cancel</SecondaryButton>
                        <DangerButton onClick={confirmDelete}>Remove</DangerButton>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}

function RemoteFormModal({ target, onClose }: { target: 'closed' | 'add' | Remote; onClose: () => void }) {
    const isEdit = target !== 'closed' && target !== 'add';
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: isEdit ? target.name : '',
        base_url: isEdit ? target.base_url : '',
        token: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const onSuccess = () => {
            reset();
            onClose();
        };

        if (isEdit) {
            put(route('sync.remotes.update', target.id), { preserveScroll: true, onSuccess });
        } else {
            post(route('sync.remotes.store'), { preserveScroll: true, onSuccess });
        }
    };

    return (
        <Modal show={target !== 'closed'} onClose={onClose} maxWidth="md">
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-medium text-slate-900">{isEdit ? 'Edit Remote' : 'Add Remote'}</h2>

                <div className="mt-4">
                    <InputLabel htmlFor="remote-name" value="Name" />
                    <TextInput
                        id="remote-name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="e.g. Cloud, LAN Hub"
                        required
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="remote-base-url" value="Base URL" />
                    <TextInput
                        id="remote-base-url"
                        className="mt-1 block w-full"
                        value={data.base_url}
                        onChange={(e) => setData('base_url', e.target.value)}
                        placeholder="https://ca-apoms.example.edu"
                        required
                    />
                    <InputError message={errors.base_url} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel
                        htmlFor="remote-token"
                        value={isEdit ? 'Device Token (leave blank to keep the current one)' : 'Device Token'}
                    />
                    <TextInput
                        id="remote-token"
                        type="password"
                        className="mt-1 block w-full"
                        value={data.token}
                        onChange={(e) => setData('token', e.target.value)}
                        placeholder={isEdit ? '••••••••' : 'Token issued by the remote via sync:register-device'}
                        required={!isEdit}
                    />
                    <InputError message={errors.token} className="mt-2" />
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={onClose}>
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton disabled={processing}>{isEdit ? 'Save Changes' : 'Add Remote'}</PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
