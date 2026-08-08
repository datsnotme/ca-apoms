import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface AnnouncementFormValues {
    title: string;
    body: string;
    department_id: string;
}

export default function AnnouncementForm({
    action,
    method,
    initialValues,
    departments,
    isAdmin,
    submitLabel,
    onCancelHref,
}: {
    action: string;
    method: 'post' | 'put';
    initialValues: Partial<AnnouncementFormValues>;
    departments: { id: number; name: string }[];
    isAdmin: boolean;
    submitLabel: string;
    onCancelHref: string;
}) {
    const { data, setData, post, put, processing, errors } = useForm<AnnouncementFormValues>({
        title: initialValues.title ?? '',
        body: initialValues.body ?? '',
        department_id: initialValues.department_id ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const submitFn = method === 'post' ? post : put;
        submitFn(action);
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-4">
            <div>
                <InputLabel htmlFor="title" value="Title" />
                <TextInput
                    id="title"
                    className="mt-1 block w-full"
                    value={data.title}
                    onChange={(e) => setData('title', e.target.value)}
                    required
                />
                <InputError message={errors.title} className="mt-2" />
            </div>

            {isAdmin ? (
                <div>
                    <InputLabel htmlFor="department_id" value="Audience" />
                    <select
                        id="department_id"
                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={data.department_id}
                        onChange={(e) => setData('department_id', e.target.value)}
                    >
                        <option value="">Entire College</option>
                        {departments.map((d) => (
                            <option key={d.id} value={d.id}>
                                {d.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.department_id} className="mt-2" />
                </div>
            ) : (
                <p className="text-sm text-slate-500">This will be posted to your own department only.</p>
            )}

            <div>
                <InputLabel htmlFor="body" value="Message" />
                <textarea
                    id="body"
                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    rows={6}
                    value={data.body}
                    onChange={(e) => setData('body', e.target.value)}
                    required
                />
                <InputError message={errors.body} className="mt-2" />
            </div>

            <div className="flex gap-3">
                <PrimaryButton disabled={processing}>{submitLabel}</PrimaryButton>
                <SecondaryButton type="button" onClick={() => (window.location.href = onCancelHref)}>
                    Cancel
                </SecondaryButton>
            </div>
        </form>
    );
}
