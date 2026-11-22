import { useEffect, useState } from 'react';

/**
 * Desglose calculado en el servidor (POST /api/cotizador/calcular). El frontend
 * no replica fórmulas: QuoteCalculatorService es la única fuente de verdad.
 *
 * @param {Array<{service_id: number, quantity: number, extra_hours: number}>} items
 * @returns {{ breakdown: object|null, loading: boolean, error: string|null }}
 */
export default function useQuotePreview(items, zoneId, date) {
    const [state, setState] = useState({ breakdown: null, loading: false, error: null });
    const key = JSON.stringify({ items, zoneId, date });

    useEffect(() => {
        if (items.length === 0) {
            setState({ breakdown: null, loading: false, error: null });
            return undefined;
        }

        const controller = new AbortController();
        setState((prev) => ({ ...prev, loading: true }));

        const timer = setTimeout(() => {
            window.axios
                .post(
                    '/api/cotizador/calcular',
                    { items, coverage_zone_id: zoneId || null, event_date: date || null },
                    { signal: controller.signal, headers: { Accept: 'application/json' } },
                )
                .then(({ data }) => setState({ breakdown: data, loading: false, error: null }))
                .catch((error) => {
                    if (controller.signal.aborted) return;
                    const errors = error?.response?.data?.errors;
                    const message = errors ? Object.values(errors).flat()[0] : 'No pudimos actualizar el total. Inténtalo de nuevo.';
                    setState((prev) => ({ ...prev, loading: false, error: message }));
                });
        }, 300);

        return () => {
            clearTimeout(timer);
            controller.abort();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key]);

    return state;
}
