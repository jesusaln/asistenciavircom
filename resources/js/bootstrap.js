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

        // Solo manejar errores 419 (CSRF token mismatch)
        if (response?.status === 419 && !config.__retried) {
            if (isRefreshingCsrf) {
                // Si ya estamos refrescando, agregar a la cola
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
                // Intentar refrescar el token CSRF
                await axios.get('/sanctum/csrf-cookie', { __retried: true });
                processQueue();
                return axios(config);
            } catch (refreshError) {
                processQueue(refreshError);
                console.warn('No se pudo refrescar el token CSRF:', refreshError);
                // El usuario deberá recargar la página
                return Promise.reject(error);
            } finally {
                isRefreshingCsrf = false;
            }
        }

        return Promise.reject(error);
    }
);

// Importar configuración de notificaciones
import './Utils/notyf';
