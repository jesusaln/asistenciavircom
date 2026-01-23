<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-amber-500 border border-transparent rounded-md font-semibold text-xs text-white dark:text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-amber-600 focus:bg-gray-700 dark:focus:bg-amber-600 active:bg-gray-900 dark:active:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:opacity-50 transition ease-in-out duration-150">
        <slot />
    </button>
</template>

