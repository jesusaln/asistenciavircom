<template>
    <Head :title="`Mantenimiento - ${vehiculoLabel}`" />
    <div class="min-h-screen bg-gray-50 py-10 transition-colors dark:bg-gray-900" :style="cssVars">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb / Header Principal -->
        <div class="mb-8 flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <div class="space-y-4">
                <Link
                    :href="route('mantenimientos.index')"
                    class="group flex items-center gap-2 text-sm font-bold text-gray-500 transition-all hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                >
                    <div class="rounded-lg bg-gray-100 p-1.5 transition-colors group-hover:bg-blue-50 dark:bg-gray-800 dark:group-hover:bg-blue-950/40">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </div>
                    Volver al control de flota
                </Link>
                <div class="flex items-center gap-4">
                    <h1 v-if="mantenimiento" class="text-3xl font-black tracking-tight text-gray-900 dark:text-white">
                        Ficha de Mantenimiento
                    </h1>
                    <div v-if="mantenimiento?.folio" class="rounded-lg bg-gray-900 px-3 py-1 text-xs font-black uppercase tracking-widest text-white dark:bg-gray-700">
                        {{ mantenimiento.folio }}
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    @click="imprimirFicha"
                    class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700/60"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 00-2 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Imprimir
                </button>
                <Link
                    v-if="mantenimiento"
                    :href="route('mantenimientos.edit', mantenimiento.id)"
                    class="flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-white shadow-md transition-all hover:bg-amber-600 hover:shadow-lg hover:-translate-y-0.5 dark:bg-amber-600 dark:hover:bg-amber-500"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Editar Registro
                </Link>
            </div>
        </div>

        <!-- Alerta de Error -->
        <div v-if="error" class="mb-8 flex animate-pulse items-center gap-4 rounded-2xl border border-red-100 bg-red-50 p-4 text-red-800 shadow-sm dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-200">
            <div class="rounded-full bg-red-100 p-2 dark:bg-red-900/50">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>
            <p class="font-bold">{{ error }}</p>
        </div>

        <!-- Contenido Principal -->
        <div v-if="mantenimiento" class="overflow-hidden rounded-[2rem] border border-gray-200 bg-white shadow-xl shadow-gray-200/50 ring-1 ring-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:shadow-none dark:ring-gray-700/60">
            <div class="p-8 md:p-10">
                <MantenimientoDetails :mantenimiento="mantenimiento" />
            </div>

            <!-- Footer con metadatos -->
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 bg-gray-50 px-8 py-5 text-xs font-bold uppercase tracking-widest text-gray-400 dark:border-gray-700 dark:bg-gray-900/40 dark:text-gray-500">
                <div class="flex items-center gap-6">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Creado: {{ formatearFechaFull(mantenimiento.created_at) }}
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Actualizado: {{ formatearFechaFull(mantenimiento.updated_at) }}
                    </span>
                </div>
                <div class="rounded-full border border-gray-200 bg-white px-3 py-1 text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                    ID: {{ mantenimiento.id }}
                </div>
            </div>
        </div>
    </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import MantenimientoDetails from '@/Components/Mantenimiento/MantenimientoDetails.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

defineOptions({ layout: AppLayout });

const { cssVars } = useCompanyColors();

const props = defineProps({
    mantenimiento: { type: Object, default: null },
    error: { type: String, default: null },
});

const vehiculoLabel = computed(() => {
    const c = props.mantenimiento?.carro;
    if (!c) return 'Vehículo no asignado';
    return `${c.marca || ''} ${c.modelo || ''}`.trim() || 'Vehículo';
});

const formatearFechaFull = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('es-MX', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const imprimirFicha = () => {
    window.print();
};
</script>

<style>
@media print {
    .bg-white { background-color: white !important; }
    .shadow-xl { box-shadow: none !important; }
    button, a[href*="index"], a[href*="edit"] { display: none !important; }
    .max-w-4xl { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
}
</style>

