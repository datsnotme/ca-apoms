import { PropsWithChildren, ReactNode } from 'react';

export function Card({
    children,
    className = '',
}: PropsWithChildren<{ className?: string }>) {
    return (
        <div className={`rounded-lg border border-slate-200 bg-white shadow-sm ${className}`}>
            {children}
        </div>
    );
}

export function CardHeader({
    title,
    description,
    actions,
}: {
    title: string;
    description?: string;
    actions?: ReactNode;
}) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <div>
                <h2 className="text-base font-semibold text-slate-900">{title}</h2>
                {description && (
                    <p className="mt-1 text-sm text-slate-900">{description}</p>
                )}
            </div>
            {actions && <div className="flex shrink-0 items-center gap-2">{actions}</div>}
        </div>
    );
}

export function CardContent({
    children,
    className = '',
}: PropsWithChildren<{ className?: string }>) {
    return <div className={`px-5 py-4 ${className}`}>{children}</div>;
}
