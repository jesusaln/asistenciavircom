<template>
    <component
        :is="href ? Link : 'button'"
        :href="href"
        :type="href ? undefined : 'button'"
        :class="iconClasses"
        :title="title"
        :disabled="disabled"
        @click="handleClick"
    >
        <slot>
            <!-- Default icons for common actions -->
            <svg v-if="action === 'view'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg v-else-if="action === 'edit'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <svg v-else-if="action === 'delete'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            <svg v-else-if="action === 'download'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <svg v-else-if="action === 'print'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <svg v-else-if="action === 'email'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <svg v-else-if="action === 'whatsapp'" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
        </slot>
    </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    // Action type: view, edit, delete, download, print, email, whatsapp
    action: {
        type: String,
        default: null
    },
    // Variant: matches action type or custom
    variant: {
        type: String,
        default: null
    },
    // Title/tooltip
    title: {
        type: String,
        default: null
    },
    // Link (makes it a Link component)
    href: {
        type: String,
        default: null
    },
    // Size: sm, md, lg
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    // Disabled state
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['click']);

// Determine variant from action if not specified
const effectiveVariant = computed(() => {
    if (props.variant) return props.variant;
    
    const actionVariants = {
        view: 'info',
        edit: 'warning',
        delete: 'danger',
        download: 'success',
        print: 'default',
        email: 'primary',
        whatsapp: 'success'
    };
    return actionVariants[props.action] || 'default';
});

// Size classes
const sizeClasses = computed(() => {
    const sizes = {
        sm: 'w-7 h-7',
        md: 'w-8 h-8',
        lg: 'w-10 h-10'
    };
    return sizes[props.size];
});

// Variant classes
const variantClasses = computed(() => {
    const variants = {
        default: 'bg-gray-100 text-gray-600 hover:bg-gray-200',
        primary: 'bg-amber-100 text-amber-600 hover:bg-amber-200',
        info: 'bg-blue-100 text-blue-600 hover:bg-blue-200',
        success: 'bg-green-100 text-green-600 hover:bg-green-200',
        warning: 'bg-amber-100 text-amber-600 hover:bg-amber-200',
        danger: 'bg-red-100 text-red-600 hover:bg-red-200'
    };
    return variants[effectiveVariant.value];
});

// Combined classes
const iconClasses = computed(() => [
    'inline-flex items-center justify-center rounded-lg transition-colors duration-200',
    'focus:outline-none focus:ring-2 focus:ring-offset-1',
    sizeClasses.value,
    variantClasses.value,
    props.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
]);

const handleClick = (event) => {
    if (!props.disabled) {
        emit('click', event);
    }
};
</script>
