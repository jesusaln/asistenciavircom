import { onUnmounted, getCurrentInstance } from 'vue';

/**
 * useCleanup - Composables para gestión automática decleanup de variables reactivas
 *
 * Previene memory leaks limpiando variables globales cuando el componente se desmonta.
 *
 * @example
 * const { register, cleanup } = useCleanup();
 *
 * // Registrar una variable para cleanup automático
 * const globalSearchTerm = ref('');
 * register(globalSearchTerm, (value) => {
 *   console.log('Limpiando:', value);
 * });
 *
 * // O simplemente resetear al valor inicial
 * register(globalSearchTerm, 'default');
 */
export function useCleanup(options = {}) {
    const {
        autoCleanup = true,
        defaultResetValue = null,
    } = options;

    // Almacén deCleanup callbacks
    const cleanupRegistry = new Map();

    /**
     * Registrar una variable para cleanup automático
     *
     * @param {Ref|object} target - Variable reactiva a limpiar
     * @param {Function|any} cleanupAction - Función de cleanup o valor de reset
     */
    function register(target, cleanupAction) {
        if (!target || typeof target !== 'object') {
            console.warn('[useCleanup] Target debe ser un objeto reactivo (ref, reactive)');
            return;
        }

        // Generar ID único para esta entrada
        const id = Symbol('cleanup-entry');

        // Guardar el cleanup action
        cleanupRegistry.set(id, {
            target,
            action: cleanupAction,
            registeredAt: Date.now(),
        });

        return id;
    }

    /**
     * Registrar múltiples variables a la vez
     *
     * @param {Array} items - Array de objetos { target, action }
     */
    function registerMany(items) {
        return items.map(item => register(item.target, item.action));
    }

    /**
     * Ejecutar cleanup para un ID específico
     *
     * @param {Symbol} id - ID devuelto por register()
     */
    function cleanup(id) {
        if (!id || !cleanupRegistry.has(id)) {
            return false;
        }

        const entry = cleanupRegistry.get(id);
        const { target, action } = entry;

        try {
            if (typeof action === 'function') {
                action(target.value);
            } else {
                // Resetear al valor por defecto o al valor especificado
                if (target.value !== undefined) {
                    target.value = action;
                }
            }
            cleanupRegistry.delete(id);
            return true;
        } catch (error) {
            console.error('[useCleanup] Error durante cleanup:', error);
            return false;
        }
    }

    /**
     * Ejecutar cleanup de todas las variables registradas
     */
    function cleanupAll() {
        const results = [];
        const ids = Array.from(cleanupRegistry.keys());

        for (const id of ids) {
            results.push(cleanup(id));
        }

        return results;
    }

    /**
     * Limpiar solo variables específicas
     *
     * @param {Array} targets - Array de variables a limpiar
     * @param {any} resetValue - Valor de reset (opcional)
     */
    function cleanupSpecific(targets, resetValue = defaultResetValue) {
        const ids = [];

        for (const [id, entry] of cleanupRegistry) {
            if (targets.includes(entry.target)) {
                ids.push(id);
            }
        }

        return ids.map(id => cleanup(id));
    }

    /**
     * Obtener el estado actual del registry (para debugging)
     */
    function getRegistryStatus() {
        return {
            size: cleanupRegistry.size,
            entries: Array.from(cleanupRegistry.entries()).map(([id, entry]) => ({
                id: id.toString().slice(7, -1),
                registeredAt: new Date(entry.registeredAt).toISOString(),
                hasAction: typeof entry.action === 'function',
            })),
        };
    }

    /**
     * Verificar si una variable está registrada
     */
    function isRegistered(target) {
        for (const entry of cleanupRegistry.values()) {
            if (entry.target === target) {
                return true;
            }
        }
        return false;
    }

    /**
     * Remover una variable del registry sin ejecutar cleanup
     */
    function unregister(target) {
        for (const [id, entry] of cleanupRegistry) {
            if (entry.target === target) {
                cleanupRegistry.delete(id);
                return true;
            }
        }
        return false;
    }

    // Si autoCleanup está activo, registrar cleanupAll en onUnmounted
    if (autoCleanup && getCurrentInstance()) {
        onUnmounted(() => {
            cleanupAll();
        });
    }

    return {
        register,
        registerMany,
        cleanup,
        cleanupAll,
        cleanupSpecific,
        unregister,
        isRegistered,
        getRegistryStatus,
    };
}

/**
 * useDebouncedCleanup - Variante con debounce para evitar limpiezas frecuentes
 *
 * @param {number} delay - Delay en ms antes de ejecutar cleanup
 */
export function useDebouncedCleanup(delay = 300) {
    const cleanup = useCleanup({ autoCleanup: false });
    let timeoutId = null;

    function delayedCleanup(targets, resetValue = null) {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }

        timeoutId = setTimeout(() => {
            cleanup.cleanupSpecific(targets, resetValue);
            timeoutId = null;
        }, delay);
    }

    function cancelPending() {
        if (timeoutId) {
            clearTimeout(timeoutId);
            timeoutId = null;
        }
    }

    return {
        ...cleanup,
        delayedCleanup,
        cancelPending,
    };
}

/**
 * useGlobalEventCleanup - Cleanup para event listeners globales
 *
 * Útil para limpiar window/document event listeners
 */
export function useGlobalEventCleanup() {
    const cleanup = useCleanup({ autoCleanup: false });
    const eventListeners = new Map();

    /**
     * Agregar un event listener global y registrar para cleanup
     */
    function addGlobalEventListener(element, event, handler, options = {}) {
        element.addEventListener(event, handler, options);

        const id = register({
            type: 'event',
            element,
            event,
            handler,
            options,
        });

        eventListeners.set(id, { element, event, handler, options });

        return id;
    }

    /**
     * Cleanup de un event listener específico
     */
    function removeGlobalEventListener(id) {
        if (!eventListeners.has(id)) {
            return false;
        }

        const { element, event, handler, options } = eventListeners.get(id);
        element.removeEventListener(event, handler, options);
        eventListeners.delete(id);

        return cleanup.cleanup(id);
    }

    /**
     * Limpiar todos los event listeners
     */
    function cleanupAllEventListeners() {
        for (const [id, data] of eventListeners) {
            const { element, event, handler, options } = data;
            element.removeEventListener(event, handler, options);
        }
        eventListeners.clear();
    }

    return {
        ...cleanup,
        addGlobalEventListener,
        removeGlobalEventListener,
        cleanupAllEventListeners,
    };
}
