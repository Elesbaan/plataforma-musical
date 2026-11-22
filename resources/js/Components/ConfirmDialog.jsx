import Button from '@/Components/Button';
import Modal from '@/Components/Modal';

/** Confirmación para acciones destructivas o irreversibles. */
export default function ConfirmDialog({
    open,
    title,
    message,
    confirmLabel = 'Eliminar',
    processing = false,
    onConfirm,
    onCancel,
}) {
    return (
        <Modal
            open={open}
            onClose={onCancel}
            title={title}
            size="sm"
            footer={
                <>
                    <Button variant="outline" onClick={onCancel} data-autofocus>
                        Cancelar
                    </Button>
                    <Button variant="danger" onClick={onConfirm} disabled={processing}>
                        {processing ? 'Procesando…' : confirmLabel}
                    </Button>
                </>
            }
        >
            <p className="text-sm text-ink-muted">{message}</p>
        </Modal>
    );
}
