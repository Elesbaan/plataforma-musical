import { Head, usePage } from '@inertiajs/react';
import { BadgePercent, CalendarDays, CircleCheck, Download, MapPin, MessageCircle } from 'lucide-react';
import Button from '@/Components/Button';
import PublicLayout from '@/Layouts/PublicLayout';
import { whatsappUrl } from '@/lib/contact';
import { formatLongDate } from '@/lib/date';
import { formatMoney } from '@/lib/format';

export default function QuoteConfirmation({ quote, downloadUrl }) {
    const { siteSettings } = usePage().props;
    const firstName = quote.client_name.split(' ')[0];
    const savings = Number(quote.discount_amount);

    const whatsappMessage = `Hola, generé mi cotización ${quote.folio} para ${quote.event_type.toLowerCase()} el ${formatLongDate(quote.event_date, false)}${
        quote.travel.city ? ` en ${quote.travel.city}` : ''
    }. Me gustaría confirmar disponibilidad.`;
    const whatsapp = whatsappUrl(siteSettings?.whatsapp_number, whatsappMessage);

    return (
        <PublicLayout>
            <Head title={`Cotización ${quote.folio}`} />

            <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
                <div className="text-center">
                    <span className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <CircleCheck className="h-9 w-9" aria-hidden="true" />
                    </span>
                    <h1 className="mt-5 font-display text-3xl font-semibold text-ink sm:text-4xl">¡Listo, {firstName}!</h1>
                    <p className="mt-3 text-ink-muted">
                        Tu cotización formal está lista. También la enviamos a <strong className="break-all text-ink">{quote.client_email}</strong>.
                    </p>
                    <p className="mt-6 inline-block rounded-2xl border border-line bg-surface-raised px-6 py-3">
                        <span className="block text-xs uppercase tracking-widest text-ink-muted">Tu folio</span>
                        <span className="font-display text-2xl font-semibold tracking-wide text-primary">{quote.folio}</span>
                    </p>
                </div>

                {savings > 0 && (
                    <p className="mt-8 flex items-center justify-center gap-2 rounded-2xl bg-emerald-50 p-4 text-center font-semibold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                        <BadgePercent className="h-5 w-5 shrink-0" aria-hidden="true" />
                        Ahorraste {formatMoney(savings)} por cotizar en línea
                    </p>
                )}

                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                    {whatsapp && (
                        <Button href={whatsapp} external target="_blank" rel="noopener noreferrer" size="lg" className="flex-1 !bg-[#25D366] text-white hover:!bg-[#1ebe5b]">
                            <MessageCircle className="h-5 w-5" aria-hidden="true" />
                            Enviar mi folio por WhatsApp
                        </Button>
                    )}
                    <Button href={downloadUrl} external variant="outline" size="lg" className="flex-1" download>
                        <Download className="h-5 w-5" aria-hidden="true" />
                        Descargar PDF
                    </Button>
                </div>

                <section className="mt-10 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6" aria-labelledby="resumen-title">
                    <h2 id="resumen-title" className="font-semibold text-ink">
                        Resumen de tu evento
                    </h2>
                    <dl className="mt-4 space-y-2 text-sm">
                        <div className="flex items-start gap-2">
                            <CalendarDays className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                            <dt className="sr-only">Fecha</dt>
                            <dd className="text-ink">
                                {quote.event_type} · {formatLongDate(quote.event_date)}
                                {quote.event_time && `, ${quote.event_time} h`}
                            </dd>
                        </div>
                        <div className="flex items-start gap-2">
                            <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                            <dt className="sr-only">Lugar</dt>
                            <dd className="text-ink">{[quote.venue_name, quote.travel.city].filter(Boolean).join(' · ')}</dd>
                        </div>
                    </dl>

                    <ul className="mt-5 space-y-2 border-t border-line pt-4 text-sm">
                        {quote.items.map((item) => (
                            <li key={item.service_name_snapshot} className="flex justify-between gap-3">
                                <span className="text-ink">
                                    {item.service_name_snapshot}
                                    {item.quantity > 1 && <span className="text-ink-muted"> × {item.quantity}</span>}
                                </span>
                                <span className="shrink-0 tabular-nums">{formatMoney(item.line_total)}</span>
                            </li>
                        ))}
                    </ul>
                    <dl className="mt-4 space-y-2 border-t border-line pt-4 text-sm">
                        <div className="flex justify-between">
                            <dt className="text-ink-muted">Subtotal</dt>
                            <dd className="tabular-nums">{formatMoney(quote.subtotal)}</dd>
                        </div>
                        {savings > 0 && (
                            <div className="flex justify-between font-semibold text-emerald-700 dark:text-emerald-400">
                                <dt>Ahorro por cotizar en línea</dt>
                                <dd className="tabular-nums">−{formatMoney(savings)}</dd>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <dt className="text-ink-muted">Viáticos</dt>
                            <dd className="tabular-nums">{quote.travel.pending ? 'Por confirmar' : formatMoney(quote.travel_fee)}</dd>
                        </div>
                        <div className="flex items-baseline justify-between border-t border-line pt-3">
                            <dt className="font-semibold text-ink">Total estimado</dt>
                            <dd className="font-display text-2xl font-semibold tabular-nums text-ink">{formatMoney(quote.total)}</dd>
                        </div>
                    </dl>
                    {quote.travel.pending && <p className="mt-3 rounded-lg bg-amber-50 p-2 text-xs text-amber-900 dark:bg-amber-950 dark:text-amber-100">{quote.travel.label}.</p>}
                    <p className="mt-4 text-xs text-ink-muted">
                        Vigente hasta el {formatLongDate(quote.valid_until, false)}. La fecha se aparta al confirmar tu reservación.
                    </p>
                </section>
            </div>
        </PublicLayout>
    );
}
