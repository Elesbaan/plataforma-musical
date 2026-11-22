import { formatMoney, formatPercent } from '@/lib/format';

/** Etiquetas de interfaz para los tipos de zona (los valores viven en CoverageZone::TYPES). */
export const ZONE_TYPES = [
    {
        value: 'local_hq',
        label: 'Sede local',
        description: 'Ciudad base o sede con músicos propios. Sin costo de viáticos.',
        tone: 'success',
    },
    {
        value: 'foranea',
        label: 'Zona foránea',
        description: 'Se cobra un monto fijo o un porcentaje por traslado.',
        tone: 'warning',
    },
    {
        value: 'custom_quote',
        label: 'Por confirmar',
        description: 'Viáticos en $0 al cotizar; se confirman según la ubicación exacta.',
        tone: 'neutral',
    },
];

export function zoneTypeMeta(value) {
    return ZONE_TYPES.find((type) => type.value === value) ?? ZONE_TYPES[2];
}

/** Texto legible del cargo de viáticos de una zona. */
export function travelFeeLabel(zone) {
    if (zone.zone_type === 'local_hq') return 'Sin viáticos';
    if (zone.zone_type === 'custom_quote') return 'Por confirmar';
    return zone.travel_fee_type === 'percentage'
        ? `${formatPercent(zone.travel_fee_value)} del subtotal`
        : formatMoney(zone.travel_fee_value);
}
