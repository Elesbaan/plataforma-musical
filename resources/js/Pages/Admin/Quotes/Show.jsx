import { useState } from 'react';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { CalendarCheck, CalendarDays, Clock, Download, Mail, MapPin, MessageCircle, Phone, Send } from 'lucide-react';
import EventForm from '@/Components/Admin/EventForm';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { TextAreaField, inputClass } from '@/Components/Form/fields';
import Modal from '@/Components/Modal';
import AdminLayout from '@/Layouts/AdminLayout';
import { telUrl, whatsappUrl, withCountryCode } from '@/lib/contact';
import { formatLongDate } from '@/lib/date';
import { formatMoney } from '@/lib/format';
import { QUOTE_STATUSES, quoteStatus, quoteWhatsappMessage } from '@/lib/quotes';

function StatusControl({ quote, canConvert, onConfirmRequest }) {
    const [processing, setProcessing] = useState(false);

    const change = (value) => {
        if (value === quote.status) return;
        if (value === 'confirmed' && !quote.event) {
            onConfirmRequest?.();
            return;
        }
        router.patch(`/admin/cotizaciones/${quote.id}/estatus`, { status: value }, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <div>
            <label htmlFor="quote-status" className="mb-1.5 block text-sm font-medium text-ink">
                Estatus
            </label>
            <select id="quote-status" value={quote.status} onChange={(e) => change(e.target.value)} disabled={processing} className={inputClass}>
                {QUOTE_STATUSES.filter((status) => status.value !== 'confirmed' || quote.event || canConvert).map((status) => (
                    <option key={status.value} value={status.value}>
                        {status.label}
                    </option>
                ))}
            </select>
            {quote.event && quote.status !== 'confirmed' && (
                <p className="mt-1.5 text-xs text-amber-700 dark:text-amber-400">El evento agendado no se modifica; cancélalo desde la agenda si es necesario.</p>
            )}
        </div>
    );
}

function NotesEditor({ quote, editable }) {
    const { data, setData, patch, processing, errors, isDirty } = useForm({ admin_notes: quote.admin_notes ?? '' });

    if (!editable) {
        return <p className="whitespace-pre-line text-sm text-ink-muted">{quote.admin_notes || 'Sin notas internas.'}</p>;
    }

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                patch(`/admin/cotizaciones/${quote.id}/notas`, { preserveScroll: true });
            }}
            className="space-y-3"
        >
            <TextAreaField
                label="Notas internas"
                hint="Solo las ve tu equipo; no aparecen en el PDF."
                rows={5}
                maxLength={5000}
                value={data.admin_notes}
                onChange={(value) => setData('admin_notes', value)}
                error={errors.admin_notes}
            />
            <div className="flex justify-end">
                <Button type="submit" variant="outline" size="sm" disabled={processing || !isDirty}>
                    {processing ? 'Guardando…' : 'Guardar notas'}
                </Button>
            </div>
        </form>
    );
}

/** Detalle de una cotización con seguimiento, notas y acciones de contacto. */
export default function Show({ quote, abilities, conversion }) {
    const { siteSettings } = usePage().props;
    const [converting, setConverting] = useState(false);
    const [confirmResend, setConfirmResend] = useState(false);
    const [resending, setResending] = useState(false);
    const status = quoteStatus(quote.status);
    const whatsapp = whatsappUrl(withCountryCode(quote.client_phone, siteSettings?.whatsapp_number), quoteWhatsappMessage(quote, siteSettings?.brand_name));

    const resend = () => {
        router.post(`/admin/cotizaciones/${quote.id}/reenviar`, {}, {
            preserveScroll: true,
            onStart: () => setResending(true),
            onFinish: () => {
                setResending(false);
                setConfirmResend(false);
            },
        });
    };

    return (
        <AdminLayout title={`Cotización ${quote.folio}`}>
            <div className="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 sm:flex-row sm:items-center sm:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="font-mono text-sm text-ink-muted">{quote.folio}</span>
                        <Badge tone={status.tone}>{status.label}</Badge>
                        {quote.is_expired && quote.status !== 'confirmed' && <Badge tone="danger">Vencida</Badge>}
                    </div>
                    <p className="mt-1 text-xl font-semibold text-ink">{quote.client_name}</p>
                    <p className="text-sm text-ink-muted">Recibida el {formatLongDate(quote.created_at?.slice(0, 10), false)}</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    {whatsapp && (
                        <Button href={whatsapp} external target="_blank" rel="noopener noreferrer" className="!bg-[#25D366] text-white hover:!bg-[#1ebe5b]">
                            <MessageCircle className="h-4 w-4" aria-hidden="true" />
                            Atender por WhatsApp
                        </Button>
                    )}
                    <Button href={`/admin/cotizaciones/${quote.id}/pdf`} external variant="outline">
                        <Download className="h-4 w-4" aria-hidden="true" />
                        PDF
                    </Button>
                    {abilities.manage && (
                        <Button variant="outline" onClick={() => setConfirmResend(true)}>
                            <Send className="h-4 w-4" aria-hidden="true" />
                            Reenviar por correo
                        </Button>
                    )}
                </div>
            </div>

            {quote.event && (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
                    <p className="flex items-center gap-2">
                        <CalendarCheck className="h-5 w-5 shrink-0" aria-hidden="true" />
                        Evento agendado: <strong>{quote.event.title}</strong> · {formatLongDate(quote.event.event_date, false)}
                    </p>
                    <Link href={`/admin/agenda/${quote.event.id}`} className="inline-flex min-h-touch items-center font-semibold underline underline-offset-2">
                        Ver en la agenda
                    </Link>
                </div>
            )}

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div className="min-w-0 space-y-6">
                    <div className="grid gap-6 md:grid-cols-2">
                        <Card title="Cliente">
                            <ul className="space-y-3 text-sm">
                                <li className="flex items-start gap-2">
                                    <Mail className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                    <a href={`mailto:${quote.client_email}`} className="break-all text-ink hover:underline">
                                        {quote.client_email}
                                    </a>
                                </li>
                                <li className="flex items-start gap-2">
                                    <Phone className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                    <a href={telUrl(quote.client_phone)} className="text-ink hover:underline">
                                        {quote.client_phone}
                                    </a>
                                </li>
                            </ul>
                        </Card>
                        <Card title="Evento">
                            <ul className="space-y-3 text-sm">
                                <li className="flex items-start gap-2">
                                    <CalendarDays className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                    <span className="text-ink">
                                        {quote.event_type} · {formatLongDate(quote.event_date)}
                                    </span>
                                </li>
                                {quote.event_time && (
                                    <li className="flex items-start gap-2">
                                        <Clock className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                        <span className="text-ink">{quote.event_time} h</span>
                                    </li>
                                )}
                                <li className="flex items-start gap-2">
                                    <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                    <span className="text-ink">{[quote.venue_name, quote.travel.city].filter(Boolean).join(' · ')}</span>
                                </li>
                            </ul>
                        </Card>
                    </div>

                    <Card title="Desglose">
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[32rem] text-left text-sm">
                                <thead className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                                    <tr>
                                        <th scope="col" className="py-2 pr-4 font-medium">Concepto</th>
                                        <th scope="col" className="py-2 pr-4 text-center font-medium">Cant.</th>
                                        <th scope="col" className="py-2 pr-4 text-right font-medium">Unitario</th>
                                        <th scope="col" className="py-2 text-right font-medium">Importe</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {quote.items.map((item) => (
                                        <tr key={item.id}>
                                            <td className="py-2.5 pr-4">
                                                {item.service_name_snapshot}
                                                {item.line_type === 'extra_hours' && (
                                                    <Badge className="ml-2" tone="neutral">
                                                        Horas extra
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="py-2.5 pr-4 text-center tabular-nums">{item.quantity}</td>
                                            <td className="py-2.5 pr-4 text-right tabular-nums">{formatMoney(item.unit_price)}</td>
                                            <td className="py-2.5 text-right tabular-nums">{formatMoney(item.line_total)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <dl className="ml-auto mt-4 max-w-sm space-y-2 border-t border-line pt-4 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-ink-muted">Subtotal</dt>
                                <dd className="tabular-nums">{formatMoney(quote.subtotal)}</dd>
                            </div>
                            {Number(quote.discount_amount) > 0 && (
                                <div className="flex justify-between text-emerald-700 dark:text-emerald-400">
                                    <dt>Descuento web aplicado</dt>
                                    <dd className="tabular-nums">−{formatMoney(quote.discount_amount)}</dd>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <dt className="text-ink-muted">Viáticos{quote.travel.city ? ` (${quote.travel.city})` : ''}</dt>
                                <dd className="tabular-nums">{quote.travel.pending ? 'Por confirmar' : formatMoney(quote.travel_fee)}</dd>
                            </div>
                            <div className="flex items-baseline justify-between border-t border-line pt-2">
                                <dt className="font-semibold">Total</dt>
                                <dd className="text-xl font-semibold tabular-nums">{formatMoney(quote.total)}</dd>
                            </div>
                        </dl>
                        <p className="mt-3 text-xs text-ink-muted">Vigente hasta el {formatLongDate(quote.valid_until, false)}.</p>
                    </Card>
                </div>

                <div className="min-w-0 space-y-6">
                    <Card title="Seguimiento">
                        <div className="space-y-5">
                            {abilities.manage ? (
                                <StatusControl quote={quote} canConvert={abilities.convert} onConfirmRequest={() => setConverting(true)} />
                            ) : (
                                <p className="text-sm">
                                    Estatus: <Badge tone={status.tone}>{status.label}</Badge>
                                </p>
                            )}
                            {abilities.convert && conversion && (
                                <Button className="w-full" onClick={() => setConverting(true)}>
                                    <CalendarCheck className="h-4 w-4" aria-hidden="true" />
                                    Confirmar y agendar evento
                                </Button>
                            )}
                            <NotesEditor quote={quote} editable={abilities.manage} />
                        </div>
                    </Card>
                </div>
            </div>

            {conversion && (
                <Modal
                    open={converting}
                    onClose={() => setConverting(false)}
                    size="lg"
                    title="Confirmar y agendar evento"
                    description="Revisa los datos prellenados desde la cotización, registra el anticipo y confirma. Se validará la capacidad diaria de cada servicio."
                >
                    <EventForm
                        compact
                        initial={conversion.event}
                        services={conversion.services}
                        submitUrl={`/admin/cotizaciones/${quote.id}/convertir`}
                        submitLabel="Confirmar y agendar"
                        onCancel={() => setConverting(false)}
                    />
                </Modal>
            )}

            <ConfirmDialog
                open={confirmResend}
                title="Reenviar cotización"
                message={`Se enviará de nuevo la cotización ${quote.folio} con su PDF a ${quote.client_email}.`}
                confirmLabel="Reenviar"
                processing={resending}
                onConfirm={resend}
                onCancel={() => setConfirmResend(false)}
            />
        </AdminLayout>
    );
}
