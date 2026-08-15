import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';

interface CredentialRow {
    id: number;
    name: string;
    issuing_body: string | null;
    license_number: string | null;
    issued_date: string | null;
    expiry_date: string | null;
}

export default function CredentialList({
    facultyId,
    credentials,
    canManage,
}: {
    facultyId: number;
    credentials: CredentialRow[];
    canManage: boolean;
}) {
    const [showAdd, setShowAdd] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        issuing_body: '',
        license_number: '',
        issued_date: '',
        expiry_date: '',
    });

    function submitAdd(e: React.FormEvent) {
        e.preventDefault();
        post(route('faculty-profiles.credentials.store', facultyId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setShowAdd(false);
            },
        });
    }

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between">
                <p className="text-sm text-slate-600">
                    {credentials.length} credential{credentials.length === 1 ? '' : 's'}
                </p>
                {canManage && (
                    <PrimaryButton type="button" onClick={() => setShowAdd((v) => !v)}>
                        {showAdd ? 'Close' : 'Add Credential'}
                    </PrimaryButton>
                )}
            </div>

            {showAdd && (
                <form onSubmit={submitAdd} className="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                        <div className="sm:col-span-2">
                            <InputLabel htmlFor="name" value="Credential Name" />
                            <input
                                id="name"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                            />
                            <InputError message={errors.name} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="issuing_body" value="Issuing Body" />
                            <input
                                id="issuing_body"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.issuing_body}
                                onChange={(e) => setData('issuing_body', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="license_number" value="License No." />
                            <input
                                id="license_number"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.license_number}
                                onChange={(e) => setData('license_number', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="issued_date" value="Issued" />
                            <input
                                id="issued_date"
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.issued_date}
                                onChange={(e) => setData('issued_date', e.target.value)}
                            />
                            <InputError message={errors.issued_date} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="expiry_date" value="Expires" />
                            <input
                                id="expiry_date"
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.expiry_date}
                                onChange={(e) => setData('expiry_date', e.target.value)}
                            />
                            <InputError message={errors.expiry_date} className="mt-1" />
                        </div>
                    </div>
                    <div className="mt-3">
                        <PrimaryButton disabled={processing || !data.name}>Add</PrimaryButton>
                    </div>
                </form>
            )}

            {credentials.length === 0 ? (
                <p className="text-sm text-slate-900">No credentials on file yet.</p>
            ) : (
                <div className="overflow-hidden rounded-md border border-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                            <tr>
                                <th className="px-3 py-2">Credential</th>
                                <th className="px-3 py-2">Issued</th>
                                <th className="px-3 py-2">Expires</th>
                                {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {credentials.map((c) => (
                                <tr key={c.id}>
                                    <td className="px-3 py-2">
                                        {c.name}
                                        {(c.issuing_body || c.license_number) && (
                                            <p className="text-xs text-slate-900">
                                                {c.issuing_body}
                                                {c.issuing_body && c.license_number && ' · '}
                                                {c.license_number}
                                            </p>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">{c.issued_date?.slice(0, 10) ?? '—'}</td>
                                    <td className="px-3 py-2">{c.expiry_date?.slice(0, 10) ?? '—'}</td>
                                    {canManage && (
                                        <td className="px-3 py-2 text-right">
                                            <ConfirmDeleteButton
                                                href={route('faculty-profiles.credentials.destroy', [facultyId, c.id])}
                                                itemLabel={c.name}
                                                label="Remove"
                                            />
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
