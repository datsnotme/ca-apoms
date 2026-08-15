import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';

const LEVEL_LABELS: Record<string, string> = {
    bachelors: "Bachelor's",
    masters: "Master's",
    doctorate: 'Doctorate',
};

interface EducationRow {
    id: number;
    level: string;
    degree: string;
    field_of_study: string | null;
    institution: string;
    year_completed: number | null;
}

export default function EducationList({
    facultyId,
    education,
    canManage,
}: {
    facultyId: number;
    education: EducationRow[];
    canManage: boolean;
}) {
    const [showAdd, setShowAdd] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        level: 'bachelors',
        degree: '',
        field_of_study: '',
        institution: '',
        year_completed: '',
    });

    function submitAdd(e: React.FormEvent) {
        e.preventDefault();
        post(route('faculty-profiles.education.store', facultyId), {
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
                    {education.length} record{education.length === 1 ? '' : 's'}
                </p>
                {canManage && (
                    <PrimaryButton type="button" onClick={() => setShowAdd((v) => !v)}>
                        {showAdd ? 'Close' : 'Add Education'}
                    </PrimaryButton>
                )}
            </div>

            {showAdd && (
                <form onSubmit={submitAdd} className="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-4">
                        <div>
                            <InputLabel htmlFor="level" value="Level" />
                            <select
                                id="level"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.level}
                                onChange={(e) => setData('level', e.target.value)}
                            >
                                {Object.entries(LEVEL_LABELS).map(([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <InputLabel htmlFor="degree" value="Degree" />
                            <input
                                id="degree"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.degree}
                                onChange={(e) => setData('degree', e.target.value)}
                            />
                            <InputError message={errors.degree} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="field_of_study" value="Field of Study" />
                            <input
                                id="field_of_study"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.field_of_study}
                                onChange={(e) => setData('field_of_study', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="year_completed" value="Year Completed" />
                            <input
                                id="year_completed"
                                type="number"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.year_completed}
                                onChange={(e) => setData('year_completed', e.target.value)}
                            />
                            <InputError message={errors.year_completed} className="mt-1" />
                        </div>
                        <div className="sm:col-span-4">
                            <InputLabel htmlFor="institution" value="Institution" />
                            <input
                                id="institution"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.institution}
                                onChange={(e) => setData('institution', e.target.value)}
                            />
                            <InputError message={errors.institution} className="mt-1" />
                        </div>
                    </div>
                    <div className="mt-3">
                        <PrimaryButton disabled={processing || !data.degree || !data.institution}>Add</PrimaryButton>
                    </div>
                </form>
            )}

            {education.length === 0 ? (
                <p className="text-sm text-slate-900">No education records yet.</p>
            ) : (
                <div className="overflow-hidden rounded-md border border-slate-200">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                            <tr>
                                <th className="px-3 py-2">Level</th>
                                <th className="px-3 py-2">Degree</th>
                                <th className="px-3 py-2">Institution</th>
                                <th className="px-3 py-2">Year</th>
                                {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {education.map((e) => (
                                <tr key={e.id}>
                                    <td className="px-3 py-2">{LEVEL_LABELS[e.level] ?? e.level}</td>
                                    <td className="px-3 py-2">
                                        {e.degree}
                                        {e.field_of_study && <p className="text-xs text-slate-900">{e.field_of_study}</p>}
                                    </td>
                                    <td className="px-3 py-2">{e.institution}</td>
                                    <td className="px-3 py-2">{e.year_completed ?? '—'}</td>
                                    {canManage && (
                                        <td className="px-3 py-2 text-right">
                                            <ConfirmDeleteButton
                                                href={route('faculty-profiles.education.destroy', [facultyId, e.id])}
                                                itemLabel={e.degree}
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
