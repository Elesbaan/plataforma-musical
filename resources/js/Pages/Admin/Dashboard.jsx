import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, CalendarCheck, CalendarDays, FileText, HandCoins, Inbox, MapPinned, Music, Palette, Pencil, TrendingUp } from 'lucide-react';
import EventCard from '@/Components/Admin/EventCard';
import Badge from '@/Components/Badge';
import Card from '@/Components/Card';
import usePermissions from '@/hooks/usePermissions';
import AdminLayout from '@/Layouts/AdminLayout';
import { formatLongDate } from '@/lib/date';
import { formatMoney } from '@/lib/format';
import { quoteStatus } from '@/lib/quotes';

const MODULES = [
    { title: 'Identidad y marca', description: 'Nombre, logos, colores, contacto y reglas del cotizador.', href: '/admin/identidad', Icon: Palette, permission: 'settings.manage' },
    { title: 'Servicios', description: 'Catálogo, precios, características y capacidad diaria.', href: '/admin/servicios', Icon: Music, permission: ['catalog.manage_all', 'catalog.manage_own'] },
    { title: 'Zonas y viáticos', description: 'Ciudades de cobertura y cargos por traslado.', href: '/admin/zonas', Icon: MapPinned, permission: 'zones.manage' },
    { title: 'Cotizaciones', description: 'Seguimiento de prospectos y cambio de estatus.', href: '/admin/cotizaciones', Icon: FileText, permission: 'quotes.view' },
    { title: 'Agenda de eventos', description: 'Calendario de eventos confirmados y ocupación.', href: '/admin/agenda', Icon: CalendarDays, permission: ['events.view_all', 'events.view_own'] },
];

function StatCard({ label, value, hint, href, Icon, tone = 'primary' }) {
    const tones = { primary: 'bg-primary/10 text-primary', accent: 'bg-accent/15 text-accent', success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' };
    const content = (
        <>
            <span className={`inline-flex h-11 w-11 items-center justify-center rounded-xl ${tones[tone]}`}>
                <Icon className="h-5 w-5" aria-hidden="true" />
            </span>
            <span className="mt-4 block font-display text-2xl font-semibold tabular-nums text-ink sm:text-3xl">{value}</span>
            <span className="mt-1 block text-sm font-medium text-ink">{label}</span>
            {hint && <span className="mt-0.5 block text-xs text-ink-muted">{hint}</span>}
        </>
    );

    return href ? (
        <Link href={href} className="block h-full rounded-2xl border border-line bg-surface-raised p-5 transition hover:border-primary/50 hover:shadow-md">
            {content}
        </Link>
    ) : (
        <div className="h-full rounded-2xl border border-line bg-surface-raised p-5">{content}</div>
    );
}

export default function Dashboard({ stats, latestQuotes, weekEvents, myServices }) {
    const { auth } = usePage().props;
    const can = usePermissions();
    const modules = MODULES.filter((module) => can(module.permission));

    const cards = [
        stats.new_quotes !== undefined && { key: 'new', label: 'Cotizaciones nuevas sin atender', value: stats.new_quotes, href: '/admin/cotizaciones?status=new', Icon: Inbox },
        stats.month_quoted !== undefined && {
            key: 'month',
            label: 'Monto cotizado este mes',
            value: formatMoney(stats.month_quoted),
            hint: `${stats.month_quotes_count} cotización(es)`,
            Icon: TrendingUp,
            tone: 'accent',
        },
        stats.upcoming_events !== undefined && { key: 'upcoming', label: 'Eventos confirmados próximos', value: stats.upcoming_events, href: '/admin/agenda?vista=lista', Icon: CalendarCheck, tone: 'success' },
        stats.pending_balance !== undefined && { key: 'balance', label: 'Saldo pendiente por cobrar', value: formatMoney(stats.pending_balance), hint: 'Eventos reservados', Icon: HandCoins, tone: 'accent' },
        stats.my_upcoming_events !== undefined && { key: 'mine', label: 'Tus próximos eventos', value: stats.my_upcoming_events, href: '/admin/agenda?vista=lista', Icon: CalendarCheck, tone: 'success' },
    ].filter(Boolean);

    return (
        <AdminLayout title="Dashboard">
            <section className="rounded-2xl border border-line bg-surface-raised p-6 sm:p-8">
                <h2 className="font-display text-2xl font-semibold sm:text-3xl">Hola, {auth?.user?.name}</h2>
                <p className="mt-2 text-ink-muted">
                    {auth?.user?.role ? `Ingresaste como ${auth.user.role.name}.` : 'Tu cuenta aún no tiene un perfil asignado.'}
                    {modules.length === 0 && ' Pide al administrador que te asigne permisos.'}
                </p>
            </section>

            {cards.length > 0 && (
                <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards.map(({ key, ...card }) => (
                        <li key={key}>
                            <StatCard {...card} />
                        </li>
                    ))}
                </ul>
            )}

            {(latestQuotes || weekEvents) && (
                <div className="grid gap-6 xl:grid-cols-2">
                    {latestQuotes && (
                        <Card
                            title="Últimas cotizaciones"
                            actions={
                                <Link href="/admin/cotizaciones" className="inline-flex min-h-touch items-center text-sm font-medium text-primary hover:underline">
                                    Ver todas
                                </Link>
                            }
                        >
                            {latestQuotes.length === 0 ? (
                                <p className="text-sm text-ink-muted">Aún no llegan cotizaciones.</p>
                            ) : (
                                <ul className="divide-y divide-line">
                                    {latestQuotes.map((quote) => {
                                        const status = quoteStatus(quote.status);
                                        return (
                                            <li key={quote.id}>
                                                <Link href={`/admin/cotizaciones/${quote.id}`} className="flex min-h-touch items-center justify-between gap-3 py-3 hover:bg-surface-sunken/60">
                                                    <span className="min-w-0">
                                                        <span className="block truncate font-medium text-ink">{quote.client_name}</span>
                                                        <span className="block text-xs text-ink-muted">
                                                            {quote.folio} · {quote.event_type} · {formatLongDate(quote.event_date, false)}
                                                        </span>
                                                    </span>
                                                    <span className="flex shrink-0 flex-col items-end gap-1">
                                                        <span className="text-sm font-semibold tabular-nums">{formatMoney(quote.total)}</span>
                                                        <Badge tone={status.tone}>{status.label}</Badge>
                                                    </span>
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </Card>
                    )}

                    {weekEvents && (
                        <Card
                            title={stats.my_upcoming_events !== undefined ? 'Tus próximos eventos' : 'Eventos de esta semana'}
                            actions={
                                <Link href="/admin/agenda" className="inline-flex min-h-touch items-center text-sm font-medium text-primary hover:underline">
                                    Abrir agenda
                                </Link>
                            }
                        >
                            {weekEvents.length === 0 ? (
                                <p className="text-sm text-ink-muted">No hay eventos próximos.</p>
                            ) : (
                                <ul className="space-y-3">
                                    {weekEvents.map((event) => (
                                        <li key={event.id}>
                                            <p className="mb-1 text-xs font-semibold capitalize text-primary">{formatLongDate(event.event_date)}</p>
                                            <EventCard event={event} />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card>
                    )}
                </div>
            )}

            {myServices && (
                <Card title="Tus servicios" description="Actualiza su galería, descripción y capacidad diaria.">
                    {myServices.length === 0 ? (
                        <p className="text-sm text-ink-muted">Aún no tienes servicios vinculados. Pide al administrador que te los asigne.</p>
                    ) : (
                        <ul className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            {myServices.map((service) => (
                                <li key={service.id}>
                                    <Link
                                        href={`/admin/servicios/${service.id}/editar`}
                                        className="flex min-h-touch items-center justify-between gap-3 rounded-xl border border-line p-4 transition hover:border-primary/50"
                                    >
                                        <span className="min-w-0">
                                            <span className="block truncate font-medium text-ink">{service.name}</span>
                                            {!service.is_active && <span className="text-xs text-ink-muted">Inactivo</span>}
                                        </span>
                                        <Pencil className="h-4 w-4 shrink-0 text-ink-muted" aria-hidden="true" />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            )}

            {modules.length > 0 && (
                <section aria-labelledby="modulos-title">
                    <h2 id="modulos-title" className="mb-3 text-sm font-semibold uppercase tracking-widest text-ink-muted">
                        Accesos rápidos
                    </h2>
                    <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {modules.map(({ title, description, href, Icon }) => (
                            <li key={href}>
                                <Link
                                    href={href}
                                    className="group flex h-full min-h-touch items-start gap-4 rounded-2xl border border-line bg-surface-raised p-5 transition hover:border-primary/50 hover:shadow-md"
                                >
                                    <span className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                        <Icon className="h-5 w-5" aria-hidden="true" />
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block font-semibold">{title}</span>
                                        <span className="mt-1 block text-sm text-ink-muted">{description}</span>
                                    </span>
                                    <ArrowRight className="mt-1 h-4 w-4 shrink-0 text-primary transition group-hover:translate-x-0.5" aria-hidden="true" />
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </AdminLayout>
    );
}
