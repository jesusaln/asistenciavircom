import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

/**
 * Composable para obtener y usar los colores de la configuración de empresa
 * Proporciona CSS variables dinámicas y utilidades de estilo
 */
export function useCompanyColors() {
    const page = usePage()

    // Colores por defecto (amber/amber-dark para fallback)
    const colors = ref({
        principal: '#3B82F6',
        secundario: '#D97706'
    })

    const isLoaded = ref(false)

    // Detección de modo oscuro
    const isDarkMode = ref(false)

    const updateDarkMode = () => {
        isDarkMode.value = document.documentElement.classList.contains('dark')
    }

    // Cargar colores desde la API
    const loadColors = async () => {
        try {
            const response = await axios.get('/empresa/configuracion/api')
            if (response.data?.configuracion) {
                colors.value.principal = response.data.configuracion.color_principal || '#F59E0B'
                colors.value.secundario = response.data.configuracion.color_secundario || '#D97706'
            }
            isLoaded.value = true
        } catch (error) {
            console.warn('No se pudieron cargar los colores de empresa, usando valores por defecto')
            isLoaded.value = true
        }
    }

    // Función para generar variantes de color (más claro/oscuro)
    const hexToRgb = (hex) => {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null
    }

    // Función para oscurecer un color
    const darkenColor = (hex, percent = 40) => {
        const rgb = hexToRgb(hex)
        if (!rgb) return hex
        const darken = (value) => Math.max(0, Math.floor(value * (1 - percent / 100)))
        return `rgb(${darken(rgb.r)}, ${darken(rgb.g)}, ${darken(rgb.b)})`
    }

    // CSS Variables dinámicas para inyectar en componentes
    const cssVars = computed(() => {
        const rgb = hexToRgb(colors.value.principal)
        const rgbSecondary = hexToRgb(colors.value.secundario)

        return {
            '--color-primary': colors.value.principal,
            '--color-primary-light': `${colors.value.principal}20`,
            '--color-primary-medium': `${colors.value.principal}40`,
            '--color-primary-rgb': rgb ? `${rgb.r}, ${rgb.g}, ${rgb.b}` : '245, 158, 11',
            '--color-secondary': colors.value.secundario,
            '--color-secondary-light': `${colors.value.secundario}20`,
            '--color-secondary-rgb': rgbSecondary ? `${rgbSecondary.r}, ${rgbSecondary.g}, ${rgbSecondary.b}` : '217, 119, 6'
        }
    })

    // Clases de utilidad para botones primarios
    const primaryButtonStyle = computed(() => ({
        backgroundColor: colors.value.principal,
        color: '#ffffff',
        borderColor: colors.value.principal
    }))

    const primaryButtonHoverStyle = computed(() => ({
        backgroundColor: colors.value.secundario
    }))

    // Estilo para focus rings
    const focusRingStyle = computed(() => ({
        '--tw-ring-color': `${colors.value.principal}80`
    }))

    // Gradiente para headers - ahora con soporte dark mode
    const headerGradientStyle = computed(() => {
        if (isDarkMode.value) {
            // En dark mode, usar versiones más oscuras de los colores
            return {
                background: `linear-gradient(135deg, ${darkenColor(colors.value.principal, 50)} 0%, ${darkenColor(colors.value.secundario, 50)} 100%)`
            }
        }
        return {
            background: `linear-gradient(135deg, ${colors.value.principal} 0%, ${colors.value.secundario} 100%)`
        }
    })

    // Gradiente sutil para fondos - ahora con soporte dark mode
    const subtleGradientStyle = computed(() => {
        if (isDarkMode.value) {
            return {
                background: `linear-gradient(135deg, ${colors.value.principal}08 0%, ${colors.value.secundario}08 100%)`
            }
        }
        return {
            background: `linear-gradient(135deg, ${colors.value.principal}10 0%, ${colors.value.secundario}10 100%)`
        }
    })

    // Badge/Tag style
    const badgeStyle = computed(() => ({
        backgroundColor: `${colors.value.principal}15`,
        color: colors.value.principal,
        borderColor: `${colors.value.principal}30`
    }))

    // Observador del modo oscuro
    let observer = null

    onMounted(() => {
        loadColors()
        updateDarkMode()

        // Observar cambios en la clase 'dark' del html
        observer = new MutationObserver(() => {
            updateDarkMode()
        })
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        })
    })

    onUnmounted(() => {
        if (observer) {
            observer.disconnect()
        }
    })

    return {
        colors,
        isLoaded,
        isDarkMode,
        cssVars,
        primaryButtonStyle,
        primaryButtonHoverStyle,
        focusRingStyle,
        headerGradientStyle,
        subtleGradientStyle,
        badgeStyle,
        loadColors
    }
}
