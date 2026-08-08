import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface EligibleStudent {
    id: number;
    student_number: string;
    name: string;
}

export default function Create({
    eligibleStudents,
    academicYears,
    semesters,
}: {
    eligibleStudents: EligibleStudent[];
    academicYears: { id: number; start_year: number; end_year: number }[];
    semesters: { id: number; label: string }[];
}) {
    const { data, setData, post, processing, errors } = useForm<{
        student_id: string;
        academic_year_id: string;
        semester_id: string;
    }>({
        student_id: eligibleStudents[0]?.id ? String(eligibleStudents[0].id) : '',
        academic_year_id: academicYears[0]?.id ? String(academicYears[0].id) : '',
        semester_id: semesters[0]?.id ? String(semesters[0].id) : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('graduation-candidates.store'));
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Nominate Student</h1>}>
            <Head title="Nominate Student" />

            <Card>
                <CardHeader
                    title="Nominate a Graduation Candidate"
                    description="Only students with a 100% complete curriculum checklist and no unresolved deficiencies are eligible."
                />
                <CardContent>
                    {eligibleStudents.length === 0 ? (
                        <EmptyState
                            title="No eligible students right now"
                            description="A student becomes eligible once their curriculum checklist is 100% complete with no unresolved deficiencies."
                        />
                    ) : (
                        <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <InputLabel htmlFor="student_id" value="Eligible Student" />
                                <select
                                    id="student_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                    value={data.student_id}
                                    onChange={(e) => setData('student_id', e.target.value)}
                                >
                                    {eligibleStudents.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.student_number} — {s.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.student_id} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="academic_year_id" value="Target Academic Year" />
                                <select
                                    id="academic_year_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                    value={data.academic_year_id}
                                    onChange={(e) => setData('academic_year_id', e.target.value)}
                                >
                                    {academicYears.map((y) => (
                                        <option key={y.id} value={y.id}>
                                            {y.start_year}-{y.end_year}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.academic_year_id} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="semester_id" value="Target Semester" />
                                <select
                                    id="semester_id"
                                    className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                    value={data.semester_id}
                                    onChange={(e) => setData('semester_id', e.target.value)}
                                >
                                    {semesters.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.semester_id} className="mt-2" />
                            </div>

                            <div className="flex gap-3 sm:col-span-2">
                                <PrimaryButton disabled={processing}>Nominate</PrimaryButton>
                                <SecondaryButton
                                    type="button"
                                    onClick={() => (window.location.href = route('graduation-candidates.index'))}
                                >
                                    Cancel
                                </SecondaryButton>
                            </div>
                        </form>
                    )}
                </CardContent>
            </Card>
        </AppLayout>
    );
}
