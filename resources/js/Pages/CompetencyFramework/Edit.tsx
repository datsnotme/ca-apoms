import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardContent } from '@/Components/ui/Card';
import CompetencyCategoryForm from './Form';
import IndicatorList from './IndicatorList';
import { PageProps } from '@/types';

interface CategoryDetail {
    id: number;
    name: string;
    description: string | null;
    sort_order: number;
    indicators: { id: number; title: string; description: string | null; sort_order: number }[];
}

export default function Edit({ category }: { category: CategoryDetail }) {
    const { auth } = usePage<PageProps>().props;
    const canManage = auth.user.permissions.includes('graduation.manage');

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Edit Competency Category</h1>}>
            <Head title={`Edit ${category.name}`} />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader title={category.name} description="Category details" />
                    <CardContent>
                        <CompetencyCategoryForm
                            action={route('competency-categories.update', category.id)}
                            method="put"
                            initialValues={{
                                name: category.name,
                                description: category.description ?? '',
                                sort_order: String(category.sort_order),
                            }}
                            submitLabel="Save Changes"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader title="Indicators" description="The rateable items evaluators score under this category." />
                    <CardContent>
                        <IndicatorList categoryId={category.id} indicators={category.indicators} canManage={canManage} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
