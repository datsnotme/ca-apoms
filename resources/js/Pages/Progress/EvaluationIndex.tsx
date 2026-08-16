import { Head, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import { Paginated } from '@/types';

interface StudentRow {
    id: number;
    student_number: string;
    name: string;
    department: { name: string } | null;
    program: { name: string } | null;
    year_level: { label: string } | null;
    adviser: { name: string } | null;
}

function ImportHistoricalGradesModal({ student, onClose }: { student: StudentRow; onClose: () => void }) {
    const fileInput = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm<{ file: File | null }>({ file: null });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('students.historical-grades.store', student.id), {
            forceFormData: true,
            onSuccess: () => {
                reset();
                if (fileInput.current) fileInput.current.value = '';
                onClose();
            },
        });
    };

    return (
        <Modal show onClose={onClose} maxWidth="md" variant="form">
            <div className="p-6">
                <h2 className="text-lg font-medium text-slate-900">Import Historical Grades</h2>
                <p className="mt-1 text-sm text-slate-700">
                    For {student.name} ({student.student_number}). Upload a spreadsheet of a prior-program grade
                    record (e.g. before a shift or transfer) transcribed from the student's paper or PDF academic
                    record. Every row is checked against this student's number and name — a file for a different
                    student is rejected entirely. Uploading again replaces the previously imported record.
                </p>

                <a
                    href={route('students.historical-grades.template', student.id)}
                    className="mt-3 inline-block text-sm font-medium text-brand-700 hover:text-brand-900"
                >
                    Download Template
                </a>

                <form onSubmit={submit} className="mt-4 flex flex-col gap-3">
                    <input
                        ref={fileInput}
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        className="text-sm"
                        onChange={(e) => setData('file', e.target.files?.[0] ?? null)}
                    />
                    <InputError message={errors.file} />

                    <div className="flex gap-3">
                        <PrimaryButton disabled={processing || !data.file}>Upload</PrimaryButton>
                        <SecondaryButton type="button" onClick={onClose}>
                            Cancel
                        </SecondaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

export default function EvaluationIndex({
    students,
    filters,
}: {
    students: Paginated<StudentRow>;
    filters: { search?: string };
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [importingFor, setImportingFor] = useState<StudentRow | null>(null);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('students.evaluation.index'), { search }, { preserveState: true });
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Evaluate Student</h1>}>
            <Head title="Evaluate Student" />

            <Card>
                <CardHeader
                    title="Individual Student Evaluation"
                    description="Generate a per-student evaluation PDF — grades, curriculum checklist, and suggested standing."
                />

                <div className="border-b border-slate-200 px-5 py-3">
                    <form onSubmit={submitSearch} className="max-w-sm">
                        <TextInput
                            aria-label="Search students by number or name"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by number or name..."
                            className="w-full"
                        />
                    </form>
                </div>

                {students.data.length === 0 ? (
                    <EmptyState title="No students found" />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                                <tr>
                                    <th scope="col" className="px-5 py-2.5">Student No.</th>
                                    <th scope="col" className="px-5 py-2.5">Name</th>
                                    <th scope="col" className="px-5 py-2.5">Department</th>
                                    <th scope="col" className="px-5 py-2.5">Program</th>
                                    <th scope="col" className="px-5 py-2.5">Year Level</th>
                                    <th scope="col" className="px-5 py-2.5">Adviser</th>
                                    <th scope="col" className="px-5 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {students.data.map((s) => (
                                    <tr key={s.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5 font-mono text-xs">{s.student_number}</td>
                                        <td className="px-5 py-2.5">{s.name}</td>
                                        <td className="px-5 py-2.5">{s.department?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.program?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.year_level?.label ?? '—'}</td>
                                        <td className="px-5 py-2.5">{s.adviser?.name ?? 'Unassigned'}</td>
                                        <td className="px-5 py-2.5 text-right">
                                            <div className="flex justify-end gap-3">
                                                <button
                                                    type="button"
                                                    onClick={() => setImportingFor(s)}
                                                    className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                >
                                                    Import Historical Grades
                                                </button>
                                                <a
                                                    href={route('students.evaluation.show', s.id)}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                >
                                                    Download Evaluation
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={students.links} from={students.from} to={students.to} total={students.total} />
            </Card>

            {importingFor && (
                <ImportHistoricalGradesModal student={importingFor} onClose={() => setImportingFor(null)} />
            )}
        </AppLayout>
    );
}
