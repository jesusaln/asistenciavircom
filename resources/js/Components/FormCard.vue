<template>
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div v-if="$slots.header" class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50">
            <slot name="header" />
        </div>
        <div class="p-6">
            <slot />
        </div>
        <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex justify-end gap-3">
            <slot name="footer" />
        </div>
    </div>
</template>
