/** Etiquetas de interfaz para los estatus de evento. */
export const EVENT_STATUSES = [
    { value: 'reserved', label: 'Reservado', tone: 'success' },
    { value: 'completed', label: 'Realizado', tone: 'neutral' },
    { value: 'cancelled', label: 'Cancelado', tone: 'danger' },
];

export function eventStatus(value) {
    return EVENT_STATUSES.find((status) => status.value === value) ?? EVENT_STATUSES[0];
}

/** "18:00" – "23:00" -> "18:00 – 23:00 h". */
export function timeRange(start, end) {
    if (!start && !end) return null;
    if (start && end) return `${start} – ${end} h`;
    return `${start ?? end} h`;
}
