<template>
    <div class="page-header-wrapper">
        <!-- Breadcrumbs (optional) -->
        <nav v-if="breadcrumbs && breadcrumbs.length" class="mb-3" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li v-for="(crumb, index) in breadcrumbs" :key="index" class="flex items-center">
                    <Link 
                        v-if="crumb.href && index < breadcrumbs.length - 1"
                        :href="crumb.href"
                        class="text-gray-500 hover:text-amber-600 transition-colors"
                    >
                        {{ crumb.label }}
                    </Link>
                    <span v-else class="text-gray-900 font-medium">{{ crumb.label }}</span>
                    <svg 
                        v-if="index < breadcrumbs.length - 1" 
                        class="h-4 w-4 text-gray-400 mx-2" 
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                    >
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </li>
            </ol>
        </nav>

        <!-- Header Content -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Left side: Title and subtitle -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <!-- Icon -->
                    <span v-if="$slots.icon" class="flex-shrink-0 h-8 w-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                        <slot name="icon" />
                    </span>
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="mt-1 text-sm text-gray-500">
                    {{ subtitle }}
                </p>
            </div>

            <!-- Right side: Actions -->
            <div v-if="$slots.actions" class="flex flex-wrap items-center gap-3">
                <slot name="actions" />
            </div>
        </div>

        <!-- Stats row (optional) -->
        <div v-if="$slots.stats" class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-4">
            <slot name="stats" />
        </div>

        <!-- Filters/Search row (optional) -->
        <div v-if="$slots.filters" class="mt-6">
            <slot name="filters" />
        </div>

        <!-- Divider -->
        <div v-if="showDivider" class="mt-6 border-b border-gray-200"></div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    // Page title
    title: {
        type: String,
        required: true
    },
    // Optional subtitle/description
    subtitle: {
        type: String,
        default: null
    },
    // Breadcrumbs array: [{ label: 'Home', href: '/' }, { label: 'Products' }]
    breadcrumbs: {
        type: Array,
        default: () => []
    },
    // Show divider at bottom
    showDivider: {
        type: Boolean,
        default: false
    }
});
</script>

<style scoped>
.page-header-wrapper {
    margin-bottom: 1.5rem;
}
</style>
