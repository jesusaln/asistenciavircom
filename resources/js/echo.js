import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const isDev = import.meta.env.DEV;
const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

/** Indica si el bundle se compiló con Reverb; útil para depuración sin ensuciar consola en prod. */
window.__cddEchoEnabled = Boolean(reverbKey);

if (reverbKey) {
    const pageHost = window.location.hostname;
    const isLocal = pageHost === 'localhost' || pageHost === '127.0.0.1' || pageHost.endsWith('.nip.io') || /^192\.168\./.test(pageHost);
    
    const reverbHost = isLocal ? pageHost : (import.meta.env.VITE_REVERB_HOST || pageHost);
    const reverbPort = isLocal ? 8080 : (import.meta.env.VITE_REVERB_PORT || 443);
    const protocol = window.location.protocol === 'https:' ? 'https' : (isLocal ? 'http' : (import.meta.env.VITE_REVERB_SCHEME || 'http'));

    if (isDev) {
        console.info('[Echo] Smart Detection:', { host: reverbHost, port: reverbPort, scheme: protocol, isLocal });
    }

    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: reverbPort,
            wssPort: protocol === 'https' ? 443 : reverbPort,
            forceTLS: protocol === 'https',
            enabledTransports: ['ws', 'wss'],
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
            },
        });

        // 🔔 Lógica Global de Notificaciones PWA
        if (window.Echo) {
            // Solicitar permiso de notificaciones si es la primera vez
            if (typeof window !== 'undefined' && 'Notification' in window) {
                if (Notification.permission === 'default') {
                    Notification.requestPermission();
                }

                // El listener de CitaCompletada + UserNotificationCreated está en
                // NotificationBell.vue (canal privado App.Models.User.{id}) y en
                // AppLayout.vue (canal público 'notificaciones'). NO duplicar aquí.
            }
        }

        if (isDev) {
            console.info('[Echo] Listo (WebSockets Echo + Reverb).');
        }
    } catch (e) {
        window.__cddEchoEnabled = false;
        if (isDev) {
            console.error('[Echo] No se pudo crear la instancia:', e);
        }
    }
} else {
    // Sin Reverb la app funciona igual; tiempo real (presencia, canales) queda desactivado.
    if (isDev) {
        console.info(
            '[Echo] Desactivado: añada VITE_REVERB_* en .env y vuelva a compilar (npm run build) para WebSockets.'
        );
    }
}
