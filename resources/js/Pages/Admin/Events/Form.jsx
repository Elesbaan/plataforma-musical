import EventForm from '@/Components/Admin/EventForm';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Form({ event, services, initialDate }) {
    return (
        <AdminLayout title={event ? `Editar: ${event.title}` : 'Nuevo evento'}>
            <EventForm
                key={event?.id ?? 'new'}
                initial={event ?? { event_date: initialDate ?? '' }}
                services={services}
                submitUrl={event ? `/admin/agenda/${event.id}` : '/admin/agenda'}
                method={event ? 'put' : 'post'}
                excludeEventId={event?.id ?? null}
                submitLabel={event ? 'Guardar cambios' : 'Agendar evento'}
                cancelHref={event ? `/admin/agenda/${event.id}` : '/admin/agenda'}
            />
        </AdminLayout>
    );
}
