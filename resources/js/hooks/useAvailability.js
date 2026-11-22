import { useEffect, useState } from 'react';

/**
 * Disponibilidad real por fecha desde GET /api/availability (AvailabilityService).
 *
 * @returns {{ byService: Record<number, {remaining: number, available: boolean}>|null, loading: boolean, error: string|null }}
 */
export default function useAvailability(date) {
    const [state, setState] = useState({ byService: null, loading: false, error: null });

    useEffect(() => {
        if (!date || !/^\d{4}-\d{2}-\d{2}$/.test(date)) {
            setState({ byService: null, loading: false, error: null });
            return undefined;
        }

        const controller = new AbortController();
        setState((prev) => ({ ...prev, loading: true, error: null }));

        window.axios
            .get('/api/availability', { params: { date }, signal: controller.signal })
            .then(({ data }) => {
                const byService = Object.fromEntries(data.services.map((s) => [s.id, { remaining: s.remaining, available: s.available }]));
                setState({ byService, loading: false, error: null });
            })
            .catch((error) => {
                if (controller.signal.aborted) return;
                const message = error?.response?.data?.errors?.date?.[0] ?? 'No pudimos consultar la disponibilidad. Revisa tu conexión e inténtalo de nuevo.';
                setState({ byService: null, loading: false, error: message });
            });

        return () => controller.abort();
    }, [date]);

    return state;
}
