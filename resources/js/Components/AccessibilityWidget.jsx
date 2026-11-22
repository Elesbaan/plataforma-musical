import { useEffect, useId, useRef, useState } from 'react';
import { Accessibility, Moon, RotateCcw, Sun, X } from 'lucide-react';
import {
    FONT_SCALES,
    TOGGLES,
    applyPreferences,
    clearPreferences,
    defaultPreferences,
    loadPreferences,
    resolvedTheme,
    savePreferences,
} from '@/lib/accessibility';

/**
 * Panel flotante de accesibilidad (esquina inferior izquierda).
 * La esquina inferior derecha queda reservada para el botón de WhatsApp.
 */
export default function AccessibilityWidget() {
    const [open, setOpen] = useState(false);
    const [prefs, setPrefs] = useState(defaultPreferences);
    const panelId = useId();
    const triggerRef = useRef(null);
    const panelRef = useRef(null);

    useEffect(() => {
        setPrefs(loadPreferences());
    }, []);

    useEffect(() => {
        if (!open) return undefined;

        panelRef.current?.querySelector('button')?.focus();

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setOpen(false);
                triggerRef.current?.focus();
            }
        };
        const onPointerDown = (event) => {
            if (!panelRef.current?.contains(event.target) && !triggerRef.current?.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('keydown', onKeyDown);
        document.addEventListener('pointerdown', onPointerDown);
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.removeEventListener('pointerdown', onPointerDown);
        };
    }, [open]);

    const update = (changes) => {
        setPrefs((prev) => {
            const next = { ...prev, ...changes };
            applyPreferences(next);
            savePreferences(next);
            return next;
        });
    };

    const reset = () => {
        const next = defaultPreferences();
        clearPreferences();
        applyPreferences(next);
        setPrefs(next);
    };

    const theme = resolvedTheme(prefs);

    return (
        <div className="fixed bottom-4 left-4 z-50 sm:bottom-6 sm:left-6">
            {open && (
                <div
                    ref={panelRef}
                    id={panelId}
                    role="dialog"
                    aria-modal="false"
                    aria-label="Opciones de accesibilidad"
                    className="absolute bottom-16 left-0 max-h-[calc(100dvh-6rem)] w-[min(20rem,calc(100vw-2rem))] overflow-y-auto rounded-2xl border border-line bg-surface-raised p-4 text-ink shadow-2xl"
                >
                    <div className="mb-4 flex items-center justify-between gap-2">
                        <h2 className="text-base font-semibold">Accesibilidad</h2>
                        <button
                            type="button"
                            onClick={() => {
                                setOpen(false);
                                triggerRef.current?.focus();
                            }}
                            className="inline-flex min-h-touch min-w-touch items-center justify-center rounded-full text-ink-muted hover:bg-surface-sunken hover:text-ink"
                            aria-label="Cerrar opciones de accesibilidad"
                        >
                            <X className="h-5 w-5" aria-hidden="true" />
                        </button>
                    </div>

                    <fieldset className="mb-4">
                        <legend className="mb-2 text-sm font-medium text-ink-muted">Tema</legend>
                        <div className="grid grid-cols-2 gap-2">
                            {[
                                { key: 'light', label: 'Claro', Icon: Sun },
                                { key: 'dark', label: 'Oscuro', Icon: Moon },
                            ].map(({ key, label, Icon }) => (
                                <button
                                    key={key}
                                    type="button"
                                    aria-pressed={theme === key}
                                    onClick={() => update({ theme: key })}
                                    className={`inline-flex min-h-touch items-center justify-center gap-2 rounded-xl border text-sm font-medium transition ${
                                        theme === key
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-line hover:bg-surface-sunken'
                                    }`}
                                >
                                    <Icon className="h-4 w-4" aria-hidden="true" />
                                    {label}
                                </button>
                            ))}
                        </div>
                    </fieldset>

                    <fieldset className="mb-4">
                        <legend className="mb-2 text-sm font-medium text-ink-muted">Tamaño de texto</legend>
                        <div className="grid grid-cols-4 gap-2">
                            {FONT_SCALES.map((scale) => (
                                <button
                                    key={scale.key}
                                    type="button"
                                    aria-pressed={prefs.fontScale === scale.key}
                                    aria-label={scale.description}
                                    onClick={() => update({ fontScale: scale.key })}
                                    className={`min-h-touch rounded-xl border text-sm font-semibold transition ${
                                        prefs.fontScale === scale.key
                                            ? 'border-primary bg-primary text-white'
                                            : 'border-line hover:bg-surface-sunken'
                                    }`}
                                >
                                    {scale.label}
                                </button>
                            ))}
                        </div>
                    </fieldset>

                    <ul className="mb-4 space-y-1">
                        {TOGGLES.map((toggle) => (
                            <li key={toggle.key}>
                                <button
                                    type="button"
                                    role="switch"
                                    aria-checked={!!prefs[toggle.key]}
                                    onClick={() => update({ [toggle.key]: !prefs[toggle.key] })}
                                    className="flex min-h-touch w-full items-center justify-between gap-3 rounded-xl px-3 text-left text-sm hover:bg-surface-sunken"
                                >
                                    <span>{toggle.label}</span>
                                    <span
                                        aria-hidden="true"
                                        className={`relative h-6 w-11 shrink-0 rounded-full transition ${
                                            prefs[toggle.key] ? 'bg-primary' : 'bg-line'
                                        }`}
                                    >
                                        <span
                                            className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-all ${
                                                prefs[toggle.key] ? 'left-[1.375rem]' : 'left-0.5'
                                            }`}
                                        />
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>

                    <button
                        type="button"
                        onClick={reset}
                        className="inline-flex min-h-touch w-full items-center justify-center gap-2 rounded-xl border border-line text-sm font-medium hover:bg-surface-sunken"
                    >
                        <RotateCcw className="h-4 w-4" aria-hidden="true" />
                        Restablecer configuración
                    </button>
                </div>
            )}

            <button
                ref={triggerRef}
                type="button"
                onClick={() => setOpen((value) => !value)}
                aria-expanded={open}
                aria-controls={open ? panelId : undefined}
                aria-label="Abrir opciones de accesibilidad"
                className="inline-flex h-14 w-14 items-center justify-center rounded-full bg-secondary text-white shadow-lg ring-2 ring-white/20 transition hover:scale-105"
            >
                <Accessibility className="h-7 w-7" aria-hidden="true" />
            </button>
        </div>
    );
}
