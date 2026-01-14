<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: [String, Number, Boolean],
  options: {
    type: Array,
    default: () => []
  },
  label: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  class: {
    type: String,
    default: 'w-full border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-md shadow-sm'
  }
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <select
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      :disabled="disabled"
      :required="required"
      :class="class"
    >
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>
</template>

<style scoped>
select:disabled {
  background-color: #f9fafb;
  opacity: 0.7;
}
</style>

