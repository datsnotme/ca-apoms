import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import TextInput from '@/Components/TextInput';
import CompetencyEvaluation from './CompetencyEvaluation';
import RecommendationDecision from './RecommendationDecision';

interface RequirementRow {
    id: number;
    status: 'pending' | 'satisfied' | 'waived';
    remarks: string | null;
    template: { id: number; title: string; description: string | null; is_required: boolean };
    satisfied_by: { name: string } | null;
    satisfied_at: string | null;
}

interface CandidateDetail {
    id: number;
    status: string;
    gwa_snapshot: string | null;
    completion_percentage_snapshot: string | null;
    deficiency_count_snapshot: number;
    nominated_at: string | null;
    student: {
        id: number;
        student_number: string;
        surname: string;
        first_name: string;
        middle_name: string | null;
        department: { name: string } | null;
        program: { name: string } | null;
        adviser: { name: string } | null;
    };
    academic_year: { start_year: number; end_year: number };
    semester: { term: string };
    nominated_by: { name: string } | null;
    recommended_by: { name: string } | null;
    recommended_at: string | null;
    recommendation_remarks: string | null;
    decided_by: { name: string } | null;
    decided_at: string | null;
    decision_remarks: string | null;
    graduated_at: string | null;
    requirements: RequirementRow[];
    competency_evaluators: {
        id: number;
        evaluator: { id: number; name: string };
        ratings: { competency_indicator_id: number; rating: number }[];
    }[];
}

const STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    nominated: 'neutral',
    under_evaluation: 'info',
    recommended: 'warning',
    approved: 'success',
    rejected: 'danger',
    graduated: 'success',
};

const REQ_STATUS_VARIANT: Record<string, 'success' | 'warning' | 'danger' | 'neutral' | 'info'> = {
    pending: 'neutral',
    satisfied: 'success',
    waived: 'info',
};

function RequirementRowControl({ candidateId, requirement }: { candidateId: number; requirement: RequirementRow }) {
    const [remarks, setRemarks] = useState(requirement.remarks ?? '');
    const [processing, setProcessing] = useState(false);

    function setStatus(status: string) {
        setProcessing(true);
        router.put(
            route('graduation-candidates.requirements.update', [candidateId, requirement.id]),
            { status, remarks: remarks || null },
            { preserveScroll: true, onFinish: () => setProcessing(false) },
        );
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            <TextInput
                value={remarks}
                onChange={(e) => setRemarks(e.target.value)}
                placeholder="Remarks (optional)"
                className="w-48 text-xs"
            />
            {requirement.status !== 'satisfied' && (
                <button
                    type="button"
                    disabled={processing}
                    onClick={() => setStatus('satisfied')}
                    className="text-xs font-medium text-brand-700 hover:text-brand-900"
                >
                    Satisfy
                </button>
            )}
            {requirement.status !== 'waived' && (
                <button
                    type="button"
                    disabled={processing}
                    onClick={() => setStatus('waived')}
                    className="text-xs font-medium text-slate-600 hover:text-slate-800"
                >
                    Waive
                </button>
            )}
            {requirement.status !== 'pending' && (
                <button
                    type="button"
                    disabled={processing}
                    onClick={() => setStatus('pending')}
                    className="text-xs font-medium text-red-600 hover:text-red-800"
                >
                    Reset
                </button>
            )}
        </div>
    );
}

interface CompetencyCategoryOption {
    id: number;
    name: string;
    indicators: { id: number; title: string; description: string | null }[];
}

export default function Show({
    candidate,
    canManage,
    evaluationComplete,
    readyForRecommendation,
    canRecommend,
    canDecide,
    canConfer,
    competencyCategories,
    availableEvaluators,
    myAssignmentId,
    myRatings,
}: {
    candidate: CandidateDetail;
    canManage: boolean;
    evaluationComplete: boolean;
    readyForRecommendation: boolean;
    canRecommend: boolean;
    canDecide: boolean;
    canConfer: boolean;
    competencyCategories: CompetencyCategoryOption[];
    availableEvaluators: { id: number; name: string }[];
    myAssignmentId: number | null;
    myRatings: Record<number, { rating: number; remarks: string | null }>;
}) {
    const requirementsComplete = candidate.requirements.every((r) => r.status !== 'pending');

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Graduating Evaluation</h1>}>
            <Head title={`Candidate — ${candidate.student.first_name} ${candidate.student.surname}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader
                        title={`${candidate.student.first_name} ${candidate.student.middle_name ?? ''} ${candidate.student.surname}`}
                        description={`${candidate.student.student_number} · ${candidate.student.program?.name ?? '—'} · ${candidate.academic_year.start_year}-${candidate.academic_year.end_year} ${candidate.semester.term}`}
                        actions={
                            <div className="flex items-center gap-4">
                                <Link
                                    href={route('students.progress.show', candidate.student.id)}
                                    className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                >
                                    View Progress
                                </Link>
                                <a
                                    href={route('graduation-candidates.report.show', candidate.id)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                >
                                    Download Report (PDF)
                                </a>
                                {canManage && (
                                    <ConfirmDeleteButton
                                        href={route('graduation-candidates.destroy', candidate.id)}
                                        itemLabel="this candidacy"
                                        label="Withdraw"
                                    />
                                )}
                            </div>
                        }
                    />
                    <div className="grid grid-cols-2 gap-4 px-5 py-4 text-center sm:grid-cols-4">
                        <div>
                            <p className="text-2xl font-semibold text-slate-900">
                                <Badge variant={STATUS_VARIANT[candidate.status] ?? 'neutral'}>{candidate.status.replace(/_/g, ' ')}</Badge>
                            </p>
                            <p className="text-xs uppercase text-slate-500">Status</p>
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-brand-700">{candidate.completion_percentage_snapshot ?? '—'}%</p>
                            <p className="text-xs uppercase text-slate-500">Completion (at nomination)</p>
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-slate-900">{candidate.gwa_snapshot ?? '—'}</p>
                            <p className="text-xs uppercase text-slate-500">GWA (at nomination)</p>
                        </div>
                        <div>
                            <p className="text-2xl font-semibold text-slate-900">{candidate.deficiency_count_snapshot}</p>
                            <p className="text-xs uppercase text-slate-500">Deficiencies (at nomination)</p>
                        </div>
                    </div>
                    <div className="border-t border-slate-200 px-5 py-3 text-sm text-slate-500">
                        Department: {candidate.student.department?.name ?? '—'} · Adviser: {candidate.student.adviser?.name ?? 'Unassigned'} ·
                        Nominated by {candidate.nominated_by?.name ?? '—'} on {candidate.nominated_at?.slice(0, 10) ?? '—'}
                    </div>
                </Card>

                <Card>
                    <CardHeader
                        title="Requirement Checklist"
                        description={
                            requirementsComplete
                                ? 'All requirements satisfied or waived.'
                                : 'Every requirement must be satisfied or waived before a department recommendation can be made.'
                        }
                    />
                    {candidate.requirements.length === 0 ? (
                        <CardContent>
                            <p className="text-sm text-slate-500">No requirement templates apply to this candidate's program.</p>
                        </CardContent>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                    <tr>
                                        <th className="px-5 py-2.5">Requirement</th>
                                        <th className="px-5 py-2.5">Status</th>
                                        {canManage && <th className="px-5 py-2.5">Actions</th>}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {candidate.requirements.map((r) => (
                                        <tr key={r.id}>
                                            <td className="px-5 py-2.5">
                                                {r.template.title}
                                                {r.template.description && <p className="text-xs text-slate-400">{r.template.description}</p>}
                                            </td>
                                            <td className="px-5 py-2.5">
                                                <Badge variant={REQ_STATUS_VARIANT[r.status]}>{r.status}</Badge>
                                                {r.satisfied_by && <div className="text-xs text-slate-400">by {r.satisfied_by.name}</div>}
                                            </td>
                                            {canManage && (
                                                <td className="px-5 py-2.5">
                                                    <RequirementRowControl candidateId={candidate.id} requirement={r} />
                                                </td>
                                            )}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>

                <CompetencyEvaluation
                    candidateId={candidate.id}
                    evaluators={candidate.competency_evaluators}
                    evaluationComplete={evaluationComplete}
                    competencyCategories={competencyCategories}
                    availableEvaluators={availableEvaluators}
                    canManage={canManage}
                    myAssignmentId={myAssignmentId}
                    myRatings={myRatings}
                />

                <RecommendationDecision
                    candidate={candidate}
                    readyForRecommendation={readyForRecommendation}
                    canRecommend={canRecommend}
                    canDecide={canDecide}
                    canConfer={canConfer}
                />
            </div>
        </AppLayout>
    );
}
