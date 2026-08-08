import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';

interface IndicatorRow {
    id: number;
    title: string;
    description: string | null;
    sort_order: number;
}

export default function IndicatorList({
    categoryId,
    indicators,
    canManage,
}: {
    categoryId: number;
    indicators: IndicatorRow[];
    canManage: boolean;
}) {
    const [showAdd, setShowAdd] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        sort_order: '0',
    });

    function submitAdd(e: React.FormEvent) {
        e.preventDefault();
        post(route('competency-categories.indicators.store', categoryId), {
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
                    {indicators.length} indicator{indicators.length === 1 ? '' : 's'}
                </p>
                {canManage && (
                    <PrimaryButton type="button" onClick={() => setShowAdd((v) => !v)}>
                        {showAdd ? 'Close' : 'Add Indicator'}
                    </PrimaryButton>
                )}
            </div>

            {showAdd && (
                <form onSubmit={submitAdd} className="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                        <div className="sm:col-span-3">
                            <InputLabel htmlFor="title" value="Indicator" />
                            <input
                                id="title"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                            />
                            <InputError message={errors.title} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="sort_order" value="Sort Order" />
                            <input
                                id="sort_order"
                                type="number"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.sort_order}
                                onChange={(e) => setData('sort_order', e.target.value)}
                            />
                        </div>
                        <div className="sm:col-span-4">
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
                        <PrimaryButton disabled={processing || !data.title}>Add to Category</PrimaryButton>
                    </div>
                </form>
            )}

            {indicators.length === 0 ? (
                <p className="text-sm text-slate-500">No indicators added to this category yet.</p>
            ) : (
                <div className="overflow-hidden rounded-md border border-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Indicator</th>
                                {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {indicators.map((i) => (
                                <tr key={i.id}>
                                    <td className="px-3 py-2">
                                        {i.title}
                                        {i.description && <p className="text-xs text-slate-400">{i.description}</p>}
                                    </td>
                                    {canManage && (
                                        <td className="px-3 py-2 text-right">
                                            <ConfirmDeleteButton
                                                href={route('competency-categories.indicators.destroy', [categoryId, i.id])}
                                                itemLabel={i.title}
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
