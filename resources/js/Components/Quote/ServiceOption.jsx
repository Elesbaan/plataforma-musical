import { Ban, Clock, Plus, Trash2 } from 'lucide-react';
import QuantityStepper from '@/Components/Quote/QuantityStepper';
import { categoryIcon, serviceTypeLabel } from '@/lib/catalog';
import { formatMoney } from '@/lib/format';

/**
 * Servicio seleccionable en el cotizador, con su disponibilidad para la fecha elegida.
 */
export default function ServiceOption({ service, selection, availability, limits, onChange }) {
    const Icon = categoryIcon(service.category?.icon);
    const remaining = availability ? availability.remaining : service.daily_capacity;
    const soldOut = availability ? !availability.available : false;
    const maxQuantity = Math.max(0, Math.min(remaining, limits.maxQuantity));
    const selected = !!selection;

    return (
        <article
            className={`rounded-2xl border p-4 transition ${
                soldOut ? 'border-line bg-surface-sunken opacity-70' : selected ? 'border-primary bg-primary/5 shadow-sm' : 'border-line bg-surface-raised'
            }`}
            aria-label={service.name}
        >
            <div className="flex gap-4">
                <div className="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-secondary sm:h-20 sm:w-20">
                    {service.cover_url ? (
                        <img src={service.cover_url} alt="" loading="lazy" className="h-full w-full object-cover" />
                    ) : (
                        <span className="flex h-full w-full items-center justify-center text-white/70">
                            <Icon className="h-7 w-7" aria-hidden="true" />
                        </span>
                    )}
                </div>
                <div className="min-w-0 flex-1">
                    <p className="text-xs text-ink-muted">{serviceTypeLabel(service.service_type)}</p>
                    <h3 className="font-semibold leading-snug text-ink">{service.name}</h3>
                    <p className="mt-1 text-sm">
                        <span className="font-semibold text-ink">{formatMoney(service.base_price)}</span> <span className="text-ink-muted">{service.price_unit}</span>
                    </p>
                    {soldOut ? (
                        <p className="mt-2 inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800 dark:bg-red-950 dark:text-red-200">
                            <Ban className="h-3.5 w-3.5" aria-hidden="true" />
                            Agotado para esta fecha
                        </p>
                    ) : (
                        availability &&
                        remaining <= 2 && (
                            <p className="mt-2 text-xs font-medium text-amber-700 dark:text-amber-400">
                                {remaining === 1 ? 'Último disponible para esta fecha' : `Quedan ${remaining} para esta fecha`}
                            </p>
                        )
                    )}
                </div>
            </div>

            {!soldOut && (
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3">
                    {!selected ? (
                        <button
                            type="button"
                            onClick={() => onChange({ quantity: 1, extra_hours: 0 })}
                            disabled={maxQuantity === 0}
                            className="inline-flex min-h-touch items-center gap-2 rounded-full bg-primary px-5 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:opacity-50"
                        >
                            <Plus className="h-4 w-4" aria-hidden="true" />
                            Agregar
                        </button>
                    ) : (
                        <>
                            <div className="flex flex-wrap items-center gap-x-6 gap-y-3">
                                <div>
                                    <p className="mb-1 text-xs text-ink-muted">Cantidad</p>
                                    <QuantityStepper
                                        label={`Cantidad de ${service.name}`}
                                        value={selection.quantity}
                                        min={1}
                                        max={maxQuantity}
                                        onChange={(quantity) => onChange({ ...selection, quantity })}
                                    />
                                </div>
                                {service.extra_hour_price && (
                                    <div>
                                        <p className="mb-1 flex items-center gap-1 text-xs text-ink-muted">
                                            <Clock className="h-3.5 w-3.5" aria-hidden="true" />
                                            Horas extra · {formatMoney(service.extra_hour_price)} c/u
                                        </p>
                                        <QuantityStepper
                                            label={`Horas extra de ${service.name}`}
                                            unit="horas"
                                            value={selection.extra_hours}
                                            min={0}
                                            max={limits.maxExtraHours}
                                            onChange={(extra_hours) => onChange({ ...selection, extra_hours })}
                                        />
                                    </div>
                                )}
                            </div>
                            <button
                                type="button"
                                onClick={() => onChange(null)}
                                className="inline-flex min-h-touch items-center gap-2 rounded-full px-3 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950"
                            >
                                <Trash2 className="h-4 w-4" aria-hidden="true" />
                                Quitar
                            </button>
                        </>
                    )}
                </div>
            )}
        </article>
    );
}
