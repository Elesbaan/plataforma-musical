/** Encabezado editorial de sección (antetítulo, título y descripción). */
export default function SectionHeading({ eyebrow, title, description, align = 'left', id, light = false }) {
    const centered = align === 'center';

    return (
        <div className={`max-w-2xl ${centered ? 'mx-auto text-center' : ''}`}>
            {eyebrow && <p className={`text-xs font-semibold uppercase tracking-[0.25em] ${light ? 'text-accent' : 'text-primary'}`}>{eyebrow}</p>}
            <h2 id={id} className={`mt-3 font-display text-3xl font-semibold leading-tight text-balance sm:text-4xl ${light ? 'text-white' : 'text-ink'}`}>
                {title}
            </h2>
            {description && <p className={`mt-4 text-base sm:text-lg ${light ? 'text-white/75' : 'text-ink-muted'}`}>{description}</p>}
        </div>
    );
}
