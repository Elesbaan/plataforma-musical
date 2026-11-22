import { Head, Link } from '@inertiajs/react';
import { Check, ChevronRight, Clock, FileText } from 'lucide-react';
import Button from '@/Components/Button';
import MediaGallery from '@/Components/Public/MediaGallery';
import SectionHeading from '@/Components/Public/SectionHeading';
import ServiceCard from '@/Components/Public/ServiceCard';
import PublicLayout from '@/Layouts/PublicLayout';
import { serviceTypeLabel } from '@/lib/catalog';
import { formatMoney } from '@/lib/format';

export default function Show({ service, related }) {
    const quoteHref = `/cotizador?servicio=${encodeURIComponent(service.slug)}`;

    return (
        <PublicLayout>
            <Head title={service.name} />

            <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
                <nav aria-label="Ruta de navegación" className="mb-6">
                    <ol className="flex flex-wrap items-center gap-1 text-sm text-ink-muted">
                        <li>
                            <Link href="/servicios" className="inline-flex min-h-touch items-center hover:text-ink">
                                Servicios
                            </Link>
                        </li>
                        {service.category && (
                            <>
                                <li aria-hidden="true">
                                    <ChevronRight className="h-4 w-4" />
                                </li>
                                <li>
                                    <Link href={`/servicios?categoria=${service.category.slug}`} className="inline-flex min-h-touch items-center hover:text-ink">
                                        {service.category.name}
                                    </Link>
                                </li>
                            </>
                        )}
                        <li aria-hidden="true">
                            <ChevronRight className="h-4 w-4" />
                        </li>
                        <li aria-current="page" className="truncate text-ink">
                            {service.name}
                        </li>
                    </ol>
                </nav>

                <div className="grid gap-10 lg:grid-cols-[minmax(0,1fr)_24rem]">
                    <div className="min-w-0">
                        <MediaGallery media={service.media} serviceName={service.name} />

                        {service.full_description && (
                            <section className="mt-10" aria-labelledby="descripcion-title">
                                <h2 id="descripcion-title" className="font-display text-2xl font-semibold text-ink">
                                    Sobre este servicio
                                </h2>
                                <p className="mt-4 whitespace-pre-line leading-relaxed text-ink-muted">{service.full_description}</p>
                            </section>
                        )}

                        {service.features.length > 0 && (
                            <section className="mt-10" aria-labelledby="incluye-title">
                                <h2 id="incluye-title" className="font-display text-2xl font-semibold text-ink">
                                    ¿Qué incluye?
                                </h2>
                                <ul className="mt-5 grid gap-3 sm:grid-cols-2">
                                    {service.features.map((feature) => (
                                        <li key={feature} className="flex items-start gap-3 rounded-xl bg-surface-raised p-3">
                                            <span className="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-white">
                                                <Check className="h-4 w-4" aria-hidden="true" />
                                            </span>
                                            <span className="text-ink">{feature}</span>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        )}
                    </div>

                    <aside className="lg:sticky lg:top-24 lg:self-start">
                        <div className="rounded-2xl border border-line bg-surface-raised p-6 shadow-sm">
                            <div className="flex flex-wrap gap-2">
                                <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">{serviceTypeLabel(service.service_type)}</span>
                                {service.category && <span className="rounded-full bg-surface-sunken px-3 py-1 text-xs font-medium text-ink-muted">{service.category.name}</span>}
                            </div>
                            <h1 className="mt-4 font-display text-3xl font-semibold leading-tight text-ink">{service.name}</h1>
                            {service.short_description && <p className="mt-3 text-ink-muted">{service.short_description}</p>}

                            <dl className="mt-6 space-y-3 border-t border-line pt-6">
                                <div>
                                    <dt className="text-sm text-ink-muted">Desde</dt>
                                    <dd className="font-display text-3xl font-semibold text-ink">
                                        {formatMoney(service.base_price)} <span className="text-base font-sans font-medium text-ink-muted">MXN</span>
                                    </dd>
                                    <dd className="text-sm text-ink-muted">{service.price_unit}</dd>
                                </div>
                                {service.extra_hour_price && (
                                    <div className="flex items-center gap-2 rounded-xl bg-surface-sunken px-3 py-2 text-sm">
                                        <Clock className="h-4 w-4 text-primary" aria-hidden="true" />
                                        <dt className="text-ink-muted">Hora extra:</dt>
                                        <dd className="font-semibold text-ink">{formatMoney(service.extra_hour_price)} MXN</dd>
                                    </div>
                                )}
                            </dl>

                            <Button href={quoteHref} size="lg" className="mt-6 w-full">
                                <FileText className="h-5 w-5" aria-hidden="true" />
                                Agregar a mi cotización
                            </Button>
                            <p className="mt-3 text-center text-xs text-ink-muted">Consulta disponibilidad para tu fecha y recibe tu PDF al instante.</p>
                        </div>
                    </aside>
                </div>

                {related.length > 0 && (
                    <section className="mt-20" aria-labelledby="relacionados-title">
                        <SectionHeading id="relacionados-title" eyebrow="Complementa tu evento" title="También te puede interesar" />
                        <ul className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {related.map((item) => (
                                <li key={item.id}>
                                    <ServiceCard service={item} />
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </PublicLayout>
    );
}
