import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { Layers, Pencil, Plus, Trash2 } from 'lucide-react';
import Badge from '@/Components/Badge';
import Button from '@/Components/Button';
import Card from '@/Components/Card';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { TextAreaField, TextField, ToggleField } from '@/Components/Form/fields';
import Modal from '@/Components/Modal';
import AdminLayout from '@/Layouts/AdminLayout';
import { CATEGORY_ICONS, categoryIcon } from '@/lib/catalog';

function CategoryForm({ category, onClose }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: category?.name ?? '',
        description: category?.description ?? '',
        icon: category?.icon === 'Mic2' ? 'MicVocal' : category?.icon ?? 'Music',
        is_active: category?.is_active ?? true,
        sort_order: category?.sort_order ?? 0,
    });

    const submit = (event) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };
        if (category) put(`/admin/categorias/${category.id}`, options);
        else post('/admin/categorias', options);
    };

    const field = (name) => ({ value: data[name], onChange: (value) => setData(name, value), error: errors[name] });

    return (
        <Modal
            open
            onClose={onClose}
            title={category ? `Editar ${category.name}` : 'Nueva categoría'}
            footer={
                <>
                    <Button variant="outline" onClick={onClose}>
                        Cancelar
                    </Button>
                    <Button type="submit" form="category-form" disabled={processing}>
                        {processing ? 'Guardando…' : 'Guardar categoría'}
                    </Button>
                </>
            }
        >
            <form id="category-form" onSubmit={submit} className="space-y-5" noValidate>
                <TextField label="Nombre" required maxLength={120} data-autofocus {...field('name')} />
                <TextAreaField label="Descripción" rows={2} maxLength={500} {...field('description')} />

                <fieldset>
                    <legend className="mb-2 text-sm font-medium text-ink">Ícono</legend>
                    <div className="grid grid-cols-5 gap-2 sm:grid-cols-9">
                        {Object.entries(CATEGORY_ICONS).map(([name, Icon]) => (
                            <label
                                key={name}
                                title={name}
                                className={`flex aspect-square min-h-touch cursor-pointer items-center justify-center rounded-xl border transition focus-within:ring-2 focus-within:ring-primary/40 ${
                                    data.icon === name ? 'border-primary bg-primary text-white' : 'border-line text-ink-muted hover:bg-surface-sunken'
                                }`}
                            >
                                <input
                                    type="radio"
                                    name="icon"
                                    value={name}
                                    checked={data.icon === name}
                                    onChange={() => setData('icon', name)}
                                    className="sr-only"
                                    aria-label={`Ícono ${name}`}
                                />
                                <Icon className="h-5 w-5" aria-hidden="true" />
                            </label>
                        ))}
                    </div>
                    {errors.icon && <p className="mt-1.5 text-sm text-red-600 dark:text-red-400">{errors.icon}</p>}
                </fieldset>

                <div className="grid gap-5 sm:grid-cols-2">
                    <ToggleField label="Categoría activa" checked={data.is_active} onChange={(value) => setData('is_active', value)} />
                    <TextField label="Orden" type="number" inputMode="numeric" min="0" {...field('sort_order')} />
                </div>
            </form>
        </Modal>
    );
}

export default function Index({ categories }) {
    const [editing, setEditing] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [processing, setProcessing] = useState(false);

    const confirmDelete = () => {
        router.delete(`/admin/categorias/${deleting.id}`, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setDeleting(null);
            },
        });
    };

    return (
        <AdminLayout
            title="Categorías"
            actions={
                <Button onClick={() => setEditing('new')}>
                    <Plus className="h-4 w-4" aria-hidden="true" />
                    Nueva categoría
                </Button>
            }
        >
            <Card title="Categorías del catálogo" description="Agrupan tus servicios en el sitio público y en el cotizador.">
                {categories.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 py-10 text-center text-ink-muted">
                        <Layers className="h-10 w-10" aria-hidden="true" />
                        <p>Aún no tienes categorías.</p>
                        <Button onClick={() => setEditing('new')}>Crear la primera categoría</Button>
                    </div>
                ) : (
                    <ul className="grid gap-3 md:grid-cols-2">
                        {categories.map((category) => {
                            const Icon = categoryIcon(category.icon);
                            return (
                                <li key={category.id} className="flex items-start gap-4 rounded-xl border border-line p-4">
                                    <span className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                        <Icon className="h-5 w-5" aria-hidden="true" />
                                    </span>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold text-ink">{category.name}</p>
                                            {!category.is_active && <Badge tone="danger">Inactiva</Badge>}
                                        </div>
                                        {category.description && <p className="mt-0.5 text-sm text-ink-muted">{category.description}</p>}
                                        <Link
                                            href={`/admin/servicios?category=${category.id}`}
                                            className="mt-2 inline-flex min-h-touch items-center text-sm font-medium text-primary hover:underline"
                                        >
                                            {category.services_count} servicio(s)
                                        </Link>
                                    </div>
                                    <div className="flex shrink-0 flex-col gap-1 sm:flex-row">
                                        <Button variant="ghost" size="sm" onClick={() => setEditing(category)} aria-label={`Editar ${category.name}`}>
                                            <Pencil className="h-4 w-4" aria-hidden="true" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setDeleting(category)}
                                            aria-label={`Eliminar ${category.name}`}
                                            className="text-red-600 dark:text-red-400"
                                        >
                                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                                        </Button>
                                    </div>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </Card>

            {editing && <CategoryForm category={editing === 'new' ? null : editing} onClose={() => setEditing(null)} />}

            <ConfirmDialog
                open={!!deleting}
                title="Eliminar categoría"
                message={
                    deleting?.services_count > 0
                        ? `${deleting.name} tiene ${deleting.services_count} servicio(s), así que no se puede eliminar. Muévelos a otra categoría o desactívala.`
                        : `¿Seguro que quieres eliminar ${deleting?.name}? Esta acción no se puede deshacer.`
                }
                processing={processing}
                onConfirm={confirmDelete}
                onCancel={() => setDeleting(null)}
            />
        </AdminLayout>
    );
}
