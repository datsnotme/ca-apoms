import { FormEventHandler, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Badge from '@/Components/ui/Badge';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface AdvisingRecordRow {
    id: number;
    session_date: string;
    summary: string;
    recommendations: string | null;
    follow_up_required: boolean;
    adviser: { name: string } | null;
    semester: { term: string; academic_year: { start_year: number; end_year: number } } | null;
}

export default function AdvisingRecords({
    studentId,
    records,
    semesters,
    canManage,
}: {
    studentId: number;
    records: AdvisingRecordRow[];
    semesters: { id: number; label: string }[];
    canManage: boolean;
}) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<{
        semester_id: string;
        session_date: string;
        summary: string;
        recommendations: string;
        follow_up_required: boolean;
    }>({
        semester_id: semesters[0]?.id ? String(semesters[0].id) : '',
        session_date: new Date().toISOString().slice(0, 10),
        summary: '',
        recommendations: '',
        follow_up_required: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('students.advising-records.store', studentId), {
            preserveScroll: true,
            onSuccess: () => {
                reset('summary', 'recommendations', 'follow_up_required');
                setShowForm(false);
            },
        });
    };

    return (
        <div className="flex flex-col gap-4">
            {records.length === 0 ? (
                <p className="text-sm text-slate-500">No advising sessions recorded yet.</p>
            ) : (
                <div className="flex flex-col gap-3">
                    {records.map((r) => (
                        <div key={r.id} className="rounded-md border border-slate-200 p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <p className="text-sm font-medium text-slate-900">
                                    {r.session_date.slice(0, 10)} · {r.adviser?.name ?? 'Unknown'}
                                    {r.semester && (
                                        <span className="ml-2 text-xs text-slate-400">
                                            {r.semester.academic_year.start_year}-{r.semester.academic_year.end_year} {r.semester.term}
                                        </span>
                                    )}
                                </p>
                                {r.follow_up_required && <Badge variant="warning">Follow-up required</Badge>}
                            </div>
                            <p className="mt-2 text-sm text-slate-700">{r.summary}</p>
                            {r.recommendations && (
                                <p className="mt-1 text-sm text-slate-500">
                                    <span className="font-medium">Recommendations:</span> {r.recommendations}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {canManage && (
                <>
                    {!showForm ? (
                        <PrimaryButton type="button" className="self-start" onClick={() => setShowForm(true)}>
                            Log Advising Session
                        </PrimaryButton>
                    ) : (
                        <form onSubmit={submit} className="flex flex-col gap-3 rounded-md border border-slate-200 p-4">
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <InputLabel value="Semester" />
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                        value={data.semester_id}
                                        onChange={(e) => setData('semester_id', e.target.value)}
                                    >
                                        {semesters.map((s) => (
                                            <option key={s.id} value={s.id}>
                                                {s.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.semester_id} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel value="Session Date" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.session_date}
                                        onChange={(e) => setData('session_date', e.target.value)}
                                    />
                                    <InputError message={errors.session_date} className="mt-1" />
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Summary" />
                                <textarea
                                    className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                    rows={3}
                                    value={data.summary}
                                    onChange={(e) => setData('summary', e.target.value)}
                                />
                                <InputError message={errors.summary} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel value="Recommendations" />
                                <textarea
                                    className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                    rows={2}
                                    value={data.recommendations}
                                    onChange={(e) => setData('recommendations', e.target.value)}
                                />
                                <InputError message={errors.recommendations} className="mt-1" />
                            </div>

                            <label className="flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    checked={data.follow_up_required}
                                    onChange={(e) => setData('follow_up_required', e.target.checked)}
                                    className="rounded border-gray-300 text-brand-700 focus:ring-brand-600"
                                />
                                Follow-up required
                            </label>

                            <div className="flex gap-3">
                                <PrimaryButton disabled={processing}>Save Session</PrimaryButton>
                                <SecondaryButton
                                    type="button"
                                    onClick={() => {
                                        setShowForm(false);
                                        reset('summary', 'recommendations', 'follow_up_required');
                                    }}
                                >
                                    Cancel
                                </SecondaryButton>
                            </div>
                        </form>
                    )}
                </>
            )}
        </div>
    );
}
