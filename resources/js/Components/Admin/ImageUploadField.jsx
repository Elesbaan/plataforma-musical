import { useEffect, useId, useState } from 'react';
import { ImageIcon, Trash2, Upload } from 'lucide-react';

/**
 * Selector de imagen con vista previa. Informa el archivo elegido (`onSelect`)
 * o la intención de quitar la imagen actual (`onRemove`).
 */
export default function ImageUploadField({
    label,
    hint,
    error,
    currentUrl,
    file,
    removed,
    accept = 'image/png,image/jpeg,image/webp',
    previewClassName = 'bg-surface-sunken',
    onSelect,
    onRemove,
}) {
    const id = useId();
    const [previewUrl, setPreviewUrl] = useState(null);

    useEffect(() => {
        if (!file) {
            setPreviewUrl(null);
            return undefined;
        }
        const url = URL.createObjectURL(file);
        setPreviewUrl(url);
        return () => URL.revokeObjectURL(url);
    }, [file]);

    const shownUrl = previewUrl ?? (removed ? null : currentUrl);

    return (
        <div>
            <p id={`${id}-label`} className="mb-1.5 text-sm font-medium text-ink">
                {label}
            </p>
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div
                    className={`flex h-24 w-full items-center justify-center overflow-hidden rounded-xl border border-dashed border-line p-3 sm:w-48 ${previewClassName}`}
                >
                    {shownUrl ? (
                        <img src={shownUrl} alt={`Vista previa: ${label}`} className="max-h-full max-w-full object-contain" />
                    ) : (
                        <ImageIcon className="h-8 w-8 text-ink-muted" aria-hidden="true" />
                    )}
                </div>
                <div className="flex flex-wrap gap-2">
                    <label
                        htmlFor={id}
                        className="inline-flex min-h-touch cursor-pointer items-center gap-2 rounded-full border border-line px-4 text-sm font-semibold text-ink hover:bg-surface-sunken focus-within:ring-2 focus-within:ring-primary/40"
                    >
                        <Upload className="h-4 w-4" aria-hidden="true" />
                        {shownUrl ? 'Cambiar' : 'Subir'}
                        <input
                            id={id}
                            type="file"
                            accept={accept}
                            className="sr-only"
                            aria-labelledby={`${id}-label`}
                            aria-describedby={hint ? `${id}-hint` : undefined}
                            onChange={(e) => {
                                const selected = e.target.files?.[0];
                                if (selected) onSelect(selected);
                                e.target.value = '';
                            }}
                        />
                    </label>
                    {shownUrl && (
                        <button
                            type="button"
                            onClick={onRemove}
                            className="inline-flex min-h-touch items-center gap-2 rounded-full px-4 text-sm font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950"
                        >
                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                            Quitar
                        </button>
                    )}
                </div>
            </div>
            {hint && !error && (
                <p id={`${id}-hint`} className="mt-1.5 text-xs text-ink-muted">
                    {hint}
                </p>
            )}
            {error && <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">{error}</p>}
        </div>
    );
}
