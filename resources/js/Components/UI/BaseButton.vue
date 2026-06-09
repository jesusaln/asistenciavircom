<template>
    <component
        :is="componentType"
        :href="href"
        :type="type"
        :disabled="disabled || loading"
        :class="buttonClasses"
        @click="handleClick"
    >
        <!-- Loading Spinner -->
        <svg 
            v-if="loading" 
            class="animate-spin -ml-1 mr-2 h-4 w-4" 
            xmlns="http://www.w3.org/2000/svg" 
            fill="none" 
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        
        <!-- Icon (left) -->
        <span v-if="icon && iconPosition === 'left'" class="mr-2">
            <slot name="icon">
                <component :is="iconComponent" v-if="iconComponent" class="h-4 w-4" />
            </slot>
        </span>
        
        <!-- Button Text -->
        <slot />
        
        <!-- Icon (right) -->
        <span v-if="icon && iconPosition === 'right'" class="ml-2">
            <slot name="icon">
                <component :is="iconComponent" v-if="iconComponent" class="h-4 w-4" />
            </slot>
        </span>
    </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    // Variant: primary, secondary, danger, success, warning, ghost, outline
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger', 'success', 'warning', 'ghost', 'outline'].includes(value)
    },
    // Size: xs, sm, md, lg, xl
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
    },
    // Button type for native button
    type: {
        type: String,
        default: 'button'
    },
    // If provided, renders as a Link component
    href: {
        type: String,
        default: null
    },
    // Disabled state
    disabled: {
        type: Boolean,
        default: false
    },
    // Loading state
    loading: {
        type: Boolean,
        default: false
    },
    // Full width button
    block: {
        type: Boolean,
        default: false
    },
    // Icon (slot or pass component)
    icon: {
        type: Boolean,
        default: false
    },
    // Icon position
    iconPosition: {
        type: String,
        default: 'left',
        validator: (value) => ['left', 'right'].includes(value)
    },
    // Icon-only button (square)
    iconOnly: {
        type: Boolean,
        default: false
    },
    // Rounded style
    rounded: {
        type: String,
        default: 'lg',
        validator: (value) => ['none', 'sm', 'md', 'lg', 'xl', 'full'].includes(value)
    }
});

const emit = defineEmits(['click']);

// Determine if we render a Link or button
const componentType = computed(() => props.href ? Link : 'button');

// Base classes that apply to all buttons
const baseClasses = computed(() => [
    'inline-flex items-center justify-center font-semibold transition-all duration-200',
    'focus:outline-none focus:ring-2 focus:ring-offset-2',
    'disabled:opacity-50 disabled:cursor-not-allowed',
    props.block ? 'w-full' : '',
    `rounded-${props.rounded}`,
]);

// Size classes
const sizeClasses = computed(() => {
    if (props.iconOnly) {
        const iconSizes = {
            xs: 'h-6 w-6',
            sm: 'h-8 w-8',
            md: 'h-10 w-10',
            lg: 'h-12 w-12',
            xl: 'h-14 w-14',
        };
        return iconSizes[props.size];
    }
    
    const sizes = {
        xs: 'px-2.5 py-1.5 text-xs',
        sm: 'px-3 py-2 text-sm',
        md: 'px-4 py-2.5 text-sm',
        lg: 'px-5 py-3 text-base',
        xl: 'px-6 py-3.5 text-lg',
    };
    return sizes[props.size];
});

// Variant classes
const variantClasses = computed(() => {
    const variants = {
        primary: 'bg-amber-500 text-white hover:bg-amber-600 focus:ring-amber-500 shadow-sm',
        secondary: 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500',
        danger: 'bg-red-500 text-white hover:bg-red-600 focus:ring-red-500 shadow-sm',
        success: 'bg-green-500 text-white hover:bg-green-600 focus:ring-green-500 shadow-sm',
        warning: 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500 shadow-sm',
        ghost: 'bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
        outline: 'bg-transparent text-amber-600 border-2 border-amber-500 hover:bg-amber-50 focus:ring-amber-500',
    };
    return variants[props.variant];
});

// Combined classes
const buttonClasses = computed(() => [
    ...baseClasses.value,
    sizeClasses.value,
    variantClasses.value,
]);

// Handle click
const handleClick = (event) => {
    if (!props.disabled && !props.loading) {
        emit('click', event);
    }
};
</script>
