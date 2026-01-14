import { ref, computed, onMounted } from 'vue'
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
        principal: '#FF6B35',
        secundario: '#D97706'
    })

    const isLoaded = ref(false)

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

    // Gradiente para headers
    const headerGradientStyle = computed(() => ({
        background: `linear-gradient(135deg, ${colors.value.principal} 0%, ${colors.value.secundario} 100%)`
    }))

    // Gradiente sutil para fondos
    const subtleGradientStyle = computed(() => ({
        background: `linear-gradient(135deg, ${colors.value.principal}10 0%, ${colors.value.secundario}10 100%)`
    }))

    // Badge/Tag style
    const badgeStyle = computed(() => ({
        backgroundColor: `${colors.value.principal}15`,
        color: colors.value.principal,
        borderColor: `${colors.value.principal}30`
    }))

    onMounted(() => {
        loadColors()
    })

    return {
        colors,
        isLoaded,
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
