import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { PageProps } from '@/types';

export default function FlashToast() {
    const { flash } = usePage<PageProps>().props;
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        setVisible(true);
        const timer = setTimeout(() => setVisible(false), 4000);
        return () => clearTimeout(timer);
    }, [flash.success, flash.error]);

    if (!visible || (!flash.success && !flash.error)) {
        return null;
    }

    const isError = Boolean(flash.error);

    return (
        <div
            role="status"
            className={`fixed bottom-6 right-6 z-50 rounded-lg px-4 py-3 text-sm font-medium shadow-lg ${
                isError ? 'bg-red-600 text-white' : 'bg-brand-700 text-white'
            }`}
        >
            {flash.error ?? flash.success}
        </div>
    );
}
