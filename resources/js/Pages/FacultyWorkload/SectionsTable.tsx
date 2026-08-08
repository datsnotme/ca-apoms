import EmptyState from '@/Components/ui/EmptyState';

interface ScheduleRow {
    day: string;
    start_time: string | null;
    end_time: string | null;
    room: string | null;
}

export interface SectionRow {
    id: number;
    role: string;
    section_id: number;
    course_code: string;
    course_title: string;
    units: number;
    section_label: string;
    semester: string;
    schedules: ScheduleRow[];
}

function formatSchedule(schedules: ScheduleRow[]): string {
    if (schedules.length === 0) return 'No schedule set';

    return schedules
        .map((s) => {
            const day = s.day.charAt(0).toUpperCase() + s.day.slice(1);
            const time = s.start_time && s.end_time ? `${s.start_time}–${s.end_time}` : '';
            return [day, time, s.room].filter(Boolean).join(' · ');
        })
        .join('; ');
}

export default function SectionsTable({ sections }: { sections: SectionRow[] }) {
    if (sections.length === 0) {
        return <EmptyState title="No classes assigned" description="No sections are assigned for the selected semester." />;
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th className="px-5 py-2.5">Course</th>
                        <th className="px-5 py-2.5">Section</th>
                        <th className="px-5 py-2.5">Units</th>
                        <th className="px-5 py-2.5">Role</th>
                        <th className="px-5 py-2.5">Schedule</th>
                        <th className="px-5 py-2.5">Semester</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {sections.map((s) => (
                        <tr key={s.id}>
                            <td className="px-5 py-2.5">
                                {s.course_code} — {s.course_title}
                            </td>
                            <td className="px-5 py-2.5 font-mono text-xs">{s.section_label}</td>
                            <td className="px-5 py-2.5">{s.units.toFixed(2)}</td>
                            <td className="px-5 py-2.5">{s.role}</td>
                            <td className="px-5 py-2.5 text-xs text-slate-600">{formatSchedule(s.schedules)}</td>
                            <td className="px-5 py-2.5">{s.semester}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
