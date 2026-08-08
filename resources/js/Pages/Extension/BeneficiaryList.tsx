import { FormEventHandler } from 'react';
import { router, useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface BeneficiaryRow {
    id: number;
    beneficiary_name: string;
    beneficiary_type: string;
    count: number | null;
    location: string | null;
    notes: string | null;
    created_by: { id: number; name: string } | null;
    can_delete: boolean;
}

export default function BeneficiaryList({
    projectId,
    beneficiaries,
    canManage,
}: {
    projectId: number;
    beneficiaries: BeneficiaryRow[];
    canManage: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        beneficiary_name: string;
        beneficiary_type: string;
        count: string;
        location: string;
    }>({
        beneficiary_name: '',
        beneficiary_type: '',
        count: '',
        location: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('extension-projects.beneficiaries.store', projectId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="flex flex-col gap-4">
            {beneficiaries.length === 0 ? (
                <p className="text-sm text-slate-500">No beneficiaries recorded yet.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Name</th>
                                <th className="px-3 py-2">Type</th>
                                <th className="px-3 py-2">Count</th>
                                <th className="px-3 py-2">Location</th>
                                <th className="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {beneficiaries.map((beneficiary) => (
                                <tr key={beneficiary.id}>
                                    <td className="px-3 py-2">
                                        {beneficiary.beneficiary_name}
                                        {beneficiary.notes && <div className="text-xs text-slate-400">{beneficiary.notes}</div>}
                                    </td>
                                    <td className="px-3 py-2">{beneficiary.beneficiary_type}</td>
                                    <td className="px-3 py-2">{beneficiary.count ?? '—'}</td>
                                    <td className="px-3 py-2">{beneficiary.location ?? '—'}</td>
                                    <td className="px-3 py-2 text-right">
                                        {beneficiary.can_delete && (
                                            <SecondaryButton
                                                type="button"
                                                onClick={() =>
                                                    router.delete(
                                                        route('extension-projects.beneficiaries.destroy', [projectId, beneficiary.id]),
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                Remove
                                            </SecondaryButton>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {canManage && (
                <form onSubmit={submit} className="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-4">
                    <div className="sm:col-span-2">
                        <TextInput
                            className="mt-1 block w-full"
                            placeholder="Beneficiary name"
                            value={data.beneficiary_name}
                            onChange={(e) => setData('beneficiary_name', e.target.value)}
                            required
                        />
                        <InputError message={errors.beneficiary_name} className="mt-1" />
                    </div>

                    <div>
                        <TextInput
                            className="mt-1 block w-full"
                            placeholder="Type (e.g. Farmer Cooperative)"
                            value={data.beneficiary_type}
                            onChange={(e) => setData('beneficiary_type', e.target.value)}
                            required
                        />
                        <InputError message={errors.beneficiary_type} className="mt-1" />
                    </div>

                    <div className="flex items-start gap-2">
                        <TextInput
                            type="number"
                            min={0}
                            className="mt-1 block w-full"
                            placeholder="Count"
                            value={data.count}
                            onChange={(e) => setData('count', e.target.value)}
                        />
                        <PrimaryButton className="mt-1" disabled={processing}>
                            Add
                        </PrimaryButton>
                    </div>

                    <div className="sm:col-span-4">
                        <TextInput
                            className="mt-1 block w-full"
                            placeholder="Location (optional)"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                        />
                        <InputError message={errors.location} className="mt-1" />
                    </div>
                </form>
            )}
        </div>
    );
}
