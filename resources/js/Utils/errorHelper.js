/**
 * Helper para manejo consistente de errores
 * Traduce mensajes de error técnicos a español legible
 */

/**
 * Traduce mensajes de error técnicos a español
 * @param {string} message - Mensaje de error original
 * @returns {string} - Mensaje traducido
 */
export const translateErrorMessage = (message) => {
    if (!message) return 'Ha ocurrido un error';

    const translations = {
        // Errores de red
        'Error network': 'Error de conexión. Verifica tu internet.',
        'Failed to fetch': 'Error de conexión. No se pudo contactar al servidor.',
        'Network Error': 'Error de red. Verifica tu conexión a internet.',
        'Network request failed': 'Error en la solicitud de red.',

        // Errores HTTP comunes
        '404': 'El recurso solicitado no fue encontrado.',
        '401': 'No tienes autorización para realizar esta acción.',
        '403': 'Acceso prohibido.',
        '500': 'Error interno del servidor. Intenta más tarde.',
        '422': 'Los datos proporcionados no son válidos.',

        // Errores de validación
        'The given data was invalid': 'Los datos proporcionados no son válidos.',

        // Mensajes genéricos
        'Something went wrong': 'Algo salió mal. Intenta de nuevo.',
        'An error occurred': 'Ocurrió un error. Intenta de nuevo.',
    };

    // Buscar traducción exacta
    if (translations[message]) {
        return translations[message];
    }

    // Traducciones parciales
    if (message.includes('Failed to fetch')) {
        return 'Error de conexión. Verifica tu internet.';
    }
    if (message.includes('Network Error')) {
        return 'Error de red. Verifica tu conexión.';
    }
    if (message.includes('timeout')) {
        return 'La solicitud tardó demasiado. Intenta de nuevo.';
    }
    if (message.includes('CORS')) {
        return 'Error de configuración del servidor.';
    }

    return message;
};

/**
 * Extrae el mensaje de error de diferentes formatos de respuesta
 * @param {Object|Error|string} error - Error o respuesta de API
 * @param {string} defaultMessage - Mensaje por defecto
 * @returns {string} - Mensaje de error legible
 */
export const extractErrorMessage = (error, defaultMessage = 'Ha ocurrido un error') => {
    // Si es un string
    if (typeof error === 'string') {
        return translateErrorMessage(error);
    }

    // Si es un objeto Error de JavaScript
    if (error instanceof Error) {
        return translateErrorMessage(error.message);
    }

    // Si es una respuesta de axios
    if (error.response?.data?.message) {
        return translateErrorMessage(error.response.data.message);
    }
    if (error.response?.data?.error) {
        return translateErrorMessage(error.response.data.error);
    }
    if (error.response?.statusText) {
        return translateErrorMessage(error.response.statusText);
    }

    // Si tiene propiedad error
    if (error.error) {
        return translateErrorMessage(error.error);
    }

    // Si tiene propiedad message
    if (error.message) {
        return translateErrorMessage(error.message);
    }

    // Si es un objeto vacío
    if (typeof error === 'object' && Object.keys(error).length === 0) {
        return defaultMessage;
    }

    return defaultMessage;
};

/**
 * Manejador de errores para try/catch
 * @param {Error} error - Error captado
 * @param {string} fallbackMessage - Mensaje por defecto
 * @param {Object} options - Opciones adicionales
 * @returns {Object} - Objeto con mensaje y logging
 */
export const handleError = (error, fallbackMessage = 'Ha ocurrido un error', options = {}) => {
    const message = extractErrorMessage(error, fallbackMessage);

    // Loguear error en desarrollo
    if (import.meta.env.DEV || !import.meta.env.PROD) {
        console.error('[Error]:', error);
    }

    return {
        message,
        originalError: error,
        isHandled: true,
    };
};
