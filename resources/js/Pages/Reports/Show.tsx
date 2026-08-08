import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import SecondaryButton from '@/Components/SecondaryButton';

interface SemesterOption {
    id: number;
    label: string;
}

interface DepartmentOption {
    id: number;
    name: string;
}

interface ShowProps {
    type: string;
    title: string;
    description: string;
    availableFilters: string[];
    filters: { semester_id: number | null; department_id: number | null };
    filterOptions: {
        semesters: SemesterOption[];
        departments: DepartmentOption[];
    };
    scopeDescription: string;
    headings: string[];
    rows: string[][];
}

export default function Show({
    type,
    title,
    description,
    availableFilters,
    filters,
    filterOptions,
    scopeDescription,
    headings,
    rows,
}: ShowProps) {
    function updateFilter(key: 'semester_id' | 'department_id', value: string) {
        router.get(
            route('reports.show', type),
            { ...filters, [key]: value || undefined },
            { preserveState: true, preserveScroll: true },
        );
    }

    const query: Record<string, string> = {};
    if (filters.semester_id) query.semester_id = String(filters.semester_id);
    if (filters.department_id) query.department_id = String(filters.department_id);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">{title}</h1>}>
            <Head title={title} />

            <div className="flex flex-col gap-6">
                <Link href={route('reports.index')} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                    ← Back to Reports
                </Link>

                <Card>
                    <CardHeader
                        title={title}
                        description={description}
                        actions={
                            <div className="flex items-center gap-3">
                                <a href={route('reports.pdf', { type, ...query })}>
                                    <SecondaryButton type="button">Download PDF</SecondaryButton>
                                </a>
                                <a href={route('reports.excel', { type, ...query })}>
                                    <SecondaryButton type="button">Download Excel</SecondaryButton>
                                </a>
                            </div>
                        }
                    />

                    <div className="flex flex-wrap items-center gap-4 border-b border-slate-200 px-5 py-3">
                        <span className="text-sm font-medium text-slate-600">{scopeDescription}</span>

                        {availableFilters.includes('semester_id') && (
                            <select
                                className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={filters.semester_id ?? ''}
                                onChange={(e) => updateFilter('semester_id', e.target.value)}
                            >
                                <option value="">Current Semester</option>
                                {filterOptions.semesters.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.label}
                                    </option>
                                ))}
                            </select>
                        )}

                        {filterOptions.departments.length > 0 && (
                            <select
                                className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={filters.department_id ?? ''}
                                onChange={(e) => updateFilter('department_id', e.target.value)}
                            >
                                <option value="">All Departments</option>
                                {filterOptions.departments.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                        )}
                    </div>

                    <CardContent>
                        {rows.length === 0 ? (
                            <EmptyState title="No data found for this selection" />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                        <tr>
                                            {headings.map((heading) => (
                                                <th key={heading} className="px-3 py-2">
                                                    {heading}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {rows.map((row, i) => (
                                            <tr key={i}>
                                                {row.map((cell, j) => (
                                                    <td key={j} className="px-3 py-2">
                                                        {cell}
                                                    </td>
                                                ))}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
