import { BadgePercent, LoaderCircle } from 'lucide-react';
import { formatMoney } from '@/lib/format';

/**
 * Desglose de la cotización (datos calculados por QuoteCalculatorService).
 */
export default function QuoteSummary({ breakdown, loading = false, error = null, zoneSelected = true }) {
    if (!breakdown) {
        return <p className="text-sm text-ink-muted">Agrega servicios para ver tu total estimado.</p>;
    }

    return (
        <div className={`space-y-3 text-sm transition-opacity ${loading ? 'opacity-60' : ''}`} aria-busy={loading}>
            <ul className="space-y-2">
                {breakdown.lines.map((line) => (
                    <li key={`${line.service_id}-${line.kind}`} className="flex justify-between gap-3">
                        <span className="min-w-0 text-ink">
                            {line.name}
                            {line.quantity > 1 && <span className="text-ink-muted"> × {line.quantity}</span>}
                        </span>
                        <span className="shrink-0 tabular-nums text-ink">{formatMoney(line.line_total)}</span>
                    </li>
                ))}
            </ul>

            <dl className="space-y-2 border-t border-line pt-3">
                <div className="flex justify-between gap-3">
                    <dt className="text-ink-muted">Subtotal</dt>
                    <dd className="tabular-nums">{formatMoney(breakdown.subtotal)}</dd>
                </div>
                {breakdown.discount_applies && (
                    <div className="flex justify-between gap-3 font-semibold text-emerald-700 dark:text-emerald-400">
                        <dt>{breakdown.discount_label}</dt>
                        <dd className="tabular-nums">−{formatMoney(breakdown.discount_amount)}</dd>
                    </div>
                )}
                <div className="flex justify-between gap-3">
                    <dt className="text-ink-muted">Viáticos</dt>
                    <dd className="text-right tabular-nums">
                        {!zoneSelected ? 'Elige tu ciudad' : breakdown.travel_pending ? 'Por confirmar' : formatMoney(breakdown.travel_fee)}
                    </dd>
                </div>
                <div className="flex items-baseline justify-between gap-3 border-t border-line pt-3">
                    <dt className="font-semibold text-ink">Total estimado</dt>
                    <dd className="font-display text-2xl font-semibold tabular-nums text-ink">{formatMoney(breakdown.total)}</dd>
                </div>
            </dl>

            {breakdown.travel_pending && breakdown.travel_label && <p className="rounded-lg bg-amber-50 p-2 text-xs text-amber-900 dark:bg-amber-950 dark:text-amber-100">{breakdown.travel_label}.</p>}

            {!breakdown.discount_applies && breakdown.discount_label && breakdown.discount_missing > 0 && (
                <p className="flex items-start gap-2 rounded-lg bg-primary/10 p-2 text-xs text-primary">
                    <BadgePercent className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                    Agrega {formatMoney(breakdown.discount_missing)} más y obtén tu {breakdown.discount_label.toLowerCase()}.
                </p>
            )}

            {loading && (
                <p className="flex items-center gap-2 text-xs text-ink-muted">
                    <LoaderCircle className="h-3.5 w-3.5 animate-spin" aria-hidden="true" />
                    Actualizando total…
                </p>
            )}
            {error && (
                <p role="alert" className="text-xs text-red-600 dark:text-red-400">
                    {error}
                </p>
            )}
        </div>
    );
}
