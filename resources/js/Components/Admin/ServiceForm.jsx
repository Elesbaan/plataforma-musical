import { useForm } from '@inertiajs/react';
import { Lock, Save } from 'lucide-react';
import FeatureListEditor from '@/Components/Admin/FeatureListEditor';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import { SelectField, TextAreaField, TextField, ToggleField } from '@/Components/Form/fields';
import { SERVICE_TYPES, serviceTypeLabel } from '@/lib/catalog';
import { formatMoney } from '@/lib/format';

/** Campos que puede editar quien solo tiene alcance propio (catalog.manage_own). */
const CONTENT_FIELDS = ['short_description', 'full_description', 'daily_capacity', 'features'];

function initialData(service, categories) {
    return {
        category_id: service?.category_id ?? categories[0]?.id ?? '',
        name: service?.name ?? '',
        short_description: service?.short_description ?? '',
        full_description: service?.full_description ?? '',
        service_type: service?.service_type ?? 'core_band',
        base_price: service?.base_price ?? '',
        price_unit: service?.price_unit ?? '',
        extra_hour_price: service?.extra_hour_price ?? '',
        daily_capacity: service?.daily_capacity ?? 1,
        is_featured: service?.is_featured ?? false,
        is_active: service?.is_active ?? true,
        sort_order: service?.sort_order ?? 0,
        features: service?.features?.map((feature) => feature.feature_text) ?? [],
    };
}

function ReadOnlyRow({ label, children }) {
    return (
        <div className="flex flex-col gap-0.5 border-b border-line py-2 last:border-0 sm:flex-row sm:justify-between sm:gap-4">
            <dt className="text-sm text-ink-muted">{label}</dt>
            <dd className="text-sm font-medium text-ink sm:text-right">{children}</dd>
        </div>
    );
}

/**
 * Formulario compartido para crear y editar servicios.
 * Con `restricted` (alcance propio) solo se editan contenido, características y
 * capacidad; los datos comerciales se muestran como solo lectura.
 */
export default function ServiceForm({ service = null, categories, restricted = false }) {
    const { data, setData, post, put, processing, errors, isDirty, transform } = useForm(initialData(service, categories));

    transform((values) => (restricted ? Object.fromEntries(CONTENT_FIELDS.map((key) => [key, values[key]])) : values));

    const submit = (event) => {
        event.preventDefault();
        if (service) put(`/admin/servicios/${service.id}`, { preserveScroll: true });
        else post('/admin/servicios');
    };

    const field = (name) => ({ value: data[name], onChange: (value) => setData(name, value), error: errors[name] });
    const restrictedError = Object.entries(errors).find(([key]) => !CONTENT_FIELDS.includes(key) && !key.startsWith('features.'))?.[1];

    return (
        <form onSubmit={submit} className="space-y-6" noValidate>
            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <div className="min-w-0 space-y-6">
                    <Card title={restricted ? 'Descripción' : 'Información general'}>
                        <div className="grid gap-5 md:grid-cols-2">
                            {!restricted && (
                                <>
                                    <TextField label="Nombre del servicio" required maxLength={150} className="md:col-span-2" {...field('name')} />
                                    <SelectField
                                        label="Categoría"
                                        required
                                        options={categories.map((category) => ({
                                            value: category.id,
                                            label: category.is_active ? category.name : `${category.name} (inactiva)`,
                                        }))}
                                        {...field('category_id')}
                                    />
                                    <SelectField label="Tipo de servicio" required options={SERVICE_TYPES} {...field('service_type')} />
                                </>
                            )}
                            <TextAreaField
                                label="Descripción corta"
                                hint="Aparece en las tarjetas del catálogo."
                                rows={2}
                                maxLength={300}
                                className="md:col-span-2"
                                {...field('short_description')}
                            />
                            <TextAreaField
                                label="Descripción completa"
                                hint="Se muestra en la página de detalle del servicio."
                                rows={6}
                                maxLength={10000}
                                className="md:col-span-2"
                                {...field('full_description')}
                            />
                        </div>
                    </Card>

                    <Card title="¿Qué incluye?" description="Lista de características que verá el cliente. Usa Enter para agregar otra.">
                        <FeatureListEditor features={data.features} onChange={(features) => setData('features', features)} errors={errors} />
                    </Card>
                </div>

                <div className="min-w-0 space-y-6">
                    {restricted ? (
                        <Card title="Datos comerciales" description="Solo el equipo administrador puede cambiarlos.">
                            <dl>
                                <ReadOnlyRow label="Servicio">{service?.name}</ReadOnlyRow>
                                <ReadOnlyRow label="Categoría">{service?.category?.name ?? '—'}</ReadOnlyRow>
                                <ReadOnlyRow label="Tipo">{serviceTypeLabel(service?.service_type)}</ReadOnlyRow>
                                <ReadOnlyRow label="Precio base">
                                    {formatMoney(service?.base_price)} <span className="font-normal text-ink-muted">{service?.price_unit}</span>
                                </ReadOnlyRow>
                                <ReadOnlyRow label="Hora extra">{service?.extra_hour_price ? formatMoney(service.extra_hour_price) : 'No aplica'}</ReadOnlyRow>
                                <ReadOnlyRow label="Publicación">{service?.is_active ? 'Activo' : 'Inactivo'}</ReadOnlyRow>
                            </dl>
                            <p className="mt-3 flex items-start gap-2 text-xs text-ink-muted">
                                <Lock className="mt-0.5 h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                Si necesitas ajustar el precio, contacta al administrador.
                            </p>
                            {restrictedError && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{restrictedError}</p>}
                        </Card>
                    ) : (
                        <Card title="Precio">
                            <div className="grid gap-5">
                                <TextField
                                    label="Precio base (MXN)"
                                    type="number"
                                    inputMode="decimal"
                                    min="0"
                                    step="0.01"
                                    required
                                    hint={data.base_price !== '' ? `Se mostrará como “Desde ${formatMoney(data.base_price)} MXN”.` : undefined}
                                    {...field('base_price')}
                                />
                                <TextField label="Unidad de precio" required maxLength={60} placeholder="Ej. por evento (5 hrs)" {...field('price_unit')} />
                                <TextField
                                    label="Tarifa por hora extra (MXN)"
                                    type="number"
                                    inputMode="decimal"
                                    min="0"
                                    step="0.01"
                                    hint="Opcional. Déjala vacía si este servicio no ofrece horas extra."
                                    {...field('extra_hour_price')}
                                />
                            </div>
                        </Card>
                    )}

                    <Card title="Disponibilidad">
                        <TextField
                            label="Capacidad diaria"
                            type="number"
                            inputMode="numeric"
                            min="1"
                            max="999"
                            required
                            hint="Cuántos eventos puede cubrir este servicio en una misma fecha. Al alcanzarse, se bloquea en el cotizador."
                            {...field('daily_capacity')}
                        />
                    </Card>

                    {!restricted && (
                        <Card title="Publicación">
                            <div className="grid gap-3">
                                <ToggleField
                                    label="Activo"
                                    description="Visible en el sitio y el cotizador."
                                    checked={data.is_active}
                                    onChange={(value) => setData('is_active', value)}
                                />
                                <ToggleField
                                    label="Destacado"
                                    description="Aparece en la portada como paquete estrella."
                                    checked={data.is_featured}
                                    onChange={(value) => setData('is_featured', value)}
                                />
                                <TextField label="Orden" type="number" inputMode="numeric" min="0" {...field('sort_order')} />
                            </div>
                        </Card>
                    )}
                </div>
            </div>

            <div className="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center justify-end gap-3 border-t border-line bg-surface-sunken/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                {isDirty && <span className="mr-auto text-sm text-ink-muted">Tienes cambios sin guardar.</span>}
                <Button variant="outline" href="/admin/servicios" className="flex-1 sm:flex-none">
                    Volver
                </Button>
                <Button type="submit" disabled={processing} className="flex-1 sm:flex-none">
                    <Save className="h-4 w-4" aria-hidden="true" />
                    {processing ? 'Guardando…' : service ? 'Guardar cambios' : 'Crear servicio'}
                </Button>
            </div>
        </form>
    );
}
