import { useId, useMemo, useState } from 'react';
import { Search } from 'lucide-react';
import { inputClass } from '@/Components/Form/fields';

/**
 * Selector múltiple de servicios con búsqueda, agrupado por categoría.
 *
 * @param {{ services: Array<{id: number, name: string, category: string|null, is_active: boolean}>, value: number[], onChange: (ids: number[]) => void, error?: string }} props
 */
export default function ServicePicker({ services, value, onChange, error }) {
    const searchId = useId();
    const [search, setSearch] = useState('');
    const selected = new Set(value.map(Number));

    const groups = useMemo(() => {
        const term = search.trim().toLowerCase();
        const filtered = services.filter((s) => !term || s.name.toLowerCase().includes(term) || (s.category ?? '').toLowerCase().includes(term));
        return filtered.reduce((acc, service) => {
            const key = service.category ?? 'Sin categoría';
            (acc[key] ??= []).push(service);
            return acc;
        }, {});
    }, [services, search]);

    const toggle = (id, checked) => {
        const next = new Set(selected);
        if (checked) next.add(id);
        else next.delete(id);
        onChange([...next]);
    };

    return (
        <div>
            <div className="relative mb-3">
                <label htmlFor={searchId} className="sr-only">
                    Buscar servicio
                </label>
                <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" aria-hidden="true" />
                <input
                    id={searchId}
                    type="search"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Buscar servicio o categoría…"
                    className={`${inputClass} pl-10`}
                />
            </div>
            <p className="mb-2 text-sm text-ink-muted" aria-live="polite">
                {selected.size} servicio(s) vinculado(s)
            </p>
            <div className="max-h-80 space-y-3 overflow-y-auto rounded-xl border border-line p-2">
                {Object.keys(groups).length === 0 && <p className="p-3 text-sm text-ink-muted">No hay servicios que coincidan.</p>}
                {Object.entries(groups).map(([category, items]) => (
                    <fieldset key={category}>
                        <legend className="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-ink-muted">{category}</legend>
                        {items.map((service) => (
                            <label key={service.id} className="flex min-h-touch cursor-pointer items-center gap-3 rounded-xl px-3 hover:bg-surface-sunken">
                                <input
                                    type="checkbox"
                                    checked={selected.has(service.id)}
                                    onChange={(e) => toggle(service.id, e.target.checked)}
                                    className="h-5 w-5 shrink-0 rounded border-line text-primary focus:ring-primary"
                                />
                                <span className="min-w-0 flex-1 text-sm text-ink">{service.name}</span>
                                {!service.is_active && <span className="text-xs text-ink-muted">(inactivo)</span>}
                            </label>
                        ))}
                    </fieldset>
                ))}
            </div>
            {error && <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">{error}</p>}
        </div>
    );
}
