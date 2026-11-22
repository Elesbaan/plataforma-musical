import { useEffect, useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Music, Pencil, Plus, Search, Star } from 'lucide-react';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import { inputClass } from '@/Components/Form/fields';
import Pagination from '@/Components/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { serviceTypeLabel } from '@/lib/catalog';
import { formatMoney } from '@/lib/format';

const STATUS_OPTIONS = [
    { value: '', label: 'Todos' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
    { value: 'featured', label: 'Destacados' },
];

function StatusBadges({ service }) {
    return (
        <div className="flex flex-wrap gap-1">
            <Badge tone={service.is_active ? 'success' : 'danger'}>{service.is_active ? 'Activo' : 'Inactivo'}</Badge>
            {service.is_featured && (
                <Badge tone="brand">
                    <Star className="h-3 w-3" aria-hidden="true" />
                    Destacado
                </Badge>
            )}
        </div>
    );
}

export default function Index({ services, categories, filters, abilities }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    const applyFilters = (changes) => {
        const params = { ...filters, search, ...changes };
        Object.keys(params).forEach((key) => !params[key] && delete params[key]);
        router.get('/admin/servicios', params, { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return undefined;
        }
        const timer = setTimeout(() => applyFilters({ search }), 350);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <AdminLayout
            title="Servicios"
            actions={
                abilities.create ? (
                    <Button href="/admin/servicios/crear">
                        <Plus className="h-4 w-4" aria-hidden="true" />
                        Nuevo servicio
                    </Button>
                ) : null
            }
        >
            <Card>
                <div className="mb-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_12rem]">
                    <div className="relative">
                        <label htmlFor="service-search" className="sr-only">
                            Buscar servicio
                        </label>
                        <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" aria-hidden="true" />
                        <input
                            id="service-search"
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar por nombre…"
                            className={`${inputClass} pl-10`}
                        />
                    </div>
                    <div>
                        <label htmlFor="service-category" className="sr-only">
                            Filtrar por categoría
                        </label>
                        <select
                            id="service-category"
                            value={filters.category ?? ''}
                            onChange={(e) => applyFilters({ category: e.target.value })}
                            className={inputClass}
                        >
                            <option value="">Todas las categorías</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="service-status" className="sr-only">
                            Filtrar por estado
                        </label>
                        <select
                            id="service-status"
                            value={filters.status ?? ''}
                            onChange={(e) => applyFilters({ status: e.target.value })}
                            className={inputClass}
                        >
                            {STATUS_OPTIONS.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                {services.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 py-10 text-center text-ink-muted">
                        <Music className="h-10 w-10" aria-hidden="true" />
                        <p>
                            {abilities.create || Object.keys(filters).length > 0
                                ? 'No encontramos servicios con esos filtros.'
                                : 'Aún no tienes servicios vinculados. Pide al administrador que te asigne los tuyos.'}
                        </p>
                    </div>
                ) : (
                    <>
                        {/* Móvil: tarjetas */}
                        <ul className="space-y-3 lg:hidden">
                            {services.data.map((service) => (
                                <li key={service.id}>
                                    <Link
                                        href={`/admin/servicios/${service.id}/editar`}
                                        className="block rounded-xl border border-line p-4 transition hover:border-primary/50"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="font-semibold text-ink">{service.name}</p>
                                                <p className="text-sm text-ink-muted">{service.category?.name}</p>
                                            </div>
                                            <Pencil className="mt-1 h-4 w-4 shrink-0 text-ink-muted" aria-hidden="true" />
                                        </div>
                                        <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                                            <p className="text-sm">
                                                <span className="font-semibold text-ink">{formatMoney(service.base_price)}</span>{' '}
                                                <span className="text-ink-muted">{service.price_unit}</span>
                                            </p>
                                            <span className="text-sm text-ink-muted">Capacidad: {service.daily_capacity}/día</span>
                                        </div>
                                        <div className="mt-2">
                                            <StatusBadges service={service} />
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>

                        {/* Escritorio: tabla */}
                        <div className="hidden overflow-x-auto lg:block">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                                    <tr>
                                        <th scope="col" className="py-3 pr-4 font-medium">Servicio</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Tipo</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Precio base</th>
                                        <th scope="col" className="py-3 pr-4 text-center font-medium">Capacidad diaria</th>
                                        <th scope="col" className="py-3 pr-4 text-center font-medium">Multimedia</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Estado</th>
                                        <th scope="col" className="py-3 font-medium">
                                            <span className="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {services.data.map((service) => (
                                        <tr key={service.id}>
                                            <td className="py-3 pr-4">
                                                <p className="font-medium text-ink">{service.name}</p>
                                                <p className="text-ink-muted">{service.category?.name}</p>
                                            </td>
                                            <td className="py-3 pr-4 text-ink-muted">{serviceTypeLabel(service.service_type)}</td>
                                            <td className="py-3 pr-4">
                                                <p className="font-medium text-ink">{formatMoney(service.base_price)}</p>
                                                <p className="text-ink-muted">{service.price_unit}</p>
                                            </td>
                                            <td className="py-3 pr-4 text-center font-medium text-ink">{service.daily_capacity}</td>
                                            <td className="py-3 pr-4 text-center text-ink-muted">{service.media_count}</td>
                                            <td className="py-3 pr-4">
                                                <StatusBadges service={service} />
                                            </td>
                                            <td className="py-3 text-right">
                                                <Button variant="ghost" size="sm" href={`/admin/servicios/${service.id}/editar`} aria-label={`Editar ${service.name}`}>
                                                    <Pencil className="h-4 w-4" aria-hidden="true" />
                                                    Editar
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}

                <Pagination paginator={services} />
            </Card>
        </AdminLayout>
    );
}
