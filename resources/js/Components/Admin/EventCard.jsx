import { Link } from '@inertiajs/react';
import { Clock, MapPin } from 'lucide-react';
import Badge from '@/Components/Badge';
import { eventStatus, timeRange } from '@/lib/events';
import { formatMoney } from '@/lib/format';

/** Tarjeta de evento para listas de la agenda (respeta el alcance de datos recibido). */
export default function EventCard({ event }) {
    const status = eventStatus(event.status);
    const time = timeRange(event.start_time, event.end_time);

    return (
        <Link href={`/admin/agenda/${event.id}`} className="block rounded-xl border border-line bg-surface-raised p-4 transition hover:border-primary/50">
            <div className="flex items-start justify-between gap-3">
                <p className="min-w-0 font-semibold text-ink">{event.title}</p>
                <Badge tone={status.tone}>{status.label}</Badge>
            </div>
            <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-ink-muted">
                {time && (
                    <span className="inline-flex items-center gap-1.5">
                        <Clock className="h-4 w-4" aria-hidden="true" />
                        {time}
                    </span>
                )}
                <span className="inline-flex items-center gap-1.5">
                    <MapPin className="h-4 w-4" aria-hidden="true" />
                    {[event.venue_name, event.city_name].filter(Boolean).join(' · ')}
                </span>
            </div>
            {event.services.length > 0 && (
                <p className="mt-2 text-sm text-ink">{event.services.map((s) => (s.quantity > 1 ? `${s.name} × ${s.quantity}` : s.name)).join(' · ')}</p>
            )}
            {event.is_full_view && (
                <div className="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-line pt-2 text-xs text-ink-muted">
                    <span>Acordado: {formatMoney(event.agreed_total)}</span>
                    <span>Anticipo: {formatMoney(event.deposit_paid)}</span>
                    <span className="font-semibold text-ink">Saldo: {formatMoney(event.balance)}</span>
                </div>
            )}
        </Link>
    );
}
