function initials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) {
        return '?';
    }

    return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
}

const SIZES = {
    sm: 'h-9 w-9 text-sm',
    md: 'h-16 w-16 text-lg',
    lg: 'h-24 w-24 text-2xl',
} as const;

export default function Avatar({
    src,
    name,
    size = 'sm',
    className = '',
}: {
    src?: string | null;
    name: string;
    size?: keyof typeof SIZES;
    className?: string;
}) {
    if (src) {
        return (
            <img
                src={src}
                alt={name}
                className={`${SIZES[size]} rounded-full object-cover ${className}`}
            />
        );
    }

    return (
        <span
            className={`flex ${SIZES[size]} items-center justify-center rounded-full bg-brand-700 font-semibold text-white ${className}`}
        >
            {initials(name)}
        </span>
    );
}
