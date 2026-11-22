import { useRef, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import BrandPreview from '@/Components/Admin/BrandPreview';
import ColorField from '@/Components/Admin/ColorField';
import ImageUploadField from '@/Components/Admin/ImageUploadField';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import { SelectField, TextAreaField, TextField, ToggleField } from '@/Components/Form/fields';
import AdminLayout from '@/Layouts/AdminLayout';

const TABS = [
    {
        key: 'brand',
        label: 'Marca y logos',
        fields: ['brand_name', 'tagline', 'seo_description', 'hero_title', 'hero_subtitle', 'hero_video_url', 'logo_light', 'logo_dark', 'favicon'],
    },
    { key: 'colors', label: 'Colores', fields: ['color_primary', 'color_secondary', 'color_accent'] },
    {
        key: 'contact',
        label: 'Contacto y redes',
        fields: ['whatsapp_number', 'phone_number', 'contact_email', 'address_text', 'facebook_url', 'instagram_url', 'tiktok_url', 'youtube_url', 'spotify_url'],
    },
    {
        key: 'quotes',
        label: 'Cotizador y descuento web',
        fields: ['online_discount_active', 'online_discount_type', 'online_discount_value', 'online_discount_min_amount', 'quote_validity_days', 'quote_terms_text'],
    },
];

const EDITABLE_FIELDS = TABS.flatMap((tab) => tab.fields).filter((field) => !['logo_light', 'logo_dark', 'favicon'].includes(field));

export default function Edit({ settings }) {
    const [activeTab, setActiveTab] = useState('brand');
    const tabRefs = useRef({});

    const initial = Object.fromEntries(EDITABLE_FIELDS.map((field) => [field, settings[field] ?? '']));
    const { data, setData, post, processing, errors, transform, isDirty } = useForm({
        ...initial,
        online_discount_active: !!settings.online_discount_active,
        online_discount_type: settings.online_discount_type ?? 'percentage',
        logo_light: null,
        logo_dark: null,
        favicon: null,
        remove_logo_light: false,
        remove_logo_dark: false,
        remove_favicon: false,
    });

    transform((values) => ({ ...values, _method: 'put' }));

    const submit = (event) => {
        event.preventDefault();
        post('/admin/identidad', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setData((current) => ({
                ...current,
                logo_light: null,
                logo_dark: null,
                favicon: null,
                remove_logo_light: false,
                remove_logo_dark: false,
                remove_favicon: false,
            })),
            onError: (errs) => {
                const tabWithError = TABS.find((tab) => tab.fields.some((field) => errs[field]));
                if (tabWithError) setActiveTab(tabWithError.key);
            },
        });
    };

    const field = (name) => ({
        value: data[name],
        onChange: (value) => setData(name, value),
        error: errors[name],
    });

    const imageField = (name, currentUrl) => ({
        currentUrl,
        file: data[name],
        removed: data[`remove_${name}`],
        error: errors[name],
        onSelect: (file) => setData((current) => ({ ...current, [name]: file, [`remove_${name}`]: false })),
        onRemove: () => setData((current) => ({ ...current, [name]: null, [`remove_${name}`]: true })),
    });

    const onTabKeyDown = (event, index) => {
        const direction = { ArrowRight: 1, ArrowLeft: -1 }[event.key];
        if (event.key === 'Home' || event.key === 'End' || direction) {
            event.preventDefault();
            const nextIndex =
                event.key === 'Home' ? 0 : event.key === 'End' ? TABS.length - 1 : (index + direction + TABS.length) % TABS.length;
            const next = TABS[nextIndex].key;
            setActiveTab(next);
            tabRefs.current[next]?.focus();
        }
    };

    const isPercentage = data.online_discount_type === 'percentage';

    return (
        <AdminLayout title="Identidad y marca">
            <form onSubmit={submit} className="space-y-6" noValidate>
                <div className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                    <div role="tablist" aria-label="Secciones de identidad" className="inline-flex min-w-full gap-1 rounded-2xl border border-line bg-surface-raised p-1 sm:min-w-0">
                        {TABS.map((tab, index) => {
                            const hasError = tab.fields.some((name) => errors[name]);
                            const selected = activeTab === tab.key;
                            return (
                                <button
                                    key={tab.key}
                                    ref={(el) => (tabRefs.current[tab.key] = el)}
                                    type="button"
                                    role="tab"
                                    id={`tab-${tab.key}`}
                                    aria-selected={selected}
                                    aria-controls={`panel-${tab.key}`}
                                    tabIndex={selected ? 0 : -1}
                                    onClick={() => setActiveTab(tab.key)}
                                    onKeyDown={(event) => onTabKeyDown(event, index)}
                                    className={`relative inline-flex min-h-touch shrink-0 items-center gap-2 whitespace-nowrap rounded-xl px-4 text-sm font-medium transition ${
                                        selected ? 'bg-primary text-white' : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'
                                    }`}
                                >
                                    {tab.label}
                                    {hasError && (
                                        <span className="h-2 w-2 rounded-full bg-red-500" aria-label="(tiene errores)" role="img" />
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div className="min-w-0 space-y-6">
                        <div role="tabpanel" id="panel-brand" aria-labelledby="tab-brand" hidden={activeTab !== 'brand'} className="space-y-6">
                            <Card title="Datos de la marca" description="Así se presenta tu marca en el sitio, en buscadores y en los PDF.">
                                <div className="grid gap-5 md:grid-cols-2">
                                    <TextField label="Nombre comercial" required maxLength={120} {...field('brand_name')} />
                                    <TextField label="Eslogan" maxLength={160} {...field('tagline')} />
                                    <TextAreaField
                                        label="Descripción para buscadores (SEO)"
                                        hint="Aparece en Google bajo el nombre de tu sitio. Ideal: 150 a 160 caracteres."
                                        rows={3}
                                        maxLength={300}
                                        className="md:col-span-2"
                                        {...field('seo_description')}
                                    />
                                </div>
                            </Card>

                            <Card title="Portada del sitio" description="Mensaje principal de la página de inicio.">
                                <div className="grid gap-5">
                                    <TextField label="Título principal" maxLength={160} {...field('hero_title')} />
                                    <TextAreaField label="Subtítulo" rows={2} maxLength={300} {...field('hero_subtitle')} />
                                    <TextField
                                        label="Video de portada (opcional)"
                                        type="url"
                                        placeholder="https://"
                                        hint="Enlace de YouTube o Vimeo para el fondo de la portada."
                                        {...field('hero_video_url')}
                                    />
                                </div>
                            </Card>

                            <Card title="Logos e ícono" description="PNG, JPG o WebP de hasta 2 MB. Usa fondo transparente para mejores resultados.">
                                <div className="grid gap-6">
                                    <ImageUploadField
                                        label="Logo para tema claro"
                                        hint="Se muestra sobre fondos claros."
                                        previewClassName="bg-white"
                                        {...imageField('logo_light', settings.logo_light_url)}
                                    />
                                    <ImageUploadField
                                        label="Logo para tema oscuro"
                                        hint="Se muestra sobre fondos oscuros (portada, pie de página y modo oscuro)."
                                        previewClassName="bg-slate-900"
                                        {...imageField('logo_dark', settings.logo_dark_url)}
                                    />
                                    <ImageUploadField
                                        label="Ícono del sitio (favicon)"
                                        hint="PNG cuadrado (ej. 512 × 512 px) o archivo .ico de hasta 512 KB."
                                        accept="image/png,image/x-icon,.ico"
                                        {...imageField('favicon', settings.favicon_url)}
                                    />
                                </div>
                            </Card>
                        </div>

                        <div role="tabpanel" id="panel-colors" aria-labelledby="tab-colors" hidden={activeTab !== 'colors'}>
                            <Card title="Colores de marca" description="Se aplican en todo el sitio al guardar. La vista previa se actualiza al instante.">
                                <div className="grid gap-6 md:grid-cols-3">
                                    <ColorField label="Primario" hint="Botones y elementos destacados." {...field('color_primary')} />
                                    <ColorField label="Secundario" hint="Encabezados, portada y pie de página." {...field('color_secondary')} />
                                    <ColorField label="Acento" hint="Llamados a la acción y detalles." {...field('color_accent')} />
                                </div>
                            </Card>
                        </div>

                        <div role="tabpanel" id="panel-contact" aria-labelledby="tab-contact" hidden={activeTab !== 'contact'} className="space-y-6">
                            <Card title="Contacto">
                                <div className="grid gap-5 md:grid-cols-2">
                                    <TextField
                                        label="WhatsApp principal"
                                        inputMode="numeric"
                                        placeholder="527221234567"
                                        hint="Con lada de país, solo números."
                                        {...field('whatsapp_number')}
                                    />
                                    <TextField label="Teléfono" type="tel" {...field('phone_number')} />
                                    <TextField label="Correo de contacto" type="email" {...field('contact_email')} />
                                    <TextField label="Dirección o zona" {...field('address_text')} />
                                </div>
                            </Card>
                            <Card title="Redes sociales" description="Deja vacío lo que no uses; no se mostrará en el sitio.">
                                <div className="grid gap-5 md:grid-cols-2">
                                    {[
                                        ['facebook_url', 'Facebook'],
                                        ['instagram_url', 'Instagram'],
                                        ['tiktok_url', 'TikTok'],
                                        ['youtube_url', 'YouTube'],
                                        ['spotify_url', 'Spotify'],
                                    ].map(([name, label]) => (
                                        <TextField key={name} label={label} type="url" placeholder="https://" {...field(name)} />
                                    ))}
                                </div>
                            </Card>
                        </div>

                        <div role="tabpanel" id="panel-quotes" aria-labelledby="tab-quotes" hidden={activeTab !== 'quotes'} className="space-y-6">
                            <Card title="Descuento por cotizar en línea" description="Se aplica sobre el subtotal de servicios, antes de viáticos.">
                                <div className="grid gap-5 md:grid-cols-2">
                                    <div className="md:col-span-2">
                                        <ToggleField
                                            label="Descuento web activo"
                                            description="Muestra y aplica el ahorro en el cotizador y en el PDF."
                                            checked={data.online_discount_active}
                                            onChange={(value) => setData('online_discount_active', value)}
                                            error={errors.online_discount_active}
                                        />
                                    </div>
                                    <SelectField
                                        label="Tipo de descuento"
                                        options={[
                                            { value: 'percentage', label: 'Porcentaje (%)' },
                                            { value: 'fixed', label: 'Monto fijo ($ MXN)' },
                                        ]}
                                        {...field('online_discount_type')}
                                    />
                                    <TextField
                                        label={isPercentage ? 'Porcentaje de descuento' : 'Monto del descuento (MXN)'}
                                        type="number"
                                        inputMode="decimal"
                                        min="0"
                                        max={isPercentage ? 100 : undefined}
                                        step="0.01"
                                        {...field('online_discount_value')}
                                    />
                                    <TextField
                                        label="Monto mínimo para aplicar (MXN)"
                                        type="number"
                                        inputMode="decimal"
                                        min="0"
                                        step="0.01"
                                        hint="Usa 0 para aplicarlo en cualquier cotización."
                                        {...field('online_discount_min_amount')}
                                    />
                                </div>
                            </Card>
                            <Card title="Cotizaciones en PDF">
                                <div className="grid gap-5">
                                    <TextField
                                        label="Vigencia de la cotización (días)"
                                        type="number"
                                        inputMode="numeric"
                                        min="1"
                                        max="90"
                                        className="md:max-w-xs"
                                        {...field('quote_validity_days')}
                                    />
                                    <TextAreaField
                                        label="Términos y condiciones"
                                        hint="Se imprimen al final de cada cotización."
                                        rows={6}
                                        maxLength={5000}
                                        {...field('quote_terms_text')}
                                    />
                                </div>
                            </Card>
                        </div>
                    </div>

                    <aside className="min-w-0 xl:sticky xl:top-20 xl:self-start">
                        <Card title="Vista previa" description="Así se verá tu marca con los cambios actuales.">
                            <BrandPreview data={data} settings={settings} />
                        </Card>
                    </aside>
                </div>

                <div className="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center justify-end gap-3 border-t border-line bg-surface-sunken/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                    {isDirty && <span className="mr-auto text-sm text-ink-muted">Tienes cambios sin guardar.</span>}
                    <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                        <Save className="h-4 w-4" aria-hidden="true" />
                        {processing ? 'Guardando…' : 'Guardar cambios'}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
