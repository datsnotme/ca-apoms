import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';

interface ProspectRow {
    student: {
        id: number;
        student_number: string;
        name: string;
        department: { name: string } | null;
        program: { name: string } | null;
        year_level: { label: string } | null;
    };
    gwa: number;
    completion_percentage: number;
    tier: 'summa_cum_laude' | 'magna_cum_laude' | 'cum_laude';
    tier_label: string;
}

const TIER_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    summa_cum_laude: 'success',
    magna_cum_laude: 'info',
    cum_laude: 'neutral',
};

export default function LatinHonors({
    prospects,
    minGwa,
    maxGwa,
}: {
    prospects: ProspectRow[];
    minGwa: number;
    maxGwa: number;
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Latin Honors Prospects</h1>}>
            <Head title="Latin Honors Prospects" />

            <Card>
                <CardHeader
                    title="Latin Honors Prospects"
                    description={`Active students with a complete curriculum, no unresolved deficiencies, and a GWA between ${minGwa.toFixed(2)} and ${maxGwa.toFixed(2)}, tiered as Summa Cum Laude (1.00–1.20), Magna Cum Laude (1.21–1.45), or Cum Laude (1.46–1.75). Computed live — nothing here is an award decision; a registrar or committee still confirms eligibility manually.`}
                />

                {prospects.length === 0 ? (
                    <EmptyState
                        title="No prospects right now"
                        description="No student currently meets the completion, deficiency, and GWA bar for Latin Honors."
                    />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    <th scope="col" className="px-5 py-2.5">Student No.</th>
                                    <th scope="col" className="px-5 py-2.5">Name</th>
                                    <th scope="col" className="px-5 py-2.5">Department</th>
                                    <th scope="col" className="px-5 py-2.5">Program</th>
                                    <th scope="col" className="px-5 py-2.5">Year Level</th>
                                    <th scope="col" className="px-5 py-2.5">GWA</th>
                                    <th scope="col" className="px-5 py-2.5">Tier</th>
                                    <th scope="col" className="px-5 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {prospects.map((p) => (
                                    <tr key={p.student.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5 font-mono text-xs">{p.student.student_number}</td>
                                        <td className="px-5 py-2.5">{p.student.name}</td>
                                        <td className="px-5 py-2.5">{p.student.department?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{p.student.program?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{p.student.year_level?.label ?? '—'}</td>
                                        <td className="px-5 py-2.5 font-mono">{p.gwa.toFixed(2)}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={TIER_VARIANT[p.tier] ?? 'neutral'}>{p.tier_label}</Badge>
                                        </td>
                                        <td className="px-5 py-2.5 text-right">
                                            <Link
                                                href={route('students.progress.show', p.student.id)}
                                                className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                            >
                                                View Progress
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
        </AppLayout>
    );
}
