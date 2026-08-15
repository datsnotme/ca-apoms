import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';

interface AwardRow {
    id: number;
    title: string;
    awarding_body: string | null;
    date_awarded: string | null;
    description: string | null;
}

export default function AwardList({
    facultyId,
    awards,
    canManage,
}: {
    facultyId: number;
    awards: AwardRow[];
    canManage: boolean;
}) {
    const [showAdd, setShowAdd] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        awarding_body: '',
        date_awarded: '',
        description: '',
    });

    function submitAdd(e: React.FormEvent) {
        e.preventDefault();
        post(route('faculty-profiles.awards.store', facultyId), {
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
                    {awards.length} award{awards.length === 1 ? '' : 's'}
                </p>
                {canManage && (
                    <PrimaryButton type="button" onClick={() => setShowAdd((v) => !v)}>
                        {showAdd ? 'Close' : 'Add Award'}
                    </PrimaryButton>
                )}
            </div>

            {showAdd && (
                <form onSubmit={submitAdd} className="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <InputLabel htmlFor="title" value="Title" />
                            <input
                                id="title"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                            />
                            <InputError message={errors.title} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="awarding_body" value="Awarding Body" />
                            <input
                                id="awarding_body"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.awarding_body}
                                onChange={(e) => setData('awarding_body', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="date_awarded" value="Date Awarded" />
                            <input
                                id="date_awarded"
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.date_awarded}
                                onChange={(e) => setData('date_awarded', e.target.value)}
                            />
                            <InputError message={errors.date_awarded} className="mt-1" />
                        </div>
                        <div className="sm:col-span-3">
                            <InputLabel htmlFor="description" value="Description (optional)" />
                            <textarea
                                id="description"
                                rows={2}
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="mt-3">
                        <PrimaryButton disabled={processing || !data.title}>Add</PrimaryButton>
                    </div>
                </form>
            )}

            {awards.length === 0 ? (
                <p className="text-sm text-slate-900">No awards recorded yet.</p>
            ) : (
                <div className="overflow-hidden rounded-md border border-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                            <tr>
                                <th className="px-3 py-2">Award</th>
                                <th className="px-3 py-2">Date</th>
                                {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {awards.map((a) => (
                                <tr key={a.id}>
                                    <td className="px-3 py-2">
                                        {a.title}
                                        {a.awarding_body && <p className="text-xs text-slate-900">{a.awarding_body}</p>}
                                        {a.description && <p className="text-xs text-slate-900">{a.description}</p>}
                                    </td>
                                    <td className="px-3 py-2">{a.date_awarded?.slice(0, 10) ?? '—'}</td>
                                    {canManage && (
                                        <td className="px-3 py-2 text-right">
                                            <ConfirmDeleteButton
                                                href={route('faculty-profiles.awards.destroy', [facultyId, a.id])}
                                                itemLabel={a.title}
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
