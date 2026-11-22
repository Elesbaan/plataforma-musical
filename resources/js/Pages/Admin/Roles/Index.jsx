import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Lock, Pencil, Plus, ShieldCheck, Trash2, Users } from 'lucide-react';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import ConfirmDialog from '@/Components/ConfirmDialog';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Index({ roles }) {
    const [deleting, setDeleting] = useState(null);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        router.delete(`/admin/perfiles/${deleting.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    const deleteMessage = () => {
        if (!deleting) return '';
        if (deleting.is_system) return `${deleting.name} es un perfil base del sistema y no se puede eliminar.`;
        if (deleting.users_count > 0) return `${deleting.name} está asignado a ${deleting.users_count} usuario(s). Asígnales otro perfil antes de eliminarlo.`;
        return `¿Seguro que quieres eliminar el perfil ${deleting.name}? Esta acción no se puede deshacer.`;
    };

    return (
        <AdminLayout
            title="Perfiles y permisos"
            actions={
                <Button href="/admin/perfiles/crear">
                    <Plus className="h-4 w-4" aria-hidden="true" />
                    Nuevo perfil
                </Button>
            }
        >
            <Card title="Perfiles" description="Cada perfil define qué módulos y acciones puede usar un usuario. Los perfiles base no se pueden eliminar.">
                <ul className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    {roles.map((role) => (
                        <li key={role.id} className="flex flex-col rounded-xl border border-line p-4">
                            <div className="flex items-start gap-3">
                                <span className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <ShieldCheck className="h-5 w-5" aria-hidden="true" />
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="font-semibold text-ink">{role.name}</p>
                                    {role.description && <p className="mt-0.5 text-sm text-ink-muted">{role.description}</p>}
                                </div>
                            </div>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {role.is_system && (
                                    <Badge tone="brand">
                                        <Lock className="h-3 w-3" aria-hidden="true" />
                                        Perfil base
                                    </Badge>
                                )}
                                <Badge>
                                    <Users className="h-3 w-3" aria-hidden="true" />
                                    {role.users_count} usuario(s)
                                </Badge>
                                <Badge>{role.permissions_count} permiso(s)</Badge>
                            </div>
                            <div className="mt-4 flex gap-2 border-t border-line pt-3">
                                <Button variant="outline" size="sm" href={`/admin/perfiles/${role.id}/editar`} className="flex-1">
                                    <Pencil className="h-4 w-4" aria-hidden="true" />
                                    Editar permisos
                                </Button>
                                {!role.is_system && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => setDeleting(role)}
                                        aria-label={`Eliminar perfil ${role.name}`}
                                        className="text-red-600 dark:text-red-400"
                                    >
                                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            </Card>

            <ConfirmDialog
                open={!!deleting}
                title="Eliminar perfil"
                message={deleteMessage()}
                processing={processing}
                onConfirm={confirmDelete}
                onCancel={() => setDeleting(null)}
            />
        </AdminLayout>
    );
}
