import { ref, computed, onMounted, watch } from 'vue'

const THEME_KEY = 'cdd_theme'

const THEME_VALUES = ['dark', 'light', 'system']

const DEFAULT_LIGHT_COLORS = {
    primary: '#FF6B35',
    secondary: '#E55A2B',
}

const DEFAULT_DARK_COLORS = {
    primary: '#FF6B35',
    secondary: '#E55A2B',
    background: '#020617',
    surface: '#0f172a',
}

const hexToRgb = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result
        ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}`
        : '255, 107, 53'
}

function migrateLegacyKeys() {
    const legacy = {
        theme: localStorage.getItem('theme'),
        darkMode: localStorage.getItem('darkMode'),
        darkModePreference: localStorage.getItem('darkModePreference'),
    }

    let resolved = null

    if (legacy.darkModePreference === 'manual') {
        resolved = legacy.theme === 'dark' || legacy.darkMode === 'true'
            ? 'dark'
            : legacy.theme === 'light'
                ? 'light'
                : 'system'
    } else if (legacy.theme === 'dark') {
        resolved = 'dark'
    } else if (legacy.theme === 'light') {
        resolved = 'light'
    } else if (legacy.darkMode === 'true') {
        resolved = 'dark'
    } else if (legacy.darkMode === 'false') {
        resolved = 'light'
    } else {
        resolved = 'system'
    }

    localStorage.setItem(THEME_KEY, resolved)

    if (legacy.theme) localStorage.removeItem('theme')
    if (legacy.darkMode) localStorage.removeItem('darkMode')
    if (legacy.darkModePreference) localStorage.removeItem('darkModePreference')

    return resolved
}

function getSystemPrefersDark() {
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
}

function resolveIsDark(mode) {
    if (mode === 'dark') return true
    if (mode === 'light') return false
    return getSystemPrefersDark()
}

const MEDIA_QUERY_LISTENERS = new Map()

function subscribeToSystemThemeChanges(callback) {
    const key = 'system-theme-watcher'
    if (MEDIA_QUERY_LISTENERS.has(key)) return

    const mq = window.matchMedia('(prefers-color-scheme: dark)')
    const handler = (e) => callback(!!e.matches)
    mq.addEventListener('change', handler)
    MEDIA_QUERY_LISTENERS.set(key, { mq, handler })
}

function unsubscribeFromSystemThemeChanges() {
    const entry = MEDIA_QUERY_LISTENERS.get('system-theme-watcher')
    if (!entry) return
    entry.mq.removeEventListener('change', entry.handler)
    MEDIA_QUERY_LISTENERS.delete('system-theme-watcher')
}

export function useDarkMode(empresaConfig = null) {
    const themeMode = ref('system')
    const isDarkMode = ref(getSystemPrefersDark())

    const resolvedPrimary = computed(() => {
        if (isDarkMode.value) return empresaConfig?.dark_mode_primary_color || DEFAULT_DARK_COLORS.primary
        return empresaConfig?.color_principal || DEFAULT_LIGHT_COLORS.primary
    })

    const resolvedSecondary = computed(() => {
        if (isDarkMode.value) return empresaConfig?.dark_mode_secondary_color || DEFAULT_DARK_COLORS.secondary
        return empresaConfig?.color_secundario || DEFAULT_LIGHT_COLORS.secondary
    })

    function setTheme(mode) {
        if (!THEME_VALUES.includes(mode)) return
        themeMode.value = mode
        isDarkMode.value = resolveIsDark(mode)
        localStorage.setItem(THEME_KEY, mode)
        applyThemeClasses()
        applyThemeCSSVariables()

        if (mode === 'system') {
            subscribeToSystemThemeChanges((prefersDark) => {
                if (themeMode.value === 'system') {
                    isDarkMode.value = prefersDark
                    applyThemeClasses()
                    applyThemeCSSVariables()
                }
            })
        } else {
            unsubscribeFromSystemThemeChanges()
        }
    }

    function applyThemeClasses() {
        const root = document.documentElement
        if (isDarkMode.value) {
            root.classList.add('dark')
            root.classList.remove('light')
        } else {
            root.classList.remove('dark')
            root.classList.add('light')
        }
        document.body.classList.toggle('dark', isDarkMode.value)
        document.body.classList.toggle('light', !isDarkMode.value)
    }

    function applyThemeCSSVariables() {
        const root = document.documentElement
        const primary = resolvedPrimary.value
        const secondary = resolvedSecondary.value

        root.style.setProperty('--color-primary', primary)
        root.style.setProperty('--color-primary-rgb', hexToRgb(primary))
        root.style.setProperty('--color-primary-soft', primary + (isDarkMode.value ? '25' : '15'))
        root.style.setProperty('--color-primary-dark', primary + 'dd')
        root.style.setProperty('--color-secondary', secondary)
        root.style.setProperty('--color-secondary-rgb', hexToRgb(secondary))
        root.style.setProperty('--color-secondary-soft', secondary + (isDarkMode.value ? '25' : '15'))
        root.style.setProperty('--color-terciary', secondary)
        root.style.setProperty('--color-terciary-soft', secondary + '15')
    }

    function enableDarkMode() { setTheme('dark') }
    function enableLightMode() { setTheme('light') }
    function enableSystemMode() { setTheme('system') }

    function toggleDarkMode() {
        setTheme(isDarkMode.value ? 'light' : 'dark')
    }

    function initializeTheme() {
        const saved = localStorage.getItem(THEME_KEY)
        if (!saved) {
            const migrated = migrateLegacyKeys()
            setTheme(migrated)
        } else {
            const valid = THEME_VALUES.includes(saved) ? saved : 'system'
            setTheme(valid)
        }
    }

    function updateThemeColors(newEmpresaConfig) {
        if (newEmpresaConfig) {
            applyThemeCSSVariables()
        }
    }

    if (empresaConfig) {
        watch(
            () => empresaConfig,
            (newConfig) => updateThemeColors(newConfig),
            { deep: true, immediate: true }
        )
    }

    onMounted(() => {
        initializeTheme()
    })

    return {
        isDarkMode: computed(() => isDarkMode.value),
        themeMode: computed(() => themeMode.value),
        isSystemPreference: computed(() => themeMode.value === 'system'),
        enableDarkMode,
        enableLightMode,
        enableSystemMode,
        toggleDarkMode,
        applyThemeColors: applyThemeCSSVariables,
        updateThemeColors,
    }
}
