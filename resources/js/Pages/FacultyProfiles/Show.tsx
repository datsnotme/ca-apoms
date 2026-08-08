import { FormEventHandler } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import EducationList from './EducationList';
import CredentialList from './CredentialList';
import TrainingList from './TrainingList';
import AwardList from './AwardList';
import DocumentList from './DocumentList';

const EMPLOYMENT_LABELS: Record<string, string> = {
    full_time: 'Full-Time',
    part_time: 'Part-Time',
    visiting: 'Visiting',
    on_leave: 'On Leave',
};

interface FacultyDetail {
    id: number;
    name: string;
    employee_number: string;
    email: string;
    department: { name: string } | null;
}

interface ProfileDetail {
    academic_rank: string | null;
    employment_status: string;
    specialization: string | null;
    office_location: string | null;
    date_hired: string | null;
    bio: string | null;
}

interface EducationRow {
    id: number;
    level: string;
    degree: string;
    field_of_study: string | null;
    institution: string;
    year_completed: number | null;
}

interface CredentialRow {
    id: number;
    name: string;
    issuing_body: string | null;
    license_number: string | null;
    issued_date: string | null;
    expiry_date: string | null;
}

interface TrainingRow {
    id: number;
    title: string;
    provider: string | null;
    training_type: string | null;
    start_date: string | null;
    end_date: string | null;
    hours: number | null;
}

interface AwardRow {
    id: number;
    title: string;
    awarding_body: string | null;
    date_awarded: string | null;
    description: string | null;
}

interface DocumentRow {
    id: number;
    category: string;
    title: string;
    original_filename: string;
    file_size: number;
    uploaded_at: string;
    verification_status: 'pending' | 'verified' | 'rejected';
    remarks: string | null;
    uploaded_by: { name: string } | null;
    verified_by: { name: string } | null;
}

export default function Show({
    faculty,
    profile,
    canManage,
    canEdit,
    canUploadDocuments,
    documentCategories,
}: {
    faculty: FacultyDetail & {
        education: EducationRow[];
        credentials: CredentialRow[];
        trainings: TrainingRow[];
        awards: AwardRow[];
        faculty_documents: DocumentRow[];
    };
    profile: ProfileDetail;
    canManage: boolean;
    canEdit: boolean;
    canUploadDocuments: boolean;
    documentCategories: { value: string; label: string }[];
}) {
    const { data, setData, put, processing, errors } = useForm({
        academic_rank: profile.academic_rank ?? '',
        employment_status: profile.employment_status,
        date_hired: profile.date_hired ?? '',
        specialization: profile.specialization ?? '',
        office_location: profile.office_location ?? '',
        bio: profile.bio ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('faculty-profiles.update', faculty.id));
    };

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Faculty Profile</h1>}>
            <Head title={`Faculty Profile — ${faculty.name}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader
                        title={faculty.name}
                        description={`${faculty.employee_number} · ${faculty.email} · ${faculty.department?.name ?? 'No department'}`}
                    />
                </Card>

                <Card>
                    <CardHeader title="Profile" description={canEdit ? 'Update the fields below.' : 'View-only.'} />
                    <CardContent>
                        {canEdit ? (
                            <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {canManage && (
                                    <>
                                        <div>
                                            <InputLabel htmlFor="academic_rank" value="Academic Rank" />
                                            <TextInput
                                                id="academic_rank"
                                                className="mt-1 block w-full"
                                                value={data.academic_rank}
                                                onChange={(e) => setData('academic_rank', e.target.value)}
                                            />
                                            <InputError message={errors.academic_rank} className="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="employment_status" value="Employment Status" />
                                            <select
                                                id="employment_status"
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                                value={data.employment_status}
                                                onChange={(e) => setData('employment_status', e.target.value)}
                                            >
                                                {Object.entries(EMPLOYMENT_LABELS).map(([value, label]) => (
                                                    <option key={value} value={value}>
                                                        {label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError message={errors.employment_status} className="mt-2" />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="date_hired" value="Date Hired" />
                                            <TextInput
                                                id="date_hired"
                                                type="date"
                                                className="mt-1 block w-full"
                                                value={data.date_hired}
                                                onChange={(e) => setData('date_hired', e.target.value)}
                                            />
                                            <InputError message={errors.date_hired} className="mt-2" />
                                        </div>
                                    </>
                                )}

                                <div>
                                    <InputLabel htmlFor="specialization" value="Specialization" />
                                    <TextInput
                                        id="specialization"
                                        className="mt-1 block w-full"
                                        value={data.specialization}
                                        onChange={(e) => setData('specialization', e.target.value)}
                                    />
                                    <InputError message={errors.specialization} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="office_location" value="Office Location" />
                                    <TextInput
                                        id="office_location"
                                        className="mt-1 block w-full"
                                        value={data.office_location}
                                        onChange={(e) => setData('office_location', e.target.value)}
                                    />
                                    <InputError message={errors.office_location} className="mt-2" />
                                </div>
                                <div className="sm:col-span-2">
                                    <InputLabel htmlFor="bio" value="Bio" />
                                    <textarea
                                        id="bio"
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-600 focus:ring-brand-600"
                                        rows={4}
                                        value={data.bio}
                                        onChange={(e) => setData('bio', e.target.value)}
                                    />
                                    <InputError message={errors.bio} className="mt-2" />
                                </div>

                                <div className="sm:col-span-2">
                                    <PrimaryButton disabled={processing}>Save Changes</PrimaryButton>
                                </div>
                            </form>
                        ) : (
                            <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <dt className="text-xs uppercase text-slate-500">Academic Rank</dt>
                                    <dd className="text-sm text-slate-800">{profile.academic_rank ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase text-slate-500">Employment Status</dt>
                                    <dd className="text-sm text-slate-800">
                                        <Badge variant="neutral">{EMPLOYMENT_LABELS[profile.employment_status]}</Badge>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase text-slate-500">Date Hired</dt>
                                    <dd className="text-sm text-slate-800">{profile.date_hired ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase text-slate-500">Specialization</dt>
                                    <dd className="text-sm text-slate-800">{profile.specialization ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs uppercase text-slate-500">Office Location</dt>
                                    <dd className="text-sm text-slate-800">{profile.office_location ?? '—'}</dd>
                                </div>
                                <div className="sm:col-span-2">
                                    <dt className="text-xs uppercase text-slate-500">Bio</dt>
                                    <dd className="text-sm text-slate-800">{profile.bio ?? '—'}</dd>
                                </div>
                            </dl>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Education" description="Degrees earned." />
                    <CardContent>
                        <EducationList facultyId={faculty.id} education={faculty.education} canManage={canManage} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Credentials" description="Licenses and certifications on file." />
                    <CardContent>
                        <CredentialList facultyId={faculty.id} credentials={faculty.credentials} canManage={canManage} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Trainings" description="Seminars, workshops, and conferences attended." />
                    <CardContent>
                        <TrainingList facultyId={faculty.id} trainings={faculty.trainings} canManage={canManage} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Awards" description="Recognitions and honors received." />
                    <CardContent>
                        <AwardList facultyId={faculty.id} awards={faculty.awards} canManage={canManage} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader
                        title="Documents"
                        description="Supporting files such as diplomas, licenses, and appointment papers. Uploads are reviewed by an Admin before being marked verified."
                    />
                    <CardContent>
                        <DocumentList
                            facultyId={faculty.id}
                            documents={faculty.faculty_documents}
                            categories={documentCategories}
                            canUpload={canUploadDocuments}
                            canManage={canManage}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
