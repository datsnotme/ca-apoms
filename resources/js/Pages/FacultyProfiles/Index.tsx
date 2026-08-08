import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import TextInput from '@/Components/TextInput';
import { Paginated } from '@/types';

const EMPLOYMENT_LABELS: Record<string, string> = {
    full_time: 'Full-Time',
    part_time: 'Part-Time',
    visiting: 'Visiting',
    on_leave: 'On Leave',
};

interface FacultyRow {
    id: number;
    name: string;
    employee_number: string;
    department: { name: string } | null;
    faculty_profile: {
        academic_rank: string | null;
        employment_status: string;
        specialization: string | null;
    } | null;
}

export default function Index({
    faculty,
    filters,
}: {
    faculty: Paginated<FacultyRow>;
    filters: { search?: string };
}) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('faculty-profiles.index'), { search }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Faculty Profiles</h1>}>
            <Head title="Faculty Profiles" />

            <Card>
                <CardHeader title="Faculty" description="Academic rank, employment status, and specialization for each faculty member." />

                <div className="border-b border-slate-200 px-5 py-3">
                    <form onSubmit={submitSearch} className="max-w-sm">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by name or employee no..."
                            className="w-full"
                        />
                    </form>
                </div>

                {faculty.data.length === 0 ? (
                    <EmptyState title="No faculty found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-5 py-2.5">Employee No.</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Department</th>
                                    <th className="px-5 py-2.5">Rank</th>
                                    <th className="px-5 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {faculty.data.map((f) => (
                                    <tr key={f.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5 font-mono text-xs">{f.employee_number}</td>
                                        <td className="px-5 py-2.5">
                                            <Link href={route('faculty-profiles.show', f.id)} className="font-medium text-brand-700 hover:text-brand-900">
                                                {f.name}
                                            </Link>
                                        </td>
                                        <td className="px-5 py-2.5">{f.department?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{f.faculty_profile?.academic_rank ?? '—'}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant="neutral">
                                                {EMPLOYMENT_LABELS[f.faculty_profile?.employment_status ?? 'full_time']}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={faculty.links} from={faculty.from} to={faculty.to} total={faculty.total} />
            </Card>
        </AppLayout>
    );
}
