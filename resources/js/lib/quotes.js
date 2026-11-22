import { formatLongDate } from '@/lib/date';

/** Etiquetas de interfaz para los estatus de cotización (valores en Quote::STATUSES). */
export const QUOTE_STATUSES = [
    { value: 'new', label: 'Nueva', tone: 'brand' },
    { value: 'contacted', label: 'Contactada', tone: 'warning' },
    { value: 'confirmed', label: 'Confirmada / Apartada', tone: 'success' },
    { value: 'cancelled', label: 'Descartada', tone: 'danger' },
];

export function quoteStatus(value) {
    return QUOTE_STATUSES.find((status) => status.value === value) ?? QUOTE_STATUSES[0];
}

/** Mensaje de WhatsApp personalizado para atender una cotización. */
export function quoteWhatsappMessage(quote, brandName) {
    const firstName = (quote.client_name ?? '').split(' ')[0];
    const from = brandName ? ` de ${brandName}` : '';
    return `Hola ${firstName}, te escribo${from} sobre tu cotización ${quote.folio} para tu ${String(quote.event_type).toLowerCase()} del ${formatLongDate(quote.event_date, false)}. ¿Te gustaría que confirmemos tu fecha?`;
}
