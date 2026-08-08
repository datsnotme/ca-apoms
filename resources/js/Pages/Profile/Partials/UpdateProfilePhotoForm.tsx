import { ChangeEvent, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import ImageCropperModal from '@/Components/ImageCropperModal';
import InputError from '@/Components/InputError';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { PageProps } from '@/types';

export default function UpdateProfilePhotoForm({
    photoUrl,
    name,
}: {
    photoUrl: string | null;
    name: string;
}) {
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

        const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
        const objectUrl = URL.createObjectURL(file);
        setPreview(objectUrl);
        setUploading(true);

        router.post(
            route('profile.photo.update'),
            { photo: file },
            {
                preserveScroll: true,
                onError: () => setPreview(null),
                onFinish: () => setUploading(false),
            },
        );
    }

    function removePhoto() {
        setRemoving(true);
        router.delete(route('profile.photo.destroy'), {
            preserveScroll: true,
            onFinish: () => {
                setRemoving(false);
                setPreview(null);
            },
        });
    }

    const displayUrl = preview ?? photoUrl;

    return (
        <section>
            <header>
                <h2 className="text-lg font-medium text-slate-900">Profile Photo</h2>
                <p className="mt-1 text-sm text-slate-500">
                    JPG or PNG, up to 2 MB. You'll be able to crop and zoom before it's saved.
                    Visible to anyone who can see your name elsewhere in the system.
                </p>
            </header>

            <div className="mt-4 flex items-center gap-5">
                {displayUrl ? (
                    <img
                        src={displayUrl}
                        alt={`${name}'s profile photo`}
                        className="h-20 w-20 rounded-full object-cover"
                    />
                ) : (
                    <div
                        aria-hidden="true"
                        className="flex h-20 w-20 items-center justify-center rounded-full bg-brand-700 text-2xl font-semibold text-white"
                    >
                        {name.charAt(0).toUpperCase()}
                    </div>
                )}

                <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-3">
                        <SecondaryButton
                            type="button"
                            disabled={uploading}
                            onClick={() => fileInputRef.current?.click()}
                        >
                            {uploading ? 'Uploading…' : photoUrl ? 'Change Photo' : 'Upload Photo'}
                        </SecondaryButton>

                        {photoUrl && (
                            <DangerButton type="button" disabled={removing} onClick={removePhoto}>
                                {removing ? 'Removing…' : 'Remove'}
                            </DangerButton>
                        )}
                    </div>

                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/png,image/jpeg"
                        className="sr-only"
                        aria-label="Upload profile photo"
                        onChange={onFileChange}
                    />

                    <InputError message={errors.photo} />
                </div>
            </div>

            <ImageCropperModal
                show={cropSrc !== null}
                imageSrc={cropSrc}
                onCancel={cancelCrop}
                onCropped={uploadCropped}
            />
        </section>
    );
}
