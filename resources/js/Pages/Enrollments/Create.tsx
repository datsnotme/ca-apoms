import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface StudentOption {
    id: number;
    student_number: string;
    surname: string;
    first_name: string;
    middle_name: string | null;
}

export default function Create({
    students,
    semesters,
}: {
    students: StudentOption[];
    semesters: { id: number; label: string }[];
}) {
    const { data, setData, post, processing, errors } = useForm({
        student_id: String(students[0]?.id ?? ''),
        semester_id: String(semesters[0]?.id ?? ''),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('enrollments.store'));
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">New Enrollment</h1>}>
            <Head title="New Enrollment" />

            <Card>
                <CardHeader title="Enroll a Student" description="Creates the semester-level enrollment; courses are added afterward." />
                <CardContent>
                    <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Student" />
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                value={data.student_id}
                                onChange={(e) => setData('student_id', e.target.value)}
                            >
                                {students.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.student_number} — {s.first_name} {s.middle_name} {s.surname}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.student_id} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Semester" />
                            <select
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
                            <PrimaryButton disabled={processing}>Create Enrollment</PrimaryButton>
                            <SecondaryButton type="button" onClick={() => (window.location.href = route('enrollments.index'))}>
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AppLayout>
    );
}
