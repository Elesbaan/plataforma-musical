import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import ServiceForm from '@/Components/Admin/ServiceForm';
import Button from '@/Components/Button';
import ConfirmDialog from '@/Components/ConfirmDialog';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Edit({ service, categories }) {
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);

    const destroy = () => {
        router.delete(`/admin/servicios/${service.id}`, {
            onStart: () => setProcessing(true),
            onFinish: () => {
                setProcessing(false);
                setConfirming(false);
            },
        });
    };

    return (
        <AdminLayout
            title={service.name}
            actions={
                <Button variant="ghost" onClick={() => setConfirming(true)} className="text-red-600 dark:text-red-400">
                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                    Eliminar servicio
                </Button>
            }
        >
            <ServiceForm key={service.id} service={service} categories={categories} />

            <ConfirmDialog
                open={confirming}
                title="Eliminar servicio"
                message={`¿Seguro que quieres eliminar ${service.name}? También se borrarán sus fotos y videos. Si solo quieres ocultarlo, desactívalo.`}
                processing={processing}
                onConfirm={destroy}
                onCancel={() => setConfirming(false)}
            />
        </AdminLayout>
    );
}
