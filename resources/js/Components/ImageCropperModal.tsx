import { PointerEvent as ReactPointerEvent, useEffect, useRef, useState } from 'react';
import Modal from './Modal';
import PrimaryButton from './PrimaryButton';
import SecondaryButton from './SecondaryButton';

const VIEWPORT_SIZE = 288;
const OUTPUT_SIZE = 512;
const MIN_ZOOM = 1;
const MAX_ZOOM = 3;

interface Rect {
    left: number;
    top: number;
}

/**
 * A dependency-free crop tool: drag to reposition, slider to zoom, always
 * covering the square viewport (like CSS object-fit: cover) so the user can
 * never leave blank space inside the crop circle. Exports a square image —
 * the circle is just a visual guide for how it'll render as an avatar
 * (AppLayout/UpdateProfilePhotoForm both display it with rounded-full).
 */
export default function ImageCropperModal({
    show,
    imageSrc,
    onCancel,
    onCropped,
}: {
    show: boolean;
    imageSrc: string | null;
    onCancel: () => void;
    onCropped: (blob: Blob) => void;
}) {
    const imgRef = useRef<HTMLImageElement>(null);
    const [naturalSize, setNaturalSize] = useState<{ w: number; h: number } | null>(null);
    const [zoom, setZoom] = useState(MIN_ZOOM);
    const [pos, setPos] = useState<Rect>({ left: 0, top: 0 });
    const dragRef = useRef<{ startX: number; startY: number; origin: Rect } | null>(null);
    const [saving, setSaving] = useState(false);

    // Reset crop state whenever a new image is loaded into the modal.
    useEffect(() => {
        if (!show) return;
        setZoom(MIN_ZOOM);
        setNaturalSize(null);
        setPos({ left: 0, top: 0 });
    }, [show, imageSrc]);

    function coverScale(w: number, h: number): number {
        return Math.max(VIEWPORT_SIZE / w, VIEWPORT_SIZE / h);
    }

    function clamp(rect: Rect, w: number, h: number, z: number): Rect {
        const scale = coverScale(w, h) * z;
        const renderedW = w * scale;
        const renderedH = h * scale;

        return {
            left: Math.min(0, Math.max(VIEWPORT_SIZE - renderedW, rect.left)),
            top: Math.min(0, Math.max(VIEWPORT_SIZE - renderedH, rect.top)),
        };
    }

    function onImageLoad() {
        const img = imgRef.current;
        if (!img) return;

        const w = img.naturalWidth;
        const h = img.naturalHeight;
        setNaturalSize({ w, h });

        const scale = coverScale(w, h);
        setPos({
            left: (VIEWPORT_SIZE - w * scale) / 2,
            top: (VIEWPORT_SIZE - h * scale) / 2,
        });
    }

    function onZoomChange(next: number) {
        setZoom(next);
        if (naturalSize) {
            setPos((prev) => clamp(prev, naturalSize.w, naturalSize.h, next));
        }
    }

    function onPointerDown(e: ReactPointerEvent<HTMLDivElement>) {
        e.currentTarget.setPointerCapture(e.pointerId);
        dragRef.current = { startX: e.clientX, startY: e.clientY, origin: pos };
    }

    function onPointerMove(e: ReactPointerEvent<HTMLDivElement>) {
        if (!dragRef.current || !naturalSize) return;

        const dx = e.clientX - dragRef.current.startX;
        const dy = e.clientY - dragRef.current.startY;
        const next = { left: dragRef.current.origin.left + dx, top: dragRef.current.origin.top + dy };

        setPos(clamp(next, naturalSize.w, naturalSize.h, zoom));
    }

    function onPointerUp(e: ReactPointerEvent<HTMLDivElement>) {
        e.currentTarget.releasePointerCapture(e.pointerId);
        dragRef.current = null;
    }

    function save() {
        const img = imgRef.current;
        if (!img || !naturalSize) return;

        const scale = coverScale(naturalSize.w, naturalSize.h) * zoom;
        const sourceX = -pos.left / scale;
        const sourceY = -pos.top / scale;
        const sourceSize = VIEWPORT_SIZE / scale;

        const canvas = document.createElement('canvas');
        canvas.width = OUTPUT_SIZE;
        canvas.height = OUTPUT_SIZE;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        ctx.drawImage(img, sourceX, sourceY, sourceSize, sourceSize, 0, 0, OUTPUT_SIZE, OUTPUT_SIZE);

        setSaving(true);
        canvas.toBlob(
            (blob) => {
                setSaving(false);
                if (blob) onCropped(blob);
            },
            'image/jpeg',
            0.92,
        );
    }

    const scale = naturalSize ? coverScale(naturalSize.w, naturalSize.h) * zoom : 1;

    return (
        <Modal show={show} onClose={onCancel} maxWidth="sm">
            <div className="p-6">
                <h2 className="text-lg font-medium text-slate-900">Adjust Photo</h2>
                <p className="mt-1 text-sm text-slate-500">
                    Drag to reposition, and use the slider to zoom in or out.
                </p>

                <div
                    className="relative mx-auto mt-4 touch-none select-none overflow-hidden rounded-md bg-slate-900"
                    style={{ width: VIEWPORT_SIZE, height: VIEWPORT_SIZE }}
                    onPointerDown={onPointerDown}
                    onPointerMove={onPointerMove}
                    onPointerUp={onPointerUp}
                >
                    {imageSrc && (
                        <img
                            ref={imgRef}
                            src={imageSrc}
                            onLoad={onImageLoad}
                            draggable={false}
                            alt=""
                            className="absolute max-w-none"
                            style={
                                naturalSize
                                    ? {
                                          left: pos.left,
                                          top: pos.top,
                                          width: naturalSize.w * scale,
                                          height: naturalSize.h * scale,
                                      }
                                    : { opacity: 0 }
                            }
                        />
                    )}

                    <div
                        className="pointer-events-none absolute inset-0 rounded-full ring-1 ring-inset ring-white/80"
                        style={{ boxShadow: '0 0 0 9999px rgba(15, 23, 42, 0.55)' }}
                    />
                </div>

                <div className="mt-4 flex items-center gap-3">
                    <label htmlFor="crop-zoom" className="text-sm text-slate-500">
                        Zoom
                    </label>
                    <input
                        id="crop-zoom"
                        type="range"
                        min={MIN_ZOOM}
                        max={MAX_ZOOM}
                        step={0.01}
                        value={zoom}
                        onChange={(e) => onZoomChange(Number(e.target.value))}
                        disabled={!naturalSize}
                        className="flex-1 accent-brand-700"
                    />
                </div>

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" onClick={onCancel}>
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="button" onClick={save} disabled={!naturalSize || saving}>
                        {saving ? 'Saving…' : 'Save'}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    );
}
