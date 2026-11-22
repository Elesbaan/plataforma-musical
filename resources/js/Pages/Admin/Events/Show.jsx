import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { CalendarDays, Clock, FileText, MapPin, Pencil, Phone, Trash2, User } from 'lucide-react';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import ConfirmDialog from '@/Components/ConfirmDialog';
import AdminLayout from '@/Layouts/AdminLayout';
import { telUrl } from '@/lib/contact';
import { formatLongDate } from '@/lib/date';
import { eventStatus, timeRange } from '@/lib/events';
import { formatMoney } from '@/lib/format';

export default function Show({ event, abilities }) {
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);
    const status = eventStatus(event.status);
    const time = timeRange(event.start_time, event.end_time);

    const destroy = () =>
        router.delete(`/admin/agenda/${event.id}`, {
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setConfirming(false);
            },
        });

    return (
        <AdminLayout
            title={event.title}
            actions={
                abilities.manage ? (
                    <>
                        <Button variant="outline" href={`/admin/agenda/${event.id}/editar`}>
                            <Pencil className="h-4 w-4" aria-hidden="true" />
                            Editar
                        </Button>
                        <Button variant="ghost" onClick={() => setConfirming(true)} className="text-red-600 dark:text-red-400">
                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                            Eliminar
                        </Button>
                    </>
                ) : null
            }
        >
            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div className="min-w-0 space-y-6">
                    <Card title="Datos operativos">
                        <ul className="space-y-3 text-sm">
                            <li className="flex items-center gap-2">
                                <Badge tone={status.tone}>{status.label}</Badge>
                            </li>
                            <li className="flex items-start gap-2">
                                <CalendarDays className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                <span className="capitalize text-ink">{formatLongDate(event.event_date)}</span>
                            </li>
                            {time && (
                                <li className="flex items-start gap-2">
                                    <Clock className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                    <span className="text-ink">{time}</span>
                                </li>
                            )}
                            <li className="flex items-start gap-2">
                                <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                <span className="text-ink">{[event.venue_name, event.city_name].filter(Boolean).join(' · ')}</span>
                            </li>
                        </ul>
                    </Card>

                    <Card title={event.is_full_view ? 'Servicios comprometidos' : 'Tus servicios en este evento'}>
                        <ul className="divide-y divide-line">
                            {event.services.map((service) => (
                                <li key={service.id} className="flex items-center justify-between py-2 text-sm">
                                    <span className="text-ink">{service.name}</span>
                                    <span className="font-semibold tabular-nums">× {service.quantity}</span>
                                </li>
                            ))}
                        </ul>
                    </Card>

                    {event.is_full_view && event.internal_notes && (
                        <Card title="Notas internas">
                            <p className="whitespace-pre-line text-sm text-ink-muted">{event.internal_notes}</p>
                        </Card>
                    )}
                </div>

                {event.is_full_view && (
                    <div className="min-w-0 space-y-6">
                        <Card title="Cliente">
                            <ul className="space-y-3 text-sm">
                                <li className="flex items-start gap-2">
                                    <User className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                    <span className="text-ink">{event.client_name}</span>
                                </li>
                                {event.client_phone && (
                                    <li className="flex items-start gap-2">
                                        <Phone className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                        <a href={telUrl(event.client_phone)} className="text-ink hover:underline">
                                            {event.client_phone}
                                        </a>
                                    </li>
                                )}
                                {event.quote && (
                                    <li className="flex items-start gap-2">
                                        <FileText className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                        <Link href={`/admin/cotizaciones/${event.quote.id}`} className="font-medium text-primary hover:underline">
                                            Cotización {event.quote.folio}
                                        </Link>
                                    </li>
                                )}
                            </ul>
                        </Card>
                        <Card title="Pagos">
                            <dl className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <dt className="text-ink-muted">Monto acordado</dt>
                                    <dd className="tabular-nums">{formatMoney(event.agreed_total)}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-ink-muted">Anticipo recibido</dt>
                                    <dd className="tabular-nums">{formatMoney(event.deposit_paid)}</dd>
                                </div>
                                <div className="flex items-baseline justify-between border-t border-line pt-2">
                                    <dt className="font-semibold">Saldo pendiente</dt>
                                    <dd className="text-xl font-semibold tabular-nums">{formatMoney(event.balance)}</dd>
                                </div>
                            </dl>
                        </Card>
                    </div>
                )}
            </div>

            <ConfirmDialog
                open={confirming}
                title="Eliminar evento"
                message="Se eliminará de la agenda y se liberará la capacidad de sus servicios en esa fecha. Si solo se canceló, cambia su estatus a Cancelado para conservar el historial."
                processing={processing}
                onConfirm={destroy}
                onCancel={() => setConfirming(false)}
            />
        </AdminLayout>
    );
}
