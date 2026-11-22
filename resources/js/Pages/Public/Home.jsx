import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BadgePercent, CalendarCheck, CirclePlay, FileText, Handshake, MapPinHouse, Music } from 'lucide-react';
import Button from '@/Components/Button';
import Modal from '@/Components/Modal';
import SectionHeading from '@/Components/Public/SectionHeading';
import ServiceCard from '@/Components/Public/ServiceCard';
import PublicLayout from '@/Layouts/PublicLayout';
import { onlineDiscount } from '@/lib/discount';

function TrustBar({ highlights }) {
    const items = [
        highlights.services_count > 0 && { value: highlights.services_count, label: 'servicios y amenidades' },
        highlights.zones_count > 0 && { value: highlights.zones_count, label: highlights.zones_count === 1 ? 'zona de cobertura' : 'zonas de cobertura' },
        highlights.base_city && { value: highlights.base_city, label: 'nuestra sede base' },
        { value: 'PDF', label: 'cotización formal al instante' },
    ].filter(Boolean);

    return (
        <div className="relative z-10 mx-auto -mt-12 max-w-6xl px-4 sm:px-6 lg:px-8">
            <dl className="grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-line bg-line shadow-xl lg:grid-cols-4">
                {items.map((item) => (
                    <div key={item.label} className="bg-surface-raised p-5 text-center">
                        <dt className="sr-only">{item.label}</dt>
                        <dd>
                            <span className="block truncate font-display text-2xl font-semibold text-ink sm:text-3xl">{item.value}</span>
                            <span className="mt-1 block text-xs text-ink-muted sm:text-sm">{item.label}</span>
                        </dd>
                    </div>
                ))}
            </dl>
        </div>
    );
}

function FeaturedServices({ featured }) {
    const [active, setActive] = useState('all');
    if (featured.length === 0) return null;

    const groups = active === 'all' ? featured : featured.filter((group) => group.category.slug === active);

    return (
        <section aria-labelledby="destacados-title" className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <SectionHeading
                    id="destacados-title"
                    eyebrow="Paquetes estrella"
                    title="Arma una celebración a la medida"
                    description="Alineaciones musicales y amenidades de alto impacto con precios transparentes desde el primer clic."
                />
                <Button href="/servicios" variant="outline" className="self-start lg:self-auto">
                    Ver catálogo completo
                    <ArrowRight className="h-4 w-4" aria-hidden="true" />
                </Button>
            </div>

            {featured.length > 1 && (
                <div className="-mx-4 mt-8 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                    <div className="flex gap-2" role="group" aria-label="Filtrar destacados por categoría">
                        {[{ slug: 'all', name: 'Todos' }, ...featured.map((group) => group.category)].map((category) => (
                            <button
                                key={category.slug}
                                type="button"
                                aria-pressed={active === category.slug}
                                onClick={() => setActive(category.slug)}
                                className={`min-h-touch shrink-0 whitespace-nowrap rounded-full border px-4 text-sm font-medium transition ${
                                    active === category.slug ? 'border-primary bg-primary text-white' : 'border-line text-ink-muted hover:text-ink'
                                }`}
                            >
                                {category.name}
                            </button>
                        ))}
                    </div>
                </div>
            )}

            <div className="mt-10 space-y-14">
                {groups.map((group) => (
                    <div key={group.category.id}>
                        <div className="mb-5 flex items-baseline justify-between gap-4">
                            <h3 className="font-display text-2xl font-semibold text-ink">{group.category.name}</h3>
                            <Link href={`/servicios?categoria=${group.category.slug}`} className="inline-flex min-h-touch shrink-0 items-center text-sm font-medium text-primary hover:underline">
                                Ver todos
                            </Link>
                        </div>
                        <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {group.services.map((service) => (
                                <li key={service.id}>
                                    <ServiceCard service={service} headingLevel={4} />
                                </li>
                            ))}
                        </ul>
                    </div>
                ))}
            </div>
        </section>
    );
}

function ValueProposition({ highlights }) {
    const pillars = [
        {
            Icon: MapPinHouse,
            title: 'Cobertura regional',
            text: highlights.base_city
                ? `Desde ${highlights.base_city} llevamos la fiesta a ${highlights.zones_count} ${highlights.zones_count === 1 ? 'zona' : 'zonas'} con viáticos claros${
                      highlights.accepts_other_cities ? ', y a cualquier ciudad del país bajo cotización.' : '.'
                  }`
                : 'Viáticos transparentes según la ciudad de tu evento, calculados al instante en el cotizador.',
        },
        {
            Icon: Music,
            title: 'Producción integral propia',
            text:
                highlights.own_count > 0
                    ? `${highlights.own_count} alineaciones y formatos propios con músicos profesionales, sonido y repertorio a la medida.`
                    : 'Músicos profesionales, sonido y repertorio a la medida de tu celebración.',
        },
        {
            Icon: Handshake,
            title: 'Aliados seleccionados',
            text:
                highlights.partner_count > 0
                    ? `${highlights.partner_count} complementos de alto impacto coordinados por un solo equipo: un contacto, una cotización.`
                    : 'Complementos de alto impacto coordinados por un solo equipo: un contacto, una cotización.',
        },
    ];

    return (
        <section aria-labelledby="propuesta-title" className="bg-secondary py-20 text-white">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <SectionHeading
                    id="propuesta-title"
                    light
                    align="center"
                    eyebrow="Por qué elegirnos"
                    title="Una sola producción, cero sorpresas"
                    description="Disponibilidad real por fecha, precios a la vista y una cotización formal en minutos."
                />
                <ul className="mt-12 grid gap-6 md:grid-cols-3">
                    {pillars.map(({ Icon, title, text }) => (
                        <li key={title} className="rounded-2xl border border-white/10 bg-white/5 p-6">
                            <span className="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-accent text-secondary">
                                <Icon className="h-6 w-6" aria-hidden="true" />
                            </span>
                            <h3 className="mt-5 text-lg font-semibold">{title}</h3>
                            <p className="mt-2 text-white/75">{text}</p>
                        </li>
                    ))}
                </ul>
            </div>
        </section>
    );
}

function EventTypes({ eventTypes }) {
    if (!eventTypes?.length) return null;

    return (
        <section aria-labelledby="eventos-title" className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <SectionHeading id="eventos-title" align="center" eyebrow="Para cada ocasión" title="¿Qué vas a celebrar?" />
            <ul className="mt-10 flex flex-wrap justify-center gap-3">
                {eventTypes.map((type) => (
                    <li key={type}>
                        <Link
                            href={`/cotizador?tipo=${encodeURIComponent(type)}`}
                            className="inline-flex min-h-touch items-center gap-2 rounded-full border border-line bg-surface-raised px-5 text-sm font-medium text-ink transition hover:border-primary hover:text-primary"
                        >
                            <CalendarCheck className="h-4 w-4" aria-hidden="true" />
                            {type}
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}

function FinalCta({ discount }) {
    return (
        <section aria-labelledby="cta-title" className="px-4 pb-20 sm:px-6 lg:px-8">
            <div className="relative isolate mx-auto max-w-5xl overflow-hidden rounded-3xl bg-primary px-6 py-14 text-center text-white sm:px-12">
                <div aria-hidden="true" className="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_bottom_right,rgb(var(--color-accent-rgb)/0.45),transparent_60%)]" />
                {discount && (
                    <p className="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-semibold">
                        <BadgePercent className="h-4 w-4" aria-hidden="true" />
                        Exclusivo al cotizar en línea
                    </p>
                )}
                <h2 id="cta-title" className="mt-5 font-display text-3xl font-semibold leading-tight text-balance sm:text-4xl">
                    {discount ? `Obtén ${discount.amount} de descuento armando tu evento aquí` : 'Arma tu evento y recibe tu cotización al instante'}
                </h2>
                <p className="mx-auto mt-4 max-w-2xl text-white/85">
                    Consulta disponibilidad real para tu fecha, elige servicios y descarga tu cotización formal en PDF en minutos.
                    {discount?.minimum && ` Aplica en cotizaciones desde ${discount.minimum} MXN.`}
                </p>
                <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    <Button href="/cotizador" variant="accent" size="lg">
                        <FileText className="h-5 w-5" aria-hidden="true" />
                        Cotizar ahora
                    </Button>
                    <Button href="/servicios" variant="light" size="lg">
                        Explorar servicios
                    </Button>
                </div>
            </div>
        </section>
    );
}

export default function Home({ featured, highlights, eventTypes, heroVideoEmbed }) {
    const { siteSettings } = usePage().props;
    const discount = onlineDiscount(siteSettings);
    const [videoOpen, setVideoOpen] = useState(false);

    return (
        <PublicLayout transparentHeader>
            <Head title={siteSettings?.tagline ?? ''} />

            <section className="relative isolate flex min-h-[92svh] items-center overflow-hidden bg-secondary text-white">
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_right,rgb(var(--color-primary-rgb)/0.55),transparent_60%),radial-gradient(ellipse_at_bottom_left,rgb(var(--color-accent-rgb)/0.25),transparent_55%)]"
                />
                <div className="mx-auto w-full max-w-7xl px-4 pb-28 pt-32 sm:px-6 lg:px-8">
                    <div className="max-w-3xl">
                        {siteSettings?.tagline && (
                            <p className="mb-5 text-xs font-semibold uppercase tracking-[0.25em] text-accent sm:text-sm">{siteSettings.tagline}</p>
                        )}
                        {siteSettings?.hero_title && (
                            <h1 className="font-display text-4xl font-semibold leading-tight text-balance sm:text-5xl lg:text-6xl">{siteSettings.hero_title}</h1>
                        )}
                        {siteSettings?.hero_subtitle && <p className="mt-6 max-w-2xl text-lg text-white/80 sm:text-xl">{siteSettings.hero_subtitle}</p>}

                        {discount && (
                            <p className="mt-6 inline-flex items-center gap-2 rounded-full border border-accent/40 bg-accent/10 px-4 py-2 text-sm font-medium text-accent">
                                <BadgePercent className="h-4 w-4" aria-hidden="true" />
                                {discount.amount} de descuento exclusivo al cotizar en línea
                            </p>
                        )}

                        <div className="mt-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <Button href="/cotizador" variant="accent" size="lg">
                                Arma tu cotización
                                <ArrowRight className="h-5 w-5" aria-hidden="true" />
                            </Button>
                            <Button href="/servicios" variant="light" size="lg">
                                Conoce los servicios
                            </Button>
                            {heroVideoEmbed && (
                                <Button variant="light" size="lg" onClick={() => setVideoOpen(true)} className="border-transparent">
                                    <CirclePlay className="h-5 w-5" aria-hidden="true" />
                                    Ver video
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            <TrustBar highlights={highlights} />
            <FeaturedServices featured={featured} />
            <ValueProposition highlights={highlights} />
            <EventTypes eventTypes={eventTypes} />
            <FinalCta discount={discount} />

            {heroVideoEmbed && (
                <Modal open={videoOpen} onClose={() => setVideoOpen(false)} title={siteSettings?.brand_name ?? 'Video'} size="lg">
                    <div className="aspect-video overflow-hidden rounded-xl bg-black">
                        {videoOpen && (
                            <iframe
                                src={heroVideoEmbed}
                                title={`Video de ${siteSettings?.brand_name ?? 'la agrupación'}`}
                                allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                                allowFullScreen
                                className="h-full w-full"
                            />
                        )}
                    </div>
                </Modal>
            )}
        </PublicLayout>
    );
}
