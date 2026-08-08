import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

interface Backup {
    filename: string;
    size: number;
    sizeHuman: string;
    createdAt: string;
}

export default function Index({ backups }: { backups: Backup[] }) {
    const [creating, setCreating] = useState(false);
    const [restoreTarget, setRestoreTarget] = useState<Backup | null>(null);
    const [restoring, setRestoring] = useState(false);

    function createBackup() {
        setCreating(true);
        router.post(route('backups.store'), {}, {
            preserveScroll: true,
            onFinish: () => setCreating(false),
        });
    }

    function confirmRestore() {
        if (!restoreTarget) return;

        setRestoring(true);
        router.post(route('backups.restore', restoreTarget.filename), {}, {
            preserveScroll: true,
            onFinish: () => {
                setRestoring(false);
                setRestoreTarget(null);
            },
        });
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Backup and Restore</h1>}>
            <Head title="Backup and Restore" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader
                        title="Database Backups"
                        description="Trigger a full database backup, download an existing one, or restore the system from one. Admin only."
                        actions={
                            <PrimaryButton onClick={createBackup} disabled={creating}>
                                {creating ? 'Creating Backup…' : 'Create Backup'}
                            </PrimaryButton>
                        }
                    />

                    <CardContent>
                        {backups.length === 0 ? (
                            <EmptyState title="No backups yet" description="Create the first backup to get started." />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                        <tr>
                                            <th className="px-3 py-2">Filename</th>
                                            <th className="px-3 py-2">Size</th>
                                            <th className="px-3 py-2">Created</th>
                                            <th className="px-3 py-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {backups.map((backup) => (
                                            <tr key={backup.filename}>
                                                <td className="px-3 py-2 font-mono text-xs">{backup.filename}</td>
                                                <td className="px-3 py-2">{backup.sizeHuman}</td>
                                                <td className="px-3 py-2">{backup.createdAt}</td>
                                                <td className="px-3 py-2">
                                                    <div className="flex items-center gap-4">
                                                        <a
                                                            href={route('backups.download', backup.filename)}
                                                            className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                        >
                                                            Download
                                                        </a>
                                                        <button
                                                            type="button"
                                                            onClick={() => setRestoreTarget(backup)}
                                                            className="text-sm font-medium text-red-600 hover:text-red-800"
                                                        >
                                                            Restore
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
            </div>

            <Modal show={restoreTarget !== null} onClose={() => setRestoreTarget(null)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-medium text-slate-900">Restore database from this backup?</h2>
                    <p className="mt-2 text-sm text-slate-500">
                        This replaces every row currently in the database with the contents of{' '}
                        <span className="font-mono">{restoreTarget?.filename}</span>. Any data recorded after this
                        backup was taken will be permanently lost. This cannot be undone.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setRestoreTarget(null)}>Cancel</SecondaryButton>
                        <DangerButton onClick={confirmRestore} disabled={restoring}>
                            {restoring ? 'Restoring…' : 'Restore Database'}
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
