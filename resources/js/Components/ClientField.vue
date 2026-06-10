<template>
  <div :class="className">
    <label class="block text-sm font-medium text-[var(--ui-text-muted)] mb-1">
      {{ label }}
    </label>
    <div :class="`p-3 rounded-xl border border-[var(--ui-border)] ${isEmpty ? 'bg-[var(--ui-surface-alt)] text-[var(--ui-text-soft)] italic' : 'bg-[var(--ui-surface)] text-[var(--ui-text)]'}`">
      <a v-if="type === 'email' && !isEmpty" :href="`mailto:${value}`" class="text-blue-600 hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">
        {{ formatValue() }}
      </a>
      <a v-else-if="type === 'phone' && !isEmpty" :href="`tel:${value}`" class="text-blue-600 hover:text-blue-700 dark:text-blue-300 dark:hover:text-blue-200">
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
