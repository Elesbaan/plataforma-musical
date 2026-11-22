import { useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Search, SearchX } from 'lucide-react';
import Button from '@/Components/Button';
import { inputClass } from '@/Components/Form/fields';
import Pagination from '@/Components/Pagination';
import SectionHeading from '@/Components/Public/SectionHeading';
import ServiceCard from '@/Components/Public/ServiceCard';
import PublicLayout from '@/Layouts/PublicLayout';

const SORTS = [
    { value: 'recomendados', label: 'Recomendados' },
    { value: 'precio-asc', label: 'Precio: menor a mayor' },
    { value: 'precio-desc', label: 'Precio: mayor a menor' },
    { value: 'nombre', label: 'Nombre (A-Z)' },
];

export default function Index({ services, categories, filters }) {
    const [search, setSearch] = useState(filters.q ?? '');
    const firstRender = useRef(true);
    const featuredOnly = !!filters.destacados;

    const apply = (changes) => {
        const params = { ...filters, q: search, ...changes };
        delete params.page;
        Object.keys(params).forEach((key) => !params[key] && delete params[key]);
        router.get('/servicios', params, { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return undefined;
        }
        const timer = setTimeout(() => apply({ q: search }), 350);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const chip = (active) =>
        `min-h-touch shrink-0 whitespace-nowrap rounded-full border px-4 text-sm font-medium transition ${
            active ? 'border-primary bg-primary text-white' : 'border-line bg-surface-raised text-ink-muted hover:text-ink'
        }`;

    return (
        <PublicLayout>
            <Head title={featuredOnly ? 'Paquetes destacados' : 'Servicios'} />

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
                <SectionHeading
                    eyebrow="Catálogo"
                    title={featuredOnly ? 'Paquetes destacados' : 'Servicios y amenidades'}
                    description="Precios base transparentes. Agrega lo que te guste a tu cotización y consulta disponibilidad real para tu fecha."
                />

                <div className="mt-8 grid gap-3 md:grid-cols-[minmax(0,1fr)_16rem]">
                    <div className="relative">
                        <label htmlFor="catalog-search" className="sr-only">
                            Buscar servicios
                        </label>
                        <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" aria-hidden="true" />
                        <input
                            id="catalog-search"
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar: mariachi, pista, violín…"
                            className={`${inputClass} pl-10`}
                        />
                    </div>
                    <div>
                        <label htmlFor="catalog-sort" className="sr-only">
                            Ordenar
                        </label>
                        <select id="catalog-sort" value={filters.orden ?? 'recomendados'} onChange={(e) => apply({ orden: e.target.value })} className={inputClass}>
                            {SORTS.map((sort) => (
                                <option key={sort.value} value={sort.value}>
                                    {sort.label}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="-mx-4 mt-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                    <div className="flex gap-2 pb-1" role="group" aria-label="Filtrar por categoría">
                        <button type="button" aria-pressed={!filters.categoria && !featuredOnly} onClick={() => apply({ categoria: '', destacados: '' })} className={chip(!filters.categoria && !featuredOnly)}>
                            Todos
                        </button>
                        <button type="button" aria-pressed={featuredOnly} onClick={() => apply({ destacados: featuredOnly ? '' : '1' })} className={chip(featuredOnly)}>
                            Destacados
                        </button>
                        {categories.map((category) => (
                            <button
                                key={category.id}
                                type="button"
                                aria-pressed={filters.categoria === category.slug}
                                onClick={() => apply({ categoria: filters.categoria === category.slug ? '' : category.slug })}
                                className={chip(filters.categoria === category.slug)}
                            >
                                {category.name}
                            </button>
                        ))}
                    </div>
                </div>

                <p className="mt-6 text-sm text-ink-muted" aria-live="polite">
                    {services.total} {services.total === 1 ? 'servicio encontrado' : 'servicios encontrados'}
                </p>

                {services.data.length === 0 ? (
                    <div className="mt-6 flex flex-col items-center gap-4 rounded-2xl border border-dashed border-line px-6 py-16 text-center">
                        <SearchX className="h-10 w-10 text-ink-muted" aria-hidden="true" />
                        <p className="text-ink-muted">No encontramos servicios con esos filtros.</p>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setSearch('');
                                router.get('/servicios', {}, { preserveScroll: true });
                            }}
                        >
                            Limpiar filtros
                        </Button>
                    </div>
                ) : (
                    <ul className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {services.data.map((service) => (
                            <li key={service.id}>
                                <ServiceCard service={service} headingLevel={2} />
                            </li>
                        ))}
                    </ul>
                )}

                <Pagination paginator={services} />
            </section>
        </PublicLayout>
    );
}
