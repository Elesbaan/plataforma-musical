import { Check } from 'lucide-react';

/** Indicador de pasos del cotizador. Los pasos ya completados se pueden revisitar. */
export default function Stepper({ steps, current, onSelect }) {
    return (
        <nav aria-label="Pasos de la cotización">
            <ol className="grid grid-cols-3 gap-2">
                {steps.map((label, index) => {
                    const step = index + 1;
                    const done = step < current;
                    const active = step === current;
                    return (
                        <li key={label}>
                            <button
                                type="button"
                                onClick={() => done && onSelect(step)}
                                disabled={!done}
                                aria-current={active ? 'step' : undefined}
                                className={`flex w-full min-h-touch flex-col items-center gap-1 rounded-xl px-1 py-2 text-center text-xs font-medium transition sm:flex-row sm:justify-center sm:gap-2 sm:text-sm ${
                                    active ? 'bg-primary text-white' : done ? 'bg-primary/10 text-primary hover:bg-primary/20' : 'bg-surface-sunken text-ink-muted'
                                }`}
                            >
                                <span
                                    className={`inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                                        active ? 'bg-white text-primary' : done ? 'bg-primary text-white' : 'bg-line text-ink-muted'
                                    }`}
                                    aria-hidden="true"
                                >
                                    {done ? <Check className="h-3.5 w-3.5" /> : step}
                                </span>
                                <span>
                                    <span className="sr-only">Paso {step}: </span>
                                    {label}
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
