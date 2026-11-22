import { Link } from '@inertiajs/react';
import ServiceForm from '@/Components/Admin/ServiceForm';
import Card from '@/Components/Card';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Create({ categories }) {
    return (
        <AdminLayout title="Nuevo servicio">
            {categories.length === 0 ? (
                <Card title="Primero crea una categoría">
                    <p className="text-sm text-ink-muted">
                        Cada servicio pertenece a una categoría.{' '}
                        <Link href="/admin/categorias" className="font-medium text-primary underline underline-offset-2">
                            Crea una categoría
                        </Link>{' '}
                        y regresa aquí.
                    </p>
                </Card>
            ) : (
                <ServiceForm categories={categories} />
            )}
        </AdminLayout>
    );
}
