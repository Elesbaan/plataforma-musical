import { Head, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import Button from '@/Components/Button';
import PublicLayout from '@/Layouts/PublicLayout';

/**
 * Inicio. En la Fase 4 se agregan barra de confianza, categorías, paquetes
 * estrella, tipos de evento y repertorio.
 */
export default function Home() {
    const { siteSettings } = usePage().props;

    return (
        <PublicLayout transparentHeader>
            <Head title={siteSettings?.tagline ?? ''} />

            <section className="relative isolate flex min-h-[100svh] items-center overflow-hidden bg-secondary text-white">
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_right,rgb(var(--color-primary-rgb)/0.55),transparent_60%),radial-gradient(ellipse_at_bottom_left,rgb(var(--color-accent-rgb)/0.25),transparent_55%)]"
                />
                <div className="mx-auto w-full max-w-7xl px-4 pb-20 pt-32 sm:px-6 lg:px-8">
                    <div className="max-w-3xl">
                        {siteSettings?.tagline && (
                            <p className="mb-5 text-xs font-semibold uppercase tracking-[0.25em] text-accent sm:text-sm">
                                {siteSettings.tagline}
                            </p>
                        )}
                        {siteSettings?.hero_title && (
                            <h1 className="font-display text-4xl font-semibold leading-tight text-balance sm:text-5xl lg:text-6xl">
                                {siteSettings.hero_title}
                            </h1>
                        )}
                        {siteSettings?.hero_subtitle && (
                            <p className="mt-6 max-w-2xl text-lg text-white/80 sm:text-xl">{siteSettings.hero_subtitle}</p>
                        )}
                        <div className="mt-10 flex flex-col gap-3 sm:flex-row">
                            <Button href="/cotizador" variant="accent" size="lg">
                                Arma tu cotización
                                <ArrowRight className="h-5 w-5" aria-hidden="true" />
                            </Button>
                            <Button href="/servicios" variant="light" size="lg">
                                Conoce los servicios
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
