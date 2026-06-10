<template>
    <component :is="as" :class="spinnerClasses" :aria-label="label" role="status">
        <template v-if="type === 'svg'">
            <svg v-if="!$slots.default" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <slot v-else />
        </template>
        <template v-else-if="type === 'border'">
            <span class="block rounded-full border-solid border-t-transparent animate-spin" :class="borderSizeClass"></span>
        </template>
        <template v-else-if="type === 'dots'">
            <span class="flex gap-1">
                <span v-for="i in 3" :key="i" class="w-2 h-2 rounded-full bg-current animate-pulse" :style="{ animationDelay: `${(i - 1) * 0.15}s` }"></span>
            </span>
        </template>
    </component>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v)
    },
    color: {
        type: String,
        default: 'amber',
        validator: (v) => ['amber', 'slate', 'emerald', 'rose', 'blue', 'white', 'current'].includes(v)
    },
    type: {
        type: String,
        default: 'svg',
        validator: (v) => ['svg', 'border', 'dots'].includes(v)
    },
    label: {
        type: String,
        default: 'Cargando...'
    },
    as: {
        type: String,
        default: 'span'
    }
});

const sizeClasses = {
    xs: 'h-3 w-3',
    sm: 'h-4 w-4',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
    xl: 'h-16 w-16'
};

const borderSizeClasses = {
    xs: 'h-2 w-2 border',
    sm: 'h-3 w-3 border-2',
    md: 'h-5 w-5 border-2',
    lg: 'h-8 w-8 border-3',
    xl: 'h-12 w-12 border-4'
};

const colorClasses = {
    amber: 'text-brand-500',
    slate: 'text-slate-500',
    emerald: 'text-emerald-500',
    rose: 'text-rose-500',
    blue: 'text-blue-500',
    white: 'text-white',
    current: 'text-current'
};

const borderColors = {
    amber: 'border-t-brand-500 border-brand-500/20',
    slate: 'border-t-slate-500 border-slate-500/20',
    emerald: 'border-t-emerald-500 border-emerald-500/20',
    rose: 'border-t-rose-500 border-rose-500/20',
    blue: 'border-t-blue-500 border-blue-500/20',
    white: 'border-t-white border-white/30',
    current: 'border-t-current border-current/20'
};

const spinnerClasses = computed(() => {
    if (props.type === 'dots') {
        return [colorClasses[props.color], 'inline-flex items-center'];
    }
    if (props.type === 'border') {
        return [borderSizeClasses[props.size], borderColors[props.color]];
    }
    return [sizeClasses[props.size], colorClasses[props.color], 'animate-spin'];
});
</script>
