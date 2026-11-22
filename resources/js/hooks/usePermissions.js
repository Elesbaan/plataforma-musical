import { usePage } from '@inertiajs/react';

/**
 * Permisos efectivos del usuario (compartidos por HandleInertiaRequests).
 * Solo sirve para mostrar u ocultar opciones: la protección real está en el backend.
 *
 * @returns {(required?: string|string[]) => boolean} `can('x')` o `can(['x', 'y'])` (cualquiera).
 */
export default function usePermissions() {
    const permissions = usePage().props.auth?.permissions ?? [];

    return (required) => {
        if (!required) return true;
        const keys = Array.isArray(required) ? required : [required];
        return keys.some((key) => permissions.includes(key));
    };
}
