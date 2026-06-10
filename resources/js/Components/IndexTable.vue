<template>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                        <th v-for="col in columns" :key="col.key" class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            {{ col.label }}
                        </th>
                        <th v-if="$slots.actions" class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                    <tr v-for="(row, idx) in rows" :key="row.id || idx" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors duration-150">
                        <td v-for="col in columns" :key="col.key" class="px-5 py-4 whitespace-nowrap">
                            <slot :name="`cell-${col.key}`" :row="row" :value="getValue(row, col)">
                                <div v-if="col.rawHtml" class="text-sm text-slate-900 dark:text-slate-100" v-html="getValue(row, col)"></div>
                                <div v-else class="text-sm text-slate-900 dark:text-slate-100">
                                    {{ getValue(row, col) }}
                                </div>
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="px-5 py-4 whitespace-nowrap text-right">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-4">
                                <div class="w-14 h-14 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center">
                                    <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 00-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-slate-700 dark:text-slate-300 font-medium">{{ emptyText || 'No hay registros' }}</p>
                                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-0.5">{{ emptySubtext || 'Los registros aparecerán aquí cuando se creen' }}</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="$slots.pagination" class="px-5 py-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
            <slot name="pagination" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    emptyText: String,
    emptySubtext: String,
})

const getValue = (row, col) => {
    if (col.format) return col.format(row[col.key], row)
    if (col.key.includes('.')) {
        return col.key.split('.').reduce((o, k) => o?.[k], row) ?? ''
    }
    return row[col.key] ?? ''
}
</script>
