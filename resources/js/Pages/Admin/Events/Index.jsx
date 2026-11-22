import { useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { CalendarDays, CalendarPlus, ChevronLeft, ChevronRight, CircleAlert, List } from 'lucide-react';
import EventCard from '@/Components/Admin/EventCard';
import MonthCalendar from '@/Components/Admin/MonthCalendar';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import AdminLayout from '@/Layouts/AdminLayout';
import { formatLongDate } from '@/lib/date';

function shiftMonth(month, delta) {
    const [year, m] = month.split('-').map(Number);
    const date = new Date(year, m - 1 + delta, 1);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function monthLabel(month) {
    const [year, m] = month.split('-').map(Number);
    const label = new Date(year, m - 1, 1).toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
    return label.charAt(0).toUpperCase() + label.slice(1);
}

function groupByDate(events) {
    return events.reduce((acc, event) => {
        (acc[event.event_date] ??= []).push(event);
        return acc;
    }, {});
}

export default function Index({ month, view, events, upcoming, saturatedDays, abilities, today }) {
    const [selected, setSelected] = useState(today.startsWith(month) ? today : `${month}-01`);
    useEffect(() => setSelected(today.startsWith(month) ? today : `${month}-01`), [month, today]);
    const go = (params) => router.get('/admin/agenda', { mes: month, vista: view, ...params }, { preserveScroll: true, preserveState: true });

    const dayEvents = events.filter((event) => event.event_date === selected);
    const daySaturation = saturatedDays[selected];
    const tab = (active) =>
        `inline-flex min-h-touch items-center gap-2 rounded-xl px-4 text-sm font-medium transition ${active ? 'bg-primary text-white' : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'}`;

    return (
        <AdminLayout
            title="Agenda de eventos"
            actions={
                abilities.manage ? (
                    <Button href={`/admin/agenda/crear?fecha=${selected}`}>
                        <CalendarPlus className="h-4 w-4" aria-hidden="true" />
                        Nuevo evento
                    </Button>
                ) : null
            }
        >
            {!abilities.financials && (
                <p className="rounded-xl bg-surface-raised p-3 text-sm text-ink-muted">
                    Ves únicamente los eventos donde participan tus servicios, en modo de consulta.
                </p>
            )}

            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="inline-flex gap-1 rounded-2xl border border-line bg-surface-raised p-1" role="group" aria-label="Vista">
                    <button type="button" aria-pressed={view === 'mes'} onClick={() => go({ vista: 'mes' })} className={tab(view === 'mes')}>
                        <CalendarDays className="h-4 w-4" aria-hidden="true" />
                        Mes
                    </button>
                    <button type="button" aria-pressed={view === 'lista'} onClick={() => go({ vista: 'lista' })} className={tab(view === 'lista')}>
                        <List className="h-4 w-4" aria-hidden="true" />
                        Próximos
                    </button>
                </div>
                {view === 'mes' && (
                    <div className="flex items-center gap-1">
                        <Button variant="ghost" size="sm" onClick={() => go({ mes: shiftMonth(month, -1) })} aria-label="Mes anterior">
                            <ChevronLeft className="h-5 w-5" aria-hidden="true" />
                        </Button>
                        <h2 className="min-w-[10rem] text-center font-semibold text-ink" aria-live="polite">
                            {monthLabel(month)}
                        </h2>
                        <Button variant="ghost" size="sm" onClick={() => go({ mes: shiftMonth(month, 1) })} aria-label="Mes siguiente">
                            <ChevronRight className="h-5 w-5" aria-hidden="true" />
                        </Button>
                        <Button variant="outline" size="sm" onClick={() => go({ mes: today.slice(0, 7) })}>
                            Hoy
                        </Button>
                    </div>
                )}
            </div>

            {view === 'mes' ? (
                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
                    <Card className="min-w-0 !p-3 sm:!p-4">
                        <MonthCalendar
                            month={month}
                            events={events}
                            saturatedDays={saturatedDays}
                            today={today}
                            selected={selected}
                            onSelect={setSelected}
                            renderEvent={(event) => (
                                <Link
                                    href={`/admin/agenda/${event.id}`}
                                    className={`block truncate rounded-md px-1.5 py-0.5 text-xs font-medium ${
                                        event.status === 'cancelled' ? 'bg-surface-sunken text-ink-muted line-through' : 'bg-primary/10 text-primary hover:bg-primary/20'
                                    }`}
                                    title={event.title}
                                >
                                    {event.start_time && `${event.start_time} `}
                                    {event.title}
                                </Link>
                            )}
                        />
                    </Card>

                    <section aria-labelledby="dia-title" className="min-w-0 space-y-3">
                        <h2 id="dia-title" className="font-semibold text-ink">
                            {formatLongDate(selected)}
                        </h2>
                        {daySaturation && (
                            <div className="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                                <p className="flex items-center gap-2 font-semibold">
                                    <CircleAlert className="h-4 w-4" aria-hidden="true" />
                                    Capacidad diaria
                                </p>
                                <ul className="mt-1 space-y-0.5">
                                    {daySaturation.map((s) => (
                                        <li key={s.service_id}>
                                            {s.name}: {s.reserved} de {s.capacity} {s.level === 'over' ? '(sobrecupo)' : '(lleno)'}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                        {dayEvents.length === 0 ? (
                            <p className="rounded-xl border border-dashed border-line p-6 text-center text-sm text-ink-muted">Sin eventos este día.</p>
                        ) : (
                            <ul className="space-y-3">
                                {dayEvents.map((event) => (
                                    <li key={event.id}>
                                        <EventCard event={event} />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            ) : (
                <Card title="Próximos eventos" description="Eventos reservados a partir de hoy, en orden cronológico.">
                    {upcoming.length === 0 ? (
                        <p className="py-8 text-center text-sm text-ink-muted">No hay eventos próximos.</p>
                    ) : (
                        <div className="space-y-6">
                            {Object.entries(groupByDate(upcoming)).map(([date, items]) => (
                                <section key={date} aria-label={formatLongDate(date)}>
                                    <h3 className="mb-2 text-sm font-semibold capitalize text-primary">{formatLongDate(date)}</h3>
                                    <ul className="grid gap-3 lg:grid-cols-2">
                                        {items.map((event) => (
                                            <li key={event.id}>
                                                <EventCard event={event} />
                                            </li>
                                        ))}
                                    </ul>
                                </section>
                            ))}
                        </div>
                    )}
                </Card>
            )}
        </AdminLayout>
    );
}
