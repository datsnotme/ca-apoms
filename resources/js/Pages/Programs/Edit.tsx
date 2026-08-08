import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import ProgramForm from './Form';

interface ProgramDetail {
    id: number;
    department_id: number;
    code: string;
    name: string;
    degree_type: string | null;
    major: string | null;
    required_total_units: number | null;
    duration_years: number | null;
    status: 'active' | 'inactive';
}

export default function Edit({
    program,
    departments,
}: {
    program: ProgramDetail;
    departments: { id: number; name: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Program</h1>}>
            <Head title={`Edit ${program.name}`} />

            <Card>
                <CardHeader title={program.name} />
                <CardContent>
                    <ProgramForm
                        action={route('programs.update', program.id)}
                        method="put"
                        initialValues={{
                            department_id: String(program.department_id),
                            code: program.code,
                            name: program.name,
                            degree_type: program.degree_type ?? '',
                            major: program.major ?? '',
                            required_total_units: program.required_total_units
                                ? String(program.required_total_units)
                                : '',
                            duration_years: program.duration_years ? String(program.duration_years) : '',
                            status: program.status,
                        }}
                        departments={departments}
                        submitLabel="Save Changes"
                        onCancelHref={route('programs.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
