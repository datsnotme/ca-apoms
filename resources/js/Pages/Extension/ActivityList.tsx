import { FormEventHandler } from 'react';
import { router, useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface ActivityRow {
    id: number;
    title: string;
    activity_type: string;
    description: string | null;
    activity_date: string | null;
    location: string | null;
    created_by: { id: number; name: string } | null;
    can_delete: boolean;
}

export default function ActivityList({
    projectId,
    activities,
    canManage,
}: {
    projectId: number;
    activities: ActivityRow[];
    canManage: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        title: string;
        activity_type: string;
        activity_date: string;
        location: string;
    }>({
        title: '',
        activity_type: '',
        activity_date: '',
        location: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('extension-projects.activities.store', projectId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="flex flex-col gap-4">
            {activities.length === 0 ? (
                <p className="text-sm text-slate-900">No activities recorded yet.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                            <tr>
                                <th className="px-3 py-2">Title</th>
                                <th className="px-3 py-2">Type</th>
                                <th className="px-3 py-2">Date</th>
                                <th className="px-3 py-2">Location</th>
                                <th className="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {activities.map((activity) => (
                                <tr key={activity.id}>
                                    <td className="px-3 py-2">
                                        {activity.title}
                                        {activity.description && <div className="text-xs text-slate-900">{activity.description}</div>}
                                    </td>
                                    <td className="px-3 py-2">{activity.activity_type}</td>
                                    <td className="px-3 py-2">{activity.activity_date ?? '—'}</td>
                                    <td className="px-3 py-2">{activity.location ?? '—'}</td>
                                    <td className="px-3 py-2 text-right">
                                        {activity.can_delete && (
                                            <SecondaryButton
                                                type="button"
                                                onClick={() =>
                                                    router.delete(route('extension-projects.activities.destroy', [projectId, activity.id]), {
                                                        preserveScroll: true,
                                                    })
                                                }
                                            >
                                                Remove
                                            </SecondaryButton>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {canManage && (
                <form onSubmit={submit} className="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-4">
                    <div className="sm:col-span-2">
                        <TextInput
                            className="mt-1 block w-full"
                            placeholder="Activity title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                        />
                        <InputError message={errors.title} className="mt-1" />
                    </div>

                    <div>
                        <TextInput
                            className="mt-1 block w-full"
                            placeholder="Type (e.g. Training Seminar)"
                            value={data.activity_type}
                            onChange={(e) => setData('activity_type', e.target.value)}
                            required
                        />
                        <InputError message={errors.activity_type} className="mt-1" />
                    </div>

                    <div className="flex items-start gap-2">
                        <TextInput
                            type="date"
                            className="mt-1 block w-full"
                            value={data.activity_date}
                            onChange={(e) => setData('activity_date', e.target.value)}
                        />
                        <PrimaryButton className="mt-1" disabled={processing}>
                            Add
                        </PrimaryButton>
                    </div>

                    <div className="sm:col-span-4">
                        <TextInput
                            className="mt-1 block w-full"
                            placeholder="Location (optional)"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                        />
                        <InputError message={errors.location} className="mt-1" />
                    </div>
                </form>
            )}
        </div>
    );
}
