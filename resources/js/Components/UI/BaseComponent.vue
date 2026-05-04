<script setup>
import { computed, toRefs } from 'vue';

/**
 * BaseComponent - Componente base con props tipadas y valores por defecto seguros
 *
 * Proporciona props compartidas y utilidades para todos los componentes del sistema.
 *
 * @example
 * <script setup>
 * import { baseProps, getBaseClasses } from '@/Components/UI/BaseComponent.vue';
 *
 * const props = defineProps({
 *   ...baseProps,
 *   // Tus props adicionales aquí
 *   title: { type: String, default: 'Default Title' },
 * });
 * </script>
 */

/**
 * Tipos de variante disponibles
 */
const VARIANTS = {
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
const SIZES = {
  XS: 'xs',
  SM: 'sm',
  MD: 'md',
  LG: 'lg',
  XL: 'xl',
};

/**
 * Props base compartidas por todos los componentes
 */
const baseProps = {
  // Estado
  disabled: {
    type: Boolean,
    default: false,
  },

  loading: {
    type: Boolean,
    default: false,
  },

  // Apariencia
  variant: {
    type: String,
    default: VARIANTS.PRIMARY,
    validator: (value) => Object.values(VARIANTS).includes(value),
  },

  size: {
    type: String,
    default: SIZES.MD,
    validator: (value) => Object.values(SIZES).includes(value),
  },

  // Texto
  label: {
    type: String,
    default: '',
  },

  placeholder: {
    type: String,
    default: '',
  },

  // Clases personalizadas
  className: {
    type: [String, Array, Object],
    default: () => '',
  },

  // Atributos ID
  id: {
    type: String,
    default: null,
  },

  // Atributo name para formularios
  name: {
    type: String,
    default: null,
  },

  // Required para formularios
  required: {
    type: Boolean,
    default: false,
  },

  // Hint/Descripción
  hint: {
    type: String,
    default: '',
  },

  // Error mensaje
  error: {
    type: [String, Boolean],
    default: false,
  },
};

/**
 * Computed para classes CSS base
 */
function getBaseClasses(props) {
  const { variant, size, disabled, loading, className } = toRefs(props);

  return computed(() => {
    const classes = [
      'base-component',
      `base-component--${variant.value}`,
      `base-component--${size.value}`,
    ];

    if (disabled.value || loading.value) {
      classes.push('base-component--disabled');
    }

    if (loading.value) {
      classes.push('base-component--loading');
    }

    // Agregar clases personalizadas
    if (className.value) {
      if (typeof className.value === 'string') {
        classes.push(className.value);
      } else if (Array.isArray(className.value)) {
        classes.push(...className.value);
      } else if (typeof className.value === 'object') {
        Object.assign(classes, className.value);
      }
    }

    return classes;
  });
}

/**
 * Generar ID único si no se proporciona
 */
function generateId(prefix = 'bc') {
  return `${prefix}-${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Validar que un valor sea del tipo esperado
 */
function validatePropType(value, expectedType) {
  if (expectedType === 'date' && value instanceof Date) {
    return true;
  }

  return typeof value === expectedType.toLowerCase();
}

// Exportar utilities
defineOptions({
  name: 'BaseComponent',
});

export {
  VARIANTS,
  SIZES,
  baseProps,
  getBaseClasses,
  generateId,
  validatePropType,
};
</script>

<template>
  <div :class="getBaseClasses($props).value">
    <slot />
  </div>
</template>

<style scoped>
.base-component {
  display: flex;
  flex-direction: column;
}

.base-component--disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.base-component--primary {
  color: var(--color-primary, #2563eb);
}

.base-component--secondary {
  color: var(--color-gray, #6b7280);
}

.base-component--danger {
  color: var(--color-red, #dc2626);
}

.base-component--success {
  color: var(--color-green, #16a34a);
}

.base-component--xs {
  font-size: 0.75rem;
}

.base-component--sm {
  font-size: 0.875rem;
}

.base-component--md {
  font-size: 1rem;
}

.base-component--lg {
  font-size: 1.125rem;
}

.base-component--xl {
  font-size: 1.25rem;
}
</style>
