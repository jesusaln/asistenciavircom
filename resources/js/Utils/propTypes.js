/**
 * BaseProps - Sistema de props tipadas para componentes Vue
 *
 * Proporciona props base compartidas con tipos y valores por defecto seguros.
 *
 * @example
 * import { baseProps, createTypedProps } from '@/Utils/propTypes';
 *
 * // En tu componente
 * const props = defineProps({
 *   ...baseProps,
 *   title: { type: String, default: 'Default' },
 * });
 */

/**
 * Tipos de variante disponibles
 */
export const VARIANTS = {
    PRIMARY: 'primary',
    SECONDARY: 'secondary',
    SUCCESS: 'success',
    DANGER: 'danger',
    WARNING: 'warning',
    GHOST: 'ghost',
    OUTLINE: 'outline',
};

/**
 * Tamaños disponibles
 */
export const SIZES = {
    XS: 'xs',
    SM: 'sm',
    MD: 'md',
    LG: 'lg',
    XL: 'xl',
};

/**
 * Props base compartidas por todos los componentes
 * Cada prop tiene: type, required, default, validator
 */
export const baseProps = {
    // Estado
    disabled: {
        type: Boolean,
        required: false,
        default: false,
    },

    loading: {
        type: Boolean,
        required: false,
        default: false,
    },

    // Apariencia
    variant: {
        type: String,
        required: false,
        default: VARIANTS.PRIMARY,
        validator: (value) => Object.values(VARIANTS).includes(value),
    },

    size: {
        type: String,
        required: false,
        default: SIZES.MD,
        validator: (value) => Object.values(SIZES).includes(value),
    },

    // Texto
    label: {
        type: String,
        required: false,
        default: '',
    },

    placeholder: {
        type: String,
        required: false,
        default: '',
    },

    // Clases personalizadas
    className: {
        type: [String, Array, Object],
        required: false,
        default: () => '',
    },

    // Atributos ID
    id: {
        type: String,
        required: false,
        default: null,
    },

    // Atributo name para formularios
    name: {
        type: String,
        required: false,
        default: null,
    },

    // Required para formularios
    required: {
        type: Boolean,
        required: false,
        default: false,
    },

    // Hint/Descripción
    hint: {
        type: String,
        required: false,
        default: '',
    },

    // Error mensaje
    error: {
        type: [String, Boolean],
        required: false,
        default: false,
    },
};

/**
 * Crear props tipadas para un componente
 *
 * @param {Object} additionalProps - Props adicionales específicas del componente
 * @param {Object} options - Opciones de configuración
 * @returns {Object} Props combinadas para use con defineProps
 */
export function createTypedProps(additionalProps = {}, options = {}) {
    const { mergeDefaults = true } = options;

    const merged = { ...baseProps };

    for (const [key, prop] of Object.entries(additionalProps)) {
        if (mergeDefaults && prop.default === undefined) {
            // Usar el tipo del prop base si existe, sino String
            const baseProp = merged[key];
            if (baseProp) {
                prop.default = baseProp.default;
            }
        }
        merged[key] = prop;
    }

    return merged;
}

/**
 * Definir prop con validación de tipo
 */
export function defineTypedProp(name, type, options = {}) {
    const {
        required = false,
        default: defaultValue,
        validator,
    } = options;

    return {
        type,
        required,
        default: defaultValue,
        validator: validator || ((value) => typeof value === type.toLowerCase()),
    };
}

/**
 * Generar ID único
 */
export function generateId(prefix = 'el') {
    return `${prefix}-${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Obtener clases CSS base para un componente
 */
export function getBaseClasses(props, prefix = 'base') {
    const { variant, size, disabled, loading } = props;

    return [
        `${prefix}-component`,
        `${prefix}-component--${variant || VARIANTS.PRIMARY}`,
        `${prefix}-component--${size || SIZES.MD}`,
    ].filter(Boolean);
}

/**
 * Obtener mensaje de error o hint
 */
export function getComponentMessage(props, type = 'error') {
    return props.error || props.hint || '';
}

/**
 * Validar prop contra tipo
 */
export function validatePropType(value, expectedType) {
    if (expectedType === 'Date' && value instanceof Date) {
        return true;
    }

    const typeMap = {
        String: 'string',
        Number: 'number',
        Boolean: 'boolean',
        Array: 'array',
        Object: 'object',
        Function: 'function',
    };

    const expected = typeMap[expectedType] || expectedType.toLowerCase();
    return typeof value === expected;
}

/**
 * Obtener valor por defecto seguro para un tipo
 */
export function getSafeDefault(type, customDefault = undefined) {
    if (customDefault !== undefined) {
        return customDefault;
    }

    const defaults = {
        String: '',
        Number: 0,
        Boolean: false,
        Array: () => [],
        Object: () => ({}),
        Function: () => () => { },
    };

    return defaults[type] ?? null;
}

/**
 * Combinar props con valores por defecto
 */
export function mergeWithDefaults(props, defaults) {
    const merged = { ...defaults };

    for (const [key, value] of Object.entries(props)) {
        if (value !== undefined) {
            merged[key] = value;
        }
    }

    return merged;
}

/**
 * Props para componentes de formulario
 */
export const formFieldProps = {
    ...baseProps,
    modelValue: {
        type: [String, Number, Boolean, Array, Object],
        required: false,
        default: undefined,
    },

    // Validación
    rules: {
        type: [String, Array, Function],
        required: false,
        default: () => [],
    },

    // Apariencia
    showLabel: {
        type: Boolean,
        required: false,
        default: true,
    },

    labelPosition: {
        type: String,
        required: false,
        default: 'top',
        validator: (v) => ['top', 'left', 'bottom'].includes(v),
    },
};

/**
 * Props para componentes de botón
 */
export const buttonProps = {
    ...baseProps,
    type: {
        type: String,
        required: false,
        default: 'button',
        validator: (v) => ['button', 'submit', 'reset'].includes(v),
    },

    block: {
        type: Boolean,
        required: false,
        default: false,
    },

    icon: {
        type: String,
        required: false,
        default: '',
    },

    iconPosition: {
        type: String,
        required: false,
        default: 'left',
        validator: (v) => ['left', 'right'].includes(v),
    },
};

/**
 * Props para componentes de modal
 */
export const modalProps = {
    ...baseProps,
    show: {
        type: Boolean,
        required: true,
    },

    title: {
        type: String,
        required: false,
        default: '',
    },

    size: {
        type: String,
        required: false,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl', 'full'].includes(v),
    },

    closable: {
        type: Boolean,
        required: false,
        default: true,
    },

    closeOnBackdrop: {
        type: Boolean,
        required: false,
        default: true,
    },

    closeOnEscape: {
        type: Boolean,
        required: false,
        default: true,
    },
};
