import { useEffect, useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Pencil, Search, UserPlus, Users } from 'lucide-react';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import { inputClass } from '@/Components/Form/fields';
import Pagination from '@/Components/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({ users, roles, filters }) {
    const [search, setSearch] = useState(filters.search ?? '');
    const firstRender = useRef(true);

    const applyFilters = (changes) => {
        const params = { ...filters, search, ...changes };
        Object.keys(params).forEach((key) => !params[key] && delete params[key]);
        router.get('/admin/usuarios', params, { preserveState: true, preserveScroll: true, replace: true });
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

    const status = (user) => <Badge tone={user.is_active ? 'success' : 'danger'}>{user.is_active ? 'Activo' : 'Inactivo'}</Badge>;

    return (
        <AdminLayout
            title="Usuarios"
            actions={
                <Button href="/admin/usuarios/crear">
                    <UserPlus className="h-4 w-4" aria-hidden="true" />
                    Nuevo usuario
                </Button>
            }
        >
            <Card>
                <div className="mb-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_12rem]">
                    <div className="relative">
                        <label htmlFor="user-search" className="sr-only">
                            Buscar usuario
                        </label>
                        <Search className="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" aria-hidden="true" />
                        <input
                            id="user-search"
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar por nombre o correo…"
                            className={`${inputClass} pl-10`}
                        />
                    </div>
                    <div>
                        <label htmlFor="user-role" className="sr-only">
                            Filtrar por perfil
                        </label>
                        <select id="user-role" value={filters.role ?? ''} onChange={(e) => applyFilters({ role: e.target.value })} className={inputClass}>
                            <option value="">Todos los perfiles</option>
                            {roles.map((role) => (
                                <option key={role.id} value={role.id}>
                                    {role.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="user-status" className="sr-only">
                            Filtrar por estado
                        </label>
                        <select id="user-status" value={filters.status ?? ''} onChange={(e) => applyFilters({ status: e.target.value })} className={inputClass}>
                            <option value="">Todos</option>
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </select>
                    </div>
                </div>

                {users.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 py-10 text-center text-ink-muted">
                        <Users className="h-10 w-10" aria-hidden="true" />
                        <p>No encontramos usuarios con esos filtros.</p>
                    </div>
                ) : (
                    <>
                        <ul className="space-y-3 md:hidden">
                            {users.data.map((user) => (
                                <li key={user.id}>
                                    <Link href={`/admin/usuarios/${user.id}/editar`} className="block rounded-xl border border-line p-4 transition hover:border-primary/50">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="font-semibold text-ink">{user.name}</p>
                                                <p className="break-all text-sm text-ink-muted">{user.email}</p>
                                            </div>
                                            <Pencil className="mt-1 h-4 w-4 shrink-0 text-ink-muted" aria-hidden="true" />
                                        </div>
                                        <div className="mt-3 flex flex-wrap gap-2">
                                            <Badge tone="brand">{user.role?.name ?? 'Sin perfil'}</Badge>
                                            {status(user)}
                                            {user.services_count > 0 && <Badge>{user.services_count} servicio(s)</Badge>}
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>

                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                                    <tr>
                                        <th scope="col" className="py-3 pr-4 font-medium">Usuario</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Perfil</th>
                                        <th scope="col" className="py-3 pr-4 text-center font-medium">Servicios vinculados</th>
                                        <th scope="col" className="py-3 pr-4 font-medium">Estado</th>
                                        <th scope="col" className="py-3 font-medium">
                                            <span className="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {users.data.map((user) => (
                                        <tr key={user.id}>
                                            <td className="py-3 pr-4">
                                                <p className="font-medium text-ink">{user.name}</p>
                                                <p className="text-ink-muted">{user.email}</p>
                                            </td>
                                            <td className="py-3 pr-4">{user.role?.name ?? <span className="text-ink-muted">Sin perfil</span>}</td>
                                            <td className="py-3 pr-4 text-center">{user.services_count}</td>
                                            <td className="py-3 pr-4">{status(user)}</td>
                                            <td className="py-3 text-right">
                                                <Button variant="ghost" size="sm" href={`/admin/usuarios/${user.id}/editar`} aria-label={`Editar ${user.name}`}>
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

                <Pagination paginator={users} />
            </Card>
        </AdminLayout>
    );
}
