import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';

interface ReportTypeOption {
    value: string;
    label: string;
    description: string;
}

export default function Index({ reportTypes }: { reportTypes: ReportTypeOption[] }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Reports</h1>}>
            <Head title="Reports" />

            <Card>
                <CardHeader
                    title="Authorized College Reports"
                    description="Printable and exportable summaries drawn from every module in the system."
                />

                <div className="divide-y divide-slate-100">
                    {reportTypes.map((type) => (
                        <Link
                            key={type.value}
                            href={route('reports.show', type.value)}
                            className="flex flex-col gap-1 px-5 py-4 hover:bg-slate-50"
                        >
                            <h3 className="text-sm font-semibold text-slate-900">{type.label}</h3>
                            <p className="text-sm text-slate-600">{type.description}</p>
                        </Link>
                    ))}
                </div>
            </Card>
        </AppLayout>
    );
}
