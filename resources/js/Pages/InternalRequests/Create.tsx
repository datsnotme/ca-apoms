import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        type: '',
        title: '',
        description: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('internal-requests.store'));
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Submit Request</h1>}>
            <Head title="Submit Request" />

            <Card>
                <CardHeader
                    title="New Internal Request"
                    description="Submitted to your Department Head (or an Admin) for review."
                />
                <CardContent>
                    <form onSubmit={submit} className="flex flex-col gap-4">
                        <div>
                            <InputLabel htmlFor="type" value="Type" />
                            <TextInput
                                id="type"
                                className="mt-1 block w-full"
                                placeholder="e.g. Leave, Resource, Equipment"
                                value={data.type}
                                onChange={(e) => setData('type', e.target.value)}
                                required
                            />
                            <InputError message={errors.type} className="mt-2" />
                        </div>

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

                        <div>
                            <InputLabel htmlFor="description" value="Description" />
                            <textarea
                                id="description"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                rows={5}
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                required
                            />
                            <InputError message={errors.description} className="mt-2" />
                        </div>

                        <div className="flex gap-3">
                            <PrimaryButton disabled={processing}>Submit Request</PrimaryButton>
                            <SecondaryButton type="button" onClick={() => (window.location.href = route('internal-requests.index'))}>
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
