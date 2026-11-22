import { Head, usePage } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Privacy() {
    const { siteSettings } = usePage().props;
    const brand = siteSettings?.brand_name ?? '';
    const email = siteSettings?.contact_email;

    return (
        <PublicLayout>
            <Head title="Aviso de privacidad" />

            <article className="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
                <h1 className="font-display text-3xl font-semibold sm:text-4xl">Aviso de privacidad</h1>

                <div className="mt-8 space-y-6 leading-relaxed text-ink-muted">
                    <p>
                        {brand} es responsable del tratamiento de los datos personales que nos compartes a través de este
                        sitio, de conformidad con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.
                    </p>

                    <section>
                        <h2 className="mb-2 text-lg font-semibold text-ink">¿Qué datos recopilamos?</h2>
                        <p>
                            Nombre, correo electrónico, número de teléfono o WhatsApp y los detalles de tu evento (fecha,
                            ciudad, salón y servicios de interés) que capturas al solicitar una cotización.
                        </p>
                    </section>

                    <section>
                        <h2 className="mb-2 text-lg font-semibold text-ink">¿Para qué los usamos?</h2>
                        <p>
                            Para elaborar y enviarte tu cotización, darle seguimiento, confirmar disponibilidad y coordinar
                            tu evento. No vendemos ni compartimos tus datos con terceros con fines comerciales.
                        </p>
                    </section>

                    <section>
                        <h2 className="mb-2 text-lg font-semibold text-ink">Tus derechos</h2>
                        <p>
                            Puedes solicitar el acceso, rectificación, cancelación u oposición al uso de tus datos (derechos
                            ARCO)
                            {email ? (
                                <>
                                    {' '}
                                    escribiéndonos a{' '}
                                    <a href={`mailto:${email}`} className="font-medium text-primary underline underline-offset-2">
                                        {email}
                                    </a>
                                </>
                            ) : (
                                ' a través de nuestros canales de contacto'
                            )}
                            .
                        </p>
                    </section>
                </div>
            </article>
        </PublicLayout>
    );
}
