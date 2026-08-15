import { Head, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import TextInput from '@/Components/TextInput';
import { Paginated } from '@/types';

interface StudentRow {
    id: number;
    student_number: string;
    name: string;
    department: { name: string } | null;
    program: { name: string } | null;
    year_level: { label: string } | null;
    adviser: { name: string } | null;
}

export default function EvaluationIndex({
    students,
    filters,
}: {
    students: Paginated<StudentRow>;
    filters: { search?: string };
}) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('students.evaluation.index'), { search }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Evaluate Student</h1>}>
            <Head title="Evaluate Student" />

            <Card>
                <CardHeader
                    title="Individual Student Evaluation"
                    description="Generate a per-student evaluation PDF — grades, curriculum checklist, and suggested standing."
                />

                <div className="border-b border-slate-200 px-5 py-3">
                    <form onSubmit={submitSearch} className="max-w-sm">
                        <TextInput
                            aria-label="Search students by number or name"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by number or name..."
                            className="w-full"
                        />
                    </form>
                </div>

                {students.data.length === 0 ? (
                    <EmptyState title="No students found" />
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
                                    <th scope="col" className="px-5 py-2.5">Adviser</th>
                                    <th scope="col" className="px-5 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {students.data.map((s) => (
                                    <tr key={s.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5 font-mono text-xs">{s.student_number}</td>
                                        <td className="px-5 py-2.5">{s.name}</td>
                                        <td className="px-5 py-2.5">{s.department?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.program?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.year_level?.label ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.adviser?.name ?? 'Unassigned'}</td>
                                        <td className="px-5 py-2.5 text-right">
                                            <a
                                                href={route('students.evaluation.show', s.id)}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                            >
                                                Download Evaluation
                                            </a>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={students.links} from={students.from} to={students.to} total={students.total} />
            </Card>
        </AppLayout>
    );
}
