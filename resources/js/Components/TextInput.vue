<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        class="border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 focus:border-amber-500 dark:focus:border-amber-500 focus:ring-amber-500 dark:focus:ring-amber-500 rounded-md shadow-sm placeholder-gray-400 dark:placeholder-slate-500 transition-colors"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>

