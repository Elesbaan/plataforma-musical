import { useEffect, useId, useMemo, useRef, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, BadgePercent, CalendarDays, ChevronUp, CircleAlert, FileText, LoaderCircle, MapPin, Pencil } from 'lucide-react';
import Button from '@/Components/Button';
import { SelectField, TextField, inputClass } from '@/Components/Form/fields';
import SectionHeading from '@/Components/Public/SectionHeading';
import QuoteSummary from '@/Components/Quote/QuoteSummary';
import ServiceOption from '@/Components/Quote/ServiceOption';
import Stepper from '@/Components/Quote/Stepper';
import useAvailability from '@/hooks/useAvailability';
import useQuotePreview from '@/hooks/useQuotePreview';
import PublicLayout from '@/Layouts/PublicLayout';
import { formatLongDate } from '@/lib/date';
import { formatMoney } from '@/lib/format';
import { travelFeeLabel } from '@/lib/zones';

const STEPS = ['Tu evento', 'Arma tu paquete', 'Recibe tu PDF'];
const STEP1_FIELDS = ['event_date', 'event_type', 'coverage_zone_id', 'custom_city_name'];

export default function QuoteBuilder({ categories, zones, eventTypes, preselect, limits, today }) {
    const [step, setStep] = useState(1);
    const [selections, setSelections] = useState({});
    const [notice, setNotice] = useState(null);
    const [summaryOpen, setSummaryOpen] = useState(false);
    const headingRef = useRef(null);
    const summaryId = useId();

    const form = useForm({
        event_date: '',
        event_type: preselect.eventType ?? '',
        coverage_zone_id: '',
        custom_city_name: '',
        event_time: '',
        venue_name: '',
        client_name: '',
        client_email: '',
        client_phone: '',
        accept_privacy: false,
        website: '',
    });
    const { data, setData, errors, processing } = form;

    const services = useMemo(() => categories.flatMap((group) => group.services), [categories]);
    const zone = zones.find((z) => String(z.id) === String(data.coverage_zone_id));
    const availability = useAvailability(data.event_date);

    const items = useMemo(
        () =>
            Object.entries(selections).map(([id, selection]) => ({
                service_id: Number(id),
                quantity: selection.quantity,
                extra_hours: selection.extra_hours,
            })),
        [selections],
    );
    const preview = useQuotePreview(items, data.coverage_zone_id, availability.byService ? data.event_date : null);

    // Preselección desde la ficha del servicio (/cotizador?servicio=slug).
    useEffect(() => {
        const service = services.find((s) => s.slug === preselect.service);
        if (service) setSelections({ [service.id]: { quantity: 1, extra_hours: 0 } });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Al conocer la disponibilidad de la fecha, quita lo agotado y ajusta cantidades.
    useEffect(() => {
        if (!availability.byService) return;
        const removed = [];
        const next = {};
        Object.entries(selections).forEach(([id, selection]) => {
            const status = availability.byService[id];
            if (!status?.available) {
                removed.push(services.find((s) => String(s.id) === id)?.name);
                return;
            }
            next[id] = { ...selection, quantity: Math.min(selection.quantity, status.remaining) };
        });
        setSelections(next);
        setNotice(removed.length ? `Quitamos de tu cotización lo que ya no tiene cupo para esa fecha: ${removed.filter(Boolean).join(', ')}.` : null);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [availability.byService]);

    useEffect(() => {
        headingRef.current?.focus();
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setSummaryOpen(false);
    }, [step]);

    const step1Ready =
        data.event_date >= today && data.event_type && zone && (zone.zone_type !== 'custom_quote' || data.custom_city_name.trim().length > 1);
    const step2Ready = items.length > 0 && !availability.loading && !preview.error;

    const setSelection = (serviceId, selection) =>
        setSelections((current) => {
            const next = { ...current };
            if (selection) next[serviceId] = selection;
            else delete next[serviceId];
            return next;
        });

    const submit = (event) => {
        event.preventDefault();
        form.transform((values) => ({ ...values, items }));
        form.post('/cotizador', {
            preserveScroll: true,
            onError: (errs) => {
                const keys = Object.keys(errs);
                if (keys.some((key) => STEP1_FIELDS.includes(key))) setStep(1);
                else if (keys.some((key) => key.startsWith('items'))) setStep(2);
            },
        });
    };

    const itemErrors = Object.entries(errors).filter(([key]) => key.startsWith('items'));
    const breakdown = preview.breakdown;

    const eventSummary = (
        <dl className="space-y-2 text-sm">
            <div className="flex items-start gap-2">
                <CalendarDays className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                <div>
                    <dt className="sr-only">Fecha y tipo</dt>
                    <dd className="text-ink">
                        {formatLongDate(data.event_date)} · {data.event_type}
                    </dd>
                </div>
            </div>
            <div className="flex items-start gap-2">
                <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                <div>
                    <dt className="sr-only">Ciudad</dt>
                    <dd className="text-ink">{zone?.zone_type === 'custom_quote' ? data.custom_city_name : zone?.city_name}</dd>
                </div>
            </div>
        </dl>
    );

    const continueButton =
        step === 2 ? (
            <Button size="lg" className="w-full" disabled={!step2Ready} onClick={() => setStep(3)}>
                Continuar
                <ArrowRight className="h-5 w-5" aria-hidden="true" />
            </Button>
        ) : null;

    return (
        <PublicLayout raiseFloating={step > 1 && items.length > 0}>
            <Head title="Cotizador en línea" />

            <div className={`mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8 ${step > 1 && items.length > 0 ? 'pb-36 lg:pb-14' : ''}`}>
                <SectionHeading
                    eyebrow="Cotizador en línea"
                    title="Arma tu evento y recibe tu cotización al instante"
                    description="Consulta disponibilidad real para tu fecha, elige servicios y descarga tu cotización formal en PDF."
                />

                <div className="mt-8">
                    <Stepper steps={STEPS} current={step} onSelect={setStep} />
                </div>

                <div className="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
                    <div className="min-w-0">
                        <h2 ref={headingRef} tabIndex={-1} className="font-display text-2xl font-semibold text-ink focus:outline-none">
                            {STEPS[step - 1]}
                        </h2>

                        {/* ---------------------------------------------------------------- Paso 1 */}
                        {step === 1 && (
                            <div className="mt-6 space-y-6">
                                <div className="grid gap-5 sm:grid-cols-2">
                                    <TextField
                                        label="Fecha del evento"
                                        type="date"
                                        required
                                        min={today}
                                        value={data.event_date}
                                        onChange={(value) => setData('event_date', value)}
                                        error={errors.event_date ?? availability.error}
                                        hint={availability.loading ? 'Consultando disponibilidad…' : availability.byService ? 'Disponibilidad actualizada para esta fecha.' : undefined}
                                    />
                                    <SelectField
                                        label="Tipo de evento"
                                        required
                                        placeholder="Elige una opción"
                                        options={eventTypes.map((type) => ({ value: type, label: type }))}
                                        value={data.event_type}
                                        onChange={(value) => setData('event_type', value)}
                                        error={errors.event_type}
                                    />
                                </div>

                                <fieldset>
                                    <legend className="mb-2 text-sm font-medium text-ink">
                                        Ciudad o zona del evento <span className="text-red-600 dark:text-red-400" aria-hidden="true">*</span>
                                    </legend>
                                    <div className="grid gap-2 sm:grid-cols-2">
                                        {zones.map((option) => (
                                            <label
                                                key={option.id}
                                                className={`flex min-h-touch cursor-pointer items-start gap-3 rounded-xl border p-3 transition ${
                                                    String(data.coverage_zone_id) === String(option.id) ? 'border-primary bg-primary/5' : 'border-line hover:bg-surface-sunken'
                                                }`}
                                            >
                                                <input
                                                    type="radio"
                                                    name="coverage_zone_id"
                                                    value={option.id}
                                                    checked={String(data.coverage_zone_id) === String(option.id)}
                                                    onChange={() => setData('coverage_zone_id', option.id)}
                                                    className="mt-1 h-4 w-4 text-primary focus:ring-primary"
                                                />
                                                <span className="min-w-0">
                                                    <span className="block text-sm font-semibold text-ink">{option.city_name}</span>
                                                    <span className="block text-xs text-ink-muted">Viáticos: {travelFeeLabel(option)}</span>
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    {errors.coverage_zone_id && <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">{errors.coverage_zone_id}</p>}
                                </fieldset>

                                {zone?.zone_type === 'custom_quote' && (
                                    <TextField
                                        label="¿En qué ciudad será tu evento?"
                                        required
                                        maxLength={120}
                                        value={data.custom_city_name}
                                        onChange={(value) => setData('custom_city_name', value)}
                                        error={errors.custom_city_name}
                                        hint="Confirmaremos los viáticos según la ubicación exacta."
                                    />
                                )}

                                <div className="flex justify-end">
                                    <Button size="lg" disabled={!step1Ready || availability.loading} onClick={() => setStep(2)} className="w-full sm:w-auto">
                                        {availability.loading ? (
                                            <LoaderCircle className="h-5 w-5 animate-spin" aria-hidden="true" />
                                        ) : (
                                            <>
                                                Elegir servicios
                                                <ArrowRight className="h-5 w-5" aria-hidden="true" />
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* ---------------------------------------------------------------- Paso 2 */}
                        {step === 2 && (
                            <div className="mt-6 space-y-8">
                                <p className="text-sm text-ink-muted">
                                    Disponibilidad para el <strong className="text-ink">{formatLongDate(data.event_date)}</strong>. Lo agotado no se puede agregar.
                                </p>
                                {(notice || itemErrors.length > 0) && (
                                    <div role="alert" className="flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                                        <CircleAlert className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                                        <div className="space-y-1">
                                            {notice && <p>{notice}</p>}
                                            {[...new Set(itemErrors.map(([, message]) => message))].map((message) => (
                                                <p key={message}>{message}</p>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {categories.map((group) => (
                                    <section key={group.category.id} aria-labelledby={`cat-${group.category.id}`}>
                                        <h3 id={`cat-${group.category.id}`} className="mb-3 text-sm font-semibold uppercase tracking-widest text-primary">
                                            {group.category.name}
                                        </h3>
                                        <div className="grid gap-3 xl:grid-cols-2">
                                            {group.services.map((service) => (
                                                <ServiceOption
                                                    key={service.id}
                                                    service={service}
                                                    selection={selections[service.id]}
                                                    availability={availability.byService?.[service.id]}
                                                    limits={limits}
                                                    onChange={(selection) => setSelection(service.id, selection)}
                                                />
                                            ))}
                                        </div>
                                    </section>
                                ))}

                                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                                    <Button variant="outline" onClick={() => setStep(1)}>
                                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                                        Cambiar fecha o ciudad
                                    </Button>
                                    <div className="hidden lg:block">{continueButton}</div>
                                </div>
                            </div>
                        )}

                        {/* ---------------------------------------------------------------- Paso 3 */}
                        {step === 3 && (
                            <form onSubmit={submit} className="mt-6 space-y-6" noValidate>
                                <div className="flex flex-wrap items-start justify-between gap-3 rounded-xl bg-surface-sunken p-4">
                                    {eventSummary}
                                    <button type="button" onClick={() => setStep(1)} className="inline-flex min-h-touch items-center gap-1 text-sm font-medium text-primary hover:underline">
                                        <Pencil className="h-4 w-4" aria-hidden="true" />
                                        Editar
                                    </button>
                                </div>

                                <div className="grid gap-5 sm:grid-cols-2">
                                    <TextField
                                        label="Nombre completo"
                                        required
                                        autoComplete="name"
                                        maxLength={120}
                                        value={data.client_name}
                                        onChange={(value) => setData('client_name', value)}
                                        error={errors.client_name}
                                        className="sm:col-span-2"
                                    />
                                    <TextField
                                        label="Correo electrónico"
                                        type="email"
                                        required
                                        autoComplete="email"
                                        value={data.client_email}
                                        onChange={(value) => setData('client_email', value)}
                                        error={errors.client_email}
                                        hint="Aquí te enviaremos tu cotización en PDF."
                                    />
                                    <TextField
                                        label="Teléfono o WhatsApp"
                                        type="tel"
                                        required
                                        autoComplete="tel"
                                        inputMode="tel"
                                        value={data.client_phone}
                                        onChange={(value) => setData('client_phone', value)}
                                        error={errors.client_phone}
                                    />
                                    <TextField
                                        label="Hora estimada"
                                        type="time"
                                        value={data.event_time}
                                        onChange={(value) => setData('event_time', value)}
                                        error={errors.event_time}
                                    />
                                    <TextField
                                        label="Salón o lugar del evento"
                                        maxLength={160}
                                        value={data.venue_name}
                                        onChange={(value) => setData('venue_name', value)}
                                        error={errors.venue_name}
                                    />
                                </div>

                                {/* Trampa anti-spam: oculta para personas y lectores de pantalla. */}
                                <div aria-hidden="true" className="absolute -left-[9999px] h-px w-px overflow-hidden">
                                    <label htmlFor="website">No llenes este campo</label>
                                    <input id="website" type="text" tabIndex={-1} autoComplete="off" value={data.website} onChange={(e) => setData('website', e.target.value)} className={inputClass} />
                                </div>

                                <div>
                                    <label className="flex min-h-touch cursor-pointer items-start gap-3 text-sm text-ink">
                                        <input
                                            type="checkbox"
                                            checked={data.accept_privacy}
                                            onChange={(e) => setData('accept_privacy', e.target.checked)}
                                            aria-invalid={!!errors.accept_privacy}
                                            className="mt-0.5 h-5 w-5 shrink-0 rounded border-line text-primary focus:ring-primary"
                                        />
                                        <span>
                                            Acepto el{' '}
                                            <Link href="/aviso-de-privacidad" target="_blank" className="font-medium text-primary underline underline-offset-2">
                                                aviso de privacidad
                                            </Link>{' '}
                                            y que me contacten sobre esta cotización.
                                        </span>
                                    </label>
                                    {errors.accept_privacy && <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">{errors.accept_privacy}</p>}
                                    {errors.website && <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">{errors.website}</p>}
                                </div>

                                <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                                    <Button variant="outline" onClick={() => setStep(2)}>
                                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                                        Volver a mis servicios
                                    </Button>
                                    <Button type="submit" size="lg" disabled={processing || items.length === 0}>
                                        {processing ? <LoaderCircle className="h-5 w-5 animate-spin" aria-hidden="true" /> : <FileText className="h-5 w-5" aria-hidden="true" />}
                                        {processing ? 'Generando tu cotización…' : 'Generar mi cotización en PDF'}
                                    </Button>
                                </div>
                            </form>
                        )}
                    </div>

                    {/* Resumen de escritorio */}
                    <aside className="hidden lg:block lg:sticky lg:top-24 lg:self-start" aria-label="Resumen de tu cotización">
                        <div className="rounded-2xl border border-line bg-surface-raised p-5 shadow-sm">
                            <h2 className="mb-4 font-semibold text-ink">Tu cotización</h2>
                            {step > 1 && <div className="mb-4 border-b border-line pb-4">{eventSummary}</div>}
                            <QuoteSummary breakdown={breakdown} loading={preview.loading} error={preview.error} zoneSelected={!!zone} />
                            {continueButton && <div className="mt-5">{continueButton}</div>}
                        </div>
                    </aside>
                </div>
            </div>

            {/* Resumen flotante colapsable en móvil */}
            {step > 1 && items.length > 0 && (
                <div className="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface-raised shadow-[0_-8px_24px_rgba(0,0,0,0.12)] lg:hidden">
                    {summaryOpen && (
                        <div id={summaryId} className="max-h-[55dvh] overflow-y-auto border-b border-line px-4 py-4">
                            <QuoteSummary breakdown={breakdown} loading={preview.loading} error={preview.error} zoneSelected={!!zone} />
                        </div>
                    )}
                    <div className="flex items-center gap-3 px-4 py-3">
                        <button
                            type="button"
                            onClick={() => setSummaryOpen((open) => !open)}
                            aria-expanded={summaryOpen}
                            aria-controls={summaryId}
                            className="flex min-h-touch min-w-0 flex-1 items-center gap-2 text-left"
                        >
                            <ChevronUp className={`h-5 w-5 shrink-0 text-ink-muted transition ${summaryOpen ? 'rotate-180' : ''}`} aria-hidden="true" />
                            <span className="min-w-0">
                                <span className="block text-xs text-ink-muted">{summaryOpen ? 'Ocultar detalle' : 'Total estimado · ver detalle'}</span>
                                <span className="block truncate font-display text-xl font-semibold tabular-nums text-ink">
                                    {breakdown ? formatMoney(breakdown.total) : '—'}
                                    {preview.loading && <LoaderCircle className="ml-2 inline h-4 w-4 animate-spin text-ink-muted" aria-hidden="true" />}
                                </span>
                                {breakdown?.discount_applies && (
                                    <span className="flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                        <BadgePercent className="h-3.5 w-3.5" aria-hidden="true" />
                                        Ahorras {formatMoney(breakdown.discount_amount)}
                                    </span>
                                )}
                            </span>
                        </button>
                        {step === 2 && (
                            <Button disabled={!step2Ready} onClick={() => setStep(3)} className="shrink-0">
                                Continuar
                                <ArrowRight className="h-4 w-4" aria-hidden="true" />
                            </Button>
                        )}
                    </div>
                </div>
            )}
        </PublicLayout>
    );
}
