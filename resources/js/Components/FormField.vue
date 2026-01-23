<template>
  <div :class="{'mb-4': type !== 'checkbox', 'flex items-center gap-2': type === 'checkbox'}" class="form-field-container">
    <!-- Label (no checkbox) -->
    <label
      v-if="label && type !== 'checkbox'"
      :for="id"
      class="block text-xs font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest mb-1.5 transition-colors"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <!-- SELECT -->
    <div v-if="type === 'select'" class="relative">
      <select
        :id="id"
        :value="props.modelValue"
        @change="onChange"
        class="mt-1 block w-full px-4 py-2.5 bg-white dark:bg-slate-900 dark:bg-gray-800 border rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 sm:text-sm transition-all duration-200 ease-in-out text-gray-900 dark:text-white dark:text-gray-100 appearance-none"
        :class="[hasError ? 'border-red-300 dark:border-red-900/50 ring-red-50 dark:ring-red-900/10' : 'border-gray-200 dark:border-slate-800 dark:border-gray-700']"
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
          class="dark:bg-gray-800"
        >
          {{ option.text }}
        </option>
      </select>
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </div>
    </div>

    <!-- TEXTAREA -->
    <textarea
      v-else-if="type === 'textarea'"
      :id="id"
      :value="props.modelValue"
      @input="onInput"
      :placeholder="placeholder"
      :rows="rows"
      class="mt-1 block w-full px-4 py-2.5 bg-white dark:bg-slate-900 dark:bg-gray-800 border rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 sm:text-sm transition-all duration-200 ease-in-out text-gray-900 dark:text-white dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500"
      :class="[hasError ? 'border-red-300 dark:border-red-900/50 ring-red-50 dark:ring-red-900/10' : 'border-gray-200 dark:border-slate-800 dark:border-gray-700']"
      :required="required"
      :disabled="disabled"
      :aria-invalid="hasError ? 'true' : 'false'"
      :aria-describedby="hasError ? `${id}-error` : undefined"
    ></textarea>

    <!-- CHECKBOX -->
    <div v-else-if="type === 'checkbox'" class="flex items-center gap-3 py-1">
      <div class="relative flex items-center">
        <input
          :id="id"
          type="checkbox"
          :checked="Boolean(props.modelValue)"
          @change="onCheckboxChange"
          class="h-5 w-5 rounded-lg border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-gray-800 transition-all cursor-pointer"
          :required="required"
          :disabled="disabled"
          :aria-invalid="hasError ? 'true' : 'false'"
          :aria-describedby="hasError ? `${id}-error` : undefined"
        />
      </div>
      <label v-if="label" :for="id" class="text-sm font-bold text-gray-700 dark:text-gray-300 select-none cursor-pointer">
        {{ label }} <span v-if="required" class="text-red-500">*</span>
      </label>
    </div>

    <!-- INPUT (resto de tipos) -->
    <div v-else class="relative">
      <input
        :id="id"
        :type="type"
        :value="props.modelValue"
        @input="onInput"
        :placeholder="placeholder"
        class="mt-1 block w-full px-4 py-2.5 bg-white dark:bg-slate-900 dark:bg-gray-800 border rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 sm:text-sm transition-all duration-200 ease-in-out text-gray-900 dark:text-white dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500"
        :class="[hasError ? 'border-red-300 dark:border-red-900/50 ring-red-50 dark:ring-red-900/10' : 'border-gray-200 dark:border-slate-800 dark:border-gray-700']"
        :required="required"
        :disabled="disabled"
        :min="min"
        :max="max"
        :step="step"
        :list="datalist && datalist.length ? `${id}-datalist` : null"
        :aria-invalid="hasError ? 'true' : 'false'"
        :aria-describedby="hasError ? `${id}-error` : undefined"
      />
    </div>

    <!-- DATALIST -->
    <datalist v-if="datalist && datalist.length" :id="`${id}-datalist`">
      <option v-for="item in datalist" :key="item" :value="item"></option>
    </datalist>

    <!-- ERROR -->
    <p v-if="hasError" :id="`${id}-error`" class="mt-1.5 text-[11px] font-bold text-red-600 dark:text-red-400 flex items-center gap-1 animate-in fade-in slide-in-from-top-1">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
      {{ displayError }}
    </p>

    <!-- Helper text (solo si hay y no hay error) -->
    <p v-else-if="hasHelperText" class="mt-1.5 text-[10px] font-medium text-gray-400 dark:text-gray-500 dark:text-gray-400 flex items-center gap-1">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
  touched: { type: Boolean, default: true }, // Cambiado a true por defecto para mayor reactividad
  placeholder: String,
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  rows: { type: Number, default: 3 },
  min: [String, Number],
  max: [String, Number],
  step: [String, Number],
  datalist: { type: Array, default: () => [] },
  helperText: { type: String, default: '' },
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

<style scoped>
.animate-in {
  animation-duration: 0.2s;
  animation-fill-mode: both;
}
@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes slide-in-from-top-1 { from { transform: translateY(-0.25rem); } to { transform: translateY(0); } }
.fade-in { animation-name: fade-in; }
.slide-in-from-top-1 { animation-name: slide-in-from-top-1; }
</style>
