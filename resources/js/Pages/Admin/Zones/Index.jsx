import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { MapPinned, Pencil, Plus, Trash2 } from 'lucide-react';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { SelectField, TextAreaField, TextField, ToggleField } from '@/Components/Form/fields';
import Modal from '@/Components/Modal';
import AdminLayout from '@/Layouts/AdminLayout';
import { ZONE_TYPES, travelFeeLabel, zoneTypeMeta } from '@/lib/zones';

const EMPTY = {
    city_name: '',
    state_name: '',
    zone_type: 'foranea',
    travel_fee_type: 'fixed',
    travel_fee_value: '',
    notes: '',
    is_active: true,
    sort_order: 0,
};

function ZoneForm({ zone, onClose }) {
    const { data, setData, post, put, processing, errors } = useForm(
        zone
            ? {
                  city_name: zone.city_name ?? '',
                  state_name: zone.state_name ?? '',
                  zone_type: zone.zone_type,
                  travel_fee_type: zone.travel_fee_type ?? 'fixed',
                  travel_fee_value: zone.travel_fee_value ?? '',
                  notes: zone.notes ?? '',
                  is_active: !!zone.is_active,
                  sort_order: zone.sort_order ?? 0,
              }
            : EMPTY,
    );

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };
        if (zone) put(`/admin/zonas/${zone.id}`, options);
        else post('/admin/zonas', options);
    };

    const field = (name) => ({ value: data[name], onChange: (value) => setData(name, value), error: errors[name] });
    const isForanea = data.zone_type === 'foranea';
    const isPercentage = data.travel_fee_type === 'percentage';

    return (
        <Modal
            open
            onClose={onClose}
            title={zone ? `Editar ${zone.city_name}` : 'Nueva zona de cobertura'}
            footer={
                <>
                    <Button variant="outline" onClick={onClose}>
                        Cancelar
                    </Button>
                    <Button type="submit" form="zone-form" disabled={processing}>
                        {processing ? 'Guardando…' : 'Guardar zona'}
                    </Button>
                </>
            }
        >
            <form id="zone-form" onSubmit={submit} className="space-y-5" noValidate>
                <div className="grid gap-5 sm:grid-cols-2">
                    <TextField label="Ciudad o zona" required maxLength={120} data-autofocus {...field('city_name')} />
                    <TextField label="Estado" maxLength={120} {...field('state_name')} />
                </div>

                <fieldset>
                    <legend className="mb-2 text-sm font-medium text-ink">Tipo de zona</legend>
                    <div className="grid gap-2">
                        {ZONE_TYPES.map((type) => (
                            <label
                                key={type.value}
                                className={`flex min-h-touch cursor-pointer items-start gap-3 rounded-xl border p-3 transition ${
                                    data.zone_type === type.value ? 'border-primary bg-primary/5' : 'border-line hover:bg-surface-sunken'
                                }`}
                            >
                                <input
                                    type="radio"
                                    name="zone_type"
                                    value={type.value}
                                    checked={data.zone_type === type.value}
                                    onChange={() => setData('zone_type', type.value)}
                                    className="mt-1 h-4 w-4 text-primary focus:ring-primary"
                                />
                                <span>
                                    <span className="block text-sm font-semibold text-ink">{type.label}</span>
                                    <span className="block text-xs text-ink-muted">{type.description}</span>
                                </span>
                            </label>
                        ))}
                    </div>
                    {errors.zone_type && <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">{errors.zone_type}</p>}
                </fieldset>

                {isForanea && (
                    <div className="grid gap-5 rounded-xl bg-surface-sunken p-4 sm:grid-cols-2">
                        <SelectField
                            label="Cobro de viáticos"
                            options={[
                                { value: 'fixed', label: 'Monto fijo ($ MXN)' },
                                { value: 'percentage', label: 'Porcentaje del subtotal (%)' },
                            ]}
                            {...field('travel_fee_type')}
                        />
                        <TextField
                            label={isPercentage ? 'Porcentaje' : 'Monto (MXN)'}
                            type="number"
                            inputMode="decimal"
                            min="0"
                            max={isPercentage ? 100 : undefined}
                            step="0.01"
                            required
                            {...field('travel_fee_value')}
                        />
                    </div>
                )}

                <TextAreaField
                    label="Notas"
                    hint="Ej. leyenda que se mostrará en la cotización."
                    rows={2}
                    maxLength={500}
                    {...field('notes')}
                />

                <div className="grid gap-5 sm:grid-cols-2">
                    <ToggleField
                        label="Zona activa"
                        description="Visible en el cotizador."
                        checked={data.is_active}
                        onChange={(value) => setData('is_active', value)}
                    />
                    <TextField label="Orden" type="number" inputMode="numeric" min="0" {...field('sort_order')} />
                </div>
            </form>
        </Modal>
    );
}

export default function Index({ zones }) {
    const [editing, setEditing] = useState(null); // null | 'new' | zona
    const [deleting, setDeleting] = useState(null);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        router.delete(`/admin/zonas/${deleting.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    const actions = (zone) => (
        <div className="flex gap-1">
            <Button variant="ghost" size="sm" onClick={() => setEditing(zone)} aria-label={`Editar ${zone.city_name}`}>
                <Pencil className="h-4 w-4" aria-hidden="true" />
                <span className="sm:sr-only lg:not-sr-only">Editar</span>
            </Button>
            <Button
                variant="ghost"
                size="sm"
                onClick={() => setDeleting(zone)}
                aria-label={`Eliminar ${zone.city_name}`}
                className="text-red-600 dark:text-red-400"
            >
                <Trash2 className="h-4 w-4" aria-hidden="true" />
            </Button>
        </div>
    );

    return (
        <AdminLayout
            title="Zonas y viáticos"
            actions={
                <Button onClick={() => setEditing('new')}>
                    <Plus className="h-4 w-4" aria-hidden="true" />
                    Nueva zona
                </Button>
            }
        >
            <Card
                title="Zonas de cobertura"
                description="Define dónde ofreces servicio y cuánto se cobra de traslado. La sede local nunca cobra viáticos."
            >
                {zones.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 py-10 text-center text-ink-muted">
                        <MapPinned className="h-10 w-10" aria-hidden="true" />
                        <p>Aún no tienes zonas registradas.</p>
                        <Button onClick={() => setEditing('new')}>Agregar la primera zona</Button>
                    </div>
                ) : (
                    <>
                        {/* Móvil: tarjetas apiladas */}
                        <ul className="space-y-3 md:hidden">
                            {zones.map((zone) => {
                                const meta = zoneTypeMeta(zone.zone_type);
                                return (
                                    <li key={zone.id} className="rounded-xl border border-line p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="font-semibold text-ink">{zone.city_name}</p>
                                                {zone.state_name && <p className="text-sm text-ink-muted">{zone.state_name}</p>}
                                            </div>
                                            {actions(zone)}
                                        </div>
                                        <div className="mt-3 flex flex-wrap items-center gap-2 text-sm">
                                            <Badge tone={meta.tone}>{meta.label}</Badge>
                                            {!zone.is_active && <Badge tone="danger">Inactiva</Badge>}
                                            <span className="font-medium text-ink">{travelFeeLabel(zone)}</span>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>

                        {/* Escritorio: tabla */}
                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                                    <tr>
                                        <th scope="col" className="py-3 pr-4 font-medium">Zona</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Tipo</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Viáticos</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Estado</th>
                                        <th scope="col" className="py-3 text-right font-medium">
                                            <span className="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {zones.map((zone) => {
                                        const meta = zoneTypeMeta(zone.zone_type);
                                        return (
                                            <tr key={zone.id}>
                                                <td className="py-3 pr-4">
                                                    <p className="font-medium text-ink">{zone.city_name}</p>
                                                    {zone.state_name && <p className="text-ink-muted">{zone.state_name}</p>}
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <Badge tone={meta.tone}>{meta.label}</Badge>
                                                </td>
                                                <td className="py-3 pr-4 font-medium text-ink">{travelFeeLabel(zone)}</td>
                                                <td className="py-3 pr-4">
                                                    <Badge tone={zone.is_active ? 'success' : 'danger'}>{zone.is_active ? 'Activa' : 'Inactiva'}</Badge>
                                                </td>
                                                <td className="py-3">
                                                    <div className="flex justify-end">{actions(zone)}</div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}
            </Card>

            {editing && <ZoneForm zone={editing === 'new' ? null : editing} onClose={() => setEditing(null)} />}

            <ConfirmDialog
                open={!!deleting}
                title="Eliminar zona"
                message={
                    deleting?.quotes_count > 0
                        ? `${deleting.city_name} tiene cotizaciones registradas, así que no se puede eliminar. Te recomendamos desactivarla.`
                        : `¿Seguro que quieres eliminar ${deleting?.city_name}? Esta acción no se puede deshacer.`
                }
                processing={processing}
                onConfirm={confirmDelete}
                onCancel={() => setDeleting(null)}
            />
        </AdminLayout>
    );
}
