<template>
    <div class="bg-white dark:bg-slate-900 dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 dark:border-slate-800 overflow-hidden transition-colors">
        <!-- Table Header with Search and Actions -->
        <div v-if="showHeader" class="px-6 py-4 border-b border-gray-100 dark:border-slate-800 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <!-- Title -->
                <h3 v-if="title" class="text-lg font-semibold text-gray-900 dark:text-white dark:text-white transition-colors">{{ title }}</h3>
                
                <!-- Search & Actions -->
                <div class="flex items-center gap-3">
                    <!-- Search Input -->
                    <div v-if="searchable" class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            :placeholder="searchPlaceholder"
                            class="w-64 pl-10 pr-4 py-2 border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-white rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                            @input="handleSearch"
                        />
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    
                    <!-- Actions Slot -->
                    <slot name="actions" />
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <!-- Table Head -->
                <thead class="bg-gray-50 dark:bg-slate-900/50 transition-colors">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            :class="[
                                'px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-wider',
                                column.sortable ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors select-none' : '',
                                column.class || ''
                            ]"
                            :style="column.width ? { width: column.width } : {}"
                            @click="column.sortable ? handleSort(column.key) : null"
                        >
                            <div class="flex items-center gap-2">
                                {{ column.label }}
                                <!-- Sort indicator -->
                                <span v-if="column.sortable" class="flex flex-col">
                                    <svg 
                                        :class="['h-3 w-3 -mb-1', sortKey === column.key && sortDirection === 'asc' ? 'text-amber-500' : 'text-gray-300 dark:text-gray-600']" 
                                        fill="currentColor" 
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M5 12l5-5 5 5H5z" />
                                    </svg>
                                    <svg 
                                        :class="['h-3 w-3', sortKey === column.key && sortDirection === 'desc' ? 'text-amber-500' : 'text-gray-300 dark:text-gray-600']" 
                                        fill="currentColor" 
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M5 8l5 5 5-5H5z" />
                                    </svg>
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                
                <!-- Table Body -->
                <tbody class="bg-white dark:bg-slate-900 dark:bg-slate-900 divide-y divide-gray-100 dark:divide-slate-800 transition-colors">
                    <template v-if="loading">
                        <!-- Loading skeleton -->
                        <tr v-for="i in skeletonRows" :key="`skeleton-${i}`" class="animate-pulse">
                            <td v-for="column in columns" :key="`skeleton-${i}-${column.key}`" class="px-6 py-4">
                                <div class="h-4 bg-gray-200 dark:bg-slate-800 rounded w-3/4 transition-colors"></div>
                            </td>
                        </tr>
                    </template>
                    
                    <template v-else-if="items.length === 0">
                        <!-- Empty state -->
                        <tr>
                            <td :colspan="columns.length" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 font-medium transition-colors">{{ emptyMessage }}</p>
                                    <p v-if="emptyDescription" class="text-sm text-gray-400 dark:text-gray-500 dark:text-gray-400 mt-1 transition-colors">{{ emptyDescription }}</p>
                                    <slot name="empty-action" />
                                </div>
                            </td>
                        </tr>
                    </template>
                    
                    <template v-else>
                        <!-- Data rows -->
                        <tr 
                            v-for="(item, index) in items" 
                            :key="itemKey ? item[itemKey] : index"
                            :class="[
                                'transition-colors duration-150',
                                hoverable ? 'hover:bg-amber-50/50 dark:hover:bg-slate-800 cursor-pointer' : '',
                                striped && index % 2 === 1 ? 'bg-gray-50/50 dark:bg-slate-900/50' : ''
                            ]"
                            @click="hoverable ? $emit('row-click', item) : null"
                        >
                            <slot :item="item" :index="index">
                                <td 
                                    v-for="column in columns" 
                                    :key="column.key" 
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 transition-colors"
                                >
                                    {{ getNestedValue(item, column.key) }}
                                </td>
                            </slot>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="showPagination && pagination" class="px-6 py-4 border-t border-gray-100 dark:border-slate-800 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 transition-colors">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <!-- Info -->
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 transition-colors">
                    Mostrando <span class="font-medium">{{ pagination.from || 0 }}</span> a 
                    <span class="font-medium">{{ pagination.to || 0 }}</span> de 
                    <span class="font-medium">{{ pagination.total || 0 }}</span> resultados
                </p>
                
                <!-- Page links -->
                <nav class="flex items-center gap-1">
                    <button
                        v-for="link in pagination.links"
                        :key="link.label"
                        :disabled="!link.url"
                        :class="[
                            'px-3 py-2 text-sm rounded-lg transition-colors duration-150',
                            link.active 
                                ? 'bg-amber-500 text-white' 
                                : link.url 
                                    ? 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800' 
                                    : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                        ]"
                        @click="link.url ? $emit('page-change', link.url) : null"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    // Table title
    title: {
        type: String,
        default: null
    },
    // Column definitions: [{ key: 'name', label: 'Name', sortable: true, width: '200px', class: '' }]
    columns: {
        type: Array,
        required: true
    },
    // Data items
    items: {
        type: Array,
        default: () => []
    },
    // Key for unique identification
    itemKey: {
        type: String,
        default: 'id'
    },
    // Loading state
    loading: {
        type: Boolean,
        default: false
    },
    // Number of skeleton rows during loading
    skeletonRows: {
        type: Number,
        default: 5
    },
    // Enable search
    searchable: {
        type: Boolean,
        default: false
    },
    // Search placeholder
    searchPlaceholder: {
        type: String,
        default: 'Buscar...'
    },
    // Pagination object (Laravel format)
    pagination: {
        type: Object,
        default: null
    },
    // Show pagination
    showPagination: {
        type: Boolean,
        default: true
    },
    // Show header
    showHeader: {
        type: Boolean,
        default: true
    },
    // Enable hover effect
    hoverable: {
        type: Boolean,
        default: false
    },
    // Striped rows
    striped: {
        type: Boolean,
        default: false
    },
    // Empty state message
    emptyMessage: {
        type: String,
        default: 'No hay datos disponibles'
    },
    // Empty state description
    emptyDescription: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['search', 'sort', 'row-click', 'page-change']);

// Local state
const searchQuery = ref('');
const sortKey = ref(null);
const sortDirection = ref('asc');

// Handle search
const handleSearch = () => {
    emit('search', searchQuery.value);
};

// Handle sort
const handleSort = (key) => {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }
    emit('sort', { key: sortKey.value, direction: sortDirection.value });
};

// Get nested value from object (e.g., 'user.name')
const getNestedValue = (obj, path) => {
    return path.split('.').reduce((acc, part) => acc?.[part], obj) ?? '-';
};
</script>
