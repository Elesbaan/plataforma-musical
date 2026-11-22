import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, CalendarDays, FileText, MapPinned, Music, Palette } from 'lucide-react';
import usePermissions from '@/hooks/usePermissions';
import AdminLayout from '@/Layouts/AdminLayout';

const MODULES = [
    { title: 'Identidad y marca', description: 'Nombre, logos, colores, contacto y reglas del cotizador.', href: '/admin/identidad', Icon: Palette, permission: 'settings.manage' },
    { title: 'Servicios', description: 'Catálogo, precios, características y capacidad diaria.', href: '/admin/servicios', Icon: Music, permission: ['catalog.manage_all', 'catalog.manage_own'] },
    { title: 'Zonas y viáticos', description: 'Ciudades de cobertura y cargos por traslado.', href: '/admin/zonas', Icon: MapPinned, permission: 'zones.manage' },
    { title: 'Cotizaciones', description: 'Seguimiento de prospectos y cambio de estatus.', href: '/admin/cotizaciones', Icon: FileText, permission: 'quotes.view' },
    { title: 'Agenda de eventos', description: 'Calendario de eventos confirmados y ocupación.', href: '/admin/agenda', Icon: CalendarDays, permission: ['events.view_all', 'events.view_own'] },
];

/** Dashboard inicial. Las métricas clave se integran en la Fase 5. */
export default function Dashboard() {
    const { auth } = usePage().props;
    const can = usePermissions();
    const modules = MODULES.filter((module) => can(module.permission));

    return (
        <AdminLayout title="Dashboard">
            <section className="rounded-2xl border border-line bg-surface-raised p-6 sm:p-8">
                <h2 className="font-display text-2xl font-semibold sm:text-3xl">Hola, {auth?.user?.name}</h2>
                <p className="mt-2 text-ink-muted">
                    {auth?.user?.role ? `Ingresaste como ${auth.user.role.name}.` : 'Tu cuenta aún no tiene un perfil asignado.'}{' '}
                    {modules.length > 0 ? 'Estos son los módulos a los que tienes acceso.' : 'Pide al administrador que te asigne permisos.'}
                </p>
            </section>

            <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {modules.map(({ title, description, href, Icon }) => (
                    <li key={href}>
                        <Link
                            href={href}
                            className="group flex h-full min-h-touch flex-col rounded-2xl border border-line bg-surface-raised p-5 transition hover:border-primary/50 hover:shadow-md"
                        >
                            <span className="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <Icon className="h-5 w-5" aria-hidden="true" />
                            </span>
                            <span className="font-semibold">{title}</span>
                            <span className="mt-1 flex-1 text-sm text-ink-muted">{description}</span>
                            <span className="mt-4 inline-flex items-center gap-1 text-sm font-medium text-primary">
                                Abrir
                                <ArrowRight className="h-4 w-4 transition group-hover:translate-x-0.5" aria-hidden="true" />
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </AdminLayout>
    );
}
