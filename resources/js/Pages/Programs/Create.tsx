import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import ProgramForm from './Form';

export default function Create({ departments }: { departments: { id: number; name: string }[] }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Program</h1>}>
            <Head title="Add Program" />

            <Card>
                <CardHeader title="New Program" />
                <CardContent>
                    <ProgramForm
                        action={route('programs.store')}
                        method="post"
                        initialValues={{}}
                        departments={departments}
                        submitLabel="Create Program"
                        onCancelHref={route('programs.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
