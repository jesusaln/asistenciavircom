/**
 * Composables para manejo de borrador/draft de formularios
 * Resuelve Error #76: Pérdida de Estado en Refresco
 * 
 * Uso:
 * import { useDraft } from '@/Composables/useDraft'
 * const { draft, saveDraft, clearDraft } = useDraft('venta-nueva', { ttl: 30 })
 */

import { ref, watch, onMounted, onUnmounted } from 'vue'

/**
 * Crear sistema de borrador para un formulario
 * @param {string} key - Clave única para identificar el formulario
 * @param {Object} options - Opciones adicionales
 */
export function useDraft(key, options = {}) {
    const {
        ttl = 30, // Tiempo de vida en minutos
        autoSave = true, // Guardar automáticamente
        autoSaveInterval = 10000, // Intervalo de auto-guardado en ms
    } = options

    const draftData = ref(null)
    const lastSaved = ref(null)
    const isDirty = ref(false)
    const timer = ref(null)

    // Clave completa para localStorage
    const storageKey = `draft_${key}`

    /**
     * Guardar datos en localStorage
     */
    const saveToStorage = (data) => {
        try {
            const payload = {
                data,
                savedAt: Date.now(),
                ttl: ttl * 60 * 1000, // Convertir a milisegundos
            }
            localStorage.setItem(storageKey, JSON.stringify(payload))
            lastSaved.value = new Date()
            isDirty.value = false
            return true
        } catch (error) {
            console.warn('[Draft] Error al guardar:', error)
            return false
        }
    }

    /**
     * Cargar datos desde localStorage
     */
    const loadFromStorage = () => {
        try {
            const stored = localStorage.getItem(storageKey)
            if (!stored) return null

            const payload = JSON.parse(stored)

            // Verificar si ha expirado
            const age = Date.now() - payload.savedAt
            if (age > payload.ttl) {
                localStorage.removeItem(storageKey)
                return null
            }

            lastSaved.value = new Date(payload.savedAt)
            return payload.data
        } catch (error) {
            console.warn('[Draft] Error al cargar:', error)
            return null
        }
    }

    /**
     * Guardar borrador manualmente
     */
    const saveDraft = (data) => {
        return saveToStorage(data)
    }

    /**
     * Borrar borrador
     */
    const clearDraft = () => {
        try {
            localStorage.removeItem(storageKey)
            draftData.value = null
            lastSaved.value = null
            isDirty.value = false
            return true
        } catch (error) {
            console.warn('[Draft] Error al清除:', error)
            return false
        }
    }

    /**
     * Cargar borrador existente
     */
    const loadDraft = () => {
        const data = loadFromStorage()
        if (data) {
            draftData.value = data
            return data
        }
        return null
    }

    /**
     * Iniciar auto-guardado
     */
    const startAutoSave = (getDataFn) => {
        if (!autoSave || timer.value) return

        timer.value = setInterval(() => {
            if (isDirty.value && getDataFn) {
                saveDraft(getDataFn())
            }
        }, autoSaveInterval)
    }

    /**
     * Detener auto-guardado
     */
    const stopAutoSave = () => {
        if (timer.value) {
            clearInterval(timer.value)
            timer.value = null
        }
    }

    /**
     * Watcher para detectar cambios
     */
    const watchForChanges = (dataRef, getDataFn) => {
        watch(dataRef, () => {
            isDirty.value = true
        }, { deep: true })
    }

    // Lifecycle hooks
    onMounted(() => {
        // Cargar borrador al montar
        loadDraft()
    })

    onUnmounted(() => {
        // Limpiar timer al desmontar
        stopAutoSave()
    })

    return {
        draft: draftData,
        lastSaved,
        isDirty,
        saveDraft,
        clearDraft,
        loadDraft,
        startAutoSave,
        stopAutoSave,
        watchForChanges,
        // Expone el estado para uso directo
        storageKey,
    }
}

/**
 * Hook simple para guardar estado en localStorage
 * Útil para datos simples que no requieren ttl
 */
export function usePersistentState(key, defaultValue = null) {
    const state = ref(defaultValue)
    const storageKey = `persistent_${key}`

    // Cargar al iniciar
    onMounted(() => {
        try {
            const stored = localStorage.getItem(storageKey)
            if (stored) {
                state.value = JSON.parse(stored)
            }
        } catch (error) {
            console.warn('[Persistent] Error al cargar:', error)
        }
    })

    // Guardar cuando cambia
    watch(state, (newValue) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(newValue))
        } catch (error) {
            console.warn('[Persistent] Error al guardar:', error)
        }
    }, { deep: true })

    const clear = () => {
        try {
            localStorage.removeItem(storageKey)
            state.value = defaultValue
        } catch (error) {
            console.warn('[Persistent] Error al清除:', error)
        }
    }

    return {
        state,
        clear,
    }
}
