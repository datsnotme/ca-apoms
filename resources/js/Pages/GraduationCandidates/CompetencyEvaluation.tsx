import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

interface Indicator {
    id: number;
    title: string;
    description: string | null;
}

interface Category {
    id: number;
    name: string;
    indicators: Indicator[];
}

interface EvaluatorRow {
    id: number;
    evaluator: { id: number; name: string };
    ratings: { competency_indicator_id: number; rating: number }[];
}

function RatingRow({
    candidateId,
    indicator,
    initial,
}: {
    candidateId: number;
    indicator: Indicator;
    initial: { rating?: number; remarks?: string | null };
}) {
    const [rating, setRating] = useState(initial.rating ? String(initial.rating) : '');
    const [remarks, setRemarks] = useState(initial.remarks ?? '');
    const [processing, setProcessing] = useState(false);
    const [saved, setSaved] = useState(false);

    function save() {
        if (!rating) return;
        setProcessing(true);
        setSaved(false);
        router.put(
            route('graduation-candidates.ratings.update', [candidateId, indicator.id]),
            { rating: Number(rating), remarks: remarks || null },
            { preserveScroll: true, onFinish: () => setProcessing(false), onSuccess: () => setSaved(true) },
        );
    }

    return (
        <tr>
            <td className="px-3 py-2">
                {indicator.title}
                {indicator.description && <p className="text-xs text-slate-400">{indicator.description}</p>}
            </td>
            <td className="px-3 py-2">
                <select
                    className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={rating}
                    onChange={(e) => {
                        setRating(e.target.value);
                        setSaved(false);
                    }}
                >
                    <option value="">Rate…</option>
                    {[1, 2, 3, 4, 5].map((v) => (
                        <option key={v} value={v}>
                            {v}
                        </option>
                    ))}
                </select>
            </td>
            <td className="px-3 py-2">
                <TextInput
                    value={remarks}
                    onChange={(e) => {
                        setRemarks(e.target.value);
                        setSaved(false);
                    }}
                    placeholder="Remarks (optional)"
                    className="w-48 text-xs"
                />
            </td>
            <td className="px-3 py-2 text-right">
                <button
                    type="button"
                    disabled={processing || !rating}
                    onClick={save}
                    className="text-xs font-medium text-brand-700 hover:text-brand-900 disabled:opacity-50"
                >
                    {saved ? 'Saved ✓' : 'Save'}
                </button>
            </td>
        </tr>
    );
}

export default function CompetencyEvaluation({
    candidateId,
    evaluators,
    evaluationComplete,
    competencyCategories,
    availableEvaluators,
    canManage,
    myAssignmentId,
    myRatings,
}: {
    candidateId: number;
    evaluators: EvaluatorRow[];
    evaluationComplete: boolean;
    competencyCategories: Category[];
    availableEvaluators: { id: number; name: string }[];
    canManage: boolean;
    myAssignmentId: number | null;
    myRatings: Record<number, { rating: number; remarks: string | null }>;
}) {
    const totalIndicators = competencyCategories.reduce((sum, c) => sum + c.indicators.length, 0);

    const { data, setData, post, processing, reset } = useForm({ evaluator_id: '' });

    function assign(e: React.FormEvent) {
        e.preventDefault();
        post(route('graduation-candidates.evaluators.store', candidateId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    }

    return (
        <Card>
            <CardHeader
                title="Competency Evaluation"
                description={
                    evaluationComplete
                        ? 'All assigned evaluators have completed their ratings.'
                        : 'Every assigned evaluator must rate all indicators before a department recommendation can be made.'
                }
            />
            <CardContent>
                <div className="flex flex-col gap-6">
                    {canManage && (
                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-slate-800">Assigned Evaluators</h3>
                            {evaluators.length === 0 ? (
                                <p className="text-sm text-slate-500">No evaluators assigned yet.</p>
                            ) : (
                                <ul className="mb-3 divide-y divide-slate-100 rounded-md border border-slate-200">
                                    {evaluators.map((ev) => (
                                        <li key={ev.id} className="flex items-center justify-between px-3 py-2 text-sm">
                                            <span>
                                                {ev.evaluator.name}{' '}
                                                <span className="text-xs text-slate-400">
                                                    ({ev.ratings.length}/{totalIndicators} rated)
                                                </span>
                                            </span>
                                            <ConfirmDeleteButton
                                                href={route('graduation-candidates.evaluators.destroy', [candidateId, ev.id])}
                                                itemLabel={ev.evaluator.name}
                                                label="Remove"
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                            {availableEvaluators.length > 0 ? (
                                <form onSubmit={assign} className="flex items-end gap-2">
                                    <div>
                                        <select
                                            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                            value={data.evaluator_id}
                                            onChange={(e) => setData('evaluator_id', e.target.value)}
                                        >
                                            <option value="">Select faculty evaluator…</option>
                                            {availableEvaluators.map((f) => (
                                                <option key={f.id} value={f.id}>
                                                    {f.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <PrimaryButton disabled={processing || !data.evaluator_id}>Assign Evaluator</PrimaryButton>
                                </form>
                            ) : (
                                <p className="text-xs text-slate-400">No more eligible faculty in this department to assign.</p>
                            )}
                        </div>
                    )}

                    {!canManage && evaluators.length > 0 && (
                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-slate-800">Assigned Evaluators</h3>
                            <ul className="divide-y divide-slate-100 rounded-md border border-slate-200">
                                {evaluators.map((ev) => (
                                    <li key={ev.id} className="flex items-center justify-between px-3 py-2 text-sm">
                                        <span>{ev.evaluator.name}</span>
                                        <Badge variant={ev.ratings.length >= totalIndicators ? 'success' : 'neutral'}>
                                            {ev.ratings.length}/{totalIndicators} rated
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {myAssignmentId && (
                        <div>
                            <h3 className="mb-2 text-sm font-semibold text-slate-800">Your Ratings</h3>
                            {competencyCategories.length === 0 ? (
                                <p className="text-sm text-slate-500">No competency indicators have been defined yet.</p>
                            ) : (
                                <div className="flex flex-col gap-4">
                                    {competencyCategories.map((cat) => (
                                        <div key={cat.id}>
                                            <p className="mb-1 text-xs font-semibold uppercase text-slate-500">{cat.name}</p>
                                            <div className="overflow-hidden rounded-md border border-slate-200">
                                                <table className="w-full text-sm">
                                                    <tbody className="divide-y divide-slate-100">
                                                        {cat.indicators.map((ind) => (
                                                            <RatingRow
                                                                key={ind.id}
                                                                candidateId={candidateId}
                                                                indicator={ind}
                                                                initial={myRatings[ind.id] ?? {}}
                                                            />
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
