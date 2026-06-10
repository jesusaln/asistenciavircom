<script setup>
import { onMounted, useTemplateRef } from 'vue';

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = useTemplateRef('input');

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
        class="border-[var(--ui-border)] bg-[var(--ui-surface)] text-[var(--ui-text)] placeholder:text-[var(--ui-text-soft)] focus:border-[var(--ui-accent)] focus:ring-[var(--ui-accent)] rounded-xl shadow-sm"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
