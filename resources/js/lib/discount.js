import { formatMoney, formatPercent } from '@/lib/format';

/**
 * Texto del descuento exclusivo web a partir de SiteSetting (solo presentación;
 * el monto real lo calcula QuoteCalculatorService en el servidor).
 *
 * @returns {{ amount: string, minimum: string|null } | null}
 */
export function onlineDiscount(settings) {
    if (!settings?.online_discount_active || !(Number(settings.online_discount_value) > 0)) return null;

    return {
        amount: settings.online_discount_type === 'percentage' ? formatPercent(settings.online_discount_value) : formatMoney(settings.online_discount_value),
        minimum: Number(settings.online_discount_min_amount) > 0 ? formatMoney(settings.online_discount_min_amount) : null,
    };
}
