/**
 * Interceptor global de Axios para manejo centralizado de errores
 * Resuelve Error #7: Falta de Manejo de Errores Global
 */
import { useNotification } from '@/Composables/useNotification';
import { translateErrorMessage } from './errorHelper';

// Variables para control de errores repetidos
let isAlertOpen = false;
let lastErrorMessage = '';
let lastErrorTime = 0;

/**
 * Traduce y formatea el mensaje de error de Axios
 */
const getReadableError = (error) => {
    if (!error.response) {
        return translateErrorMessage(error.message) || 'Error de conexión. Verifica tu internet.';
    }

    const { status, data } = error.response;

    // Si el backend envía un mensaje específico, usarlo
    if (data.message && typeof data.message === 'string') {
        return translateErrorMessage(data.message);
    }

    if (data.error && typeof data.error === 'string') {
        return translateErrorMessage(data.error);
    }

    // Errores de validación (422)
    if (status === 422 && data.errors) {
        const firstField = Object.keys(data.errors)[0];
        const firstError = data.errors[firstField][0];
        return Array.isArray(firstError) ? firstError[0] : firstError;
    }

    // Mensajes por código de estado
    switch (status) {
        case 400: return 'Solicitud incorrecta. Intenta nuevamente.';
        case 401: return 'Sesión expirada. Por favor inicia sesión.';
        case 403: return 'No tienes permisos para realizar esta acción.';
        case 404: return 'El recurso solicitado no existe.';
        case 419: return 'La página ha expirado. Por favor recarga.';
        case 429: return 'Demasiadas solicitudes. Espera un momento.';
        case 500: return 'Error interno del servidor. Contacta a soporte.';
        case 503: return 'Servicio no disponible temporalmente.';
        default: return `Error ${status}: Ocurrió un problema inesperado.`;
    }
};

/**
 * Configura el interceptor en la instancia de Axios proporcionada
 * @param {import('axios').AxiosInstance} axiosInstance 
 */
export const setupGlobalErrorInterceptor = (axiosInstance) => {
    const notification = useNotification();

    axiosInstance.interceptors.response.use(
        (response) => response,
        (error) => {
            const { config, response } = error;

            // Ignorar errores si la solicitud explícitamente pide no manejarlos globalmente
            // (e.g. validaciones de campo específicas que el componente quiere manejar)
            if (config?.skipGlobalErrorHandler) {
                return Promise.reject(error);
            }

            // Ignorar errores 419 (CSRF) ya manejados por bootstrap.js
            if (response?.status === 419) {
                return Promise.reject(error);
            }

            const message = getReadableError(error);
            const now = Date.now();

            // Evitar spam de notificaciones idénticas (debounce de 2s)
            if (message === lastErrorMessage && (now - lastErrorTime) < 2000) {
                return Promise.reject(error);
            }

            lastErrorMessage = message;
            lastErrorTime = now;

            // Mostrar notificación según tipo de error
            if (!response || response.status >= 500) {
                notification.error(message);
            } else if (response.status === 403 || response.status === 401) {
                notification.warning(message);
            } else if (response.status === 422) {
                // Validación: Info o Warning warn
                notification.warning(message);
            } else {
                notification.error(message);
            }

            // Manejo especial para 401 (Redirección si es necesario, aunque Inertia suele manejarlo)
            if (response?.status === 401) {
                // Opcional: window.location.href = '/login';
            }

            return Promise.reject(error);
        }
    );
};
