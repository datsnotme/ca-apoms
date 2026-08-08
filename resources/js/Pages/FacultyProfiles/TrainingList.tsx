import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';

interface TrainingRow {
    id: number;
    title: string;
    provider: string | null;
    training_type: string | null;
    start_date: string | null;
    end_date: string | null;
    hours: number | null;
}

export default function TrainingList({
    facultyId,
    trainings,
    canManage,
}: {
    facultyId: number;
    trainings: TrainingRow[];
    canManage: boolean;
}) {
    const [showAdd, setShowAdd] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        provider: '',
        training_type: '',
        start_date: '',
        end_date: '',
        hours: '',
    });

    function submitAdd(e: React.FormEvent) {
        e.preventDefault();
        post(route('faculty-profiles.trainings.store', facultyId), {
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
                    {trainings.length} training{trainings.length === 1 ? '' : 's'}
                </p>
                {canManage && (
                    <PrimaryButton type="button" onClick={() => setShowAdd((v) => !v)}>
                        {showAdd ? 'Close' : 'Add Training'}
                    </PrimaryButton>
                )}
            </div>

            {showAdd && (
                <form onSubmit={submitAdd} className="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                        <div className="sm:col-span-2">
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
                            <InputLabel htmlFor="provider" value="Provider" />
                            <input
                                id="provider"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.provider}
                                onChange={(e) => setData('provider', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="training_type" value="Type" />
                            <input
                                id="training_type"
                                placeholder="Seminar, workshop..."
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.training_type}
                                onChange={(e) => setData('training_type', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="start_date" value="Start Date" />
                            <input
                                id="start_date"
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.start_date}
                                onChange={(e) => setData('start_date', e.target.value)}
                            />
                            <InputError message={errors.start_date} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="end_date" value="End Date" />
                            <input
                                id="end_date"
                                type="date"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.end_date}
                                onChange={(e) => setData('end_date', e.target.value)}
                            />
                            <InputError message={errors.end_date} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="hours" value="Hours" />
                            <input
                                id="hours"
                                type="number"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.hours}
                                onChange={(e) => setData('hours', e.target.value)}
                            />
                            <InputError message={errors.hours} className="mt-1" />
                        </div>
                    </div>
                    <div className="mt-3">
                        <PrimaryButton disabled={processing || !data.title}>Add</PrimaryButton>
                    </div>
                </form>
            )}

            {trainings.length === 0 ? (
                <p className="text-sm text-slate-500">No trainings recorded yet.</p>
            ) : (
                <div className="overflow-hidden rounded-md border border-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Title</th>
                                <th className="px-3 py-2">Dates</th>
                                <th className="px-3 py-2">Hours</th>
                                {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {trainings.map((t) => (
                                <tr key={t.id}>
                                    <td className="px-3 py-2">
                                        {t.title}
                                        {(t.provider || t.training_type) && (
                                            <p className="text-xs text-slate-400">
                                                {t.training_type}
                                                {t.training_type && t.provider && ' · '}
                                                {t.provider}
                                            </p>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        {t.start_date?.slice(0, 10) ?? '—'}
                                        {t.end_date && t.end_date !== t.start_date ? ` – ${t.end_date.slice(0, 10)}` : ''}
                                    </td>
                                    <td className="px-3 py-2">{t.hours ?? '—'}</td>
                                    {canManage && (
                                        <td className="px-3 py-2 text-right">
                                            <ConfirmDeleteButton
                                                href={route('faculty-profiles.trainings.destroy', [facultyId, t.id])}
                                                itemLabel={t.title}
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
