<template>
  <div :class="className">
    <label class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
    </label>
    <div :class="`p-3 rounded-md border ${isEmpty ? 'bg-gray-50 text-gray-500 dark:text-gray-400 italic' : 'bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-800'}`">
      <a v-if="type === 'email' && !isEmpty" :href="`mailto:${value}`" class="text-blue-600 hover:text-blue-800">
        {{ formatValue() }}
      </a>
      <a v-else-if="type === 'phone' && !isEmpty" :href="`tel:${value}`" class="text-blue-600 hover:text-blue-800">
        {{ formatValue() }}
      </a>
      <span v-else>
        {{ formatValue() }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: String,
  value: [String, Number],
  type: {
    type: String,
    default: 'text'
  },
  className: {
    type: String,
    default: ''
  }
});

const isEmpty = computed(() => !props.value || props.value.toString().trim() === '');

const formatValue = () => {
  if (isEmpty.value) return 'No especificado';
  return props.value;
};
</script>

