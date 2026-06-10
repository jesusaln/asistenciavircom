import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'

const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result
        ? { r: parseInt(result[1], 16), g: parseInt(result[2], 16), b: parseInt(result[3], 16) }
        : null
}

const darkenColor = (hex, percent = 40) => {
    const rgb = hexToRgb(hex)
    if (!rgb) return hex
    const d = (v) => Math.max(0, Math.floor(v * (1 - percent / 100)))
    return `rgb(${d(rgb.r)}, ${d(rgb.g)}, ${d(rgb.b)})`
}

export function useCompanyColors() {
    const page = usePage()

    const colors = ref({
        principal: '#F59E0B',
        secundario: '#D97706',
    })

    const isLoaded = ref(false)

    const isDarkMode = ref(
        typeof document !== 'undefined' && document.documentElement.classList.contains('dark')
    )

    let observer = null

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

    const primaryButtonStyle = computed(() => ({
        backgroundColor: colors.value.principal,
        color: '#ffffff',
        borderColor: colors.value.principal,
    }))

    const primaryButtonHoverStyle = computed(() => ({
        backgroundColor: colors.value.secundario,
    }))

    const focusRingStyle = computed(() => ({
        '--tw-ring-color': `${colors.value.principal}80`,
    }))

    const headerGradientStyle = computed(() => {
        if (isDarkMode.value) {
            return {
                background: `linear-gradient(135deg, ${darkenColor(colors.value.principal, 50)} 0%, ${darkenColor(colors.value.secundario, 50)} 100%)`,
            }
        }
        return {
            background: `linear-gradient(135deg, ${colors.value.principal} 0%, ${colors.value.secundario} 100%)`,
        }
    })

    const subtleGradientStyle = computed(() => {
        if (isDarkMode.value) {
            return {
                background: `linear-gradient(135deg, ${colors.value.principal}08 0%, ${colors.value.secundario}08 100%)`,
            }
        }
        return {
            background: `linear-gradient(135deg, ${colors.value.principal}10 0%, ${colors.value.secundario}10 100%)`,
        }
    })

    const badgeStyle = computed(() => ({
        backgroundColor: `${colors.value.principal}15`,
        color: colors.value.principal,
        borderColor: `${colors.value.principal}30`,
    }))

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
            '--color-secondary-rgb': rgbSecondary ? `${rgbSecondary.r}, ${rgbSecondary.g}, ${rgbSecondary.b}` : '217, 119, 6',
        }
    })

    onMounted(() => {
        loadColors()

        const update = () => {
            isDarkMode.value = document.documentElement.classList.contains('dark')
        }

        observer = new MutationObserver(update)
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        })
    })

    onUnmounted(() => {
        if (observer) observer.disconnect()
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
        loadColors,
    }
}

export default useCompanyColors
