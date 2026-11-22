import { Minus, Plus } from 'lucide-react';

/** Control de cantidad accesible (− valor +). */
export default function QuantityStepper({ value, min = 0, max, onChange, label, unit = '' }) {
    const button =
        'inline-flex h-11 w-11 items-center justify-center rounded-full border border-line text-ink transition hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-40';

    return (
        <div className="inline-flex items-center gap-2" role="group" aria-label={label}>
            <button type="button" className={button} onClick={() => onChange(Math.max(min, value - 1))} disabled={value <= min} aria-label={`Quitar uno: ${label}`}>
                <Minus className="h-4 w-4" aria-hidden="true" />
            </button>
            <output className="min-w-[2.5rem] text-center text-base font-semibold tabular-nums text-ink" aria-live="polite">
                {value}
                {unit && <span className="sr-only"> {unit}</span>}
            </output>
            <button type="button" className={button} onClick={() => onChange(Math.min(max, value + 1))} disabled={value >= max} aria-label={`Agregar uno: ${label}`}>
                <Plus className="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    );
}
