/** Contenedor de sección del panel con título y descripción opcionales. */
export default function Card({ title, description, actions, className = '', children }) {
    return (
        <section className={`rounded-2xl border border-line bg-surface-raised p-5 sm:p-6 ${className}`}>
            {(title || actions) && (
                <div className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div className="min-w-0">
                        {title && <h2 className="text-base font-semibold text-ink">{title}</h2>}
                        {description && <p className="mt-1 text-sm text-ink-muted">{description}</p>}
                    </div>
                    {actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}
                </div>
            )}
            {children}
        </section>
    );
}
