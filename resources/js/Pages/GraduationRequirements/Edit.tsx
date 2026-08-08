import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import GraduationRequirementForm from './Form';

interface TemplateDetail {
    id: number;
    program_id: number | null;
    title: string;
    description: string | null;
    is_required: boolean;
    sort_order: number;
}

export default function Edit({
    template,
    programs,
}: {
    template: TemplateDetail;
    programs: { id: number; name: string }[];
}) {
    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Graduation Requirement</h1>}>
            <Head title={`Edit ${template.title}`} />

            <Card>
                <CardHeader title={template.title} />
                <CardContent>
                    <GraduationRequirementForm
                        action={route('graduation-requirement-templates.update', template.id)}
                        method="put"
                        initialValues={{
                            program_id: template.program_id ? String(template.program_id) : '',
                            title: template.title,
                            description: template.description ?? '',
                            is_required: template.is_required,
                            sort_order: String(template.sort_order),
                        }}
                        programs={programs}
                        submitLabel="Save Changes"
                        onCancelHref={route('graduation-requirement-templates.index')}
                    />
                </CardContent>
            </Card>
        </AppLayout>
    );
}
