import { FormEventHandler } from 'react';
import { router, useForm } from '@inertiajs/react';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface OutputRow {
    id: number;
    title: string;
    type: string;
    description: string | null;
    output_date: string | null;
    reference_url: string | null;
    created_by: { id: number; name: string } | null;
    can_delete: boolean;
}

export default function OutputList({
    projectId,
    outputs,
    canManage,
}: {
    projectId: number;
    outputs: OutputRow[];
    canManage: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm<{
        title: string;
        type: string;
        output_date: string;
        reference_url: string;
    }>({
        title: '',
        type: '',
        output_date: '',
        reference_url: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('research-projects.outputs.store', projectId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="flex flex-col gap-4">
            {outputs.length === 0 ? (
                <p className="text-sm text-slate-500">No outputs recorded yet.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Title</th>
                                <th className="px-3 py-2">Type</th>
                                <th className="px-3 py-2">Date</th>
                                <th className="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {outputs.map((output) => (
                                <tr key={output.id}>
                                    <td className="px-3 py-2">
                                        {output.reference_url ? (
                                            <a
                                                href={output.reference_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="font-medium text-brand-700 hover:text-brand-900"
                                            >
                                                {output.title}
                                            </a>
                                        ) : (
                                            output.title
                                        )}
                                        {output.description && <div className="text-xs text-slate-400">{output.description}</div>}
                                    </td>
                                    <td className="px-3 py-2">{output.type}</td>
                                    <td className="px-3 py-2">{output.output_date ?? '—'}</td>
                                    <td className="px-3 py-2 text-right">
                                        {output.can_delete && (
                                            <SecondaryButton
                                                type="button"
                                                onClick={() =>
                                                    router.delete(route('research-projects.outputs.destroy', [projectId, output.id]), {
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
                            placeholder="Output title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                        />
                        <InputError message={errors.title} className="mt-1" />
                    </div>

                    <div>
                        <TextInput
                            className="mt-1 block w-full"
                            placeholder="Type (e.g. Journal Article)"
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                            required
                        />
                        <InputError message={errors.type} className="mt-1" />
                    </div>

                    <div className="flex items-start gap-2">
                        <TextInput
                            type="date"
                            className="mt-1 block w-full"
                            value={data.output_date}
                            onChange={(e) => setData('output_date', e.target.value)}
                        />
                        <PrimaryButton className="mt-1" disabled={processing}>
                            Add
                        </PrimaryButton>
                    </div>

                    <div className="sm:col-span-4">
                        <TextInput
                            className="mt-1 block w-full"
                            placeholder="Reference URL (optional)"
                            value={data.reference_url}
                            onChange={(e) => setData('reference_url', e.target.value)}
                        />
                        <InputError message={errors.reference_url} className="mt-1" />
                    </div>
                </form>
            )}
        </div>
    );
}
