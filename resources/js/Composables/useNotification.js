import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

/**
 * Composable unificado para notificaciones
 * Resuelve Error #74: Inconsistencia de Notificaciones
 * 
 * Uso:
 * import { useNotification } from '@/Composables/useNotification'
 * const { success, error, warning, info } = useNotification()
 */

// Instancia singleton de Notyf para no crear múltiples instancias en el DOM
let notyfInstance = null;

const getNotyf = () => {
    if (!notyfInstance) {
        notyfInstance = new Notyf({
            duration: 4000,
            position: { x: 'right', y: 'top' },
            dismissible: true,
            types: [
                {
                    type: 'success',
                    background: '#10B981',
                    icon: {
                        className: 'notyf__icon--success',
                        tagName: 'i',
                        text: '' // Icono por defecto de notyf es SVG, lo dejamos
                    }
                },
                {
                    type: 'warning',
                    background: '#F59E0B',
                    icon: {
                        className: 'material-icons',
                        tagName: 'i',
                        text: 'warning'
                    }
                },
                {
                    type: 'error',
                    background: '#EF4444',
                    duration: 5000,
                    dismissible: true
                },
                {
                    type: 'info',
                    background: '#3B82F6',
                    icon: {
                        className: 'material-icons',
                        tagName: 'i',
                        text: 'info'
                    }
                }
            ]
        });
    }
    return notyfInstance;
};

export function useNotification() {
    const notify = getNotyf();

    return {
        /**
         * Muestra mensaje de éxito (Verde)
         */
        success: (message) => notify.success(message),

        /**
         * Muestra mensaje de error (Rojo)
         */
        error: (message) => notify.error(message),

        /**
         * Muestra mensaje de advertencia (Amarillo/Naranja)
         */
        warning: (message) => notify.open({ type: 'warning', message }),

        /**
         * Muestra mensaje de información (Azul)
         */
        info: (message) => notify.open({ type: 'info', message }),

        /**
         * Limpia todas las notificaciones
         */
        dismissAll: () => notify.dismissAll()
    };
}
