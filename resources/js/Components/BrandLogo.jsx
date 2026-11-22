import { usePage } from '@inertiajs/react';
import useTheme from '@/hooks/useTheme';

/**
 * Logo dinámico de la marca: usa el logo claro en tema claro y el oscuro en
 * tema oscuro (con respaldo mutuo). Sin logos cargados, muestra el nombre comercial.
 *
 * @param {{ forceTheme?: 'light'|'dark', className?: string, textClassName?: string }} props
 */
export default function BrandLogo({ forceTheme, className = 'h-10 w-auto', textClassName = '' }) {
    const { siteSettings } = usePage().props;
    const activeTheme = useTheme();
    const theme = forceTheme ?? activeTheme;

    const src =
        theme === 'dark'
            ? siteSettings?.logo_dark_url ?? siteSettings?.logo_light_url
            : siteSettings?.logo_light_url ?? siteSettings?.logo_dark_url;

    const name = siteSettings?.brand_name ?? '';

    if (src) {
        return <img src={src} alt={name} className={`${className} object-contain`} />;
    }

    return <span className={`font-display text-xl font-semibold tracking-tight ${textClassName}`}>{name}</span>;
}
