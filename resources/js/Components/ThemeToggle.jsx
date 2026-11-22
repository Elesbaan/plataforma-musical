import { Moon, Sun } from 'lucide-react';
import useTheme from '@/hooks/useTheme';
import { applyPreferences, loadPreferences, savePreferences } from '@/lib/accessibility';

/** Interruptor compacto de tema claro/oscuro; comparte preferencias con el widget de accesibilidad. */
export default function ThemeToggle({ className = '' }) {
    const theme = useTheme();
    const next = theme === 'dark' ? 'light' : 'dark';

    const toggle = () => {
        const prefs = { ...loadPreferences(), theme: next };
        applyPreferences(prefs);
        savePreferences(prefs);
    };

    return (
        <button
            type="button"
            onClick={toggle}
            aria-label={next === 'dark' ? 'Activar modo oscuro' : 'Activar modo claro'}
            className={`inline-flex min-h-touch min-w-touch items-center justify-center rounded-full text-ink-muted hover:bg-surface-sunken hover:text-ink ${className}`}
        >
            {theme === 'dark' ? <Sun className="h-5 w-5" aria-hidden="true" /> : <Moon className="h-5 w-5" aria-hidden="true" />}
        </button>
    );
}
