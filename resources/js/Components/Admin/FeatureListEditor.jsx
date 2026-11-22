import { useRef } from 'react';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import { inputClass } from '@/Components/Form/fields';

/** Lista editable de características incluidas (agregar, quitar y reordenar). */
export default function FeatureListEditor({ features, onChange, errors = {} }) {
    const listRef = useRef(null);

    const update = (index, value) => onChange(features.map((item, i) => (i === index ? value : item)));
    const remove = (index) => onChange(features.filter((_, i) => i !== index));
    const move = (index, direction) => {
        const target = index + direction;
        if (target < 0 || target >= features.length) return;
        const next = [...features];
        [next[index], next[target]] = [next[target], next[index]];
        onChange(next);
    };
    const add = () => {
        onChange([...features, '']);
        requestAnimationFrame(() => {
            const inputs = listRef.current?.querySelectorAll('input');
            inputs?.[inputs.length - 1]?.focus();
        });
    };

    const iconButton = 'inline-flex min-h-touch min-w-touch items-center justify-center rounded-xl text-ink-muted hover:bg-surface-sunken hover:text-ink disabled:opacity-30';

    return (
        <div>
            {features.length === 0 && <p className="mb-3 text-sm text-ink-muted">Aún no hay características. Agrega lo que incluye este servicio.</p>}
            <ol ref={listRef} className="space-y-2">
                {features.map((feature, index) => {
                    const error = errors[`features.${index}`];
                    return (
                        <li key={index}>
                            <div className="flex items-center gap-1">
                                <span className="w-6 shrink-0 text-right text-sm text-ink-muted" aria-hidden="true">
                                    {index + 1}.
                                </span>
                                <input
                                    type="text"
                                    value={feature}
                                    maxLength={200}
                                    onChange={(e) => update(index, e.target.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            add();
                                        }
                                    }}
                                    aria-label={`Característica ${index + 1}`}
                                    aria-invalid={!!error}
                                    placeholder="Ej. Sonido profesional"
                                    className={`${inputClass} min-w-0 flex-1`}
                                />
                                <button type="button" className={`${iconButton} hidden sm:inline-flex`} onClick={() => move(index, -1)} disabled={index === 0} aria-label={`Subir característica ${index + 1}`}>
                                    <ArrowUp className="h-4 w-4" aria-hidden="true" />
                                </button>
                                <button
                                    type="button"
                                    className={`${iconButton} hidden sm:inline-flex`}
                                    onClick={() => move(index, 1)}
                                    disabled={index === features.length - 1}
                                    aria-label={`Bajar característica ${index + 1}`}
                                >
                                    <ArrowDown className="h-4 w-4" aria-hidden="true" />
                                </button>
                                <button type="button" className={`${iconButton} text-red-600 dark:text-red-400`} onClick={() => remove(index)} aria-label={`Quitar característica ${index + 1}`}>
                                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                                </button>
                            </div>
                            {error && <p className="ml-7 mt-1 text-sm text-red-600 dark:text-red-400">{error}</p>}
                        </li>
                    );
                })}
            </ol>
            <button
                type="button"
                onClick={add}
                className="mt-3 inline-flex min-h-touch items-center gap-2 rounded-full border border-dashed border-line px-4 text-sm font-semibold text-ink hover:bg-surface-sunken"
            >
                <Plus className="h-4 w-4" aria-hidden="true" />
                Agregar característica
            </button>
            {errors.features && <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">{errors.features}</p>}
        </div>
    );
}
