import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-gradient-to-b from-brand-900 via-brand-800 to-brand-900 px-4">
            <div className="mb-6 flex items-center gap-2">
                <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-gold-400 text-sm font-bold text-brand-900">
                    CA
                </span>
                <span className="text-lg font-semibold text-white">CA-APOMS</span>
            </div>

            <div className="w-full overflow-hidden rounded-lg bg-white px-6 py-8 shadow-xl sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
