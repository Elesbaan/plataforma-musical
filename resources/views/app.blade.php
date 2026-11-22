@php
    $siteSettings = \App\Models\SiteSetting::current();
@endphp
<!DOCTYPE html>
<html lang="es-MX">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ $siteSettings->brand_name }}</title>
        @if ($siteSettings->seo_description)
            <meta name="description" content="{{ $siteSettings->seo_description }}">
        @endif
        <meta name="theme-color" content="{{ $siteSettings->safeColor('color_secondary') }}">
        <link rel="icon" href="{{ $siteSettings->favicon_url ?? asset('favicon.ico') }}">

        <style>
            :root {
                --color-primary: {{ $siteSettings->safeColor('color_primary') }};
                --color-secondary: {{ $siteSettings->safeColor('color_secondary') }};
                --color-accent: {{ $siteSettings->safeColor('color_accent') }};
                --color-primary-rgb: {{ $siteSettings->rgbChannels('color_primary') }};
                --color-secondary-rgb: {{ $siteSettings->rgbChannels('color_secondary') }};
                --color-accent-rgb: {{ $siteSettings->rgbChannels('color_accent') }};
            }
        </style>

        {{-- Aplica las preferencias de accesibilidad antes de pintar para evitar parpadeos. --}}
        <script>
            (function () {
                var root = document.documentElement;
                var prefs = {};
                try { prefs = JSON.parse(localStorage.getItem('a11y_preferences') || '{}') || {}; } catch (e) {}
                var theme = prefs.theme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                root.classList.toggle('dark', theme === 'dark');
                var scales = { sm: '90%', md: '100%', lg: '115%', xl: '130%' };
                if (scales[prefs.fontScale]) root.style.fontSize = scales[prefs.fontScale];
                root.classList.toggle('a11y-high-contrast', !!prefs.highContrast);
                root.classList.toggle('a11y-grayscale', !!prefs.grayscale);
                root.classList.toggle('a11y-highlight-links', !!prefs.highlightLinks);
                root.classList.toggle('a11y-reduce-motion', !!prefs.reduceMotion);
            })();
        </script>

        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="bg-surface text-ink font-sans antialiased">
        @inertia
    </body>
</html>
