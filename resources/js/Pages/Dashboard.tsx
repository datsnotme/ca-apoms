import { Head, Link } from '@inertiajs/react';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { Bar } from 'react-chartjs-2';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/Card';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

interface StatCard {
    label: string;
    value: number;
    href: string;
}

interface ChartData {
    labels: string[];
    values: number[];
}

interface DashboardProps {
    role: 'administrator' | 'dean' | 'department-head' | 'faculty';
    departmentName: string | null;
    statCards: StatCard[];
    charts: Record<string, ChartData>;
}

const CHART_TITLES: Record<string, string> = {
    studentsByStatus: 'Students by Status',
    graduationPipeline: 'Graduation Pipeline',
    atRiskByDepartment: 'At-Risk Students by Department',
    equipmentByStatus: 'Equipment by Status',
};

const ROLE_TITLES: Record<DashboardProps['role'], string> = {
    administrator: 'College-Wide Dashboard',
    dean: 'College-Wide Dashboard',
    'department-head': 'Department Dashboard',
    faculty: 'My Dashboard',
};

function StatTile({ stat }: { stat: StatCard }) {
    return (
        <Link href={stat.href}>
            <Card className="transition hover:border-brand-300 hover:shadow-sm">
                <CardContent>
                    <p className="text-xs font-medium uppercase tracking-wide text-slate-500">{stat.label}</p>
                    <p className="mt-1 text-2xl font-semibold text-slate-900">{stat.value}</p>
                </CardContent>
            </Card>
        </Link>
    );
}

function DistributionChart({ chartKey, data }: { chartKey: string; data: ChartData }) {
    if (data.values.every((v) => v === 0)) {
        return (
            <Card>
                <CardHeader title={CHART_TITLES[chartKey] ?? chartKey} />
                <CardContent>
                    <p className="text-sm text-slate-500">No data yet.</p>
                </CardContent>
            </Card>
        );
    }

    const summary = data.labels.map((label, i) => `${label}: ${data.values[i]}`).join(', ');

    return (
        <Card>
            <CardHeader title={CHART_TITLES[chartKey] ?? chartKey} />
            <CardContent>
                <div role="img" aria-label={`${CHART_TITLES[chartKey] ?? chartKey}. ${summary}`}>
                    <Bar
                        aria-hidden="true"
                        data={{
                            labels: data.labels,
                            datasets: [
                                {
                                    data: data.values,
                                    backgroundColor: '#4d7c0f',
                                    borderRadius: 4,
                                },
                            ],
                        }}
                        options={{
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                        }}
                        height={220}
                    />
                </div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ role, departmentName, statCards, charts }: DashboardProps) {
    const chartEntries = Object.entries(charts);

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">{ROLE_TITLES[role]}</h1>}>
            <Head title="Dashboard" />

            {departmentName && <p className="mb-4 text-sm text-slate-500">{departmentName}</p>}

            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                {statCards.map((stat) => (
                    <StatTile key={stat.label} stat={stat} />
                ))}
            </div>

            {chartEntries.length > 0 && (
                <div className="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                    {chartEntries.map(([key, data]) => (
                        <DistributionChart key={key} chartKey={key} data={data} />
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
