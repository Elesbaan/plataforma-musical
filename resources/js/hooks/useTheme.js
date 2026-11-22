import { useEffect, useState } from 'react';

function currentTheme() {
    if (typeof document === 'undefined') return 'light';
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

/**
 * Tema activo ('light' | 'dark') observando la clase `dark` de <html>,
 * que controla el widget de accesibilidad.
 */
export default function useTheme() {
    const [theme, setTheme] = useState(currentTheme);

    useEffect(() => {
        const observer = new MutationObserver(() => setTheme(currentTheme()));
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        return () => observer.disconnect();
    }, []);

    return theme;
}
