import { ChangeEvent, useRef, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader } from '@/Components/ui/Card';
import ImageCropperModal from '@/Components/ImageCropperModal';
import InputError from '@/Components/InputError';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { PageProps } from '@/types';

export default function Edit({ logoUrl }: { logoUrl: string | null }) {
    const { errors } = usePage<PageProps>().props;
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [uploading, setUploading] = useState(false);
    const [removing, setRemoving] = useState(false);
    const [cropSrc, setCropSrc] = useState<string | null>(null);

    function onFileChange(e: ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = () => setCropSrc(reader.result as string);
        reader.readAsDataURL(file);
    }

    function resetFileInput() {
        if (fileInputRef.current) fileInputRef.current.value = '';
    }

    function cancelCrop() {
        setCropSrc(null);
        resetFileInput();
    }

    function uploadCropped(blob: Blob) {
        setCropSrc(null);
        resetFileInput();

        const file = new File([blob], 'logo.jpg', { type: 'image/jpeg' });
        const objectUrl = URL.createObjectURL(file);
        setPreview(objectUrl);
        setUploading(true);

        router.post(
            route('branding.update'),
            { logo: file },
            {
                preserveScroll: true,
                onError: () => setPreview(null),
                onFinish: () => setUploading(false),
            },
        );
    }

    function removeLogo() {
        setRemoving(true);
        router.delete(route('branding.destroy'), {
            preserveScroll: true,
            onFinish: () => {
                setRemoving(false);
                setPreview(null);
            },
        });
    }

    const displayUrl = preview ?? logoUrl;

    return (
        <AppLayout header={<h1 className="text-lg font-semibold text-slate-900">System Logo</h1>}>
            <Head title="System Logo" />

            <Card>
                <CardHeader
                    title="System Logo"
                    description="Shown in the sidebar and on the login page, in place of the default badge."
                />
                <CardContent>
                    <div className="flex items-center gap-5">
                        {displayUrl ? (
                            <img
                                src={displayUrl}
                                alt="System logo"
                                className="h-20 w-20 rounded-lg object-cover ring-1 ring-slate-200"
                            />
                        ) : (
                            <div
                                aria-hidden="true"
                                className="flex h-20 w-20 items-center justify-center rounded-lg bg-brand-900 text-2xl font-bold text-gold-400"
                            >
                                CA
                            </div>
                        )}

                        <div className="flex flex-col gap-2">
                            <div className="flex items-center gap-3">
                                <SecondaryButton
                                    type="button"
                                    disabled={uploading}
                                    onClick={() => fileInputRef.current?.click()}
                                >
                                    {uploading ? 'Uploading…' : logoUrl ? 'Change Logo' : 'Upload Logo'}
                                </SecondaryButton>

                                {logoUrl && (
                                    <DangerButton type="button" disabled={removing} onClick={removeLogo}>
                                        {removing ? 'Removing…' : 'Remove'}
                                    </DangerButton>
                                )}
                            </div>

                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/png,image/jpeg"
                                className="sr-only"
                                aria-label="Upload system logo"
                                onChange={onFileChange}
                            />

                            <p className="text-sm text-slate-900">
                                JPG or PNG, up to 2 MB. You'll be able to crop and zoom before it's saved.
                            </p>

                            <InputError message={errors.logo} />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <ImageCropperModal
                show={cropSrc !== null}
                imageSrc={cropSrc}
                shape="square"
                onCancel={cancelCrop}
                onCropped={uploadCropped}
            />
        </AppLayout>
    );
}
