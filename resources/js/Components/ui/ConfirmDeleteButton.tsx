import { useState } from 'react';
import { router } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';

export default function ConfirmDeleteButton({
    href,
    itemLabel,
    label = 'Delete',
}: {
    href: string;
    itemLabel: string;
    label?: string;
}) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    function confirmDelete() {
        setProcessing(true);
        router.delete(href, {
            preserveScroll: true,
            onFinish: () => {
                setProcessing(false);
                setOpen(false);
            },
        });
    }

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="text-sm font-medium text-red-600 hover:text-red-800"
            >
                {label}
            </button>

            <Modal show={open} onClose={() => setOpen(false)} maxWidth="md">
                <div className="p-6">
                    <h2 className="text-lg font-medium text-slate-900">
                        Delete {itemLabel}?
                    </h2>
                    <p className="mt-2 text-sm text-slate-500">
                        This archives the record. It will no longer appear in active lists,
                        but is not permanently erased.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setOpen(false)}>
                            Cancel
                        </SecondaryButton>
                        <DangerButton onClick={confirmDelete} disabled={processing}>
                            {processing ? 'Deleting...' : 'Delete'}
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </>
    );
}
