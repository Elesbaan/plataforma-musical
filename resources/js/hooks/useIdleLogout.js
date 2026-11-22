import { useCallback, useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';

const STORAGE_KEY = 'admin_last_server_contact';
const PING_INTERVAL_MS = 60 * 1000;
const ACTIVITY_EVENTS = ['pointerdown', 'keydown', 'scroll', 'touchstart', 'mousemove'];

function readShared() {
    try {
        return Number(window.localStorage.getItem(STORAGE_KEY)) || 0;
    } catch {
        return 0;
    }
}

function writeShared(value) {
    try {
        window.localStorage.setItem(STORAGE_KEY, String(value));
    } catch {
        // Sin almacenamiento: el temporizador funciona solo en esta pestaña.
    }
}

/**
 * Cierra la sesión del panel tras `lifetimeMinutes` sin contacto con el servidor.
 * El tiempo se cuenta desde la última petición (navegación de Inertia o keep-alive),
 * que es lo mismo que mide la sesión de Laravel. La actividad del usuario envía un
 * keep-alive como máximo una vez por minuto. Se sincroniza entre pestañas.
 *
 * @returns {{ secondsLeft: number|null, stayConnected: () => void, logoutNow: () => void }}
 *   secondsLeft es distinto de null durante la ventana de aviso.
 */
export default function useIdleLogout(lifetimeMinutes, warningSeconds = 60) {
    const lastContact = useRef(Date.now());
    const pinging = useRef(false);
    const loggingOut = useRef(false);
    const [secondsLeft, setSecondsLeft] = useState(null);
    const warningRef = useRef(false);

    const markContact = useCallback(() => {
        const now = Date.now();
        lastContact.current = now;
        writeShared(now);
    }, []);

    const ping = useCallback(async () => {
        if (pinging.current) return;
        pinging.current = true;
        try {
            await window.axios.post('/admin/session/keep-alive');
            markContact();
        } catch {
            // Si la sesión ya expiró, el siguiente ciclo del temporizador cerrará la sesión.
        } finally {
            pinging.current = false;
        }
    }, [markContact]);

    const logoutNow = useCallback((reason) => {
        if (loggingOut.current) return;
        loggingOut.current = true;
        router.post('/admin/logout', reason ? { reason } : {});
    }, []);

    const stayConnected = useCallback(() => {
        warningRef.current = false;
        setSecondsLeft(null);
        ping();
    }, [ping]);

    useEffect(() => {
        if (!lifetimeMinutes) return undefined;

        markContact();
        const lifetimeMs = lifetimeMinutes * 60 * 1000;

        const onActivity = () => {
            if (warningRef.current) return; // durante el aviso se requiere confirmación explícita
            const last = Math.max(lastContact.current, readShared());
            if (Date.now() - last > PING_INTERVAL_MS) ping();
        };

        const removeSuccess = router.on('success', markContact);
        ACTIVITY_EVENTS.forEach((event) => window.addEventListener(event, onActivity, { passive: true }));

        const timer = window.setInterval(() => {
            const last = Math.max(lastContact.current, readShared());
            lastContact.current = last;
            const remainingMs = lifetimeMs - (Date.now() - last);

            if (remainingMs <= 0) {
                window.clearInterval(timer);
                logoutNow('inactivity');
            } else if (remainingMs <= warningSeconds * 1000) {
                warningRef.current = true;
                setSecondsLeft(Math.ceil(remainingMs / 1000));
            } else if (warningRef.current) {
                // Otra pestaña renovó la sesión.
                warningRef.current = false;
                setSecondsLeft(null);
            }
        }, 1000);

        return () => {
            removeSuccess();
            ACTIVITY_EVENTS.forEach((event) => window.removeEventListener(event, onActivity));
            window.clearInterval(timer);
        };
    }, [lifetimeMinutes, warningSeconds, markContact, ping, logoutNow]);

    return { secondsLeft, stayConnected, logoutNow: () => logoutNow() };
}
