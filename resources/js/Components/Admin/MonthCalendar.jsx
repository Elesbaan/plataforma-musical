import { CircleAlert } from 'lucide-react';

const WEEKDAYS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

function pad(n) {
    return String(n).padStart(2, '0');
}

/** Semanas (lunes a domingo) del mes "YYYY-MM", con días de relleno en null. */
function buildWeeks(month) {
    const [year, m] = month.split('-').map(Number);
    const first = new Date(year, m - 1, 1);
    const daysInMonth = new Date(year, m, 0).getDate();
    const offset = (first.getDay() + 6) % 7;
    const cells = [...Array(offset).fill(null), ...Array.from({ length: daysInMonth }, (_, i) => `${year}-${pad(m)}-${pad(i + 1)}`)];
    while (cells.length % 7) cells.push(null);
    return Array.from({ length: cells.length / 7 }, (_, i) => cells.slice(i * 7, i * 7 + 7));
}

/**
 * Calendario mensual. En escritorio muestra los eventos dentro de cada día; en
 * móvil muestra indicadores y el día seleccionado se detalla fuera del calendario.
 */
export default function MonthCalendar({ month, events, saturatedDays, today, selected, onSelect, renderEvent }) {
    const byDate = events.reduce((acc, event) => {
        (acc[event.event_date] ??= []).push(event);
        return acc;
    }, {});

    return (
        <div>
            <div className="grid grid-cols-7 border-b border-line pb-2 text-center text-xs font-semibold uppercase tracking-wide text-ink-muted">
                {WEEKDAYS.map((day) => (
                    <div key={day}>{day}</div>
                ))}
            </div>
            <div className="mt-2 grid gap-1">
                {buildWeeks(month).map((week, wi) => (
                    <div key={wi} className="grid grid-cols-7 gap-1">
                        {week.map((date, di) => {
                            if (!date) return <div key={di} aria-hidden="true" />;
                            const dayEvents = byDate[date] ?? [];
                            const saturation = saturatedDays[date];
                            const over = saturation?.some((s) => s.level === 'over');
                            const isToday = date === today;
                            const isSelected = date === selected;
                            const day = Number(date.slice(8));
                            const label = `${day}${dayEvents.length ? `, ${dayEvents.length} evento(s)` : ''}${saturation ? over ? ', con sobrecupo' : ', con servicios en capacidad máxima' : ''}`;

                            return (
                                <div
                                    key={date}
                                    className={`flex min-h-[3.25rem] flex-col rounded-xl border p-1 text-left transition lg:min-h-[7.5rem] lg:p-2 ${
                                        isSelected ? 'border-primary ring-2 ring-primary/30' : 'border-line'
                                    } ${saturation ? (over ? 'bg-red-50 dark:bg-red-950/40' : 'bg-amber-50 dark:bg-amber-950/30') : 'bg-surface-raised'}`}
                                >
                                    <button
                                        type="button"
                                        onClick={() => onSelect(date)}
                                        aria-label={label}
                                        aria-pressed={isSelected}
                                        className="flex min-h-touch w-full items-start justify-between gap-1 rounded-lg lg:min-h-0"
                                    >
                                        <span
                                            className={`inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold ${
                                                isToday ? 'bg-primary text-white' : 'text-ink'
                                            }`}
                                        >
                                            {day}
                                        </span>
                                        {saturation && (
                                            <CircleAlert className={`h-4 w-4 shrink-0 ${over ? 'text-red-600' : 'text-amber-600'}`} aria-hidden="true" />
                                        )}
                                    </button>
                                    {dayEvents.length > 0 && (
                                        <>
                                            <div className="mt-auto flex flex-wrap gap-0.5 lg:hidden" aria-hidden="true">
                                                {dayEvents.slice(0, 3).map((event) => (
                                                    <span key={event.id} className={`h-1.5 w-1.5 rounded-full ${event.status === 'cancelled' ? 'bg-ink-muted/40' : 'bg-primary'}`} />
                                                ))}
                                            </div>
                                            <ul className="mt-1 hidden space-y-1 lg:block">
                                                {dayEvents.slice(0, 3).map((event) => (
                                                    <li key={event.id}>{renderEvent(event)}</li>
                                                ))}
                                                {dayEvents.length > 3 && <li className="text-xs text-ink-muted">+{dayEvents.length - 3} más</li>}
                                            </ul>
                                        </>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                ))}
            </div>
        </div>
    );
}
