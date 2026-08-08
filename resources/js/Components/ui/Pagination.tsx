import { Link } from '@inertiajs/react';
import { PaginationLink } from '@/types';

export default function Pagination({
    links,
    from,
    to,
    total,
}: {
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}) {
    if (total === 0) {
        return null;
    }

    function ariaLabelFor(label: string, active: boolean): string {
        if (label.includes('Previous')) return 'Previous page';
        if (label.includes('Next')) return 'Next page';

        const page = label.replace(/&laquo;|&raquo;/g, '').trim();

        return active ? `Current page, page ${page}` : `Go to page ${page}`;
    }

    return (
        <div className="flex flex-col items-center justify-between gap-3 border-t border-slate-200 px-5 py-3 sm:flex-row">
            <p className="text-sm text-slate-500">
                Showing {from} to {to} of {total} results
            </p>
            <nav aria-label="Pagination" className="flex flex-wrap gap-1">
                {links.map((link, index) => (
                    <Link
                        key={index}
                        href={link.url ?? '#'}
                        preserveScroll
                        aria-label={ariaLabelFor(link.label, link.active)}
                        aria-current={link.active ? 'page' : undefined}
                        aria-disabled={link.url ? undefined : true}
                        className={`min-w-9 rounded-md px-3 py-1.5 text-center text-sm ${
                            link.active
                                ? 'bg-brand-700 text-white'
                                : link.url
                                  ? 'text-slate-600 hover:bg-slate-100'
                                  : 'cursor-default text-slate-300'
                        }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ))}
            </nav>
        </div>
    );
}
