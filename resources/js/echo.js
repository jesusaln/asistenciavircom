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

                // Escuchar canal de notificaciones públicas
                window.Echo.channel('notificaciones')
                    .listen('CitaCompletada', (e) => {
                        console.log('🔔 Cita Completada recibida:', e.cita);
                        
                        if (Notification.permission === 'granted') {
                            const title = `✅ Cita #${e.cita.id} Completada`;
                            const options = {
                                body: `Técnico: ${e.cita.tecnico?.name || 'N/A'}\nCliente: ${e.cita.cliente?.nombre_razon_social || 'N/A'}\nServicio: ${e.cita.tipo_servicio}`,
                                icon: '/images/icon-192x192.png',
                                badge: '/images/icon-192x192.png',
                                vibrate: [200, 100, 200],
                                tag: `cita-completada-${e.cita.id}`,
                                renotify: true,
                                data: {
                                    url: `/citas/${e.cita.id}` // Para que al hacer clic te lleve a la cita
                                }
                            };
                            
                            const notification = new Notification(title, options);
                            
                            // Abrir la cita al hacer clic en la notificación
                            notification.onclick = function(event) {
                                event.preventDefault();
                                window.open(this.data.url, '_blank');
                                notification.close();
                            };

                            // Sonido opcional (solo si el navegador permite autoplay)
                            try {
                                const soundPath = localStorage.getItem('cdd_notification_sound') || '/sounds/modern-chime.mp3';
                                const audio = new Audio(soundPath);
                                audio.play();
                            } catch (err) { /* Ignorar errores de autoplay */ }
                        }
                    });
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
