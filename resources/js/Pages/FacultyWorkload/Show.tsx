import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import SectionsTable, { SectionRow } from './SectionsTable';

function SemesterSelect({
    facultyId,
    semesterId,
    semesters,
}: {
    facultyId: number;
    semesterId: number | null;
    semesters: { id: number; label: string }[];
}) {
    return (
        <select
            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
            value={semesterId ?? ''}
            onChange={(e) => router.get(route('faculty-workload.show', facultyId), { semester_id: e.target.value }, { preserveState: true })}
        >
            {semesters.map((s) => (
                <option key={s.id} value={s.id}>
                    {s.label}
                </option>
            ))}
        </select>
    );
}

export default function Show({
    faculty,
    sections,
    totalUnits,
    semesters,
    filters,
}: {
    faculty: { id: number; name: string };
    sections: SectionRow[];
    totalUnits: number;
    semesters: { id: number; label: string }[];
    filters: { semester_id: number | null };
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Faculty Workload</h1>}>
            <Head title={`Faculty Workload — ${faculty.name}`} />

            <div className="flex flex-col gap-6">
                <Link href={route('faculty-workload.index')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                    ← Back to Faculty Workload
                </Link>

                <Card>
                    <CardHeader
                        title={faculty.name}
                        description="Assigned class sections for the selected semester."
                        actions={<SemesterSelect facultyId={faculty.id} semesterId={filters.semester_id} semesters={semesters} />}
                    />
                    <CardContent>
                        <div className="flex flex-col gap-4">
                            <p className="text-sm text-slate-600">
                                Total units: <span className="font-semibold text-slate-900">{totalUnits.toFixed(2)}</span>
                            </p>
                            <SectionsTable sections={sections} />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
