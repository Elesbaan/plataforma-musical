import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    title: (title, page) => {
        const brand = page?.props?.siteSettings?.brand_name ?? '';
        if (!title) return brand;
        return brand ? `${title} · ${brand}` : title;
    },
    resolve: (name) => {
        const page = pages[`./Pages/${name}.jsx`];
        if (!page) throw new Error(`Página no encontrada: ${name}`);
        return page();
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: 'var(--color-accent)',
    },
});
