<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-xl font-semibold text-xs text-[var(--ui-text)] uppercase tracking-wide shadow-sm hover:bg-[var(--ui-surface-alt)] focus:outline-none focus:ring-2 focus:ring-[var(--ui-accent)] focus:ring-offset-2 focus:ring-offset-[var(--ui-surface)] disabled:opacity-25 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
