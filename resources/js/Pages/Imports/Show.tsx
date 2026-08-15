import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';

interface ErrorRow {
    id: number;
    row_number: number;
    error_message: string;
    raw_data: Record<string, unknown>;
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    processing: 'warning',
    completed: 'success',
    failed: 'danger',
};

export default function Show({
    batch,
    errors,
}: {
    batch: {
        id: number;
        type: string;
        file_name: string;
        status: 'processing' | 'completed' | 'failed';
        total_rows: number;
        success_rows: number;
        error_rows: number;
        uploaded_by: { name: string } | null;
        created_at: string;
    };
    errors: ErrorRow[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Import Result</h1>}>
            <Head title={`Import — ${batch.file_name}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader
                        title={batch.file_name}
                        description={`${batch.type} · uploaded by ${batch.uploaded_by?.name ?? 'unknown'}`}
                        actions={<Badge variant={STATUS_VARIANT[batch.status]}>{batch.status}</Badge>}
                    />
                    <div className="grid grid-cols-3 gap-4 px-5 py-4 text-center">
                        <div>
                            <p className="text-2xl font-semibold text-slate-900">{batch.total_rows}</p>
                            <p className="text-xs uppercase text-slate-900">Total Rows</p>
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-brand-700">{batch.success_rows}</p>
                            <p className="text-xs uppercase text-slate-900">Imported</p>
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-red-600">{batch.error_rows}</p>
                            <p className="text-xs uppercase text-slate-900">Errors</p>
                        </div>
                    </div>
                </Card>

                <Card>
                    <CardHeader
                        title="Row Errors"
                        actions={
                            errors.length > 0 ? (
                                <a
                                    href={route('imports.errors', batch.id)}
                                    className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                >
                                    Download Error Report
                                </a>
                            ) : undefined
                        }
                    />
                    {errors.length === 0 ? (
                        <EmptyState title="No errors — every row imported successfully" />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                    <tr>
                                        <th className="px-5 py-2.5">Row</th>
                                        <th className="px-5 py-2.5">Error</th>
                                        <th className="px-5 py-2.5">Raw Data</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {errors.map((e) => (
                                        <tr key={e.id}>
                                            <td className="px-5 py-2.5">{e.row_number}</td>
                                            <td className="px-5 py-2.5 text-red-600">{e.error_message}</td>
                                            <td className="px-5 py-2.5 font-mono text-xs text-slate-900">
                                                {JSON.stringify(e.raw_data)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}
