import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { CircleAlert, CircleCheck, X } from 'lucide-react';

/** Muestra los mensajes flash (`success` / `error`) compartidos por el backend. */
export default function FlashMessages() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(true);

    useEffect(() => setVisible(true), [flash?.success, flash?.error]);

    const message = flash?.error ?? flash?.success;
    if (!message || !visible) return null;

    const isError = !!flash?.error;
    const Icon = isError ? CircleAlert : CircleCheck;

    return (
        <div
            role={isError ? 'alert' : 'status'}
            className={`flex items-start gap-3 rounded-xl border px-4 py-3 text-sm ${
                isError
                    ? 'border-red-300 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100'
                    : 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100'
            }`}
        >
            <Icon className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
            <p className="flex-1">{message}</p>
            <button
                type="button"
                onClick={() => setVisible(false)}
                className="-m-2 inline-flex min-h-touch min-w-touch items-center justify-center rounded-lg"
                aria-label="Cerrar mensaje"
            >
                <X className="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    );
}
