/** Utilidades de presentación para datos de contacto provenientes de SiteSetting. */

export function whatsappUrl(number, message = '') {
    const digits = String(number ?? '').replace(/\D/g, '');
    if (!digits) return null;
    const text = message ? `?text=${encodeURIComponent(message)}` : '';
    return `https://wa.me/${digits}${text}`;
}

/**
 * Agrega la lada de país a un teléfono nacional de 10 dígitos tomando la lada
 * del WhatsApp de la agencia (ej. 52 si la agencia es de México).
 */
export function withCountryCode(phone, agencyNumber) {
    const digits = String(phone ?? '').replace(/\D/g, '');
    const agency = String(agencyNumber ?? '').replace(/\D/g, '');
    if (digits.length !== 10 || agency.length <= 10) return digits;
    return `${agency.slice(0, agency.length - 10)}${digits}`;
}

export function telUrl(number) {
    const digits = String(number ?? '').replace(/[^\d+]/g, '');
    return digits ? `tel:${digits}` : null;
}

/** Redes sociales configuradas, en el orden en que se muestran. */
export function socialLinks(settings) {
    return [
        { label: 'Facebook', url: settings?.facebook_url },
        { label: 'Instagram', url: settings?.instagram_url },
        { label: 'TikTok', url: settings?.tiktok_url },
        { label: 'YouTube', url: settings?.youtube_url },
        { label: 'Spotify', url: settings?.spotify_url },
    ].filter((link) => !!link.url);
}
