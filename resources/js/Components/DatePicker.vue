<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ fechaInicio: null, fechaFin: null })
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Selecciona una fecha'
  },
  minDate: {
    type: [String, Date],
    default: null
  },
  maxDate: {
    type: [String, Date],
    default: null
  },
  disabled: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  format: {
    type: String,
    default: 'YYYY-MM-DD'
  },
  meses: {
    type: Number,
    default: 6,
    validator: (value) => [1, 2, 3, 6, 12, 18, 24, 36].includes(value)
  },
  error: {
    type: String,
    default: null
  },
  helpText: {
    type: String,
    default: null
  },
  showEndDate: {
    type: Boolean,
    default: true
  },
  allowPastDates: {
    type: Boolean,
    default: true
  },
  showQuickActions: {
    type: Boolean,
    default: true
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'compact', 'inline'].includes(value)
  }
})

const emit = defineEmits(['update:modelValue', 'change', 'focus', 'blur'])

// Estado interno
const isStartDateFocused = ref(false)
const isEndDateFocused = ref(false)
const showCalendar = ref(false)
const internalStartValue = ref('')
const validationMessage = ref('')

// Inicializar valor interno
const initializeInternalValue = () => {
  if (props.modelValue?.fechaInicio) {
    const date = new Date(props.modelValue.fechaInicio)
    internalStartValue.value = date.toISOString().split('T')[0]
  }
}

// Watchers
watch(() => props.modelValue?.fechaInicio, () => {
  initializeInternalValue()
}, { immediate: true })

// Computadas
const calculatedEndDate = computed(() => {
  if (!internalStartValue.value) return null

  try {
    const startDate = new Date(internalStartValue.value)
    if (isNaN(startDate.getTime())) return null

    const endDate = new Date(startDate)
    endDate.setMonth(startDate.getMonth() + props.meses)

    // Ajustar para meses con diferente cantidad de días
    if (endDate.getDate() !== startDate.getDate()) {
      endDate.setDate(0) // Último día del mes anterior
    }

    return endDate.toISOString().split('T')[0]
  } catch (error) {
    return null
  }
})

const formattedMin = computed(() => {
  if (!props.allowPastDates) {
    const today = new Date().toISOString().split('T')[0]
    return props.minDate ?
      (new Date(props.minDate) > new Date(today) ? new Date(props.minDate).toISOString().split('T')[0] : today) :
      today
  }
  return props.minDate ? new Date(props.minDate).toISOString().split('T')[0] : null
})

const formattedMax = computed(() =>
  props.maxDate ? new Date(props.maxDate).toISOString().split('T')[0] : null
)

const isValid = computed(() => {
  if (!internalStartValue.value) return !props.required

  const date = new Date(internalStartValue.value)
  if (isNaN(date.getTime())) return false

  if (formattedMin.value && internalStartValue.value < formattedMin.value) return false
  if (formattedMax.value && internalStartValue.value > formattedMax.value) return false

  return true
})

const durationText = computed(() => {
  const mesesMap = {
    1: '1 mes',
    2: '2 meses',
    3: '3 meses',
    6: '6 meses',
    12: '1 año',
    18: '1 año y 6 meses',
    24: '2 años',
    36: '3 años'
  }
  return mesesMap[props.meses] || `${props.meses} meses`
})

const quickActions = computed(() => [
  { label: 'Hoy', days: 0 },
  { label: 'Mañana', days: 1 },
  { label: 'En 1 semana', days: 7 },
  { label: 'En 2 semanas', days: 14 },
  { label: 'En 1 mes', days: 30 }
].filter(action => {
  if (!props.allowPastDates && action.days === 0) {
    return new Date().getHours() < 23 // Solo mostrar "Hoy" si no es muy tarde
  }
  return true
}))

// Métodos
const formatDate = (dateStr, format = 'es-ES') => {
  if (!dateStr) return ''

  try {
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return ''

    return date.toLocaleDateString(format, {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    })
  } catch (error) {
    return ''
  }
}

const formatDateLong = (dateStr) => {
  if (!dateStr) return ''

  try {
    const date = new Date(dateStr)
    return date.toLocaleDateString('es-ES', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  } catch (error) {
    return ''
  }
}

const validateDate = (value) => {
  validationMessage.value = ''

  if (!value && props.required) {
    validationMessage.value = 'Este campo es obligatorio'
    return false
  }

  if (value) {
    const date = new Date(value)
    if (isNaN(date.getTime())) {
      validationMessage.value = 'Fecha inválida'
      return false
    }

    if (formattedMin.value && value < formattedMin.value) {
      const minFormatted = formatDate(formattedMin.value)
      validationMessage.value = `La fecha debe ser posterior a ${minFormatted}`
      return false
    }

    if (formattedMax.value && value > formattedMax.value) {
      const maxFormatted = formatDate(formattedMax.value)
      validationMessage.value = `La fecha debe ser anterior a ${maxFormatted}`
      return false
    }
  }

  return true
}

const handleChange = (event) => {
  const value = event.target.value
  internalStartValue.value = value

  if (validateDate(value)) {
    if (value) {
      const startDate = new Date(value).toISOString()
      const endDateValue = calculatedEndDate.value
      const endDate = endDateValue ? new Date(endDateValue).toISOString() : null

      const result = {
        fechaInicio: startDate,
        fechaFin: endDate
      }

      emit('update:modelValue', result)
      emit('change', result)
    } else {
      const result = { fechaInicio: null, fechaFin: null }
      emit('update:modelValue', result)
      emit('change', result)
    }
  }
}

const handleFocus = (type) => {
  if (type === 'start') {
    isStartDateFocused.value = true
    emit('focus', 'start')
  }
}

const handleBlur = (type) => {
  if (type === 'start') {
    isStartDateFocused.value = false
    emit('blur', 'start')
    validateDate(internalStartValue.value)
  }
}

const setQuickDate = (days) => {
  const date = new Date()
  date.setDate(date.getDate() + days)
  const dateStr = date.toISOString().split('T')[0]

  internalStartValue.value = dateStr
  handleChange({ target: { value: dateStr } })
}

const clearDate = () => {
  internalStartValue.value = ''
  const result = { fechaInicio: null, fechaFin: null }
  emit('update:modelValue', result)
  emit('change', result)
  validationMessage.value = ''
}

// Clases CSS computadas
const inputClasses = computed(() => {
  const base = 'block w-full px-3 py-2 border rounded-md shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-1'
  const variants = {
    default: 'sm:text-sm',
    compact: 'text-xs py-1',
    inline: 'border-0 shadow-none px-1'
  }

  let classes = `${base} ${variants[props.variant]}`

  if (props.error || validationMessage.value) {
    classes += ' border-red-300 focus:border-red-500 focus:ring-red-500'
  } else if (isValid.value && internalStartValue.value) {
    classes += ' border-green-300 focus:border-green-500 focus:ring-green-500'
  } else {
    classes += ' border-gray-300 focus:border-amber-500 focus:ring-amber-500'
  }

  if (props.disabled) {
    classes += ' bg-gray-100 text-gray-500 dark:text-gray-400 cursor-not-allowed'
  }

  return classes
})

const containerClasses = computed(() => {
  if (props.variant === 'inline') return 'flex items-center space-x-2'
  return 'space-y-3'
})
</script>

<template>
  <div :class="containerClasses">
    <!-- Fecha de inicio -->
    <div class="relative flex-1">
      <!-- Label -->
      <label
        v-if="label && variant !== 'inline'"
        class="block text-sm font-medium text-gray-700 mb-1"
        :class="{ 'text-red-600': error || validationMessage }"
      >
        {{ label }}
        <span v-if="required" class="text-red-500 ml-1">*</span>
      </label>

      <!-- Input container -->
      <div class="relative">
        <input
          type="date"
          :value="internalStartValue"
          @input="handleChange"
          @focus="handleFocus('start')"
          @blur="handleBlur('start')"
          :placeholder="placeholder"
          :min="formattedMin"
          :max="formattedMax"
          :disabled="disabled"
          :required="required"
          :class="inputClasses"
        />

        <!-- Clear button -->
        <button
          v-if="internalStartValue && !disabled"
          type="button"
          @click="clearDate"
          class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Quick actions -->
      <div v-if="showQuickActions && variant === 'default'" class="mt-2 flex flex-wrap gap-1">
        <button
          v-for="action in quickActions"
          :key="action.label"
          type="button"
          @click="setQuickDate(action.days)"
          :disabled="disabled"
          class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ action.label }}
        </button>
      </div>

      <!-- Help text -->
      <p v-if="helpText && !error && !validationMessage" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        {{ helpText }}
      </p>

      <!-- Error message -->
      <p v-if="error || validationMessage" class="mt-1 text-xs text-red-600 flex items-center">
        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        {{ error || validationMessage }}
      </p>
    </div>

    <!-- Fecha de fin (calculada) -->
    <div v-if="showEndDate && calculatedEndDate" class="relative flex-1">
      <label
        v-if="variant !== 'inline'"
        class="block text-sm font-medium text-gray-700 mb-1"
      >
        Fecha de fin
        <span class="text-xs font-normal text-gray-500 dark:text-gray-400 ml-1">({{ durationText }})</span>
      </label>

      <div class="relative">
        <input
          type="text"
          :value="formatDate(calculatedEndDate)"
          readonly
          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-600 cursor-default"
          :class="variant === 'compact' ? 'text-xs py-1' : 'sm:text-sm'"
        />

        <!-- Info icon -->
        <div class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <!-- Duration info -->
      <div v-if="calculatedEndDate && variant === 'default'" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        {{ formatDateLong(calculatedEndDate) }}
      </div>
    </div>

    <!-- Compact summary for inline variant -->
    <div v-if="variant === 'inline' && internalStartValue" class="text-xs text-gray-600 whitespace-nowrap">
      {{ durationText }}
    </div>
  </div>
</template>

<style scoped>
/* Mejoras en el picker de fecha */
input[type="date"]::-webkit-calendar-picker-indicator {
  color: rgba(0, 0, 0, 0.5);
  cursor: pointer;
  padding: 2px;
  border-radius: 2px;
}

input[type="date"]::-webkit-calendar-picker-indicator:hover {
  background-color: rgba(0, 0, 0, 0.1);
}

input[type="date"]:disabled::-webkit-calendar-picker-indicator {
  color: rgba(0, 0, 0, 0.3);
  cursor: not-allowed;
}

/* Animaciones */
.transition-all {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Estados de focus mejorados */
input:focus {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
}

/* Mejoras visuales */
input[readonly] {
  background-image: linear-gradient(45deg, transparent 25%, rgba(0,0,0,.025) 25%, rgba(0,0,0,.025) 50%, transparent 50%, transparent 75%, rgba(0,0,0,.025) 75%, rgba(0,0,0,.025));
  background-size: 8px 8px;
}
</style>

