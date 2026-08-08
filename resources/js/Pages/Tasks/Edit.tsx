import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import TaskForm from './Form';

interface TaskDetail {
    id: number;
    title: string;
    description: string | null;
    assigned_to: number | null;
    due_date: string | null;
}

export default function Edit({ task, assigneeOptions }: { task: TaskDetail; assigneeOptions: { id: number; name: string }[] }) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Task</h1>}>
            <Head title="Edit Task" />

            <Card>
                <CardHeader title={task.title} />
                <CardContent>
                    <TaskForm
                        action={route('tasks.update', task.id)}
                        method="put"
                        initialValues={{
                            title: task.title,
                            description: task.description ?? '',
                            assigned_to: task.assigned_to ? String(task.assigned_to) : '',
                            due_date: task.due_date ?? '',
                        }}
                        assigneeOptions={assigneeOptions}
                        submitLabel="Save Changes"
                        onCancelHref={route('tasks.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
