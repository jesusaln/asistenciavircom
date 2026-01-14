<template>
    <div :class="cardClasses">
        <!-- Icon -->
        <div v-if="$slots.icon || icon" :class="iconContainerClasses">
            <slot name="icon">
                <component :is="icon" v-if="icon" class="h-6 w-6" />
            </slot>
        </div>
        
        <!-- Content -->
        <div class="flex-1 min-w-0">
            <!-- Label -->
            <p class="text-sm font-medium text-gray-500 truncate">
                {{ label }}
            </p>
            
            <!-- Value -->
            <div class="mt-1 flex items-baseline gap-2">
                <p :class="valueClasses">
                    <span v-if="prefix" class="text-lg font-medium text-gray-500">{{ prefix }}</span>
                    {{ formattedValue }}
                </p>
                
                <!-- Trend indicator -->
                <span v-if="trend !== null" :class="trendClasses">
                    <svg v-if="trend > 0" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else-if="trend < 0" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-medium">{{ Math.abs(trend) }}%</span>
                </span>
            </div>
            
            <!-- Description -->
            <p v-if="description" class="mt-1 text-xs text-gray-400">
                {{ description }}
            </p>
        </div>
        
        <!-- Link/Action -->
        <Link v-if="href" :href="href" class="absolute inset-0" :aria-label="`Ver ${label}`" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    // Stat label
    label: {
        type: String,
        required: true
    },
    // Value to display
    value: {
        type: [String, Number],
        required: true
    },
    // Prefix (e.g., '$' for currency)
    prefix: {
        type: String,
        default: ''
    },
    // Description text
    description: {
        type: String,
        default: null
    },
    // Trend percentage (positive = up, negative = down, null = no trend)
    trend: {
        type: Number,
        default: null
    },
    // Color variant: default, success, warning, danger, info
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'success', 'warning', 'danger', 'info', 'primary'].includes(value)
    },
    // Icon component or null
    icon: {
        type: [Object, Function],
        default: null
    },
    // Link href (makes card clickable)
    href: {
        type: String,
        default: null
    },
    // Format value as currency
    currency: {
        type: Boolean,
        default: false
    },
    // Compact size
    compact: {
        type: Boolean,
        default: false
    }
});

// Format value
const formattedValue = computed(() => {
    if (props.currency && typeof props.value === 'number') {
        return new Intl.NumberFormat('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(props.value);
    }
    return props.value;
});

// Card container classes
const cardClasses = computed(() => [
    'relative bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden',
    'flex items-start gap-4',
    props.compact ? 'p-4' : 'p-6',
    props.href ? 'hover:shadow-md hover:border-amber-200 transition-all duration-200 cursor-pointer' : '',
]);

// Icon container classes based on variant
const iconContainerClasses = computed(() => {
    const baseClasses = 'flex-shrink-0 p-3 rounded-lg';
    const variantClasses = {
        default: 'bg-gray-100 text-gray-600',
        primary: 'bg-amber-100 text-amber-600',
        success: 'bg-green-100 text-green-600',
        warning: 'bg-yellow-100 text-yellow-600',
        danger: 'bg-red-100 text-red-600',
        info: 'bg-blue-100 text-blue-600',
    };
    return [baseClasses, variantClasses[props.variant]];
});

// Value classes
const valueClasses = computed(() => [
    'text-2xl font-bold',
    props.variant === 'success' ? 'text-green-600' :
    props.variant === 'danger' ? 'text-red-600' :
    props.variant === 'warning' ? 'text-yellow-600' :
    props.variant === 'info' ? 'text-blue-600' :
    props.variant === 'primary' ? 'text-amber-600' :
    'text-gray-900'
]);

// Trend indicator classes
const trendClasses = computed(() => [
    'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
    props.trend > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
]);
</script>
