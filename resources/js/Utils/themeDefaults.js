/**
 * FUENTE ÚNICA DE VERDAD para los colores del tema.
 * useDarkMode.js y todos los componentes deben importar desde aquí.
 * NO hardcodees colores en componentes.
 */
export const THEME_DEFAULTS = {
    light: {
        primary: '#FF6B35',
        secondary: '#E55A2B',
        tertiary: '#FBBF24',
    },
    dark: {
        primary: '#FF6B35',
        secondary: '#E55A2B',
        tertiary: '#FBBF24',
        background: '#020617',
        surface: '#0f172a',
    },
};

export const THEME_KEY = 'cdd_theme';

export const THEME_VALUES = ['dark', 'light', 'system'];

export function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result
        ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}`
        : '255, 107, 53';
}

function hexToHsl(hex) {
    let r = 0, g = 0, b = 0;
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (result) {
        r = parseInt(result[1], 16) / 255;
        g = parseInt(result[2], 16) / 255;
        b = parseInt(result[3], 16) / 255;
    }
    const max = Math.max(r, g, b), min = Math.min(r, g, b);
    let h = 0, s = 0, l = (max + min) / 2;
    if (max !== min) {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
            case g: h = ((b - r) / d + 2) / 6; break;
            case b: h = ((r - g) / d + 4) / 6; break;
        }
    }
    return { h: h * 360, s: s * 100, l: l * 100 };
}

function hslStr(h, s, l) { return `hsl(${h}, ${s}%, ${l}%)`; }

function generateBrandPalette(hex) {
    const { h, s, l } = hexToHsl(hex);
    return {
        900: hslStr(h, Math.min(s * 1.2, 100), Math.max(l - 35, 5)),
        800: hslStr(h, Math.min(s * 1.15, 100), Math.max(l - 25, 10)),
        700: hslStr(h, Math.min(s * 1.1, 100), Math.max(l - 15, 15)),
        600: hslStr(h, s, Math.max(l - 8, 20)),
        500: hex,
        400: hslStr(h, s, Math.min(l + 10, 90)),
        300: hslStr(h, s * 0.9, Math.min(l + 20, 92)),
        200: hslStr(h, s * 0.8, Math.min(l + 30, 95)),
        100: hslStr(h, s * 0.6, Math.min(l + 40, 97)),
        50:  hslStr(h, s * 0.4, Math.min(l + 45, 98)),
    };
}

/**
 * Aplica las variables CSS al :root.
 * Úsala desde cualquier componente que necesite setear el tema
 * (ej. useDarkMode, app.js global init, o páginas públicas sin layout).
 */
export function applyThemeCSSVariables(empresaConfig, isDark = false) {
    const root = document.documentElement;
    const primary = empresaConfig?.color_principal || THEME_DEFAULTS.light.primary;
    const secondary = empresaConfig?.color_secundario || THEME_DEFAULTS.light.secondary;
    const tertiary = empresaConfig?.color_terciario || THEME_DEFAULTS.light.tertiary;

    root.style.setProperty('--color-primary', primary);
    root.style.setProperty('--color-primary-rgb', hexToRgb(primary));
    root.style.setProperty('--color-primary-soft', primary + (isDark ? '25' : '15'));
    root.style.setProperty('--color-primary-dark', primary + 'dd');
    root.style.setProperty('--color-secondary', secondary);
    root.style.setProperty('--color-secondary-rgb', hexToRgb(secondary));
    root.style.setProperty('--color-secondary-soft', secondary + (isDark ? '25' : '15'));
    root.style.setProperty('--color-terciary', tertiary);
    root.style.setProperty('--color-terciary-soft', tertiary + (isDark ? '25' : '15'));

    // UI accent dinámico desde color_principal
    root.style.setProperty('--ui-accent', primary);
    root.style.setProperty('--ui-accent-rgb', hexToRgb(primary));
    root.style.setProperty('--ui-accent-contrast', isDark ? '#0F172A' : '#FFFFFF');

    // Paleta brand-* dinámica desde color_principal
    const palette = generateBrandPalette(primary);
    Object.entries(palette).forEach(([key, val]) => {
        root.style.setProperty(`--color-brand-${key}`, val);
    });
}
