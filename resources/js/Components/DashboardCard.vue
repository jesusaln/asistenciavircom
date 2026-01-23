<template>
    <PanLink :href="href" class="group bg-white dark:bg-slate-900 dark:bg-gray-800 p-6 rounded-2xl shadow-lg border border-gray-200 dark:border-slate-800 dark:border-gray-700 transition-all transform hover:scale-105 hover:shadow-xl text-center flex flex-col items-center justify-center h-full">
        <div class="flex flex-col items-center justify-center space-y-2">
            <FontAwesomeIcon :icon="icon" :class="`h-10 w-10 text-${color}-600 group-hover:text-${color}-700 transition-colors`" />
            <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-white transition-colors">{{ title }} <span v-if="count !== null">{{ count }}</span></h2>
            <p v-if="subtitle" class="text-sm text-gray-600 dark:text-gray-400 mt-1 transition-colors">{{ subtitle }}</p>
        </div>
    </PanLink>
</template>

<script setup>
import PanLink from '@/Components/PanLink.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

defineProps({
    href: String,
    title: String,
    count: {
        type: Number,
        default: null // Cambiado a null para ser opcional
    },
    icon: Array, // e.g., ['fas', 'users']
    color: String, // e.g., 'blue', 'green'
    subtitle: { // Nuevo prop para añadir información adicional
        type: String,
        default: ''
    }
});
</script>

