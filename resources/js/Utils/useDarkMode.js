import { ref, computed, onMounted, watch } from 'vue'

/**
 * Composable para manejar el tema oscuro de la aplicación
 * @param {Object} empresaConfig - Configuración de la empresa desde props
 * @returns {Object} - Objeto con estado y funciones del tema oscuro
 */
export function useDarkMode(empresaConfig = null) {
    // Estado reactivo del tema oscuro
    const isDarkMode = ref(false)
    const isSystemPreference = ref(true)

    // Colores del tema claro (por defecto)
    const lightTheme = {
        primary: empresaConfig?.color_principal || '#3B82F6',
        secondary: empresaConfig?.color_secundario || '#1E40AF',
        background: '#FFFFFF',
        surface: '#F9FAFB',
        text: '#111827',
        textSecondary: '#6B7280',
        border: '#E5E7EB',
        hover: '#F3F4F6',
    }

    // Colores del tema oscuro
    const darkTheme = {
        primary: empresaConfig?.dark_mode_primary_color || '#1E40AF',
        secondary: empresaConfig?.dark_mode_secondary_color || '#3B82F6',
        background: empresaConfig?.dark_mode_background_color || '#0F172A',
        surface: empresaConfig?.dark_mode_surface_color || '#1E293B',
        text: '#F9FAFB',
        textSecondary: '#D1D5DB',
        border: '#374151',
        hover: '#374151',
    }

    // Tema actual basado en el modo
    const currentTheme = computed(() => {
        return isDarkMode.value ? darkTheme : lightTheme
    })

    /**
     * Aplicar colores CSS personalizados al documento
     */
    const applyThemeColors = () => {
        const root = document.documentElement

        if (isDarkMode.value) {
            // Aplicar colores del tema oscuro
            root.style.setProperty('--empresa-color-primary', darkTheme.primary)
            root.style.setProperty('--empresa-color-secondary', darkTheme.secondary)
            root.style.setProperty('--empresa-bg-primary', darkTheme.background)
            root.style.setProperty('--empresa-bg-surface', darkTheme.surface)
            root.style.setProperty('--empresa-text-primary', darkTheme.text)
            root.style.setProperty('--empresa-text-secondary', darkTheme.textSecondary)
            root.style.setProperty('--empresa-border-color', darkTheme.border)
            root.style.setProperty('--empresa-hover-color', darkTheme.hover)

            // Agregar clases de tema oscuro al body
            document.body.classList.add('dark')
            document.body.classList.remove('light')
        } else {
            // Aplicar colores del tema claro
            root.style.setProperty('--empresa-color-primary', lightTheme.primary)
            root.style.setProperty('--empresa-color-secondary', lightTheme.secondary)
            root.style.setProperty('--empresa-bg-primary', lightTheme.background)
            root.style.setProperty('--empresa-bg-surface', lightTheme.surface)
            root.style.setProperty('--empresa-text-primary', lightTheme.text)
            root.style.setProperty('--empresa-text-secondary', lightTheme.textSecondary)
            root.style.setProperty('--empresa-border-color', lightTheme.border)
            root.style.setProperty('--empresa-hover-color', lightTheme.hover)

            // Agregar clases de tema claro al body
            document.body.classList.add('light')
            document.body.classList.remove('dark')
        }
    }

    /**
     * Cambiar al modo oscuro
     */
    const enableDarkMode = () => {
        isDarkMode.value = true
        isSystemPreference.value = false
        localStorage.setItem('darkMode', 'true')
        localStorage.setItem('darkModePreference', 'manual')
        applyThemeColors()
    }

    /**
     * Cambiar al modo claro
     */
    const enableLightMode = () => {
        isDarkMode.value = false
        isSystemPreference.value = false
        localStorage.setItem('darkMode', 'false')
        localStorage.setItem('darkModePreference', 'manual')
        applyThemeColors()
    }

    /**
     * Cambiar al modo automático (basado en preferencia del sistema)
     */
    const enableSystemMode = () => {
        isSystemPreference.value = true
        localStorage.setItem('darkModePreference', 'system')

        // Detectar preferencia del sistema
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        isDarkMode.value = prefersDark
        applyThemeColors()
    }

    /**
     * Alternar entre modo oscuro y claro
     */
    const toggleDarkMode = () => {
        if (isDarkMode.value) {
            enableLightMode()
        } else {
            enableDarkMode()
        }
    }

    /**
     * Inicializar el tema oscuro
     */
    const initializeDarkMode = () => {
        // Verificar si hay una preferencia guardada
        const savedPreference = localStorage.getItem('darkModePreference')
        const savedDarkMode = localStorage.getItem('darkMode')

        if (savedPreference === 'manual' && savedDarkMode === 'true') {
            enableDarkMode()
        } else if (savedPreference === 'manual' && savedDarkMode === 'false') {
            enableLightMode()
        } else {
            // Usar preferencia del sistema por defecto
            enableSystemMode()
        }

        // Escuchar cambios en la preferencia del sistema
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
        mediaQuery.addEventListener('change', (e) => {
            if (isSystemPreference.value) {
                isDarkMode.value = e.matches
                applyThemeColors()
            }
        })
    }

    /**
     * Actualizar colores del tema cuando cambia la configuración de empresa
     */
    const updateThemeColors = (newEmpresaConfig) => {
        if (newEmpresaConfig) {
            // Actualizar colores del tema claro
            lightTheme.primary = newEmpresaConfig.color_principal || '#3B82F6'
            lightTheme.secondary = newEmpresaConfig.color_secundario || '#1E40AF'

            // Actualizar colores del tema oscuro si están configurados
            if (newEmpresaConfig.dark_mode_enabled) {
                darkTheme.primary = newEmpresaConfig.dark_mode_primary_color || '#1E40AF'
                darkTheme.secondary = newEmpresaConfig.dark_mode_secondary_color || '#3B82F6'
                darkTheme.background = newEmpresaConfig.dark_mode_background_color || '#0F172A'
                darkTheme.surface = newEmpresaConfig.dark_mode_surface_color || '#1E293B'
            }

            // Aplicar los nuevos colores
            applyThemeColors()
        }
    }

    // Inicializar cuando se monte el componente
    onMounted(() => {
        initializeDarkMode()
    })

    // Observar cambios en la configuración de empresa
    if (empresaConfig) {
        watch(
            () => empresaConfig,
            (newConfig) => {
                updateThemeColors(newConfig)
            },
            { deep: true, immediate: true }
        )
    }

    return {
        // Estado
        isDarkMode: computed(() => isDarkMode.value),
        isSystemPreference: computed(() => isSystemPreference.value),
        currentTheme,

        // Funciones
        enableDarkMode,
        enableLightMode,
        enableSystemMode,
        toggleDarkMode,
        applyThemeColors,
        updateThemeColors,
    }
}