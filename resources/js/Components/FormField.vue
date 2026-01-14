<template>
  <div :class="{'mb-4': type !== 'checkbox', 'flex items-center gap-2': type === 'checkbox'}">
    <!-- Label (no checkbox) -->
    <label
      v-if="label && type !== 'checkbox'"
      :for="id"
      class="block text-sm font-medium text-gray-700 mb-1"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- SELECT -->
    <select
      v-if="type === 'select'"
      :id="id"
      :value="props.modelValue"
      @change="onChange"
      class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-200 ease-in-out"
      :class="[hasError ? 'border-red-300' : 'border-gray-300']"
      :required="required"
      :disabled="disabled"
      :aria-invalid="hasError ? 'true' : 'false'"
      :aria-describedby="hasError ? `${id}-error` : undefined"
    >
      <option v-if="placeholder" disabled value="">{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="option.value"
        :value="option.value"
        :disabled="option.disabled"
      >
        {{ option.text }}
      </option>
    </select>

    <!-- TEXTAREA -->
    <textarea
      v-else-if="type === 'textarea'"
      :id="id"
      :value="props.modelValue"
      @input="onInput"
      :placeholder="placeholder"
      :rows="rows"
      class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-200 ease-in-out"
      :class="[hasError ? 'border-red-300' : 'border-gray-300']"
      :required="required"
      :disabled="disabled"
      :aria-invalid="hasError ? 'true' : 'false'"
      :aria-describedby="hasError ? `${id}-error` : undefined"
    ></textarea>

    <!-- CHECKBOX -->
    <div v-else-if="type === 'checkbox'" class="items-center">
      <input
        :id="id"
        type="checkbox"
        :checked="Boolean(props.modelValue)"
        @change="onCheckboxChange"
        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
        :required="required"
        :disabled="disabled"
        :aria-invalid="hasError ? 'true' : 'false'"
        :aria-describedby="hasError ? `${id}-error` : undefined"
      />
      <label v-if="label" :for="id" class="text-sm text-gray-700 select-none">
        {{ label }} <span v-if="required" class="text-red-500">*</span>
      </label>
    </div>

    <!-- INPUT (resto de tipos) -->
    <input
      v-else
      :id="id"
      :type="type"
      :value="props.modelValue"
      @input="onInput"
      :placeholder="placeholder"
      class="mt-1 block w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-200 ease-in-out"
      :class="[hasError ? 'border-red-300' : 'border-gray-300']"
      :required="required"
      :disabled="disabled"
      :min="min"
      :max="max"
      :step="step"
      :list="datalist && datalist.length ? `${id}-datalist` : null"
      :aria-invalid="hasError ? 'true' : 'false'"
      :aria-describedby="hasError ? `${id}-error` : undefined"
    />

    <!-- DATALIST -->
    <datalist v-if="datalist && datalist.length" :id="`${id}-datalist`">
      <option v-for="item in datalist" :key="item" :value="item"></option>
    </datalist>

    <!-- ERROR -->
    <p v-if="hasError" :id="`${id}-error`" class="mt-1 text-sm text-red-600">
      {{ displayError }}
    </p>

    <!-- Helper text (solo si hay y no hay error) -->
    <p v-else-if="hasHelperText" class="mt-1 text-xs text-gray-500">
      {{ helperText }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number, Boolean],
  label: String,
  id: { type: String, required: true },
  type: { type: String, default: 'text' },
  options: { type: Array, default: () => [] },
  error: { type: [String, Array], default: '' },
  touched: { type: Boolean, default: false },
  placeholder: String,
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  rows: { type: Number, default: 3 },
  min: [String, Number],
  max: [String, Number],
  step: [String, Number],
  datalist: { type: Array, default: () => [] },
  helperText: { type: String, default: '' }, // nuevo
})

const emit = defineEmits(['update:modelValue', 'input', 'change', 'clear-error'])

const hasError = computed(() =>
  props.touched && (Array.isArray(props.error) ? props.error.length > 0 : !!props.error)
)
const displayError = computed(() =>
  Array.isArray(props.error) ? (props.error[0] ?? '') : (props.error || '')
)
const hasHelperText = computed(() =>
  !hasError.value && typeof props.helperText === 'string' && props.helperText.trim() !== ''
)

function onInput(e) {
  const v = e.target.value
  emit('update:modelValue', v)
  emit('input', v)
  emit('clear-error', props.id)
}

function onChange(e) {
  const v = e.target.value
  emit('update:modelValue', v)
  emit('change', v)
  emit('clear-error', props.id)
}

function onCheckboxChange(e) {
  const v = e.target.checked
  emit('update:modelValue', v)
  emit('change', v)
  emit('clear-error', props.id)
}
</script>

