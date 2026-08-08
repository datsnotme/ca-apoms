import { Head, Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader } from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import EmptyState from '@/Components/ui/EmptyState';
import Pagination from '@/Components/ui/Pagination';
import ConfirmDeleteButton from '@/Components/ui/ConfirmDeleteButton';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Paginated } from '@/types';

interface UserRow {
    id: number;
    name: string;
    email: string;
    employee_number: string;
    status: 'active' | 'inactive';
    department: { name: string } | null;
    roles: { name: string }[];
}

const ROLE_LABELS: Record<string, string> = {
    'college-administrator': 'College Administrator',
    'college-dean': 'College Dean',
    'department-head': 'Department Head',
    'faculty-member': 'Faculty Member',
};

export default function Index({
    users,
    filters,
    showArchived,
}: {
    users: Paginated<UserRow>;
    filters: { search?: string };
    showArchived: boolean;
}) {
    const [search, setSearch] = useState(filters.search ?? '');

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('users.index'), { search, archived: showArchived ? 1 : undefined }, { preserveState: true });
    };

    function toggleArchived() {
        router.get(route('users.index'), { archived: showArchived ? undefined : 1, search }, { preserveState: true });
    }

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">User Management</h1>}>
            <Head title="User Management" />

            <Card>
                <CardHeader
                    title={showArchived ? 'Archived Users' : 'System Users'}
                    description="Staff accounts across all roles."
                    actions={
                        <div className="flex items-center gap-2">
                            <SecondaryButton onClick={toggleArchived}>
                                {showArchived ? 'View Active' : 'View Archived'}
                            </SecondaryButton>
                            {!showArchived && (
                                <Link href={route('users.create')}>
                                    <PrimaryButton>Add User</PrimaryButton>
                                </Link>
                            )}
                        </div>
                    }
                />

                <div className="border-b border-slate-200 px-5 py-3">
                    <form onSubmit={submitSearch} className="max-w-sm">
                        <TextInput
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by name, email, or employee no..."
                            className="w-full"
                        />
                    </form>
                </div>

                {users.data.length === 0 ? (
                    <EmptyState title={showArchived ? 'No archived users' : 'No users found'} />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-5 py-2.5">Employee No.</th>
                                    <th className="px-5 py-2.5">Name</th>
                                    <th className="px-5 py-2.5">Role</th>
                                    <th className="px-5 py-2.5">Department</th>
                                    <th className="px-5 py-2.5">Status</th>
                                    <th className="px-5 py-2.5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {users.data.map((u) => (
                                    <tr key={u.id} className="hover:bg-slate-50">
                                        <td className="px-5 py-2.5 font-mono text-xs">{u.employee_number}</td>
                                        <td className="px-5 py-2.5">
                                            <div>{u.name}</div>
                                            <div className="text-xs text-slate-400">{u.email}</div>
                                        </td>
                                        <td className="px-5 py-2.5">
                                            {u.roles[0] ? ROLE_LABELS[u.roles[0].name] ?? u.roles[0].name : '—'}
                                        </td>
                                        <td className="px-5 py-2.5">{u.department?.name ?? '—'}</td>
                                        <td className="px-5 py-2.5">
                                            <Badge variant={u.status === 'active' ? 'success' : 'neutral'}>
                                                {u.status === 'active' ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </td>
                                        <td className="px-5 py-2.5 text-right">
                                            <div className="flex justify-end gap-3">
                                                {showArchived ? (
                                                    <Link
                                                        href={route('users.reactivate', u.id)}
                                                        method="patch"
                                                        as="button"
                                                        className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                    >
                                                        Restore
                                                    </Link>
                                                ) : (
                                                    <>
                                                        <Link
                                                            href={route('users.edit', u.id)}
                                                            className="text-sm font-medium text-brand-700 hover:text-brand-900"
                                                        >
                                                            Edit
                                                        </Link>
                                                        <ConfirmDeleteButton
                                                            href={route('users.destroy', u.id)}
                                                            itemLabel={u.name}
                                                        />
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <Pagination links={users.links} from={users.from} to={users.to} total={users.total} />
            </Card>
        </AppLayout>
    );
}
