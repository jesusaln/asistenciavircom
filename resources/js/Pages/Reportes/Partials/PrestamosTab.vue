<template>
    <div>
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Reporte de Préstamos</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ prestamosTotales }}</div>
                    <div class="text-sm text-blue-600">Total Préstamos</div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ prestamosActivos }}</div>
                    <div class="text-sm text-green-600">Préstamos Activos</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">{{ prestamosCompletados }}</div>
                    <div class="text-sm text-purple-600">Préstamos Completados</div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-orange-600">{{ montoTotalPrestado }}</div>
                    <div class="text-sm text-orange-600">Monto Total Prestado</div>
                </div>
            </div>
        </div>

        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Préstamos por Cliente</h3>
            <p class="text-sm text-gray-500 mb-4">Análisis detallado de préstamos agrupados por cliente</p>
            <div class="bg-gray-50 p-6 rounded-lg max-w-2xl mx-auto">
                <div class="text-center">
                    <p class="text-gray-600">Los datos de préstamos se cargan desde el controlador específico</p>
                    <Link
                        :href="route('reportes.prestamos-por-cliente')"
                        class="inline-flex items-center mt-4 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors"
                    >
                        Ver Reporte Detallado de Préstamos
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    prestamos: { type: Array, default: () => [] },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const prestamosTotales = computed(() => {
    return props.prestamos?.length || 0;
});

const prestamosActivos = computed(() => {
    return props.prestamos?.filter(p => p.estado === 'activo').length || 0;
});

const prestamosCompletados = computed(() => {
    return props.prestamos?.filter(p => p.estado === 'completado').length || 0;
});

const montoTotalPrestado = computed(() => {
    return formatCurrency(props.prestamos?.reduce((acc, p) => acc + (p.monto_prestado || 0), 0) || 0);
});
</script>

