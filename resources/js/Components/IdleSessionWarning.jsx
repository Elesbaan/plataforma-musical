import { useEffect, useRef } from 'react';
import { Clock } from 'lucide-react';
import Button from '@/Components/Button';

/** Aviso previo al cierre automático de sesión por inactividad. */
export default function IdleSessionWarning({ secondsLeft, onStay, onLogout }) {
    const stayRef = useRef(null);
    const visible = secondsLeft !== null;

    useEffect(() => {
        if (visible) stayRef.current?.focus();
    }, [visible]);

    if (!visible) return null;

    return (
        <div className="fixed inset-0 z-[70] flex items-end justify-center bg-black/50 p-4 sm:items-center">
            <div
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="idle-title"
                aria-describedby="idle-description"
                className="w-full max-w-sm rounded-2xl border border-line bg-surface-raised p-6 text-ink shadow-2xl"
            >
                <div className="mb-3 flex items-center gap-3">
                    <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-accent/15 text-accent">
                        <Clock className="h-5 w-5" aria-hidden="true" />
                    </span>
                    <h2 id="idle-title" className="text-lg font-semibold">
                        ¿Sigues ahí?
                    </h2>
                </div>
                <p id="idle-description" className="text-sm text-ink-muted" aria-live="polite">
                    Por seguridad, tu sesión se cerrará en <strong className="text-ink">{secondsLeft} segundos</strong> por
                    inactividad.
                </p>
                <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button variant="outline" onClick={onLogout}>
                        Cerrar sesión
                    </Button>
                    <Button ref={stayRef} onClick={onStay}>
                        Seguir conectado
                    </Button>
                </div>
            </div>
        </div>
    );
}
