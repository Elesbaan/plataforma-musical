import { useEffect, useId, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, Mail, MapPin, Menu, Phone, X } from 'lucide-react';
import AccessibilityWidget from '@/Components/AccessibilityWidget';
import BrandLogo from '@/Components/BrandLogo';
import Button from '@/Components/Button';
import FlashMessages from '@/Components/FlashMessages';
import WhatsAppButton from '@/Components/WhatsAppButton';
import { socialLinks, telUrl, whatsappUrl } from '@/lib/contact';

/** Estructura de navegación del sitio (rutas, no datos de negocio). */
const NAV_ITEMS = [
    { label: 'Inicio', href: '/' },
    { label: 'Servicios', href: '/servicios' },
    { label: 'Paquetes', href: '/paquetes' },
    { label: 'Cotizador en línea', href: '/cotizador' },
    { label: 'Contacto', href: '#contacto' },
];

const QUOTE_HREF = '/cotizador';

function NavLink({ item, className, onClick }) {
    const { url } = usePage();
    const active = item.href === '/' ? url === '/' : url.startsWith(item.href);

    if (item.href.startsWith('#')) {
        return (
            <a href={item.href} className={className(false)} onClick={onClick}>
                {item.label}
            </a>
        );
    }

    return (
        <Link href={item.href} className={className(active)} aria-current={active ? 'page' : undefined} onClick={onClick}>
            {item.label}
        </Link>
    );
}

/**
 * Layout del sitio público.
 *
 * @param {{ transparentHeader?: boolean, children: React.ReactNode }} props
 *   transparentHeader: el encabezado inicia transparente sobre un hero oscuro y
 *   se vuelve sólido al desplazarse.
 *   raiseFloating: eleva los botones flotantes en móvil (páginas con barra inferior fija).
 */
export default function PublicLayout({ transparentHeader = false, raiseFloating = false, children }) {
    const { siteSettings, flash } = usePage().props;
    const [menuOpen, setMenuOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);
    const menuId = useId();
    const toggleRef = useRef(null);

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 24);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => router.on('navigate', () => setMenuOpen(false)), []);

    useEffect(() => {
        if (!menuOpen) return undefined;
        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                setMenuOpen(false);
                toggleRef.current?.focus();
            }
        };
        document.addEventListener('keydown', onKeyDown);
        document.body.style.overflow = 'hidden';
        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = '';
        };
    }, [menuOpen]);

    const solid = !transparentHeader || scrolled || menuOpen;
    const year = new Date().getFullYear();
    const socials = socialLinks(siteSettings);
    const whatsapp = whatsappUrl(siteSettings?.whatsapp_number);
    const phone = telUrl(siteSettings?.phone_number);

    const desktopLinkClass = (active) =>
        `inline-flex min-h-touch items-center rounded-full px-3 text-sm font-medium transition ${
            solid
                ? active
                    ? 'text-primary'
                    : 'text-ink-muted hover:text-ink'
                : active
                  ? 'text-white'
                  : 'text-white/80 hover:text-white'
        }`;

    const mobileLinkClass = (active) =>
        `flex min-h-[52px] items-center rounded-xl px-4 text-base font-medium ${
            active ? 'bg-primary/10 text-primary' : 'text-ink hover:bg-surface-sunken'
        }`;

    return (
        <div className="flex min-h-screen flex-col bg-surface text-ink">
            <a
                href="#contenido"
                className="sr-only z-[60] rounded-full bg-primary px-4 py-2 text-white focus:not-sr-only focus:fixed focus:left-4 focus:top-4"
            >
                Saltar al contenido
            </a>

            <header
                className={`${transparentHeader ? 'fixed' : 'sticky'} inset-x-0 top-0 z-40 transition-colors duration-300 ${
                    solid ? 'border-b border-line bg-surface/95 backdrop-blur' : 'bg-transparent'
                }`}
            >
                <nav aria-label="Navegación principal" className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:h-20 sm:px-6 lg:px-8">
                    <Link href="/" className="flex min-h-touch min-w-0 items-center" aria-label={`${siteSettings?.brand_name ?? ''} — Inicio`}>
                        <BrandLogo
                            forceTheme={solid ? undefined : 'dark'}
                            className="h-9 w-auto max-w-[10rem] sm:h-11"
                            textClassName={`truncate ${solid ? 'text-ink' : 'text-white'}`}
                        />
                    </Link>

                    <ul className="hidden items-center gap-1 lg:flex">
                        {NAV_ITEMS.map((item) => (
                            <li key={item.href}>
                                <NavLink item={item} className={desktopLinkClass} />
                            </li>
                        ))}
                    </ul>

                    <div className="flex items-center gap-2">
                        <Button href={QUOTE_HREF} variant="accent" className="hidden sm:inline-flex">
                            Cotiza en línea
                        </Button>
                        <button
                            ref={toggleRef}
                            type="button"
                            onClick={() => setMenuOpen((open) => !open)}
                            aria-expanded={menuOpen}
                            aria-controls={menuId}
                            aria-label={menuOpen ? 'Cerrar menú' : 'Abrir menú'}
                            className={`inline-flex min-h-touch min-w-touch items-center justify-center rounded-full lg:hidden ${
                                solid ? 'text-ink hover:bg-surface-sunken' : 'text-white hover:bg-white/10'
                            }`}
                        >
                            {menuOpen ? <X className="h-6 w-6" aria-hidden="true" /> : <Menu className="h-6 w-6" aria-hidden="true" />}
                        </button>
                    </div>
                </nav>

                {menuOpen && (
                    <div id={menuId} className="h-[calc(100dvh-4rem)] overflow-y-auto border-t border-line bg-surface px-4 pb-8 pt-4 sm:h-[calc(100dvh-5rem)] lg:hidden">
                        <ul className="space-y-1">
                            {NAV_ITEMS.map((item) => (
                                <li key={item.href}>
                                    <NavLink item={item} className={mobileLinkClass} onClick={() => setMenuOpen(false)} />
                                </li>
                            ))}
                        </ul>
                        <Button href={QUOTE_HREF} variant="accent" size="lg" className="mt-6 w-full">
                            Cotiza en línea
                            <ArrowRight className="h-5 w-5" aria-hidden="true" />
                        </Button>
                    </div>
                )}
            </header>

            <main id="contenido" className="flex-1">
                {(flash?.success || flash?.error) && (
                    <div className={`mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 ${transparentHeader ? 'pt-24' : 'pt-6'}`}>
                        <FlashMessages />
                    </div>
                )}
                {children}
            </main>

            <footer id="contacto" className="bg-secondary text-white">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
                    <div className="lg:col-span-2">
                        <BrandLogo forceTheme="dark" className="h-12 w-auto max-w-[12rem]" textClassName="text-2xl text-white" />
                        {siteSettings?.tagline && <p className="mt-4 max-w-md text-white/75">{siteSettings.tagline}</p>}
                        {socials.length > 0 && (
                            <ul className="mt-6 flex flex-wrap gap-2" aria-label="Redes sociales">
                                {socials.map((social) => (
                                    <li key={social.label}>
                                        <a
                                            href={social.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex min-h-touch items-center rounded-full border border-white/25 px-4 text-sm text-white/85 transition hover:border-accent hover:text-white"
                                        >
                                            {social.label}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    <div>
                        <h2 className="text-sm font-semibold uppercase tracking-widest text-accent">Explora</h2>
                        <ul className="mt-4 space-y-1">
                            {NAV_ITEMS.filter((item) => !item.href.startsWith('#')).map((item) => (
                                <li key={item.href}>
                                    <Link href={item.href} className="inline-flex min-h-touch items-center text-white/80 hover:text-white">
                                        {item.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div>
                        <h2 className="text-sm font-semibold uppercase tracking-widest text-accent">Contacto</h2>
                        <ul className="mt-4 space-y-3 text-white/80">
                            {siteSettings?.phone_number && phone && (
                                <li>
                                    <a href={phone} className="inline-flex min-h-touch items-center gap-3 hover:text-white">
                                        <Phone className="h-5 w-5 shrink-0" aria-hidden="true" />
                                        {siteSettings.phone_number}
                                    </a>
                                </li>
                            )}
                            {whatsapp && (
                                <li>
                                    <a href={whatsapp} target="_blank" rel="noopener noreferrer" className="inline-flex min-h-touch items-center gap-3 hover:text-white">
                                        <Phone className="h-5 w-5 shrink-0" aria-hidden="true" />
                                        WhatsApp
                                    </a>
                                </li>
                            )}
                            {siteSettings?.contact_email && (
                                <li>
                                    <a href={`mailto:${siteSettings.contact_email}`} className="inline-flex min-h-touch items-center gap-3 break-all hover:text-white">
                                        <Mail className="h-5 w-5 shrink-0" aria-hidden="true" />
                                        {siteSettings.contact_email}
                                    </a>
                                </li>
                            )}
                            {siteSettings?.address_text && (
                                <li className="flex items-start gap-3">
                                    <MapPin className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                                    <span>{siteSettings.address_text}</span>
                                </li>
                            )}
                        </ul>
                    </div>
                </div>

                <div className="border-t border-white/10">
                    <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 pb-24 text-sm text-white/60 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:pb-6 lg:px-8">
                        <p>
                            © {year} {siteSettings?.brand_name}. Todos los derechos reservados.
                        </p>
                        <Link href="/aviso-de-privacidad" className="inline-flex min-h-touch items-center hover:text-white">
                            Aviso de privacidad
                        </Link>
                    </div>
                </div>
            </footer>

            <AccessibilityWidget raised={raiseFloating} />
            <WhatsAppButton raised={raiseFloating} />
        </div>
    );
}
