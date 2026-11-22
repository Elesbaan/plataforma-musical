import { useEffect, useId, useRef } from 'react';
import { X } from 'lucide-react';

const SIZES = { sm: 'sm:max-w-md', md: 'sm:max-w-xl', lg: 'sm:max-w-3xl' };

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Diálogo modal accesible: atrapa el foco, cierra con Esc y devuelve el foco
 * al elemento que lo abrió. En móvil se muestra como hoja inferior.
 */
export default function Modal({ open, onClose, title, description, size = 'md', footer, children }) {
    const titleId = useId();
    const descriptionId = useId();
    const panelRef = useRef(null);
    const onCloseRef = useRef(onClose);
    onCloseRef.current = onClose;

    useEffect(() => {
        if (!open) return undefined;

        const previouslyFocused = document.activeElement;
        const panel = panelRef.current;
        (panel?.querySelector('[data-autofocus]') ?? panel?.querySelector(FOCUSABLE))?.focus();
        document.body.style.overflow = 'hidden';

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.stopPropagation();
                onCloseRef.current?.();
                return;
            }
            if (event.key !== 'Tab' || !panel) return;
            const items = [...panel.querySelectorAll(FOCUSABLE)];
            if (items.length === 0) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        };

        document.addEventListener('keydown', onKeyDown);
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = '';
            previouslyFocused?.focus?.();
        };
    }, [open]);

    if (!open) return null;

    return (
        <div className="fixed inset-0 z-[60] flex items-end justify-center sm:items-center sm:p-4">
            <div className="absolute inset-0 bg-black/50" aria-hidden="true" onClick={onClose} />
            <div
                ref={panelRef}
                role="dialog"
                aria-modal="true"
                aria-labelledby={titleId}
                aria-describedby={description ? descriptionId : undefined}
                className={`relative flex max-h-[92dvh] w-full flex-col rounded-t-2xl border border-line bg-surface-raised text-ink shadow-2xl sm:rounded-2xl ${SIZES[size]}`}
            >
                <div className="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                    <div className="min-w-0">
                        <h2 id={titleId} className="text-lg font-semibold">
                            {title}
                        </h2>
                        {description && (
                            <p id={descriptionId} className="mt-1 text-sm text-ink-muted">
                                {description}
                            </p>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Cerrar"
                        className="-mr-2 inline-flex min-h-touch min-w-touch shrink-0 items-center justify-center rounded-full text-ink-muted hover:bg-surface-sunken hover:text-ink"
                    >
                        <X className="h-5 w-5" aria-hidden="true" />
                    </button>
                </div>
                <div className="flex-1 overflow-y-auto px-5 py-5">{children}</div>
                {footer && (
                    <div className="flex flex-col-reverse gap-2 border-t border-line px-5 py-4 sm:flex-row sm:justify-end">{footer}</div>
                )}
            </div>
        </div>
    );
}
