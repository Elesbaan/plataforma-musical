/** Utilidades de presentación para datos de contacto provenientes de SiteSetting. */

export function whatsappUrl(number, message = '') {
    const digits = String(number ?? '').replace(/\D/g, '');
    if (!digits) return null;
    const text = message ? `?text=${encodeURIComponent(message)}` : '';
    return `https://wa.me/${digits}${text}`;
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
