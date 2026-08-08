import { FormEventHandler, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import ClassSectionForm from './Form';

interface ScheduleRow {
    id: number;
    day_of_week: string;
    start_time: string;
    end_time: string;
    facility: { id: number; name: string } | null;
}

interface ClassSectionDetail {
    id: number;
    course_id: number;
    semester_id: number;
    section_label: string;
    max_students: number;
    status: 'open' | 'closed';
    faculty_id: number | null;
    schedules: ScheduleRow[];
    enrolled_count: number;
}

function AddScheduleForm({
    classSectionId,
    dayOptions,
    facilityOptions,
}: {
    classSectionId: number;
    dayOptions: { value: string; label: string }[];
    facilityOptions: { id: number; name: string }[];
}) {
    const [dayOfWeek, setDayOfWeek] = useState(dayOptions[0]?.value ?? 'monday');
    const [startTime, setStartTime] = useState('08:00');
    const [endTime, setEndTime] = useState('09:00');
    const [facilityId, setFacilityId] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(
            route('class-sections.schedules.store', classSectionId),
            { day_of_week: dayOfWeek, start_time: startTime, end_time: endTime, facility_id: facilityId },
            {
                preserveScroll: true,
                onError: (err) => setErrors(err as Record<string, string>),
                onSuccess: () => setFacilityId(''),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <form onSubmit={submit} className="grid grid-cols-1 gap-3 rounded-md border border-slate-200 p-4 sm:grid-cols-5">
            <div>
                <InputLabel value="Day" />
                <select
                    className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={dayOfWeek}
                    onChange={(e) => setDayOfWeek(e.target.value)}
                >
                    {dayOptions.map((d) => (
                        <option key={d.value} value={d.value}>
                            {d.label}
                        </option>
                    ))}
                </select>
                <InputError message={errors.day_of_week} className="mt-1" />
            </div>
            <div>
                <InputLabel value="Start Time" />
                <TextInput type="time" className="mt-1 block w-full" value={startTime} onChange={(e) => setStartTime(e.target.value)} />
                <InputError message={errors.start_time} className="mt-1" />
            </div>
            <div>
                <InputLabel value="End Time" />
                <TextInput type="time" className="mt-1 block w-full" value={endTime} onChange={(e) => setEndTime(e.target.value)} />
                <InputError message={errors.end_time} className="mt-1" />
            </div>
            <div>
                <InputLabel value="Facility" />
                <select
                    className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                    value={facilityId}
                    onChange={(e) => setFacilityId(e.target.value)}
                >
                    <option value="">Unassigned</option>
                    {facilityOptions.map((f) => (
                        <option key={f.id} value={f.id}>
                            {f.name}
                        </option>
                    ))}
                </select>
                <InputError message={errors.facility_id} className="mt-1" />
            </div>
            <div className="flex items-end">
                <PrimaryButton disabled={processing}>Add Schedule</PrimaryButton>
            </div>
        </form>
    );
}

export default function Edit({
    classSection,
    courses,
    semesters,
    faculty,
    statuses,
    dayOptions,
    facilityOptions,
}: {
    classSection: ClassSectionDetail;
    courses: { id: number; code: string; title: string }[];
    semesters: { id: number; label: string }[];
    faculty: { id: number; name: string }[];
    statuses: { value: string; label: string }[];
    dayOptions: { value: string; label: string }[];
    facilityOptions: { id: number; name: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Class Section</h1>}>
            <Head title={`Edit ${classSection.section_label}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader
                        title={`Section ${classSection.section_label}`}
                        description={`${classSection.enrolled_count} / ${classSection.max_students} enrolled`}
                        actions={
                            <Link href={route('class-sections.roster', classSection.id)} className="text-sm font-medium text-brand-700 hover:text-brand-900">
                                View Roster
                            </Link>
                        }
                    />
                    <CardContent>
                        <ClassSectionForm
                            action={route('class-sections.update', classSection.id)}
                            method="put"
                            initialValues={{
                                course_id: String(classSection.course_id),
                                semester_id: String(classSection.semester_id),
                                section_label: classSection.section_label,
                                max_students: String(classSection.max_students),
                                status: classSection.status,
                                faculty_id: classSection.faculty_id ? String(classSection.faculty_id) : '',
                            }}
                            courses={courses}
                            semesters={semesters}
                            faculty={faculty}
                            statuses={statuses}
                            submitLabel="Save Changes"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Schedule" description="Meeting days, times, and facility." />
                    <CardContent className="flex flex-col gap-4">
                        {classSection.schedules.length === 0 ? (
                            <p className="text-sm text-slate-500">No schedule entries yet.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                        <tr>
                                            <th className="px-5 py-2.5">Day</th>
                                            <th className="px-5 py-2.5">Start</th>
                                            <th className="px-5 py-2.5">End</th>
                                            <th className="px-5 py-2.5">Facility</th>
                                            <th className="px-5 py-2.5 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {classSection.schedules.map((s) => (
                                            <tr key={s.id}>
                                                <td className="px-5 py-2.5 capitalize">{s.day_of_week}</td>
                                                <td className="px-5 py-2.5">{s.start_time}</td>
                                                <td className="px-5 py-2.5">{s.end_time}</td>
                                                <td className="px-5 py-2.5">{s.facility?.name ?? '—'}</td>
                                                <td className="px-5 py-2.5 text-right">
                                                    <SecondaryButton
                                                        type="button"
                                                        onClick={() =>
                                                            router.delete(
                                                                route('class-sections.schedules.destroy', [classSection.id, s.id]),
                                                                { preserveScroll: true },
                                                            )
                                                        }
                                                    >
                                                        Remove
                                                    </SecondaryButton>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        <AddScheduleForm classSectionId={classSection.id} dayOptions={dayOptions} facilityOptions={facilityOptions} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
