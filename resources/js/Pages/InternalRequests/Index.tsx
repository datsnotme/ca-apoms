import { FormEventHandler, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import Modal from '@/Components/Modal';
import { Paginated } from '@/types';

type Status = 'pending' | 'approved' | 'rejected' | 'cancelled';

interface RequestRow {
    id: number;
    type: string;
    title: string;
    description: string;
    status: Status;
    remarks: string | null;
    created_at: string;
    requester: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    reviewed_by: { id: number; name: string } | null;
    can_review: boolean;
    can_cancel: boolean;
}

const STATUS_VARIANT: Record<Status, 'neutral' | 'success' | 'danger'> = {
    pending: 'neutral',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'danger',
};

function ReviewControls({ request }: { request: RequestRow }) {
    const [remarks, setRemarks] = useState('');
    const [processing, setProcessing] = useState(false);

    function submit(decision: 'approved' | 'rejected') {
        setProcessing(true);
        router.patch(
            route('internal-requests.review', request.id),
            { decision, remarks: remarks || null },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    return (
        <div className="mt-2 flex flex-wrap items-center gap-2">
            <TextInput
                value={remarks}
                onChange={(e) => setRemarks(e.target.value)}
                placeholder="Remarks (optional)"
                className="w-48 text-xs"
            />
            <PrimaryButton type="button" disabled={processing} onClick={() => submit('approved')}>
                Approve
            </PrimaryButton>
            <SecondaryButton type="button" disabled={processing} onClick={() => submit('rejected')}>
                Reject
            </SecondaryButton>
        </div>
    );
}

export default function Index({
    requests,
    statuses,
    filters,
}: {
    requests: Paginated<RequestRow>;
    statuses: { value: string; label: string }[];
    filters: { status: string };
}) {
    const [showCreate, setShowCreate] = useState(false);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Internal Requests</h1>}>
            <Head title="Internal Requests" />

            <Card>
                <CardHeader
                    title="Internal Requests"
                    description="Requests submitted for department or college approval."
                    actions={<PrimaryButton onClick={() => setShowCreate(true)}>Submit Request</PrimaryButton>}
                />

                <div className="flex items-center gap-2 border-b border-slate-200 px-5 py-3">
                    <select
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                        value={filters.status}
                        onChange={(e) => router.get(route('internal-requests.index'), { status: e.target.value }, { preserveState: true })}
                    >
                        <option value="">All Statuses</option>
                        {statuses.map((s) => (
                            <option key={s.value} value={s.value}>
                                {s.label}
                            </option>
                        ))}
                    </select>
                </div>

                {requests.data.length === 0 ? (
                    <EmptyState title="No requests found" />
                ) : (
                    <div className="divide-y divide-slate-100">
                        {requests.data.map((req) => (
                            <div key={req.id} className="flex items-start justify-between gap-4 px-5 py-4">
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h3 className="text-sm font-semibold text-slate-900">{req.title}</h3>
                                        <Badge variant="info">{req.type}</Badge>
                                        <Badge variant={STATUS_VARIANT[req.status]}>{req.status}</Badge>
                                    </div>
                                    <p className="mt-1 whitespace-pre-wrap text-sm text-slate-600">{req.description}</p>
                                    <p className="mt-2 text-xs text-slate-900">
                                        {req.requester?.name ?? 'Unknown'} · {req.department?.name ?? '—'} · {req.created_at.slice(0, 10)}
                                    </p>
                                    {req.remarks && <p className="mt-1 text-xs text-slate-900">Remarks: {req.remarks}</p>}
                                    {req.reviewed_by && <p className="text-xs text-slate-900">Reviewed by {req.reviewed_by.name}</p>}
                                    {req.can_review && <ReviewControls request={req} />}
                                </div>
                                {req.can_cancel && (
                                    <div className="shrink-0">
                                        <SecondaryButton
                                            type="button"
                                            onClick={() =>
                                                router.patch(
                                                    route('internal-requests.cancel', req.id),
                                                    {},
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Cancel Request
                                        </SecondaryButton>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                <Pagination links={requests.links} from={requests.from} to={requests.to} total={requests.total} />
            </Card>

            <SubmitRequestModal show={showCreate} onClose={() => setShowCreate(false)} />
        </AppLayout>
    );
}

function SubmitRequestModal({ show, onClose }: { show: boolean; onClose: () => void }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        type: '',
        title: '',
        description: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('internal-requests.store'), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="lg" variant="form">
            <div className="p-6">
                <h2 className="text-lg font-medium text-slate-900">New Internal Request</h2>
                <p className="mt-1 text-sm text-slate-600">Submitted to your Department Head (or an Admin) for review.</p>
                <form onSubmit={submit} className="mt-4 flex flex-col gap-4">
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
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
