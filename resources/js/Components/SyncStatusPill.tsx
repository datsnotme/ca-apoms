import { Link } from '@inertiajs/react';

export default function SyncStatusPill({ pendingConflicts }: { pendingConflicts: number }) {
    if (pendingConflicts > 0) {
        return (
            <Link
                href={route('sync.conflicts')}
                className="flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700 hover:bg-red-100"
                title="Unresolved sync conflicts need attention"
            >
                <span className="h-1.5 w-1.5 rounded-full bg-red-500" />
                {pendingConflicts} sync {pendingConflicts === 1 ? 'conflict' : 'conflicts'}
            </Link>
        );
    }

    return (
        <Link
            href={route('sync.index')}
            className="flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-100"
            title="Sync Center"
        >
            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
            Sync OK
        </Link>
    );
}
