import { FormEventHandler, useEffect, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { Paginated } from '@/types';

interface EligibleUser {
    id: number;
    name: string;
    email: string;
}

interface Device {
    id: number;
    name: string;
    device_code: string;
    role_hint: string | null;
    status: string;
    last_sync_at: string | null;
    is_local: boolean;
    owner: EligibleUser | null;
}

export default function Index({
    devices,
    eligibleUsers,
    newDeviceToken,
}: {
    devices: Paginated<Device>;
    eligibleUsers: EligibleUser[];
    newDeviceToken: string | null;
}) {
    const [registerOpen, setRegisterOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<Device | null>(null);
    const [revokeTarget, setRevokeTarget] = useState<Device | null>(null);
    const [reissueTarget, setReissueTarget] = useState<Device | null>(null);
    const [tokenModalToken, setTokenModalToken] = useState<string | null>(null);
    const [busyId, setBusyId] = useState<number | null>(null);

    // A fresh token arrives as a page prop after register/reissue redirects
    // back here — surface it once via the reveal modal, matching the
    // console command's "shown once" behavior.
    useEffect(() => {
        if (newDeviceToken) {
            setTokenModalToken(newDeviceToken);
        }
    }, [newDeviceToken]);

    function setLocal(device: Device) {
        setBusyId(device.id);
        router.post(route('sync.devices.set-local', device.id), {}, { preserveScroll: true, onFinish: () => setBusyId(null) });
    }

    function confirmRevoke() {
        if (!revokeTarget) return;
        setBusyId(revokeTarget.id);
        router.post(route('sync.devices.revoke', revokeTarget.id), {}, {
            preserveScroll: true,
            onFinish: () => {
                setBusyId(null);
                setRevokeTarget(null);
            },
        });
    }

    function confirmReissue() {
        if (!reissueTarget) return;
        setBusyId(reissueTarget.id);
        router.post(route('sync.devices.reissue-token', reissueTarget.id), {}, {
            preserveScroll: true,
            onFinish: () => {
                setBusyId(null);
                setReissueTarget(null);
            },
        });
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Devices</h1>}>
            <Head title="Devices" />

            <Card>
                <CardHeader
                    title="Sync Devices"
                    description="Every device authorized to call this instance's sync API, and the Admin account each one authenticates as."
                    actions={<PrimaryButton onClick={() => setRegisterOpen(true)}>Register Device</PrimaryButton>}
                />
                <CardContent>
                    {devices.data.length === 0 ? (
                        <EmptyState title="No devices registered yet" action={<PrimaryButton onClick={() => setRegisterOpen(true)}>Register Device</PrimaryButton>} />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                    <tr>
                                        <th className="px-3 py-2">Name</th>
                                        <th className="px-3 py-2">Code</th>
                                        <th className="px-3 py-2">Owner</th>
                                        <th className="px-3 py-2">Role Hint</th>
                                        <th className="px-3 py-2">Status</th>
                                        <th className="px-3 py-2">Last Synced</th>
                                        <th className="px-3 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {devices.data.map((device) => (
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
                                                {device.owner ? (
                                                    <span title={device.owner.email}>{device.owner.name}</span>
                                                ) : (
                                                    <span className="text-slate-400">—</span>
                                                )}
                                            </td>
                                            <td className="px-3 py-2 text-slate-500">{device.role_hint ?? '—'}</td>
                                            <td className="px-3 py-2">
                                                <Badge variant={device.status === 'active' ? 'success' : device.status === 'revoked' ? 'danger' : 'neutral'}>
                                                    {device.status}
                                                </Badge>
                                            </td>
                                            <td className="px-3 py-2">
                                                {device.last_sync_at ? new Date(device.last_sync_at).toLocaleString() : 'Never'}
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex flex-wrap items-center gap-3">
                                                    <button
                                                        type="button"
                                                        disabled={busyId === device.id}
                                                        onClick={() => setEditTarget(device)}
                                                        className="text-sm font-medium text-slate-600 hover:text-slate-900 disabled:opacity-50"
                                                    >
                                                        Edit
                                                    </button>
                                                    {!device.is_local && device.status === 'active' && (
                                                        <button
                                                            type="button"
                                                            disabled={busyId === device.id}
                                                            onClick={() => setLocal(device)}
                                                            className="text-sm font-medium text-brand-700 hover:text-brand-900 disabled:opacity-50"
                                                        >
                                                            Set as this instance
                                                        </button>
                                                    )}
                                                    <button
                                                        type="button"
                                                        disabled={busyId === device.id || !device.owner}
                                                        onClick={() => setReissueTarget(device)}
                                                        className="text-sm font-medium text-gold-600 hover:text-gold-800 disabled:opacity-50"
                                                    >
                                                        Reissue Token
                                                    </button>
                                                    {device.status !== 'revoked' && (
                                                        <button
                                                            type="button"
                                                            disabled={busyId === device.id}
                                                            onClick={() => setRevokeTarget(device)}
                                                            className="text-sm font-medium text-red-600 hover:text-red-800 disabled:opacity-50"
                                                        >
                                                            Revoke
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <Pagination links={devices.links} from={devices.from} to={devices.to} total={devices.total} />
                </CardContent>
            </Card>

            <RegisterDeviceModal show={registerOpen} eligibleUsers={eligibleUsers} onClose={() => setRegisterOpen(false)} />
            <EditDeviceModal device={editTarget} onClose={() => setEditTarget(null)} />

            <Modal show={revokeTarget !== null} onClose={() => setRevokeTarget(null)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-medium text-slate-900">Revoke this device?</h2>
                    <p className="mt-2 text-sm text-slate-500">
                        <span className="font-medium">{revokeTarget?.name}</span>&apos;s token will stop working immediately —
                        it can no longer call the sync API. The device record and its sync history are kept.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setRevokeTarget(null)}>Cancel</SecondaryButton>
                        <DangerButton onClick={confirmRevoke}>Revoke</DangerButton>
                    </div>
                </div>
            </Modal>

            <Modal show={reissueTarget !== null} onClose={() => setReissueTarget(null)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-medium text-slate-900">Issue a new token?</h2>
                    <p className="mt-2 text-sm text-slate-500">
                        <span className="font-medium">{reissueTarget?.name}</span>&apos;s current token will stop working, and
                        a new one will be shown once. Use this if the old token was lost or compromised.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setReissueTarget(null)}>Cancel</SecondaryButton>
                        <PrimaryButton onClick={confirmReissue}>Issue New Token</PrimaryButton>
                    </div>
                </div>
            </Modal>

            <TokenRevealModal token={tokenModalToken} onClose={() => setTokenModalToken(null)} />
        </AppLayout>
    );
}

function RegisterDeviceModal({
    show,
    eligibleUsers,
    onClose,
}: {
    show: boolean;
    eligibleUsers: EligibleUser[];
    onClose: () => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        owner_user_id: '',
        role_hint: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('sync.devices.store'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="md">
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-medium text-slate-900">Register Device</h2>
                <p className="mt-1 text-sm text-slate-500">
                    Issues a Sanctum token scoped to the sync API only — it can never do more than the Admin it
                    authenticates as.
                </p>

                <div className="mt-4">
                    <InputLabel htmlFor="device-name" value="Device Name" />
                    <TextInput
                        id="device-name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="e.g. Admin PC (LAN Hub), Cloud Instance"
                        required
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="device-owner" value="Authenticates As" />
                    <select
                        id="device-owner"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={data.owner_user_id}
                        onChange={(e) => setData('owner_user_id', e.target.value)}
                        required
                    >
                        <option value="">Select an Admin…</option>
                        {eligibleUsers.map((u) => (
                            <option key={u.id} value={u.id}>
                                {u.name} ({u.email})
                            </option>
                        ))}
                    </select>
                    {eligibleUsers.length === 0 && (
                        <p className="mt-1 text-xs text-amber-700">No users with sync.manage found.</p>
                    )}
                    <InputError message={errors.owner_user_id} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="device-role-hint" value="Role Hint (optional)" />
                    <TextInput
                        id="device-role-hint"
                        className="mt-1 block w-full"
                        value={data.role_hint}
                        onChange={(e) => setData('role_hint', e.target.value)}
                        placeholder="e.g. lan-hub, cloud, admin-pc"
                    />
                    <InputError message={errors.role_hint} className="mt-2" />
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={onClose}>
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton disabled={processing}>Register Device</PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}

function EditDeviceModal({ device, onClose }: { device: Device | null; onClose: () => void }) {
    const { data, setData, put, processing, errors, reset } = useForm({
        name: device?.name ?? '',
        role_hint: device?.role_hint ?? '',
    });

    useEffect(() => {
        if (device) {
            setData({ name: device.name, role_hint: device.role_hint ?? '' });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [device]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!device) return;
        put(route('sync.devices.update', device.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Modal show={device !== null} onClose={onClose} maxWidth="md">
            <form onSubmit={submit} className="p-6">
                <h2 className="text-lg font-medium text-slate-900">Edit Device</h2>

                <div className="mt-4">
                    <InputLabel htmlFor="edit-device-name" value="Device Name" />
                    <TextInput
                        id="edit-device-name"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="edit-device-role-hint" value="Role Hint (optional)" />
                    <TextInput
                        id="edit-device-role-hint"
                        className="mt-1 block w-full"
                        value={data.role_hint}
                        onChange={(e) => setData('role_hint', e.target.value)}
                    />
                    <InputError message={errors.role_hint} className="mt-2" />
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={onClose}>
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton disabled={processing}>Save Changes</PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}

function TokenRevealModal({ token, onClose }: { token: string | null; onClose: () => void }) {
    const [copied, setCopied] = useState(false);

    async function copyToken() {
        if (!token) return;
        await navigator.clipboard.writeText(token);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    return (
        <Modal show={token !== null} onClose={onClose} maxWidth="lg">
            <div className="p-6">
                <h2 className="text-lg font-medium text-slate-900">Device Token</h2>
                <p className="mt-2 text-sm text-slate-500">
                    Copy this token now — it won&apos;t be shown again. Paste it into the other instance&apos;s
                    Sync Center as this remote&apos;s token.
                </p>
                <div className="mt-4 flex items-center gap-2">
                    <code className="flex-1 overflow-x-auto rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                        {token}
                    </code>
                    <SecondaryButton type="button" onClick={copyToken}>
                        {copied ? 'Copied!' : 'Copy'}
                    </SecondaryButton>
                </div>
                <div className="mt-6 flex justify-end">
                    <PrimaryButton type="button" onClick={onClose}>
                        Done
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}
