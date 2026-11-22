import { useEffect, useRef, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    FileText,
    Layers,
    LayoutDashboard,
    LogOut,
    MapPinned,
    Menu,
    Music,
    Palette,
    PanelLeftClose,
    PanelLeftOpen,
    X,
} from 'lucide-react';
import BrandLogo from '@/Components/BrandLogo';
import FlashMessages from '@/Components/FlashMessages';
import IdleSessionWarning from '@/Components/IdleSessionWarning';
import ThemeToggle from '@/Components/ThemeToggle';
import useIdleLogout from '@/hooks/useIdleLogout';
import usePermissions from '@/hooks/usePermissions';

/** Módulos del panel (rutas, no datos de negocio) y el permiso que exige cada uno. */
const NAV_ITEMS = [
    { label: 'Dashboard', href: '/admin', Icon: LayoutDashboard, exact: true },
    { label: 'Identidad y marca', href: '/admin/identidad', Icon: Palette, permission: 'settings.manage' },
    { label: 'Categorías', href: '/admin/categorias', Icon: Layers, permission: 'catalog.manage_all' },
    { label: 'Servicios', href: '/admin/servicios', Icon: Music, permission: ['catalog.manage_all', 'catalog.manage_own'] },
    { label: 'Zonas y viáticos', href: '/admin/zonas', Icon: MapPinned, permission: 'zones.manage' },
    { label: 'Cotizaciones', href: '/admin/cotizaciones', Icon: FileText, permission: 'quotes.view' },
    { label: 'Agenda de eventos', href: '/admin/agenda', Icon: CalendarDays, permission: ['events.view_all', 'events.view_own'] },
];

const COLLAPSE_KEY = 'admin_sidebar_collapsed';

function readCollapsed() {
    try {
        return window.localStorage.getItem(COLLAPSE_KEY) === '1';
    } catch {
        return false;
    }
}

function SidebarNav({ collapsed, onNavigate }) {
    const { url } = usePage();
    const can = usePermissions();

    return (
        <ul className="space-y-1">
            {NAV_ITEMS.filter((item) => can(item.permission)).map(({ label, href, Icon, exact }) => {
                const active = exact ? url === href : url === href || url.startsWith(`${href}/`) || url.startsWith(`${href}?`);
                return (
                    <li key={href}>
                        <Link
                            href={href}
                            onClick={onNavigate}
                            aria-current={active ? 'page' : undefined}
                            title={collapsed ? label : undefined}
                            className={`flex min-h-touch items-center gap-3 rounded-xl px-3 text-sm font-medium transition ${
                                active ? 'bg-primary text-white' : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'
                            } ${collapsed ? 'lg:justify-center lg:px-0' : ''}`}
                        >
                            <Icon className="h-5 w-5 shrink-0" aria-hidden="true" />
                            <span className={collapsed ? 'lg:sr-only' : ''}>{label}</span>
                        </Link>
                    </li>
                );
            })}
        </ul>
    );
}

/**
 * Layout del panel administrativo: sidebar colapsable en escritorio y
 * cajón deslizable en móvil.
 *
 * @param {{ title: string, actions?: React.ReactNode, children: React.ReactNode }} props
 */
export default function AdminLayout({ title, actions, children }) {
    const { auth } = usePage().props;
    const [collapsed, setCollapsed] = useState(false);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const drawerToggleRef = useRef(null);
    const drawerRef = useRef(null);
    const idle = useIdleLogout(auth?.sessionLifetime);

    useEffect(() => setCollapsed(readCollapsed()), []);
    useEffect(() => router.on('navigate', () => setDrawerOpen(false)), []);

    useEffect(() => {
        if (!drawerOpen) return undefined;
        drawerRef.current?.querySelector('a, button')?.focus();
        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setDrawerOpen(false);
                drawerToggleRef.current?.focus();
            }
        };
        document.addEventListener('keydown', onKeyDown);
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = '';
        };
    }, [drawerOpen]);

    const toggleCollapsed = () => {
        setCollapsed((value) => {
            try {
                window.localStorage.setItem(COLLAPSE_KEY, value ? '0' : '1');
            } catch {
                // Sin almacenamiento disponible: el estado aplica solo en esta visita.
            }
            return !value;
        });
    };

    const logoutButton = (compact) => (
        <Link
            href="/admin/logout"
            method="post"
            as="button"
            title={compact ? 'Cerrar sesión' : undefined}
            className={`flex min-h-touch w-full items-center gap-3 rounded-xl px-3 text-sm font-medium text-ink-muted hover:bg-surface-sunken hover:text-ink ${
                compact ? 'lg:justify-center lg:px-0' : ''
            }`}
        >
            <LogOut className="h-5 w-5 shrink-0" aria-hidden="true" />
            <span className={compact ? 'lg:sr-only' : ''}>Cerrar sesión</span>
        </Link>
    );

    return (
        <div className="min-h-screen bg-surface-sunken text-ink">
            <Head title={title} />
            <IdleSessionWarning secondsLeft={idle.secondsLeft} onStay={idle.stayConnected} onLogout={idle.logoutNow} />

            {/* Sidebar de escritorio */}
            <aside
                className={`fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-line bg-surface-raised transition-[width] duration-200 lg:flex ${
                    collapsed ? 'w-20' : 'w-64'
                }`}
            >
                <div className={`flex h-16 items-center border-b border-line ${collapsed ? 'justify-center px-2' : 'px-5'}`}>
                    <Link href="/admin" className="flex min-h-touch min-w-0 items-center" aria-label="Ir al dashboard">
                        {collapsed ? (
                            <Music className="h-6 w-6 text-primary" aria-hidden="true" />
                        ) : (
                            <BrandLogo className="h-9 w-auto max-w-[11rem]" textClassName="truncate text-lg" />
                        )}
                    </Link>
                </div>
                <nav aria-label="Menú del panel" className="flex-1 overflow-y-auto p-3">
                    <SidebarNav collapsed={collapsed} />
                </nav>
                <div className="space-y-1 border-t border-line p-3">
                    <button
                        type="button"
                        onClick={toggleCollapsed}
                        aria-label={collapsed ? 'Expandir menú lateral' : 'Contraer menú lateral'}
                        className={`flex min-h-touch w-full items-center gap-3 rounded-xl px-3 text-sm font-medium text-ink-muted hover:bg-surface-sunken hover:text-ink ${
                            collapsed ? 'justify-center px-0' : ''
                        }`}
                    >
                        {collapsed ? (
                            <PanelLeftOpen className="h-5 w-5" aria-hidden="true" />
                        ) : (
                            <>
                                <PanelLeftClose className="h-5 w-5" aria-hidden="true" />
                                Contraer menú
                            </>
                        )}
                    </button>
                    {logoutButton(collapsed)}
                </div>
            </aside>

            {/* Cajón móvil */}
            {drawerOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <button
                        type="button"
                        tabIndex={-1}
                        aria-hidden="true"
                        className="absolute inset-0 h-full w-full bg-black/50"
                        onClick={() => setDrawerOpen(false)}
                    />
                    <aside
                        ref={drawerRef}
                        role="dialog"
                        aria-modal="true"
                        aria-label="Menú del panel"
                        className="absolute inset-y-0 left-0 flex w-[min(18rem,85vw)] flex-col bg-surface-raised shadow-2xl"
                    >
                        <div className="flex h-16 items-center justify-between border-b border-line px-4">
                            <BrandLogo className="h-9 w-auto max-w-[10rem]" textClassName="truncate text-lg" />
                            <button
                                type="button"
                                onClick={() => {
                                    setDrawerOpen(false);
                                    drawerToggleRef.current?.focus();
                                }}
                                aria-label="Cerrar menú"
                                className="inline-flex min-h-touch min-w-touch items-center justify-center rounded-full hover:bg-surface-sunken"
                            >
                                <X className="h-6 w-6" aria-hidden="true" />
                            </button>
                        </div>
                        <nav className="flex-1 overflow-y-auto p-3">
                            <SidebarNav collapsed={false} onNavigate={() => setDrawerOpen(false)} />
                        </nav>
                        <div className="border-t border-line p-3">{logoutButton(false)}</div>
                    </aside>
                </div>
            )}

            <div className={`transition-[padding] duration-200 ${collapsed ? 'lg:pl-20' : 'lg:pl-64'}`}>
                <header className="sticky top-0 z-20 flex h-16 items-center gap-2 border-b border-line bg-surface-raised/95 px-4 backdrop-blur sm:px-6">
                    <button
                        ref={drawerToggleRef}
                        type="button"
                        onClick={() => setDrawerOpen(true)}
                        aria-expanded={drawerOpen}
                        aria-label="Abrir menú"
                        className="-ml-2 inline-flex min-h-touch min-w-touch items-center justify-center rounded-full hover:bg-surface-sunken lg:hidden"
                    >
                        <Menu className="h-6 w-6" aria-hidden="true" />
                    </button>
                    <h1 className="min-w-0 flex-1 truncate text-lg font-semibold">{title}</h1>
                    <ThemeToggle />
                    {auth?.user && (
                        <span className="hidden max-w-[12rem] truncate text-sm text-ink-muted sm:inline" title={auth.user.email}>
                            {auth.user.name}
                        </span>
                    )}
                </header>

                <main className="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
                    <FlashMessages />
                    {actions && <div className="flex flex-wrap items-center justify-end gap-2">{actions}</div>}
                    {children}
                </main>
            </div>
        </div>
    );
}
