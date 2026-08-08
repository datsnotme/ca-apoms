import { useState } from 'react';
import { router } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

/**
 * The bulk counterpart to ConfirmDeleteButton — same confirm-modal pattern,
 * operating on a set of selected ids via a single request instead of one
 * href per row. Renders nothing when nothing is selected.
 */
export default function BulkDeleteBar({
    href,
    ids,
    itemLabelPlural,
    description = 'This archives the selected records. They will no longer appear in active lists, but are not permanently erased.',
    onDeleted,
}: {
    href: string;
    ids: number[];
    itemLabelPlural: string;
    description?: string;
    onDeleted: () => void;
}) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    if (ids.length === 0) {
        return null;
    }

    function confirmDelete() {
        setProcessing(true);
        router.delete(href, {
            data: { ids },
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
                onDeleted();
            },
        });
    }

    return (
        <div className="flex items-center gap-4 border-b border-slate-200 bg-brand-50 px-5 py-2.5">
            <p className="text-sm font-medium text-slate-700">
                {ids.length} {ids.length === 1 ? 'row' : 'rows'} selected
            </p>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="text-sm font-medium text-red-600 hover:text-red-800"
            >
                Delete Selected
            </button>

            <Modal show={open} onClose={() => setOpen(false)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-medium text-slate-900">
                        Delete {ids.length} {itemLabelPlural}?
                    </h2>
                    <p className="mt-2 text-sm text-slate-500">{description}</p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setOpen(false)}>Cancel</SecondaryButton>
                        <DangerButton onClick={confirmDelete} disabled={processing}>
                            {processing ? 'Deleting…' : 'Delete'}
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </div>
    );
}
