const HEX = /^#[0-9A-Fa-f]{6}$/;

export function isHexColor(value) {
    return HEX.test(String(value ?? ''));
}

/** "#6D28D9" -> "109 40 217" (canales para las variables CSS de Tailwind). */
export function hexToChannels(hex, fallback = '0 0 0') {
    if (!isHexColor(hex)) return fallback;
    const n = parseInt(hex.slice(1), 16);
    return `${(n >> 16) & 255} ${(n >> 8) & 255} ${n & 255}`;
}

function luminance(hex) {
    const n = parseInt(hex.slice(1), 16);
    const [r, g, b] = [(n >> 16) & 255, (n >> 8) & 255, n & 255].map((c) => {
        const v = c / 255;
        return v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4;
    });
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

/** Relación de contraste WCAG entre dos colores hexadecimales (1 a 21). */
export function contrastRatio(a, b) {
    if (!isHexColor(a) || !isHexColor(b)) return null;
    const [l1, l2] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return (l1 + 0.05) / (l2 + 0.05);
}
