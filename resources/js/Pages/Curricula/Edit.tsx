import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import CurriculumForm from './Form';
import Checklist from './Checklist';
import { PageProps } from '@/types';

interface CurriculumDetail {
    id: number;
    program_id: number;
    effective_academic_year_id: number;
    code: string;
    name: string;
    required_total_units: number | null;
    status: 'active' | 'inactive';
    notes: string | null;
    curriculum_courses: {
        id: number;
        year_level: number;
        semester: string;
        is_required: boolean;
        units: string;
        course: { id: number; code: string; title: string };
    }[];
}

export default function Edit({
    curriculum,
    programs,
    academicYears,
    availableCourses,
}: {
    curriculum: CurriculumDetail;
    programs: { id: number; name: string }[];
    academicYears: { id: number; start_year: number; end_year: number }[];
    availableCourses: { id: number; code: string; title: string; units: string }[];
}) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('curricula.manage');

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Curriculum</h1>}>
            <Head title={`Edit ${curriculum.name}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader title={curriculum.name} description="Curriculum details" />
                    <CardContent>
                        <CurriculumForm
                            action={route('curricula.update', curriculum.id)}
                            method="put"
                            initialValues={{
                                program_id: String(curriculum.program_id),
                                effective_academic_year_id: String(curriculum.effective_academic_year_id),
                                code: curriculum.code,
                                name: curriculum.name,
                                required_total_units: curriculum.required_total_units
                                    ? String(curriculum.required_total_units)
                                    : '',
                                status: curriculum.status,
                                notes: curriculum.notes ?? '',
                            }}
                            programs={programs}
                            academicYears={academicYears}
                            submitLabel="Save Changes"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Curriculum Checklist" description="Courses grouped by year level and semester." />
                    <CardContent>
                        <Checklist
                            curriculumId={curriculum.id}
                            curriculumCourses={curriculum.curriculum_courses}
                            availableCourses={availableCourses}
                            canManage={canManage}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
