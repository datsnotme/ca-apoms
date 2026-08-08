import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import TaskForm from './Form';

export default function Create({ assigneeOptions }: { assigneeOptions: { id: number; name: string }[] }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Add Task</h1>}>
            <Head title="Add Task" />

            <Card>
                <CardHeader title="New Task" />
                <CardContent>
                    <TaskForm
                        action={route('tasks.store')}
                        method="post"
                        initialValues={{}}
                        assigneeOptions={assigneeOptions}
                        submitLabel="Add Task"
                        onCancelHref={route('tasks.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
