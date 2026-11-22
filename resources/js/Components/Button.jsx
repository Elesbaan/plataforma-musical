import { Link } from '@inertiajs/react';

const VARIANTS = {
    primary: 'bg-primary text-white hover:bg-primary/90 shadow-sm',
    accent: 'bg-accent text-secondary hover:bg-accent/90 shadow-sm',
    outline: 'border border-line text-ink hover:bg-surface-sunken',
    ghost: 'text-ink hover:bg-surface-sunken',
    light: 'border border-white/40 text-white hover:bg-white/10',
    danger: 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
};

const SIZES = {
    sm: 'min-h-touch px-3 text-sm',
    md: 'min-h-touch px-5 text-sm',
    lg: 'min-h-[52px] px-7 text-base',
};

/**
 * Botón base reutilizable. Con `href` se renderiza como enlace de Inertia
 * (o `<a>` si `external` es verdadero).
 */
export default function Button({
    variant = 'primary',
    size = 'md',
    href,
    external = false,
    className = '',
    children,
    ...props
}) {
    const classes = `inline-flex items-center justify-center gap-2 rounded-full font-semibold transition disabled:cursor-not-allowed disabled:opacity-60 ${VARIANTS[variant]} ${SIZES[size]} ${className}`;

    if (href && external) {
        return (
            <a href={href} className={classes} {...props}>
                {children}
            </a>
        );
    }

    if (href) {
        return (
            <Link href={href} className={classes} {...props}>
                {children}
            </Link>
        );
    }

    return (
        <button type="button" className={classes} {...props}>
            {children}
        </button>
    );
}
