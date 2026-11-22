import { useEffect, useState } from 'react';
import { ArrowRight, Sparkles } from 'lucide-react';
import { hexToChannels } from '@/lib/color';

function useObjectUrl(file) {
    const [url, setUrl] = useState(null);
    useEffect(() => {
        if (!file) {
            setUrl(null);
            return undefined;
        }
        const objectUrl = URL.createObjectURL(file);
        setUrl(objectUrl);
        return () => URL.revokeObjectURL(objectUrl);
    }, [file]);
    return url;
}

/**
 * Vista previa en vivo de la identidad: redefine las variables CSS de marca
 * solo dentro de este bloque, así que usa las mismas clases que el sitio real.
 */
export default function BrandPreview({ data, settings }) {
    const darkLogoFile = useObjectUrl(data.logo_dark);
    const logo = darkLogoFile ?? (data.remove_logo_dark ? null : settings.logo_dark_url) ?? settings.logo_light_url;

    const style = {
        '--color-primary': data.color_primary,
        '--color-secondary': data.color_secondary,
        '--color-accent': data.color_accent,
        '--color-primary-rgb': hexToChannels(data.color_primary),
        '--color-secondary-rgb': hexToChannels(data.color_secondary),
        '--color-accent-rgb': hexToChannels(data.color_accent),
    };

    return (
        <div style={style} className="overflow-hidden rounded-2xl border border-line" aria-label="Vista previa de la identidad" role="img">
            <div className="flex items-center justify-between gap-3 bg-secondary px-4 py-3 text-white">
                {logo ? (
                    <img src={logo} alt="" className="h-7 w-auto max-w-[8rem] object-contain" />
                ) : (
                    <span className="truncate font-display text-base font-semibold">{data.brand_name || 'Tu marca'}</span>
                )}
                <span className="shrink-0 rounded-full bg-accent px-3 py-1 text-xs font-semibold text-secondary">Cotiza</span>
            </div>
            <div className="relative isolate bg-secondary px-4 py-8 text-white">
                <div
                    aria-hidden="true"
                    className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_right,rgb(var(--color-primary-rgb)/0.6),transparent_65%)]"
                />
                {data.tagline && <p className="mb-2 text-[0.65rem] font-semibold uppercase tracking-[0.2em] text-accent">{data.tagline}</p>}
                <p className="font-display text-xl font-semibold leading-snug">{data.hero_title || 'Título principal de tu sitio'}</p>
                <div className="mt-4 flex flex-wrap gap-2">
                    <span className="inline-flex items-center gap-1 rounded-full bg-accent px-3 py-1.5 text-xs font-semibold text-secondary">
                        Arma tu cotización <ArrowRight className="h-3 w-3" />
                    </span>
                    <span className="rounded-full border border-white/40 px-3 py-1.5 text-xs font-semibold">Servicios</span>
                </div>
            </div>
            <div className="bg-surface p-4">
                <div className="rounded-xl border border-line bg-surface-raised p-3">
                    <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[0.65rem] font-medium text-primary">
                        <Sparkles className="h-3 w-3" /> Destacado
                    </span>
                    <p className="mt-2 text-sm font-semibold text-ink">Servicio de ejemplo</p>
                    <p className="text-xs text-ink-muted">Desde $00,000 MXN</p>
                    <span className="mt-3 inline-flex rounded-full bg-primary px-3 py-1.5 text-xs font-semibold text-white">Agregar</span>
                </div>
            </div>
        </div>
    );
}
