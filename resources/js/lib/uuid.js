/**
 * UUID v4. `crypto.randomUUID` solo existe en contextos seguros (HTTPS);
 * se usa `getRandomValues` como alternativa para sitios servidos por HTTP.
 */
export function uuid() {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID();

    const bytes = window.crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((b) => b.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

/** Tamaño legible (ej. 12.4 MB). */
export function formatBytes(bytes) {
    if (!Number.isFinite(bytes)) return '';
    const units = ['B', 'KB', 'MB', 'GB'];
    let value = bytes;
    let unit = 0;
    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }
    return `${value.toLocaleString('es-MX', { maximumFractionDigits: unit === 0 ? 0 : 1 })} ${units[unit]}`;
}
