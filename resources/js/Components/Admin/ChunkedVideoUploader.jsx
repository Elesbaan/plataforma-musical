import { useEffect, useId, useRef, useState } from 'react';
import { CircleCheck, CircleX, Film, LoaderCircle, Upload } from 'lucide-react';
import Button from '@/Components/Button';
import { formatBytes, uuid } from '@/lib/uuid';

const MAX_RETRIES = 3;

function firstError(error) {
    const data = error?.response?.data;
    if (data?.errors) return Object.values(data.errors).flat()[0];
    if (data?.message && error.response.status !== 500) return data.message;
    if (!error?.response) return 'Se perdió la conexión. Revisa tu internet e inténtalo de nuevo.';
    return 'No se pudo subir el video. Inténtalo de nuevo.';
}

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Sube videos pesados en fragmentos (tamaño definido por el servidor) a
 * /admin/media/upload-chunk, con barra de progreso real, reintentos por
 * fragmento y opción de cancelar.
 *
 * @param {{ serviceId: number, limits: { chunkBytes: number, maxVideoBytes: number, videoExtensions: string[] }, onComplete?: (media: object) => void }} props
 */
export default function ChunkedVideoUploader({ serviceId, limits, onComplete }) {
    const inputId = useId();
    const [file, setFile] = useState(null);
    const [status, setStatus] = useState('idle'); // idle | uploading | done | error | cancelled
    const [progress, setProgress] = useState(0);
    const [message, setMessage] = useState('');
    const controllerRef = useRef(null);

    useEffect(() => () => controllerRef.current?.abort(), []);

    const accept = limits.videoExtensions.map((ext) => `.${ext}`).join(',');
    const uploading = status === 'uploading';

    const selectFile = (selected) => {
        setProgress(0);
        setMessage('');
        if (!selected) return;

        const extension = selected.name.split('.').pop()?.toLowerCase();
        if (!limits.videoExtensions.includes(extension)) {
            setFile(null);
            setStatus('error');
            setMessage(`Formato no permitido. Usa ${limits.videoExtensions.map((e) => e.toUpperCase()).join(', ')}.`);
            return;
        }
        if (selected.size > limits.maxVideoBytes) {
            setFile(null);
            setStatus('error');
            setMessage(`El video pesa ${formatBytes(selected.size)}; el máximo es ${formatBytes(limits.maxVideoBytes)}.`);
            return;
        }

        setFile(selected);
        setStatus('idle');
    };

    const upload = async () => {
        if (!file) return;

        const controller = new AbortController();
        controllerRef.current = controller;
        const uploadId = uuid();
        const totalChunks = Math.ceil(file.size / limits.chunkBytes);

        setStatus('uploading');
        setProgress(0);
        setMessage('');

        try {
            for (let index = 0; index < totalChunks; index++) {
                const start = index * limits.chunkBytes;
                const blob = file.slice(start, Math.min(start + limits.chunkBytes, file.size));

                for (let attempt = 1; ; attempt++) {
                    const form = new FormData();
                    form.append('upload_id', uploadId);
                    form.append('service_id', serviceId);
                    form.append('file_name', file.name);
                    form.append('total_size', file.size);
                    form.append('total_chunks', totalChunks);
                    form.append('chunk_index', index);
                    form.append('chunk', blob, `${file.name}.part`);

                    try {
                        const { data } = await window.axios.post('/admin/media/upload-chunk', form, {
                            signal: controller.signal,
                            headers: { Accept: 'application/json' },
                            onUploadProgress: (event) => {
                                const sent = start + Math.min(event.loaded, blob.size);
                                setProgress(Math.min(99, Math.round((sent / file.size) * 100)));
                            },
                        });

                        if (data.status === 'complete') {
                            setProgress(100);
                            setStatus('done');
                            setMessage(data.message);
                            setFile(null);
                            onComplete?.(data.media);
                        }
                        break;
                    } catch (error) {
                        if (controller.signal.aborted) throw error;
                        const status = error?.response?.status;
                        const retryable = !status || status >= 500 || status === 429;
                        if (!retryable || attempt >= MAX_RETRIES) throw error;
                        await wait(1000 * attempt);
                    }
                }
            }
        } catch (error) {
            if (controller.signal.aborted) {
                setStatus('cancelled');
                setMessage('Subida cancelada.');
            } else {
                setStatus('error');
                setMessage(firstError(error));
            }
        } finally {
            controllerRef.current = null;
        }
    };

    return (
        <div className="rounded-xl border border-dashed border-line p-4">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                <span className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                    <Film className="h-5 w-5" aria-hidden="true" />
                </span>
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold text-ink">Video en vivo desde tu equipo</p>
                    <p className="text-xs text-ink-muted">
                        {limits.videoExtensions.map((e) => e.toUpperCase()).join(', ')} de hasta {formatBytes(limits.maxVideoBytes)}. Se sube por partes,
                        así que puedes enviar archivos pesados.
                    </p>
                    {file && (
                        <p className="mt-1 truncate text-sm text-ink" title={file.name}>
                            {file.name} · {formatBytes(file.size)}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap gap-2">
                    {!uploading && (
                        <label
                            htmlFor={inputId}
                            className="inline-flex min-h-touch cursor-pointer items-center gap-2 rounded-full border border-line px-4 text-sm font-semibold text-ink hover:bg-surface-sunken focus-within:ring-2 focus-within:ring-primary/40"
                        >
                            Elegir video
                            <input
                                id={inputId}
                                type="file"
                                accept={accept}
                                className="sr-only"
                                onChange={(e) => {
                                    selectFile(e.target.files?.[0]);
                                    e.target.value = '';
                                }}
                            />
                        </label>
                    )}
                    {file && !uploading && (
                        <Button onClick={upload}>
                            <Upload className="h-4 w-4" aria-hidden="true" />
                            Subir video
                        </Button>
                    )}
                    {uploading && (
                        <Button variant="outline" onClick={() => controllerRef.current?.abort()}>
                            Cancelar
                        </Button>
                    )}
                </div>
            </div>

            {(uploading || status === 'done') && (
                <div className="mt-4">
                    <div className="mb-1 flex items-center justify-between text-xs text-ink-muted">
                        <span className="inline-flex items-center gap-1.5">
                            {uploading && <LoaderCircle className="h-3.5 w-3.5 animate-spin" aria-hidden="true" />}
                            {uploading ? 'Subiendo video…' : 'Completado'}
                        </span>
                        <span aria-hidden="true">{progress}%</span>
                    </div>
                    <div
                        role="progressbar"
                        aria-label="Progreso de la subida del video"
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={progress}
                        className="h-2.5 overflow-hidden rounded-full bg-surface-sunken"
                    >
                        <div className="h-full rounded-full bg-primary transition-[width] duration-200" style={{ width: `${progress}%` }} />
                    </div>
                </div>
            )}

            {message && status !== 'uploading' && (
                <p
                    role={status === 'error' ? 'alert' : 'status'}
                    className={`mt-3 flex items-center gap-2 text-sm ${
                        status === 'done' ? 'text-emerald-700 dark:text-emerald-400' : status === 'error' ? 'text-red-600 dark:text-red-400' : 'text-ink-muted'
                    }`}
                >
                    {status === 'done' ? <CircleCheck className="h-4 w-4" aria-hidden="true" /> : <CircleX className="h-4 w-4" aria-hidden="true" />}
                    {message}
                </p>
            )}
        </div>
    );
}
