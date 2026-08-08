import { FormEventHandler, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface CandidateSummary {
    id: number;
    status: string;
    recommended_by: { name: string } | null;
    recommended_at: string | null;
    recommendation_remarks: string | null;
    decided_by: { name: string } | null;
    decided_at: string | null;
    decision_remarks: string | null;
    graduated_at: string | null;
}

function RecommendForm({ candidateId }: { candidateId: number }) {
    const { data, setData, post, processing } = useForm({ remarks: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('graduation-candidates.recommendation.store', candidateId), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-2">
            <textarea
                className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                rows={2}
                placeholder="Remarks (optional)"
                value={data.remarks}
                onChange={(e) => setData('remarks', e.target.value)}
            />
            <div>
                <PrimaryButton disabled={processing}>Recommend for Graduation</PrimaryButton>
            </div>
        </form>
    );
}

function DecisionForm({ candidateId }: { candidateId: number }) {
    const [remarks, setRemarks] = useState('');
    const [pendingDecision, setPendingDecision] = useState<'approve' | 'reject' | null>(null);

    function submit(decision: 'approve' | 'reject') {
        setPendingDecision(decision);
        router.post(
            route('graduation-candidates.decision.store', candidateId),
            { decision, remarks: remarks || null },
            { preserveScroll: true, onFinish: () => setPendingDecision(null) },
        );
    }

    return (
        <div className="flex flex-col gap-2">
            <textarea
                className="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                rows={2}
                placeholder="Remarks (optional)"
                value={remarks}
                onChange={(e) => setRemarks(e.target.value)}
            />
            <div className="flex gap-3">
                <PrimaryButton type="button" disabled={pendingDecision !== null} onClick={() => submit('approve')}>
                    {pendingDecision === 'approve' ? 'Approving…' : 'Approve'}
                </PrimaryButton>
                <SecondaryButton type="button" disabled={pendingDecision !== null} onClick={() => submit('reject')}>
                    {pendingDecision === 'reject' ? 'Rejecting…' : 'Reject'}
                </SecondaryButton>
            </div>
        </div>
    );
}

function ConferButton({ candidateId }: { candidateId: number }) {
    const [processing, setProcessing] = useState(false);

    function confer() {
        setProcessing(true);
        router.post(
            route('graduation-candidates.confer.store', candidateId),
            {},
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    return (
        <PrimaryButton type="button" disabled={processing} onClick={confer}>
            {processing ? 'Confirming…' : 'Mark as Graduated'}
        </PrimaryButton>
    );
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    recommended: 'warning',
    approved: 'success',
    rejected: 'danger',
    graduated: 'success',
};

export default function RecommendationDecision({
    candidate,
    readyForRecommendation,
    canRecommend,
    canDecide,
    canConfer,
}: {
    candidate: CandidateSummary;
    readyForRecommendation: boolean;
    canRecommend: boolean;
    canDecide: boolean;
    canConfer: boolean;
}) {
    if (candidate.status === 'nominated') {
        return null;
    }

    const decisionLabel = candidate.status === 'rejected' ? 'rejected' : 'approved';

    return (
        <Card>
            <CardHeader
                title="Recommendation & Approval"
                description="A Department Head recommends a candidate once the checklist and evaluation are both complete; the Dean then approves or rejects."
            />
            <CardContent>
                <div className="flex flex-col gap-6">
                    {candidate.status === 'under_evaluation' && (
                        <div>
                            {canRecommend ? (
                                readyForRecommendation ? (
                                    <RecommendForm candidateId={candidate.id} />
                                ) : (
                                    <p className="text-sm text-slate-500">
                                        Waiting on the requirement checklist and/or competency evaluation to be completed before
                                        this candidate can be recommended.
                                    </p>
                                )
                            ) : (
                                <p className="text-sm text-slate-500">Awaiting department recommendation.</p>
                            )}
                        </div>
                    )}

                    {(candidate.recommended_by || candidate.status === 'recommended') && (
                        <div>
                            <h3 className="mb-1 text-sm font-semibold text-slate-800">Department Recommendation</h3>
                            {candidate.recommended_by ? (
                                <p className="text-sm text-slate-600">
                                    Recommended by {candidate.recommended_by.name} on {candidate.recommended_at?.slice(0, 10)}
                                    {candidate.recommendation_remarks && (
                                        <span className="block text-xs text-slate-400">"{candidate.recommendation_remarks}"</span>
                                    )}
                                </p>
                            ) : (
                                <Badge variant="warning">Pending</Badge>
                            )}
                        </div>
                    )}

                    {candidate.status === 'recommended' && (
                        <div>
                            {canDecide ? (
                                <DecisionForm candidateId={candidate.id} />
                            ) : (
                                <p className="text-sm text-slate-500">Awaiting the Dean's decision.</p>
                            )}
                        </div>
                    )}

                    {candidate.decided_by && (
                        <div>
                            <h3 className="mb-1 text-sm font-semibold text-slate-800">Dean's Decision</h3>
                            <p className="text-sm text-slate-600">
                                {/* Only an approved candidate can later move to "graduated", so the
                                    decision itself was always "approved" even once conferred. */}
                                <Badge variant={STATUS_VARIANT[decisionLabel] ?? 'neutral'}>{decisionLabel}</Badge>{' '}
                                by {candidate.decided_by.name} on {candidate.decided_at?.slice(0, 10)}
                                {candidate.decision_remarks && (
                                    <span className="block text-xs text-slate-400">"{candidate.decision_remarks}"</span>
                                )}
                            </p>
                        </div>
                    )}

                    {candidate.status === 'approved' && canConfer && <ConferButton candidateId={candidate.id} />}

                    {candidate.graduated_at && (
                        <div>
                            <h3 className="mb-1 text-sm font-semibold text-slate-800">Graduation Conferred</h3>
                            <p className="text-sm text-slate-600">
                                <Badge variant="success">graduated</Badge> on {candidate.graduated_at.slice(0, 10)}
                            </p>
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
