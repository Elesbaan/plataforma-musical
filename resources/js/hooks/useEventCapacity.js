import { useEffect, useState } from 'react';

/**
 * Unidades ya reservadas por servicio en una fecha (sin contar el evento que se
 * edita), para alertar en vivo cuando un servicio alcanza o supera su capacidad.
 *
 * @returns {{ reserved: Record<number, number>|null, loading: boolean }}
 */
export default function useEventCapacity(date, excludeEventId = null) {
    const [state, setState] = useState({ reserved: null, loading: false });

    useEffect(() => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(date ?? '')) {
            setState({ reserved: null, loading: false });
            return undefined;
        }
        const controller = new AbortController();
        setState((prev) => ({ ...prev, loading: true }));

        window.axios
            .get('/admin/agenda/capacidad', { params: { date, exclude: excludeEventId || undefined }, signal: controller.signal })
            .then(({ data }) => setState({ reserved: data.reserved ?? {}, loading: false }))
            .catch(() => !controller.signal.aborted && setState({ reserved: null, loading: false }));

        return () => controller.abort();
    }, [date, excludeEventId]);

    return state;
}
