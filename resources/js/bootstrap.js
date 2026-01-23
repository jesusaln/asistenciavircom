import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurar axios para CSRF (XSRF cookie)
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Interceptor para manejar errores 419 y recuperación automática
let isRefreshingCsrf = false;
let failedQueue = [];

const processQueue = (error = null) => {
    failedQueue.forEach(prom => {
        if (error) {
            prom.reject(error);
        } else {
            prom.resolve();
        }
    });
    failedQueue = [];
};

window.axios.interceptors.response.use(
    response => response,
    async (error) => {
        const { response, config } = error || {};

        // Caso 1: Error 419 (CSRF Token Mismatch)
        if (response?.status === 419 && !config.__retried) {
            if (isRefreshingCsrf) {
                return new Promise((resolve, reject) => {
                    failedQueue.push({ resolve, reject });
                }).then(() => {
                    config.__retried = true;
                    return axios(config);
                }).catch(err => Promise.reject(err));
            }

            isRefreshingCsrf = true;
            config.__retried = true;

            try {
                await axios.get('/sanctum/csrf-cookie', { __retried: true });
                processQueue();
                return axios(config);
            } catch (refreshError) {
                processQueue(refreshError);
                console.warn('No se pudo refrescar el token CSRF:', refreshError);
                // Forzar recarga si no se puede recuperar la sesión
                window.location.reload();
                return Promise.reject(error);
            } finally {
                isRefreshingCsrf = false;
            }
        }

        // Caso 2: Error 403 (Forbidden / Unauthorized)
        if (response?.status === 403) {
            console.warn('Acceso denegado (403):', response?.data?.message || 'No autorizado');

            // Si tenemos notyf (Toast) disponible globalmente, usarlo
            if (window.$toast) {
                window.$toast.error('Acceso denegado: No tienes permisos para realizar esta acción.');
            } else {
                alert('Acceso denegado: No tienes permisos para realizar esta acción.');
            }
            // No recargamos página en 403, solo avisamos
            return Promise.reject(error);
        }

        // Caso 3: Error 401 (Unauthenticated) - Sesión expirada
        if (response?.status === 401) {
            console.warn('Sesión expirada (401). Redirigiendo al login...');
            window.location.href = '/login';
            return Promise.reject(error);
        }

        return Promise.reject(error);
    }
);

// Importar configuración de notificaciones
import './Utils/notyf';
