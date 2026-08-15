import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import { Paginated, AppNotification } from '@/types';

export default function Index({ notifications }: { notifications: Paginated<AppNotification> }) {
    function open(notification: AppNotification) {
        if (!notification.read_at) {
            router.patch(
                route('notifications.read', notification.id),
                {},
                { preserveScroll: true, preserveState: true, onFinish: () => router.visit(notification.url) },
            );
            return;
        }
        router.visit(notification.url);
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">Notifications</h1>}>
            <Head title="Notifications" />

            <Card>
                <CardHeader
                    title="Notifications"
                    description="Announcements, meeting invitations, task assignments, and request updates."
                    actions={
                        <Link
                            href={route('notifications.read-all')}
                            method="post"
                            as="button"
                            className="text-sm font-medium text-brand-700 hover:text-brand-900"
                        >
                            Mark all read
                        </Link>
                    }
                />

                {notifications.data.length === 0 ? (
                    <EmptyState title="No notifications yet" />
                ) : (
                    <div className="divide-y divide-slate-100">
                        {notifications.data.map((n) => (
                            <button
                                key={n.id}
                                type="button"
                                onClick={() => open(n)}
                                className={`flex w-full items-start justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50 ${
                                    n.read_at ? '' : 'bg-brand-50/60'
                                }`}
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <h3 className="text-sm font-semibold text-slate-900">{n.title}</h3>
                                        {!n.read_at && <Badge variant="info">New</Badge>}
                                    </div>
                                    <p className="mt-1 text-sm text-slate-600">{n.message}</p>
                                    <p className="mt-1 text-xs text-slate-900">{n.created_at.slice(0, 16).replace('T', ' ')}</p>
                                </div>
                            </button>
                        ))}
                    </div>
                )}

                <Pagination links={notifications.links} from={notifications.from} to={notifications.to} total={notifications.total} />
            </Card>
        </AppLayout>
    );
}
