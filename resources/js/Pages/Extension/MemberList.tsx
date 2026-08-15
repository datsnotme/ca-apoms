import { FormEventHandler } from 'react';
import { router, useForm } from '@inertiajs/react';
import Badge from '@/Components/ui/Badge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

interface MemberRow {
    id: number;
    is_lead: boolean;
    user: { id: number; name: string };
    can_delete: boolean;
}

export default function MemberList({
    projectId,
    members,
    memberOptions,
    canManage,
}: {
    projectId: number;
    members: MemberRow[];
    memberOptions: { id: number; name: string }[];
    canManage: boolean;
}) {
    const { data, setData, post, processing, reset } = useForm<{ user_id: string }>({ user_id: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('extension-projects.members.store', projectId), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="flex flex-col gap-4">
            {members.length === 0 ? (
                <p className="text-sm text-slate-900">No members yet.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="bg-slate-50 text-left text-xs uppercase text-slate-900">
                            <tr>
                                <th className="px-3 py-2">Member</th>
                                <th className="px-3 py-2">Role</th>
                                {canManage && <th className="px-3 py-2 text-right">Actions</th>}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {members.map((member) => (
                                <tr key={member.id}>
                                    <td className="px-3 py-2">{member.user.name}</td>
                                    <td className="px-3 py-2">
                                        <Badge variant={member.is_lead ? 'success' : 'neutral'}>{member.is_lead ? 'Lead' : 'Member'}</Badge>
                                    </td>
                                    {canManage && (
                                        <td className="px-3 py-2 text-right">
                                            {member.can_delete && !member.is_lead && (
                                                <SecondaryButton
                                                    type="button"
                                                    onClick={() =>
                                                        router.delete(route('extension-projects.members.destroy', [projectId, member.id]), {
                                                            preserveScroll: true,
                                                        })
                                                    }
                                                >
                                                    Remove
                                                </SecondaryButton>
                                            )}
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {canManage && (
                <form onSubmit={submit} className="flex flex-wrap items-end gap-3 rounded-md border border-slate-200 p-4">
                    <div className="min-w-[16rem]">
                        <select
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-600 focus:ring-brand-600"
                            value={data.user_id}
                            onChange={(e) => setData('user_id', e.target.value)}
                            required
                        >
                            <option value="">Select a member to add…</option>
                            {memberOptions.map((u) => (
                                <option key={u.id} value={u.id}>
                                    {u.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <PrimaryButton disabled={processing || !data.user_id}>Add Member</PrimaryButton>
                </form>
            )}
        </div>
    );
}
