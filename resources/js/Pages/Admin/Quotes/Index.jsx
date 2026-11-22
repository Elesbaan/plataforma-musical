import { useEffect, useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { CalendarCheck, ChevronRight, FileText, Search } from 'lucide-react';
import Badge from '@/Components/Badge';
import Card from '@/Components/Card';
import { inputClass } from '@/Components/Form/fields';
import Pagination from '@/Components/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { formatLongDate } from '@/lib/date';
import { formatMoney } from '@/lib/format';
import { QUOTE_STATUSES, quoteStatus } from '@/lib/quotes';

export default function Index({ quotes, statusCounts, zones, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    const apply = (changes) => {
        const params = { ...filters, search, ...changes };
        delete params.page;
        Object.keys(params).forEach((key) => !params[key] && delete params[key]);
        router.get('/admin/cotizaciones', params, { preserveState: true, preserveScroll: true, replace: true });
    };

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return undefined;
        }
        const timer = setTimeout(() => apply({ search }), 350);
        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const tab = (active) =>
        `inline-flex min-h-touch shrink-0 items-center gap-2 whitespace-nowrap rounded-xl px-4 text-sm font-medium transition ${
            active ? 'bg-primary text-white' : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'
        }`;
    const total = Object.values(statusCounts).reduce((sum, n) => sum + n, 0);

    return (
        <AdminLayout title="Cotizaciones">
            <div className="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <div className="inline-flex min-w-full gap-1 rounded-2xl border border-line bg-surface-raised p-1 sm:min-w-0" role="group" aria-label="Filtrar por estatus">
                    <button type="button" aria-pressed={!filters.status} onClick={() => apply({ status: '' })} className={tab(!filters.status)}>
                        Todas <span className="text-xs opacity-80">{total}</span>
                    </button>
                    {QUOTE_STATUSES.map((status) => (
                        <button key={status.value} type="button" aria-pressed={filters.status === status.value} onClick={() => apply({ status: status.value })} className={tab(filters.status === status.value)}>
                            {status.label} <span className="text-xs opacity-80">{statusCounts[status.value]}</span>
                        </button>
                    ))}
                </div>
            </div>

            <Card>
                <div className="mb-5 grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_12rem_10rem_10rem]">
                    <div className="relative md:col-span-2 xl:col-span-1">
                        <label htmlFor="quote-search" className="sr-only">
                            Buscar por folio, nombre o teléfono
                        </label>
                        <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" aria-hidden="true" />
                        <input
                            id="quote-search"
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Folio, nombre o teléfono…"
                            className={`${inputClass} pl-10`}
                        />
                    </div>
                    <div>
                        <label htmlFor="quote-zone" className="sr-only">
                            Zona
                        </label>
                        <select id="quote-zone" value={filters.zone ?? ''} onChange={(e) => apply({ zone: e.target.value })} className={inputClass}>
                            <option value="">Todas las zonas</option>
                            {zones.map((zone) => (
                                <option key={zone.id} value={zone.id}>
                                    {zone.city_name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="quote-from" className="mb-1 block text-xs text-ink-muted">
                            Evento desde
                        </label>
                        <input id="quote-from" type="date" value={filters.from ?? ''} onChange={(e) => apply({ from: e.target.value })} className={inputClass} />
                    </div>
                    <div>
                        <label htmlFor="quote-to" className="mb-1 block text-xs text-ink-muted">
                            Evento hasta
                        </label>
                        <input id="quote-to" type="date" value={filters.to ?? ''} onChange={(e) => apply({ to: e.target.value })} className={inputClass} />
                    </div>
                </div>

                {quotes.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 py-10 text-center text-ink-muted">
                        <FileText className="h-10 w-10" aria-hidden="true" />
                        <p>No hay cotizaciones con esos filtros.</p>
                    </div>
                ) : (
                    <>
                        <ul className="space-y-3 lg:hidden">
                            {quotes.data.map((quote) => {
                                const status = quoteStatus(quote.status);
                                return (
                                    <li key={quote.id}>
                                        <Link href={`/admin/cotizaciones/${quote.id}`} className="block rounded-xl border border-line p-4 transition hover:border-primary/50">
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="font-mono text-xs text-ink-muted">{quote.folio}</p>
                                                    <p className="font-semibold text-ink">{quote.client_name}</p>
                                                    <p className="text-sm text-ink-muted">
                                                        {quote.event_type} · {formatLongDate(quote.event_date, false)}
                                                    </p>
                                                </div>
                                                <ChevronRight className="mt-1 h-5 w-5 shrink-0 text-ink-muted" aria-hidden="true" />
                                            </div>
                                            <div className="mt-3 flex flex-wrap items-center justify-between gap-2">
                                                <div className="flex flex-wrap gap-1">
                                                    <Badge tone={status.tone}>{status.label}</Badge>
                                                    {quote.has_event && (
                                                        <Badge tone="success">
                                                            <CalendarCheck className="h-3 w-3" aria-hidden="true" />
                                                            Agendada
                                                        </Badge>
                                                    )}
                                                </div>
                                                <span className="font-semibold tabular-nums text-ink">{formatMoney(quote.total)}</span>
                                            </div>
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>

                        <div className="hidden overflow-x-auto lg:block">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                                    <tr>
                                        <th scope="col" className="py-3 pr-4 font-medium">Folio</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Cliente</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Evento</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Ciudad</th>
                                        <th scope="col" className="py-3 pr-4 text-right font-medium">Total</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Estatus</th>
                                        <th scope="col" className="py-3 font-medium">
                                            <span className="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {quotes.data.map((quote) => {
                                        const status = quoteStatus(quote.status);
                                        return (
                                            <tr key={quote.id}>
                                                <td className="py-3 pr-4 font-mono text-xs">{quote.folio}</td>
                                                <td className="py-3 pr-4">
                                                    <p className="font-medium text-ink">{quote.client_name}</p>
                                                    <p className="text-ink-muted">{quote.client_phone}</p>
                                                </td>
                                                <td className="py-3 pr-4">
                                                    <p className="text-ink">{quote.event_type}</p>
                                                    <p className="text-ink-muted">{formatLongDate(quote.event_date, false)}</p>
                                                </td>
                                                <td className="py-3 pr-4 text-ink-muted">{quote.city}</td>
                                                <td className="py-3 pr-4 text-right font-medium tabular-nums">{formatMoney(quote.total)}</td>
                                                <td className="py-3 pr-4">
                                                    <div className="flex flex-wrap gap-1">
                                                        <Badge tone={status.tone}>{status.label}</Badge>
                                                        {quote.has_event && <Badge tone="success">Agendada</Badge>}
                                                    </div>
                                                </td>
                                                <td className="py-3 text-right">
                                                    <Link
                                                        href={`/admin/cotizaciones/${quote.id}`}
                                                        className="inline-flex min-h-touch items-center gap-1 rounded-full px-3 text-sm font-medium text-primary hover:bg-surface-sunken"
                                                    >
                                                        Ver
                                                        <ChevronRight className="h-4 w-4" aria-hidden="true" />
                                                    </Link>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}

                <Pagination paginator={quotes} />
            </Card>
        </AdminLayout>
    );
}
