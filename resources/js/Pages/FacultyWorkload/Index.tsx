import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import SectionsTable, { SectionRow } from './SectionsTable';

interface WorkloadRow {
    id: number;
    name: string;
    department: string | null;
    section_count: number;
    total_units: number;
}

function SemesterSelect({ semesterId, semesters }: { semesterId: number | null; semesters: { id: number; label: string }[] }) {
    return (
        <select
            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
            value={semesterId ?? ''}
            onChange={(e) => router.get(route('faculty-workload.index'), { semester_id: e.target.value }, { preserveState: true })}
        >
            {semesters.map((s) => (
                <option key={s.id} value={s.id}>
                    {s.label}
                </option>
            ))}
        </select>
    );
}

export default function Index({
    mode,
    sections,
    totalUnits,
    workloads,
    semesters,
    filters,
}: {
    mode: 'own' | 'dashboard';
    sections?: SectionRow[];
    totalUnits?: number;
    workloads?: WorkloadRow[];
    semesters: { id: number; label: string }[];
    filters: { semester_id: number | null };
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Faculty Workload</h1>}>
            <Head title="Faculty Workload" />

            <Card>
                <CardHeader
                    title={mode === 'own' ? 'My Classes' : 'Faculty Workload'}
                    description={
                        mode === 'own'
                            ? 'Your assigned class sections for the selected semester.'
                            : 'Assigned class sections and teaching load per faculty member.'
                    }
                    actions={<SemesterSelect semesterId={filters.semester_id} semesters={semesters} />}
                />
                <CardContent>
                    {mode === 'own' ? (
                        <div className="flex flex-col gap-4">
                            <p className="text-sm text-slate-600">
                                Total units: <span className="font-semibold text-slate-900">{(totalUnits ?? 0).toFixed(2)}</span>
                            </p>
                            <SectionsTable sections={sections ?? []} />
                        </div>
                    ) : (workloads ?? []).length === 0 ? (
                        <EmptyState title="No faculty found" description="No faculty members are visible in your scope." />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                    <tr>
                                        <th className="px-5 py-2.5">Faculty</th>
                                        <th className="px-5 py-2.5">Department</th>
                                        <th className="px-5 py-2.5">Sections</th>
                                        <th className="px-5 py-2.5">Total Units</th>
                                        <th className="px-5 py-2.5 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {(workloads ?? []).map((w) => (
                                        <tr key={w.id} className="hover:bg-slate-50">
                                            <td className="px-5 py-2.5">{w.name}</td>
                                            <td className="px-5 py-2.5">{w.department ?? '—'}</td>
                                            <td className="px-5 py-2.5">{w.section_count}</td>
                                            <td className="px-5 py-2.5">{w.total_units.toFixed(2)}</td>
                                            <td className="px-5 py-2.5 text-right">
                                                <Link
                                                    href={route('faculty-workload.show', w.id)}
                                                    data={{ semester_id: filters.semester_id ?? '' }}
                                                    className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                >
                                                    View Classes
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
