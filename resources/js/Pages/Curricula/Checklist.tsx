import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';

const SEMESTER_LABELS: Record<string, string> = {
    FIRST: '1st Semester',
    SECOND: '2nd Semester',
    SUMMER: 'Summer',
};
const SEMESTER_ORDER: Record<string, number> = { FIRST: 0, SECOND: 1, SUMMER: 2 };

interface CurriculumCourseRow {
    id: number;
    year_level: number;
    semester: string;
    is_required: boolean;
    units: string;
    course: { id: number; code: string; title: string };
}

interface AvailableCourse {
    id: number;
    code: string;
    title: string;
    units: string;
}

export default function Checklist({
    curriculumId,
    curriculumCourses,
    availableCourses,
    canManage,
}: {
    curriculumId: number;
    curriculumCourses: CurriculumCourseRow[];
    availableCourses: AvailableCourse[];
    canManage: boolean;
}) {
    const [showAdd, setShowAdd] = useState(false);

    const groups = new Map<string, CurriculumCourseRow[]>();
    for (const cc of curriculumCourses) {
        const key = `${cc.year_level}-${cc.semester}`;
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key)!.push(cc);
    }
    const sortedGroups = [...groups.entries()].sort(([a], [b]) => {
        const [ay, as] = a.split('-');
        const [by, bs] = b.split('-');
        if (ay !== by) return Number(ay) - Number(by);
        return SEMESTER_ORDER[as] - SEMESTER_ORDER[bs];
    });

    const totalUnits = curriculumCourses.reduce((sum, cc) => sum + Number(cc.units), 0);

    const { data, setData, post, processing, errors, reset } = useForm({
        course_id: '',
        year_level: '1',
        semester: 'FIRST',
        is_required: true,
        units: '3',
        sequence_order: '0',
    });

    function submitAdd(e: React.FormEvent) {
        e.preventDefault();
        post(route('curricula.courses.store', curriculumId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setShowAdd(false);
            },
        });
    }

    return (
        <div className="flex flex-col gap-6">
            <div className="flex items-center justify-between">
                <p className="text-sm text-slate-600">
                    {curriculumCourses.length} course{curriculumCourses.length === 1 ? '' : 's'} &middot;{' '}
                    {totalUnits} total units
                </p>
                {canManage && availableCourses.length > 0 && (
                    <PrimaryButton type="button" onClick={() => setShowAdd((v) => !v)}>
                        {showAdd ? 'Close' : 'Add Course'}
                    </PrimaryButton>
                )}
            </div>

            {showAdd && (
                <form onSubmit={submitAdd} className="rounded-md border border-slate-200 bg-slate-50 p-4">
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <div className="sm:col-span-2">
                            <InputLabel htmlFor="course_id" value="Course" />
                            <select
                                id="course_id"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.course_id}
                                onChange={(e) => {
                                    const course = availableCourses.find((c) => String(c.id) === e.target.value);
                                    setData((prev) => ({
                                        ...prev,
                                        course_id: e.target.value,
                                        units: course?.units ?? prev.units,
                                    }));
                                }}
                            >
                                <option value="">Select course&hellip;</option>
                                {availableCourses.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.code} — {c.title}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.course_id} className="mt-1" />
                        </div>
                        <div>
                            <InputLabel htmlFor="year_level" value="Year" />
                            <input
                                id="year_level"
                                type="number"
                                min={1}
                                max={6}
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.year_level}
                                onChange={(e) => setData('year_level', e.target.value)}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="semester" value="Semester" />
                            <select
                                id="semester"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.semester}
                                onChange={(e) => setData('semester', e.target.value)}
                            >
                                <option value="FIRST">1st Sem</option>
                                <option value="SECOND">2nd Sem</option>
                                <option value="SUMMER">Summer</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel htmlFor="units" value="Units" />
                            <input
                                id="units"
                                type="number"
                                step="0.5"
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.units}
                                onChange={(e) => setData('units', e.target.value)}
                            />
                        </div>
                    </div>
                    <label className="mt-3 flex items-center gap-2 text-sm text-slate-700">
                        <Checkbox
                            checked={data.is_required}
                            onChange={(e) => setData('is_required', e.target.checked)}
                        />
                        Required course
                    </label>
                    <div className="mt-3">
                        <PrimaryButton disabled={processing || !data.course_id}>Add to Curriculum</PrimaryButton>
                    </div>
                </form>
            )}

            {sortedGroups.length === 0 && (
                <p className="text-sm text-slate-500">No courses added to this curriculum yet.</p>
            )}

            {sortedGroups.map(([key, group]) => {
                const [year, semester] = key.split('-');
                return (
                    <div key={key}>
                        <h3 className="mb-2 text-sm font-semibold text-slate-800">
                            Year {year} — {SEMESTER_LABELS[semester] ?? semester}
                        </h3>
                        <div className="overflow-hidden rounded-md border border-slate-200">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                    <tr>
                                        <th className="px-3 py-2">Code</th>
                                        <th className="px-3 py-2">Title</th>
                                        <th className="px-3 py-2">Units</th>
                                        <th className="px-3 py-2">Required</th>
                                        {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {group.map((cc) => (
                                        <tr key={cc.id}>
                                            <td className="px-3 py-2 font-mono text-xs">{cc.course.code}</td>
                                            <td className="px-3 py-2">{cc.course.title}</td>
                                            <td className="px-3 py-2">{cc.units}</td>
                                            <td className="px-3 py-2">{cc.is_required ? 'Required' : 'Elective'}</td>
                                            {canManage && (
                                                <td className="px-3 py-2 text-right">
                                                    <ConfirmDeleteButton
                                                        href={route('curricula.courses.destroy', [curriculumId, cc.id])}
                                                        itemLabel={cc.course.title}
                                                        label="Remove"
                                                    />
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
