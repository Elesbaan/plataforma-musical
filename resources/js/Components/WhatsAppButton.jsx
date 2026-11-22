import { usePage } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import { whatsappUrl } from '@/lib/contact';

/** Botón flotante de WhatsApp (esquina inferior derecha). Se oculta si no hay número configurado. */
export default function WhatsAppButton({ message = '' }) {
    const { siteSettings } = usePage().props;
    const href = whatsappUrl(siteSettings?.whatsapp_number, message);

    if (!href) return null;

    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Escríbenos por WhatsApp (se abre en una pestaña nueva)"
            className="fixed bottom-4 right-4 z-50 inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition hover:scale-105 sm:bottom-6 sm:right-6"
        >
            <MessageCircle className="h-7 w-7" aria-hidden="true" />
        </a>
    );
}
