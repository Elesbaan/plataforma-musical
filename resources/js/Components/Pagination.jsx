import { Link } from '@inertiajs/react';

function cleanLabel(label) {
    return String(label).replace('&laquo;', '«').replace('&raquo;', '»').replace(/Previous|Anterior/i, 'Anterior').replace(/Next|Siguiente/i, 'Siguiente');
}

/** Paginación a partir del paginador de Laravel (`links`, `from`, `to`, `total`). */
export default function Pagination({ paginator }) {
    if (!paginator || paginator.last_page <= 1) return null;

    return (
        <nav aria-label="Paginación" className="mt-6 flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
            <p className="text-sm text-ink-muted">
                Mostrando {paginator.from} a {paginator.to} de {paginator.total}
            </p>
            <ul className="flex flex-wrap justify-center gap-1">
                {paginator.links.map((link, index) => {
                    const label = cleanLabel(link.label);
                    const base = 'inline-flex min-h-touch min-w-touch items-center justify-center rounded-xl px-3 text-sm';
                    return (
                        <li key={`${label}-${index}`}>
                            {link.url ? (
                                <Link
                                    href={link.url}
                                    preserveScroll
                                    aria-current={link.active ? 'page' : undefined}
                                    className={`${base} ${link.active ? 'bg-primary font-semibold text-white' : 'text-ink hover:bg-surface-sunken'}`}
                                >
                                    {label}
                                </Link>
                            ) : (
                                <span className={`${base} text-ink-muted/60`} aria-hidden="true">
                                    {label}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
