import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';

interface OutstandingRow {
    id: number;
    borrowed_at: string;
    expected_return_at: string | null;
    purpose: string | null;
    is_overdue: boolean;
    equipment: {
        id: number;
        name: string;
        type: string;
        department: { name: string } | null;
    };
    borrowed_by: { id: number; name: string } | null;
    recorded_by: { id: number; name: string } | null;
}

export default function Accountability({ outstanding }: { outstanding: OutstandingRow[] }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Equipment Accountability</h1>}>
            <Head title="Equipment Accountability" />

            <Card>
                <CardHeader
                    title="Outstanding Borrowings"
                    description="Every equipment item currently checked out and who is accountable for it. Computed live from borrowing records — nothing here is a separately maintained list."
                />

                {outstanding.length === 0 ? (
                    <EmptyState title="Nothing outstanding — all equipment has been returned" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-5 py-2.5">Equipment</th>
                                    <th className="px-5 py-2.5">Accountable To</th>
                                    <th className="px-5 py-2.5">Borrowed</th>
                                    <th className="px-5 py-2.5">Expected Return</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {outstanding.map((row) => (
                                    <tr key={row.id}>
                                        <td className="px-5 py-2.5">
                                            <Link
                                                href={route('equipment.show', row.equipment.id)}
                                                className="font-medium text-brand-700 hover:text-brand-900"
                                            >
                                                {row.equipment.name}
                                            </Link>
                                            <div className="text-xs text-slate-400">
                                                {row.equipment.type} · {row.equipment.department?.name ?? 'Shared / College-wide'}
                                            </div>
                                        </td>
                                        <td className="px-5 py-2.5">{row.borrowed_by?.name ?? 'Unknown'}</td>
                                        <td className="px-5 py-2.5">{row.borrowed_at.slice(0, 10)}</td>
                                        <td className="px-5 py-2.5">
                                            {row.expected_return_at ? row.expected_return_at.slice(0, 10) : '—'}
                                            {row.is_overdue && (
                                                <span className="ml-2">
                                                    <Badge variant="danger">Overdue</Badge>
                                                </span>
                                            )}
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
