import { ReactNode } from 'react';

export default function EmptyState({
    title,
    description,
    action,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center gap-2 px-4 py-12 text-center">
            <p className="text-sm font-medium text-slate-700">{title}</p>
            {description && <p className="max-w-sm text-sm text-slate-900">{description}</p>}
            {action && <div className="mt-2">{action}</div>}
        </div>
    );
}
