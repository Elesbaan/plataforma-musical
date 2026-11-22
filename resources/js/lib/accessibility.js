/**
 * Preferencias de accesibilidad persistidas en localStorage (`a11y_preferences`).
 * El script en línea de app.blade.php aplica estas mismas claves antes del primer
 * pintado; si cambias la estructura, actualiza ambos lugares.
 */
export const STORAGE_KEY = 'a11y_preferences';

export const FONT_SCALES = [
    { key: 'sm', label: 'A-', value: '90%', description: 'Texto pequeño' },
    { key: 'md', label: 'A', value: '100%', description: 'Texto normal' },
    { key: 'lg', label: 'A+', value: '115%', description: 'Texto grande' },
    { key: 'xl', label: 'A++', value: '130%', description: 'Texto muy grande' },
];

export const TOGGLES = [
    { key: 'highContrast', className: 'a11y-high-contrast', label: 'Alto contraste' },
    { key: 'grayscale', className: 'a11y-grayscale', label: 'Escala de grises' },
    { key: 'highlightLinks', className: 'a11y-highlight-links', label: 'Resaltar enlaces y botones' },
    { key: 'reduceMotion', className: 'a11y-reduce-motion', label: 'Pausar animaciones' },
];

export function systemTheme() {
    if (typeof window === 'undefined' || !window.matchMedia) return 'light';
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function defaultPreferences() {
    return {
        theme: null, // null = seguir la preferencia del sistema
        fontScale: 'md',
        highContrast: false,
        grayscale: false,
        highlightLinks: false,
        reduceMotion: false,
    };
}

export function loadPreferences() {
    try {
        const stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}');
        return { ...defaultPreferences(), ...(stored && typeof stored === 'object' ? stored : {}) };
    } catch {
        return defaultPreferences();
    }
}

export function savePreferences(prefs) {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch {
        // Almacenamiento bloqueado (modo privado): las preferencias aplican solo en esta visita.
    }
}

export function clearPreferences() {
    try {
        window.localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Sin acceso al almacenamiento; no hay nada que limpiar.
    }
}

export function resolvedTheme(prefs) {
    return prefs.theme ?? systemTheme();
}

export function applyPreferences(prefs) {
    const root = document.documentElement;
    root.classList.toggle('dark', resolvedTheme(prefs) === 'dark');

    const scale = FONT_SCALES.find((s) => s.key === prefs.fontScale) ?? FONT_SCALES[1];
    root.style.fontSize = scale.value;

    TOGGLES.forEach(({ key, className }) => root.classList.toggle(className, !!prefs[key]));
}
