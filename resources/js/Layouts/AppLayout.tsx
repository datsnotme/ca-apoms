import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';
import Dropdown from '@/Components/Dropdown';
import FlashToast from '@/Components/ui/FlashToast';
import NotificationBell from '@/Components/NotificationBell';
import { NAVIGATION, NavItem } from '@/navigation';
import { PageProps } from '@/types';

function hasAccess(item: NavItem, permissions: string[]): boolean {
    return !item.permission || permissions.includes(item.permission);
}

function NavLink({ item, permissions }: { item: NavItem; permissions: string[] }) {
    if (!hasAccess(item, permissions)) {
        return null;
    }

    if (item.children) {
        const visibleChildren = item.children.filter((child) => hasAccess(child, permissions));
        if (visibleChildren.length === 0) {
            return null;
        }

        return (
            <div className="mt-1">
                <p className="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-wide text-brand-300">
                    {item.label}
                </p>
                {visibleChildren.map((child) => (
                    <NavLink key={child.label} item={child} permissions={permissions} />
                ))}
            </div>
        );
    }

    if (!item.routeName) {
        return (
            <span
                title="Coming in a later phase"
                className="flex cursor-default items-center rounded-md px-3 py-2 text-sm text-brand-400/60"
            >
                {item.label}
            </span>
        );
    }

    const active = route().current(`${item.routeName.split('.')[0]}*`);

    return (
        <Link
            href={route(item.routeName)}
            className={`flex items-center rounded-md px-3 py-2 text-sm font-medium transition-colors ${
                active ? 'bg-white/15 text-white' : 'text-brand-100 hover:bg-white/10 hover:text-white'
            }`}
        >
            {item.label}
        </Link>
    );
}

export default function AppLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, notificationCenter } = usePage<PageProps>().props;
    const [sidebarOpen, setSidebarOpen] = useState(true);

    return (
        <div className="flex min-h-screen bg-slate-50">
            {sidebarOpen && (
                <aside className="fixed inset-y-0 left-0 z-30 w-64 overflow-y-auto bg-brand-900 pb-6 print:hidden lg:static lg:shrink-0">
                    <div className="flex h-16 items-center gap-2 px-4">
                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gold-400 text-sm font-bold text-brand-900">
                            CA
                        </span>
                        <span className="text-sm font-semibold text-white">CA-APOMS</span>
                    </div>
                    <nav className="space-y-0.5 px-2">
                        {NAVIGATION.map((item) => (
                            <NavLink key={item.label} item={item} permissions={auth.user.permissions} />
                        ))}
                    </nav>
                </aside>
            )}

            <div className="flex min-h-screen flex-1 flex-col">
                <header className="flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 print:hidden">
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setSidebarOpen((v) => !v)}
                            className="rounded-md p-2 text-slate-500 hover:bg-slate-100"
                            aria-label="Toggle sidebar"
                        >
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        {header}
                    </div>

                    <div className="flex items-center gap-4">
                        <div className="hidden text-right sm:block">
                            <p className="text-sm font-medium text-slate-900">{auth.user.name}</p>
                            <p className="text-xs text-slate-500">
                                {auth.user.role
                                    ?.split('-')
                                    .map((w) => w[0].toUpperCase() + w.slice(1))
                                    .join(' ')}
                                {auth.user.department ? ` · ${auth.user.department.name}` : ''}
                            </p>
                        </div>
                        {notificationCenter && (
                            <NotificationBell unreadCount={notificationCenter.unread_count} recent={notificationCenter.recent} />
                        )}
                        <Dropdown>
                            <Dropdown.Trigger>
                                <button
                                    type="button"
                                    aria-label="Account menu"
                                    className="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-brand-700 text-sm font-semibold text-white"
                                >
                                    {auth.user.profile_photo_url ? (
                                        <img
                                            src={auth.user.profile_photo_url}
                                            alt=""
                                            className="h-full w-full object-cover"
                                        />
                                    ) : (
                                        auth.user.name.charAt(0).toUpperCase()
                                    )}
                                </button>
                            </Dropdown.Trigger>
                            <Dropdown.Content>
                                <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                                <Dropdown.Link href={route('logout')} method="post" as="button">
                                    Log Out
                                </Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </header>

                <main className="flex-1 px-4 py-6 lg:px-8">{children}</main>
            </div>

            <FlashToast />
        </div>
    );
}
