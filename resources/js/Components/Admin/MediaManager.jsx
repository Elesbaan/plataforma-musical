import { useId, useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ImagePlus, Link2, Star, Trash2, Video } from 'lucide-react';
import ChunkedVideoUploader from '@/Components/Admin/ChunkedVideoUploader';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { TextField } from '@/Components/Form/fields';

const TYPE_LABELS = { image: 'Foto', video_local: 'Video', video_embed: 'Video en línea' };

function MediaPreview({ item }) {
    if (item.media_type === 'image') {
        return <img src={item.url} alt={item.title ?? 'Foto del servicio'} loading="lazy" className="h-full w-full object-cover" />;
    }
    if (item.media_type === 'video_local') {
        return <video src={item.url} controls preload="metadata" className="h-full w-full bg-black object-contain" />;
    }
    return (
        <iframe
            src={item.url}
            title={item.title ?? 'Video del servicio'}
            loading="lazy"
            allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
            allowFullScreen
            className="h-full w-full bg-black"
        />
    );
}

function PhotoUploader({ serviceId, limits }) {
    const inputId = useId();
    const { post, processing, progress, errors, transform, clearErrors } = useForm({ images: [] });
    const imageErrors = Object.entries(errors).filter(([key]) => key.startsWith('images'));

    const submit = (files) => {
        clearErrors();
        transform(() => ({ images: files }));
        post(`/admin/servicios/${serviceId}/media/fotos`, { forceFormData: true, preserveScroll: true });
    };

    return (
        <div className="rounded-xl border border-dashed border-line p-4">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                <span className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <ImagePlus className="h-5 w-5" aria-hidden="true" />
                </span>
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-ink">Fotos de producciones reales</p>
                    <p className="text-xs text-ink-muted">
                        JPG, PNG o WebP de hasta {Math.round(limits.maxImageKb / 1024)} MB, hasta {limits.maxImagesPerUpload} a la vez. Se optimizan
                        automáticamente.
                    </p>
                </div>
                <label
                    htmlFor={inputId}
                    className={`inline-flex min-h-touch cursor-pointer items-center gap-2 rounded-full border border-line px-4 text-sm font-semibold text-ink hover:bg-surface-sunken focus-within:ring-2 focus-within:ring-primary/40 ${
                        processing ? 'pointer-events-none opacity-60' : ''
                    }`}
                >
                    {processing ? 'Subiendo…' : 'Elegir fotos'}
                    <input
                        id={inputId}
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/webp"
                        className="sr-only"
                        disabled={processing}
                        onChange={(e) => {
                            const files = [...(e.target.files ?? [])];
                            e.target.value = '';
                            if (files.length) submit(files);
                        }}
                    />
                </label>
            </div>
            {processing && progress && (
                <div
                    role="progressbar"
                    aria-label="Progreso de la subida de fotos"
                    aria-valuemin={0}
                    aria-valuemax={100}
                    aria-valuenow={Math.round(progress.percentage ?? 0)}
                    className="mt-4 h-2.5 overflow-hidden rounded-full bg-surface-sunken"
                >
                    <div className="h-full rounded-full bg-primary transition-[width]" style={{ width: `${progress.percentage ?? 0}%` }} />
                </div>
            )}
            {!processing && imageErrors.length > 0 && (
                <ul className="mt-3 space-y-1 text-sm text-red-600 dark:text-red-400" role="alert">
                    {[...new Set(imageErrors.map(([, message]) => message))].map((message) => (
                        <li key={message}>{message}</li>
                    ))}
                </ul>
            )}
        </div>
    );
}

function EmbedForm({ serviceId }) {
    const { data, setData, post, processing, errors, reset } = useForm({ embed_url: '', title: '' });

    const submit = (event) => {
        event.preventDefault();
        post(`/admin/servicios/${serviceId}/media/video`, { preserveScroll: true, onSuccess: () => reset() });
    };

    return (
        <form onSubmit={submit} className="rounded-xl border border-dashed border-line p-4" noValidate>
            <div className="mb-4 flex items-center gap-4">
                <span className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <Video className="h-5 w-5" aria-hidden="true" />
                </span>
                <div>
                    <p className="text-sm font-semibold text-ink">Video de YouTube o Vimeo</p>
                    <p className="text-xs text-ink-muted">Pega el enlace del video; no consume espacio de tu hosting.</p>
                </div>
            </div>
            <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_auto] md:items-end">
                <TextField
                    label="Enlace"
                    type="url"
                    placeholder="https://www.youtube.com/watch?v=…"
                    value={data.embed_url}
                    onChange={(value) => setData('embed_url', value)}
                    error={errors.embed_url}
                />
                <TextField label="Título (opcional)" maxLength={255} value={data.title} onChange={(value) => setData('title', value)} error={errors.title} />
                <Button type="submit" disabled={processing || !data.embed_url} className={errors.embed_url || errors.title ? 'md:mb-7' : ''}>
                    <Link2 className="h-4 w-4" aria-hidden="true" />
                    Agregar
                </Button>
            </div>
        </form>
    );
}

/** Galería multimedia de un servicio. */
export default function MediaManager({ service, limits }) {
    const [deleting, setDeleting] = useState(null);
    const [processing, setProcessing] = useState(false);
    const media = service.media ?? [];

    const patch = (item, payload) => router.patch(`/admin/media/${item.id}`, payload, { preserveScroll: true });

    const confirmDelete = () => {
        router.delete(`/admin/media/${deleting.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <Card title="Galería del servicio" description="Fotos y videos en vivo que verán tus clientes. La foto marcada como portada encabeza el servicio.">
            <div className="grid gap-4 lg:grid-cols-3">
                <PhotoUploader serviceId={service.id} limits={limits} />
                <div className="lg:col-span-2">
                    <ChunkedVideoUploader
                        serviceId={service.id}
                        limits={limits}
                        onComplete={() => router.reload({ only: ['service'], preserveScroll: true })}
                    />
                </div>
                <div className="lg:col-span-3">
                    <EmbedForm serviceId={service.id} />
                </div>
            </div>

            {media.length === 0 ? (
                <p className="mt-6 rounded-xl bg-surface-sunken p-6 text-center text-sm text-ink-muted">Este servicio aún no tiene fotos ni videos.</p>
            ) : (
                <ul className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {media.map((item, index) => (
                        <li key={item.id} className="overflow-hidden rounded-xl border border-line bg-surface">
                            <div className="relative aspect-video bg-surface-sunken">
                                <MediaPreview item={item} />
                                <div className="pointer-events-none absolute left-2 top-2 flex gap-1">
                                    <Badge tone="neutral" className="bg-black/60 text-white">
                                        {TYPE_LABELS[item.media_type]}
                                    </Badge>
                                    {item.is_cover && (
                                        <Badge tone="brand" className="bg-accent text-secondary">
                                            <Star className="h-3 w-3" aria-hidden="true" />
                                            Portada
                                        </Badge>
                                    )}
                                </div>
                            </div>
                            <div className="flex items-center gap-1 p-2">
                                <p className="min-w-0 flex-1 truncate px-2 text-sm text-ink" title={item.title ?? ''}>
                                    {item.title || <span className="text-ink-muted">Sin título</span>}
                                </p>
                                {item.media_type === 'image' && !item.is_cover && (
                                    <Button variant="ghost" size="sm" onClick={() => patch(item, { is_cover: true })} aria-label="Usar como portada" title="Usar como portada">
                                        <Star className="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                )}
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => patch(item, { move: 'up' })}
                                    disabled={index === 0}
                                    aria-label="Mover antes"
                                    title="Mover antes"
                                >
                                    <ArrowUp className="h-4 w-4" aria-hidden="true" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => patch(item, { move: 'down' })}
                                    disabled={index === media.length - 1}
                                    aria-label="Mover después"
                                    title="Mover después"
                                >
                                    <ArrowDown className="h-4 w-4" aria-hidden="true" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setDeleting(item)}
                                    aria-label="Eliminar de la galería"
                                    title="Eliminar"
                                    className="text-red-600 dark:text-red-400"
                                >
                                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <ConfirmDialog
                open={!!deleting}
                title="Eliminar de la galería"
                message="¿Seguro que quieres eliminar este archivo? Si es una foto o video subido, también se borrará del servidor."
                processing={processing}
                onConfirm={confirmDelete}
                onCancel={() => setDeleting(null)}
            />
        </Card>
    );
}
