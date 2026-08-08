import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import CurriculumForm from './Form';

export default function Create({
    programs,
    academicYears,
}: {
    programs: { id: number; name: string }[];
    academicYears: { id: number; start_year: number; end_year: number }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Curriculum</h1>}>
            <Head title="Add Curriculum" />

            <Card>
                <CardHeader title="New Curriculum" />
                <CardContent>
                    <CurriculumForm
                        action={route('curricula.store')}
                        method="post"
                        initialValues={{}}
                        programs={programs}
                        academicYears={academicYears}
                        submitLabel="Create Curriculum"
                        onCancelHref={route('curricula.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
