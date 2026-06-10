<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-[var(--ui-accent)] text-[var(--ui-accent-contrast)] border border-transparent rounded-xl font-semibold text-xs uppercase tracking-wide hover:brightness-110 active:brightness-90 focus:outline-none focus:ring-2 focus:ring-[var(--ui-accent)] focus:ring-offset-2 focus:ring-offset-[var(--ui-surface)] disabled:opacity-50 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
