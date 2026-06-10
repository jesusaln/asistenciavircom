<template>
    <div :class="alertClasses" role="alert" :aria-live="type === 'error' ? 'assertive' : 'polite'">
        <div v-if="icon" class="flex-shrink-0">
            <svg v-if="icon === 'info'" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else-if="icon === 'success'" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg v-else-if="icon === 'warning'" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <svg v-else-if="icon === 'error'" class="h-5 w-5" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="flex-1">
            <h3 v-if="title" class="text-sm font-semibold" :class="titleClasses">{{ title }}</h3>
            <div :class="textClasses">
                <slot />
            </div>
        </div>
        <button v-if="dismissible" @click="$emit('dismiss')" class="ml-auto flex-shrink-0 rounded-xl p-1 transition-colors" :class="dismissClasses" :aria-label="'Cerrar alerta'">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'info',
        validator: (v) => ['info', 'success', 'warning', 'error'].includes(v)
    },
    title: {
        type: String,
        default: ''
    },
    dismissible: {
        type: Boolean,
        default: false
    },
    icon: {
        type: [String, Boolean],
        default: 'auto'
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md'].includes(v)
    }
});

defineEmits(['dismiss']);

const iconMap = {
    info: 'info',
    success: 'success',
    warning: 'warning',
    error: 'error'
};

const resolvedIcon = computed(() => props.icon === 'auto' ? iconMap[props.type] : props.icon);

const baseClasses = computed(() => [
    'flex items-start gap-3 rounded-xl p-4 border',
    props.size === 'sm' ? 'px-3 py-2' : 'p-4'
]);

const variants = {
    info: {
        alert: 'bg-sky-50 dark:bg-sky-900/20 border-sky-200 dark:border-sky-800/30',
        icon: 'text-sky-500 dark:text-sky-400',
        title: 'text-sky-900 dark:text-sky-100',
        text: 'text-sky-800 dark:text-sky-200 text-sm',
        dismiss: 'text-sky-500 dark:text-sky-400 hover:bg-sky-100 dark:hover:bg-sky-800/30'
    },
    success: {
        alert: 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800/30',
        icon: 'text-emerald-500 dark:text-emerald-400',
        title: 'text-emerald-900 dark:text-emerald-100',
        text: 'text-emerald-800 dark:text-emerald-200 text-sm',
        dismiss: 'text-emerald-500 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-800/30'
    },
    warning: {
        alert: 'bg-brand-50 dark:bg-brand-900/20 border-brand-200 dark:border-brand-800/30',
        icon: 'text-brand-500 dark:text-amber-400',
        title: 'text-brand-900 dark:text-amber-100',
        text: 'text-brand-800 dark:text-brand-200 text-sm',
        dismiss: 'text-brand-500 dark:text-brand-400 hover:bg-brand-100 dark:hover:bg-brand-800/30'
    },
    error: {
        alert: 'bg-rose-50 dark:bg-rose-900/20 border-rose-200 dark:border-rose-800/30',
        icon: 'text-rose-500 dark:text-rose-400',
        title: 'text-rose-900 dark:text-rose-100',
        text: 'text-rose-800 dark:text-rose-200 text-sm',
        dismiss: 'text-rose-500 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-800/30'
    }
};

const alertClasses = computed(() => [...baseClasses.value, ...variants[props.type].alert.split(' ')]);
const iconClasses = computed(() => variants[props.type].icon);
const titleClasses = computed(() => variants[props.type].title);
const textClasses = computed(() => variants[props.type].text);
const dismissClasses = computed(() => variants[props.type].dismiss);
</script>
