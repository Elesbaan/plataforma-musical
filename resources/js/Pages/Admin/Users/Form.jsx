import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Save, Trash2 } from 'lucide-react';
import ServicePicker from '@/Components/Admin/ServicePicker';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { SelectField, TextField, ToggleField } from '@/Components/Form/fields';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Form({ user, roles, services }) {
    const [showPassword, setShowPassword] = useState(false);
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const isSelf = !!user?.is_self;

    const { data, setData, post, put, processing, errors, isDirty } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        role_id: user?.role_id ?? '',
        is_active: user?.is_active ?? true,
        service_ids: user?.service_ids ?? [],
    });

    const selectedRole = roles.find((role) => String(role.id) === String(data.role_id));
    const needsServices = !!selectedRole?.has_own_scope;

    const submit = (event) => {
        event.preventDefault();
        if (user) put(`/admin/usuarios/${user.id}`);
        else post('/admin/usuarios');
    };

    const destroy = () => {
        router.delete(`/admin/usuarios/${user.id}`, {
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setConfirmingDelete(false);
            },
        });
    };

    const field = (name) => ({ value: data[name], onChange: (value) => setData(name, value), error: errors[name] });
    const passwordToggle = (
        <button
            type="button"
            onClick={() => setShowPassword((value) => !value)}
            aria-label={showPassword ? 'Ocultar contraseñas' : 'Mostrar contraseñas'}
            className="inline-flex min-h-touch items-center gap-2 rounded-full px-3 text-sm font-medium text-ink-muted hover:bg-surface-sunken hover:text-ink"
        >
            {showPassword ? <EyeOff className="h-4 w-4" aria-hidden="true" /> : <Eye className="h-4 w-4" aria-hidden="true" />}
            {showPassword ? 'Ocultar' : 'Mostrar'}
        </button>
    );

    return (
        <AdminLayout
            title={user ? `Usuario: ${user.name}` : 'Nuevo usuario'}
            actions={
                user && !isSelf ? (
                    <Button variant="ghost" onClick={() => setConfirmingDelete(true)} className="text-red-600 dark:text-red-400">
                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                        Eliminar usuario
                    </Button>
                ) : null
            }
        >
            <form onSubmit={submit} className="space-y-6" noValidate>
                <div className="grid gap-6 xl:grid-cols-2">
                    <Card title="Datos de acceso">
                        <div className="grid gap-5">
                            <TextField label="Nombre completo" required maxLength={120} autoComplete="off" {...field('name')} />
                            <TextField label="Correo electrónico" type="email" required autoComplete="off" {...field('email')} />
                        </div>
                    </Card>

                    <Card
                        title="Contraseña"
                        description={user ? 'Déjala vacía para conservar la actual.' : 'Mínimo 8 caracteres, con letras y números.'}
                        actions={passwordToggle}
                    >
                        <div className="grid gap-5">
                            <TextField
                                label={user ? 'Nueva contraseña' : 'Contraseña'}
                                type={showPassword ? 'text' : 'password'}
                                required={!user}
                                autoComplete="new-password"
                                {...field('password')}
                            />
                            <TextField
                                label="Confirmar contraseña"
                                type={showPassword ? 'text' : 'password'}
                                required={!user}
                                autoComplete="new-password"
                                {...field('password_confirmation')}
                            />
                        </div>
                    </Card>
                </div>

                <Card title="Perfil y estado">
                    <div className="grid gap-5 md:grid-cols-2">
                        <SelectField
                            label="Perfil"
                            required
                            placeholder="Elige un perfil"
                            options={roles.map((role) => ({ value: role.id, label: role.name }))}
                            disabled={isSelf}
                            hint={isSelf ? 'No puedes cambiar tu propio perfil.' : undefined}
                            {...field('role_id')}
                        />
                        <div>
                            <ToggleField
                                label="Cuenta activa"
                                description={isSelf ? 'No puedes desactivar tu propia cuenta.' : 'Si está inactiva, no podrá iniciar sesión.'}
                                checked={data.is_active}
                                onChange={(value) => !isSelf && setData('is_active', value)}
                                error={errors.is_active}
                            />
                        </div>
                    </div>
                </Card>

                {needsServices && (
                    <Card
                        title="Servicios vinculados"
                        description="Este perfil trabaja solo con sus propios servicios. Elige cuáles le pertenecen (ej. el dueño del mariachi)."
                    >
                        <ServicePicker
                            services={services}
                            value={data.service_ids}
                            onChange={(ids) => setData('service_ids', ids)}
                            error={errors.service_ids ?? Object.entries(errors).find(([key]) => key.startsWith('service_ids.'))?.[1]}
                        />
                    </Card>
                )}

                <div className="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center justify-end gap-3 border-t border-line bg-surface-sunken/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                    {isDirty && <span className="mr-auto text-sm text-ink-muted">Tienes cambios sin guardar.</span>}
                    <Button variant="outline" href="/admin/usuarios" className="flex-1 sm:flex-none">
                        Volver
                    </Button>
                    <Button type="submit" disabled={processing} className="flex-1 sm:flex-none">
                        <Save className="h-4 w-4" aria-hidden="true" />
                        {processing ? 'Guardando…' : user ? 'Guardar usuario' : 'Crear usuario'}
                    </Button>
                </div>
            </form>

            {user && (
                <ConfirmDialog
                    open={confirmingDelete}
                    title="Eliminar usuario"
                    message={`¿Seguro que quieres eliminar la cuenta de ${user.name}? Si solo quieres quitarle el acceso, desactívala.`}
                    processing={deleting}
                    onConfirm={destroy}
                    onCancel={() => setConfirmingDelete(false)}
                />
            )}
        </AdminLayout>
    );
}
