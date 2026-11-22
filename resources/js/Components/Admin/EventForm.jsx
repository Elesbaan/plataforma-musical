import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { CircleAlert, Plus, Save, Trash2, TriangleAlert } from 'lucide-react';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import { SelectField, TextAreaField, TextField, inputClass } from '@/Components/Form/fields';
import useEventCapacity from '@/hooks/useEventCapacity';
import { EVENT_STATUSES } from '@/lib/events';
import { formatMoney } from '@/lib/format';

export function emptyEvent(date = '') {
    return {
        title: '',
        event_date: date,
        start_time: '',
        end_time: '',
        city_name: '',
        venue_name: '',
        client_name: '',
        client_phone: '',
        agreed_total: '',
        deposit_paid: 0,
        status: 'reserved',
        internal_notes: '',
        services: [{ service_id: '', quantity: 1 }],
        force_overbooking: false,
    };
}

/**
 * Formulario de evento (alta, edición y conversión de cotización) con alertas
 * en vivo de capacidad diaria y confirmación explícita ante sobrecupo.
 */
export default function EventForm({ initial, services, submitUrl, method = 'post', excludeEventId = null, submitLabel = 'Guardar evento', onCancel, cancelHref, compact = false }) {
    const { data, setData, submit, processing, errors, transform, clearErrors } = useForm({ ...emptyEvent(), ...initial });
    const capacity = useEventCapacity(data.event_date, excludeEventId);
    const [forcing, setForcing] = useState(false);

    const byId = Object.fromEntries(services.map((s) => [String(s.id), s]));
    const conflicts = data.services
        .filter((row) => row.service_id && capacity.reserved)
        .map((row) => {
            const service = byId[String(row.service_id)];
            const reserved = Number(capacity.reserved[row.service_id] ?? 0);
            const total = reserved + Number(row.quantity || 0);
            return service && total >= service.daily_capacity ? { ...row, service, reserved, total, over: total > service.daily_capacity } : null;
        })
        .filter(Boolean);
    const hasOver = conflicts.some((c) => c.over) && data.status === 'reserved';

    const send = (force) => {
        transform((values) => ({ ...values, force_overbooking: force }));
        setForcing(force);
        submit(method, submitUrl, { preserveScroll: true, onFinish: () => setForcing(false) });
    };

    const updateRow = (index, changes) => setData('services', data.services.map((row, i) => (i === index ? { ...row, ...changes } : row)));
    const field = (name) => ({ value: data[name], onChange: (value) => setData(name, value), error: errors[name] });
    const balance = Math.max(0, Number(data.agreed_total || 0) - Number(data.deposit_paid || 0));
    const serviceErrors = Object.entries(errors).filter(([key]) => key.startsWith('services'));

    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                clearErrors();
                send(false);
            }}
            className="space-y-6"
            noValidate
        >
            <div className={`grid gap-6 ${compact ? '' : 'xl:grid-cols-2'}`}>
                <Card title="Evento">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <TextField label="Título" required maxLength={160} className="sm:col-span-2" placeholder="Ej. Boda — Hacienda San Gabriel" {...field('title')} />
                        <TextField label="Fecha" type="date" required {...field('event_date')} />
                        <SelectField label="Estatus" options={EVENT_STATUSES} {...field('status')} />
                        <TextField label="Hora de inicio" type="time" {...field('start_time')} />
                        <TextField label="Hora de término" type="time" {...field('end_time')} />
                        <TextField label="Ciudad" required maxLength={120} {...field('city_name')} />
                        <TextField label="Salón o lugar" maxLength={160} {...field('venue_name')} />
                    </div>
                </Card>

                <Card title="Cliente y pagos">
                    <div className="grid gap-5 sm:grid-cols-2">
                        <TextField label="Cliente" required maxLength={120} {...field('client_name')} />
                        <TextField label="Teléfono" type="tel" maxLength={30} {...field('client_phone')} />
                        <TextField label="Monto acordado (MXN)" type="number" inputMode="decimal" min="0" step="0.01" required {...field('agreed_total')} />
                        <TextField label="Anticipo recibido (MXN)" type="number" inputMode="decimal" min="0" step="0.01" {...field('deposit_paid')} />
                    </div>
                    <p className="mt-4 flex items-baseline justify-between rounded-xl bg-surface-sunken px-4 py-3 text-sm">
                        <span className="text-ink-muted">Saldo pendiente</span>
                        <span className="text-lg font-semibold tabular-nums text-ink">{formatMoney(balance)}</span>
                    </p>
                </Card>
            </div>

            <Card title="Servicios comprometidos" description="Cada unidad descuenta la capacidad diaria del servicio en esta fecha.">
                <ul className="space-y-3">
                    {data.services.map((row, index) => {
                        const conflict = conflicts.find((c) => String(c.service_id) === String(row.service_id));
                        return (
                            <li key={index} className="rounded-xl border border-line p-3">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                                    <div className="min-w-0 flex-1">
                                        <label htmlFor={`svc-${index}`} className="mb-1 block text-xs text-ink-muted">
                                            Servicio
                                        </label>
                                        <select
                                            id={`svc-${index}`}
                                            value={row.service_id}
                                            onChange={(e) => updateRow(index, { service_id: e.target.value })}
                                            className={inputClass}
                                            aria-invalid={!!errors[`services.${index}.service_id`]}
                                        >
                                            <option value="">Elige un servicio</option>
                                            {services.map((service) => (
                                                <option key={service.id} value={service.id}>
                                                    {service.name} (capacidad {service.daily_capacity}/día){service.is_active ? '' : ' — inactivo'}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="flex items-end gap-2">
                                        <div className="w-24">
                                            <label htmlFor={`qty-${index}`} className="mb-1 block text-xs text-ink-muted">
                                                Cantidad
                                            </label>
                                            <input
                                                id={`qty-${index}`}
                                                type="number"
                                                min="1"
                                                max="50"
                                                inputMode="numeric"
                                                value={row.quantity}
                                                onChange={(e) => updateRow(index, { quantity: e.target.value })}
                                                className={inputClass}
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => setData('services', data.services.filter((_, i) => i !== index))}
                                            disabled={data.services.length === 1}
                                            aria-label="Quitar servicio"
                                            className="inline-flex min-h-touch min-w-touch items-center justify-center rounded-xl text-red-600 hover:bg-red-50 disabled:opacity-30 dark:text-red-400 dark:hover:bg-red-950"
                                        >
                                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>
                                {conflict && (
                                    <p
                                        className={`mt-2 flex items-center gap-2 text-xs font-medium ${
                                            conflict.over ? 'text-red-700 dark:text-red-400' : 'text-amber-700 dark:text-amber-400'
                                        }`}
                                    >
                                        {conflict.over ? <CircleAlert className="h-4 w-4" aria-hidden="true" /> : <TriangleAlert className="h-4 w-4" aria-hidden="true" />}
                                        {conflict.over
                                            ? `Supera su capacidad diaria: ${conflict.total} de ${conflict.service.daily_capacity} en esta fecha.`
                                            : `Alcanza su capacidad diaria (${conflict.total} de ${conflict.service.daily_capacity}); ya no quedarán lugares ese día.`}
                                    </p>
                                )}
                            </li>
                        );
                    })}
                </ul>
                <button
                    type="button"
                    onClick={() => setData('services', [...data.services, { service_id: '', quantity: 1 }])}
                    className="mt-3 inline-flex min-h-touch items-center gap-2 rounded-full border border-dashed border-line px-4 text-sm font-semibold text-ink hover:bg-surface-sunken"
                >
                    <Plus className="h-4 w-4" aria-hidden="true" />
                    Agregar servicio
                </button>
                {serviceErrors.length > 0 && (
                    <ul className="mt-3 space-y-1 text-sm text-red-600 dark:text-red-400">
                        {[...new Set(serviceErrors.map(([, message]) => message))].map((message) => (
                            <li key={message}>{message}</li>
                        ))}
                    </ul>
                )}
            </Card>

            <Card title="Notas internas">
                <TextAreaField label="Notas" rows={3} maxLength={5000} {...field('internal_notes')} />
            </Card>

            {(errors.overbooking || hasOver) && (
                <div role="alert" className="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
                    <p className="flex items-start gap-2 font-semibold">
                        <CircleAlert className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                        Alerta de sobrecupo
                    </p>
                    <p className="mt-1">
                        {errors.overbooking ?? 'Uno o más servicios superan su capacidad diaria en esta fecha.'} Puedes ajustar las cantidades o confirmar el evento bajo tu criterio.
                    </p>
                    {errors.overbooking && (
                        <Button variant="danger" className="mt-3" disabled={processing} onClick={() => send(true)}>
                            {processing && forcing ? 'Guardando…' : 'Confirmar de todos modos'}
                        </Button>
                    )}
                </div>
            )}

            <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                {onCancel && (
                    <Button variant="outline" onClick={onCancel}>
                        Cancelar
                    </Button>
                )}
                {cancelHref && (
                    <Button variant="outline" href={cancelHref}>
                        Cancelar
                    </Button>
                )}
                <Button type="submit" disabled={processing}>
                    <Save className="h-4 w-4" aria-hidden="true" />
                    {processing && !forcing ? 'Guardando…' : submitLabel}
                </Button>
            </div>
        </form>
    );
}
