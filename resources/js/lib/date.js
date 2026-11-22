/** "2026-12-12" -> "sábado, 12 de diciembre de 2026" (sin desfase de zona horaria). */
export function formatLongDate(value, withWeekday = true) {
    if (!value) return '';
    const date = new Date(`${value}T12:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('es-MX', {
        weekday: withWeekday ? 'long' : undefined,
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}
