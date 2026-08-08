import { PropsWithChildren } from 'react';

const VARIANTS = {
    success: 'bg-brand-100 text-brand-800',
    warning: 'bg-gold-100 text-gold-800',
    danger: 'bg-red-100 text-red-800',
    info: 'bg-blue-100 text-blue-800',
    neutral: 'bg-slate-100 text-slate-700',
} as const;

export default function Badge({
    variant = 'neutral',
    children,
}: PropsWithChildren<{ variant?: keyof typeof VARIANTS }>) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${VARIANTS[variant]}`}
        >
            {children}
        </span>
    );
}
