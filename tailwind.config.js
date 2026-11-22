import defaultTheme from 'tailwindcss/defaultTheme';

/**
 * Los colores de marca provienen de SiteSetting y se inyectan como variables CSS
 * en resources/views/app.blade.php. Los colores neutros (superficies, texto,
 * bordes) se definen en resources/css/app.css para modo claro, oscuro y alto contraste.
 *
 * @type {import('tailwindcss').Config}
 */
const token = (name) => `rgb(var(${name}) / <alpha-value>)`;

export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.{js,jsx}',
    ],
    theme: {
        extend: {
            colors: {
                primary: token('--color-primary-rgb'),
                secondary: token('--color-secondary-rgb'),
                accent: token('--color-accent-rgb'),
                surface: {
                    DEFAULT: token('--surface'),
                    raised: token('--surface-raised'),
                    sunken: token('--surface-sunken'),
                },
                ink: {
                    DEFAULT: token('--ink'),
                    muted: token('--ink-muted'),
                },
                line: token('--line'),
            },
            fontFamily: {
                sans: ['"Inter Variable"', ...defaultTheme.fontFamily.sans],
                display: ['"Playfair Display Variable"', ...defaultTheme.fontFamily.serif],
            },
            minHeight: {
                touch: '44px',
            },
            minWidth: {
                touch: '44px',
            },
        },
    },
    plugins: [],
};
