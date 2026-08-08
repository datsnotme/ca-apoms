import { Link, router } from '@inertiajs/react';
import Dropdown from '@/Components/Dropdown';
import { AppNotification } from '@/types';

function timeAgo(value: string): string {
    const seconds = Math.floor((Date.now() - new Date(value).getTime()) / 1000);
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    return `${Math.floor(hours / 24)}d ago`;
}

export default function NotificationBell({ unreadCount, recent }: { unreadCount: number; recent: AppNotification[] }) {
    function openNotification(notification: AppNotification) {
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
        <Dropdown>
            <Dropdown.Trigger>
                <button
                    type="button"
                    className="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100"
                    aria-label="Notifications"
                >
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9"
                        />
                    </svg>
                    {unreadCount > 0 && (
                        <span className="absolute right-1 top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white">
                            {unreadCount > 9 ? '9+' : unreadCount}
                        </span>
                    )}
                </button>
            </Dropdown.Trigger>
            <Dropdown.Content width="96">
                <div className="flex items-center justify-between border-b border-slate-100 px-4 py-2">
                    <span className="text-sm font-semibold text-slate-900">Notifications</span>
                    {unreadCount > 0 && (
                        <button
                            type="button"
                            className="text-xs font-medium text-brand-700 hover:text-brand-900"
                            onClick={() => router.post(route('notifications.read-all'), {}, { preserveScroll: true })}
                        >
                            Mark all read
                        </button>
                    )}
                </div>

                {recent.length === 0 ? (
                    <p className="px-4 py-6 text-center text-sm text-slate-500">No notifications yet.</p>
                ) : (
                    <div className="max-h-96 overflow-y-auto">
                        {recent.map((n) => (
                            <button
                                key={n.id}
                                type="button"
                                onClick={() => openNotification(n)}
                                className={`block w-full border-b border-slate-50 px-4 py-3 text-left text-sm hover:bg-slate-50 ${
                                    n.read_at ? '' : 'bg-brand-50'
                                }`}
                            >
                                <p className="font-medium text-slate-900">{n.title}</p>
                                <p className="mt-0.5 text-xs text-slate-600">{n.message}</p>
                                <p className="mt-1 text-[11px] text-slate-400">{timeAgo(n.created_at)}</p>
                            </button>
                        ))}
                    </div>
                )}

                <Link
                    href={route('notifications.index')}
                    className="block border-t border-slate-100 px-4 py-2 text-center text-xs font-medium text-brand-700 hover:bg-slate-50 hover:text-brand-900"
                >
                    View All
                </Link>
            </Dropdown.Content>
        </Dropdown>
    );
}
