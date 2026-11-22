import { useForm } from '@inertiajs/react';
import { Lock, Save } from 'lucide-react';
import PermissionMatrix from '@/Components/Admin/PermissionMatrix';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import { TextAreaField, TextField } from '@/Components/Form/fields';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Form({ role, permissionGroups }) {
    const isSuperAdmin = !!role?.is_super_admin;
    const allKeys = permissionGroups.flatMap((group) => group.permissions.map((p) => p.key));

    const { data, setData, post, put, processing, errors, isDirty } = useForm({
        name: role?.name ?? '',
        description: role?.description ?? '',
        permissions: isSuperAdmin ? allKeys : role?.permissions ?? [],
    });

    const submit = (event) => {
        event.preventDefault();
        if (role) put(`/admin/perfiles/${role.id}`);
        else post('/admin/perfiles');
    };

    const permissionError = Object.entries(errors).find(([key]) => key.startsWith('permissions'))?.[1];

    return (
        <AdminLayout title={role ? `Perfil: ${role.name}` : 'Nuevo perfil'}>
            <form onSubmit={submit} className="space-y-6" noValidate>
                <Card title="Datos del perfil">
                    <div className="grid gap-5 md:grid-cols-2">
                        <TextField
                            label="Nombre del perfil"
                            required
                            maxLength={100}
                            value={data.name}
                            onChange={(value) => setData('name', value)}
                            error={errors.name}
                        />
                        <TextAreaField
                            label="Descripción"
                            rows={2}
                            maxLength={255}
                            value={data.description}
                            onChange={(value) => setData('description', value)}
                            error={errors.description}
                        />
                    </div>
                    {role?.is_system && (
                        <p className="mt-4 flex items-start gap-2 rounded-xl bg-surface-sunken p-3 text-sm text-ink-muted">
                            <Lock className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            Este es un perfil base del sistema: puedes renombrarlo y ajustar sus permisos, pero no eliminarlo.
                        </p>
                    )}
                </Card>

                <Card
                    title="Matriz de permisos"
                    description={
                        isSuperAdmin
                            ? 'El Super Administrador siempre tiene todos los permisos; no se pueden quitar.'
                            : 'Activa lo que este perfil puede hacer. Los permisos “solo sus servicios” limitan el acceso a los servicios vinculados a cada usuario.'
                    }
                >
                    <PermissionMatrix
                        groups={permissionGroups}
                        value={data.permissions}
                        onChange={(keys) => setData('permissions', keys)}
                        disabled={isSuperAdmin}
                    />
                    {permissionError && <p className="mt-3 text-sm text-red-600 dark:text-red-400">{permissionError}</p>}
                </Card>

                <div className="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center justify-end gap-3 border-t border-line bg-surface-sunken/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                    {isDirty && <span className="mr-auto text-sm text-ink-muted">Tienes cambios sin guardar.</span>}
                    <Button variant="outline" href="/admin/perfiles" className="flex-1 sm:flex-none">
                        Volver
                    </Button>
                    <Button type="submit" disabled={processing} className="flex-1 sm:flex-none">
                        <Save className="h-4 w-4" aria-hidden="true" />
                        {processing ? 'Guardando…' : role ? 'Guardar perfil' : 'Crear perfil'}
                    </Button>
                </div>
            </form>
        </AdminLayout>
    );
}
