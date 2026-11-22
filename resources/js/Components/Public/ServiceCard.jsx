import { Link } from '@inertiajs/react';
import { ArrowRight, PlayCircle } from 'lucide-react';
import { categoryIcon, serviceTypeLabel } from '@/lib/catalog';
import { formatMoney } from '@/lib/format';

/** Tarjeta de servicio para el sitio público con precio transparente. */
export default function ServiceCard({ service, headingLevel = 3 }) {
    const Heading = `h${headingLevel}`;
    const Icon = categoryIcon(service.category?.icon);
    const href = `/servicios/${service.slug}`;

    return (
        <article className="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-line bg-surface-raised shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
            <Link href={href} className="relative block aspect-[4/3] overflow-hidden bg-secondary" tabIndex={-1} aria-hidden="true">
                {service.cover_url ? (
                    <img
                        src={service.cover_url}
                        alt=""
                        loading="lazy"
                        className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                    />
                ) : (
                    <div className="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top_right,rgb(var(--color-primary-rgb)/0.7),transparent_70%)]">
                        <Icon className="h-14 w-14 text-white/70" />
                    </div>
                )}
                <div className="absolute left-3 top-3 flex flex-wrap gap-1.5">
                    <span className="rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white backdrop-blur">{serviceTypeLabel(service.service_type)}</span>
                    {service.has_video && (
                        <span className="inline-flex items-center gap-1 rounded-full bg-accent px-2.5 py-1 text-xs font-semibold text-secondary">
                            <PlayCircle className="h-3.5 w-3.5" />
                            Video en vivo
                        </span>
                    )}
                </div>
            </Link>

            <div className="flex flex-1 flex-col p-5">
                {service.category && <p className="text-xs font-semibold uppercase tracking-widest text-primary">{service.category.name}</p>}
                <Heading className="mt-1 font-display text-xl font-semibold text-ink">
                    <Link href={href} className="after:absolute after:inset-0 focus:outline-none">
                        {service.name}
                    </Link>
                </Heading>
                {service.short_description && <p className="mt-2 line-clamp-3 text-sm text-ink-muted">{service.short_description}</p>}

                <div className="mt-auto flex items-end justify-between gap-3 pt-5">
                    <p>
                        <span className="block text-xs text-ink-muted">Desde</span>
                        <span className="text-lg font-semibold text-ink">{formatMoney(service.base_price)} MXN</span>
                        <span className="block text-xs text-ink-muted">{service.price_unit}</span>
                    </p>
                    <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white" aria-hidden="true">
                        <ArrowRight className="h-5 w-5" />
                    </span>
                </div>
            </div>
        </article>
    );
}
