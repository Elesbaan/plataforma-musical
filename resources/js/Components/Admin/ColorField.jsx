import { useId } from 'react';
import { contrastRatio, isHexColor } from '@/lib/color';
import { inputClass } from '@/Components/Form/fields';

/** Selector de color (picker + código hexadecimal) con indicador de contraste. */
export default function ColorField({ label, hint, value, onChange, error }) {
    const id = useId();
    const ratio = contrastRatio('#FFFFFF', value);
    const passes = ratio !== null && ratio >= 4.5;

    return (
        <div>
            <label htmlFor={`${id}-hex`} className="mb-1.5 block text-sm font-medium text-ink">
                {label}
            </label>
            <div className="flex items-center gap-3">
                <input
                    type="color"
                    value={isHexColor(value) ? value : '#000000'}
                    onChange={(e) => onChange(e.target.value.toUpperCase())}
                    aria-label={`${label}: selector visual`}
                    className="h-11 w-14 shrink-0 cursor-pointer rounded-lg border border-line bg-surface p-1"
                />
                <input
                    id={`${id}-hex`}
                    type="text"
                    value={value ?? ''}
                    maxLength={7}
                    onChange={(e) => onChange(e.target.value.toUpperCase())}
                    aria-invalid={!!error}
                    aria-describedby={`${id}-help`}
                    className={`${inputClass} font-mono uppercase`}
                />
            </div>
            <p id={`${id}-help`} className="mt-1.5 text-xs text-ink-muted">
                {error ? (
                    <span className="text-sm text-red-600 dark:text-red-400">{error}</span>
                ) : (
                    <>
                        {hint && <span className="block">{hint}</span>}
                        {ratio !== null && (
                            <span className={passes ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'}>
                                Contraste con texto blanco: {ratio.toFixed(1)}:1 {passes ? '(legible)' : '(poco legible, oscurécelo)'}
                            </span>
                        )}
                    </>
                )}
            </p>
        </div>
    );
}
