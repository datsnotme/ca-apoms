import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import CourseForm from './Form';

interface CourseDetail {
    id: number;
    department_id: number;
    code: string;
    title: string;
    description: string | null;
    units: string;
    lecture_hours: string;
    laboratory_hours: string;
    category: string;
    recommended_year_level: number | null;
    recommended_semester: string | null;
    is_active: boolean;
    prerequisite_ids: number[];
    corequisite_ids: number[];
}

export default function Edit({
    course,
    departments,
    courses,
}: {
    course: CourseDetail;
    departments: { id: number; name: string }[];
    courses: { id: number; code: string; title: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Course</h1>}>
            <Head title={`Edit ${course.title}`} />

            <Card>
                <CardHeader title={`${course.code} — ${course.title}`} />
                <CardContent>
                    <CourseForm
                        action={route('courses.update', course.id)}
                        method="put"
                        initialValues={{
                            department_id: String(course.department_id),
                            code: course.code,
                            title: course.title,
                            description: course.description ?? '',
                            units: course.units,
                            lecture_hours: course.lecture_hours,
                            laboratory_hours: course.laboratory_hours,
                            category: course.category,
                            recommended_year_level: course.recommended_year_level
                                ? String(course.recommended_year_level)
                                : '',
                            recommended_semester: course.recommended_semester ?? '',
                            is_active: course.is_active,
                            prerequisite_ids: course.prerequisite_ids,
                            corequisite_ids: course.corequisite_ids,
                        }}
                        departments={departments}
                        courses={courses}
                        submitLabel="Save Changes"
                        onCancelHref={route('courses.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
