<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '../Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    rentas: Array,
    empresa: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

const getStatusClasses = (estado) => {
    const maps = {
        'activo': 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'vencido': 'bg-red-50 text-red-600 border-red-100',
        'pendiente_firma': 'bg-amber-50 text-amber-600 border-amber-100',
        'finalizado': 'bg-gray-50 text-gray-600 border-gray-100',
    };
    return maps[estado] || 'bg-gray-50 text-gray-600 border-gray-100';
};
</script>

<template>
    <Head title="Mis Rentas - Punto de Venta" />

    <ClientLayout :empresa="empresa">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <!-- Header -->
            <div class="mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Mis Rentas</h1>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Gestione sus contratos de renta de puntos de venta y equipos.</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-blue-600 dark:text-blue-400 text-xs font-bold uppercase tracking-widest border border-blue-100 dark:border-blue-800">
                        {{ rentas.length }} Contrato(s)
                    </div>
                </div>
            </div>

            <!-- Grid de Rentas -->
            <div class="grid gap-8">
                <div v-for="renta in rentas" :key="renta.id" 
                     class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden group hover:border-[var(--color-primary)] transition-all duration-300">
                    
                    <div class="p-8 md:p-10">
                        <div class="flex flex-col lg:flex-row justify-between gap-8">
                            <!-- Info Principal -->
                            <div class="flex-1">
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/20 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 text-2xl shadow-inner group-hover:scale-110 transition-transform">
                                        <font-awesome-icon icon="cash-register" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-3 mb-1">
                                            <h3 class="text-2xl font-black text-gray-900 dark:text-white transition-colors">Contrato #{{ renta.numero_contrato || 'S/N' }}</h3>
                                            <span 
                                                class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border"
                                                :class="getStatusClasses(renta.firma_digital ? renta.estado : 'pendiente_firma')"
                                            >
                                                {{ renta.firma_digital ? renta.estado : 'Pendiente de Firma' }}
                                            </span>
                                        </div>
                                        <p class="text-sm font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest font-mono">ID: {{ renta.id }}</p>
                                    </div>
                                </div>

                                <!-- Detalles en Grid -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 py-6 border-y border-gray-50 dark:border-gray-700/50">
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Monto Mensual</p>
                                        <p class="text-lg font-black text-gray-900 dark:text-white">{{ formatCurrency(renta.monto_mensual) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Próximo Pago</p>
                                        <p class="text-lg font-black text-blue-600 dark:text-blue-400">Día {{ renta.dia_pago }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Vigencia</p>
                                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ formatDate(renta.fecha_inicio) }} - {{ formatDate(renta.fecha_fin) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Equipos</p>
                                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ renta.equipos?.length || 0 }} unidad(es)</p>
                                    </div>
                                </div>

                                <!-- Lista de Equipos -->
                                <div v-if="renta.equipos && renta.equipos.length > 0" class="mt-6 flex flex-wrap gap-2">
                                    <span v-for="equipo in renta.equipos" :key="equipo.id" 
                                          class="px-3 py-1 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 border border-gray-100 dark:border-gray-700">
                                        {{ equipo.nombre }} ({{ equipo.marca }})
                                    </span>
                                </div>
                            </div>

                            <!-- Acciones -->
                            <div class="flex lg:flex-col justify-center gap-4 lg:min-w-[240px]">
                                <!-- Si no tiene firma, botón GRANDE de firmar -->
                                <template v-if="!renta.firma_digital">
                                    <Link 
                                        :href="route('portal.rentas.firmar', renta.id)"
                                        class="flex-1 flex items-center justify-center gap-3 px-8 py-5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-1 transition-all group/btn"
                                    >
                                        <font-awesome-icon icon="file-signature" class="text-xl group-hover/btn:rotate-12 transition-transform" />
                                        Firmar Contrato
                                    </Link>
                                    <p class="text-[10px] text-center font-bold text-amber-600 bg-amber-50 dark:bg-amber-900/20 py-2 rounded-lg border border-amber-100 dark:border-amber-800 animate-pulse">
                                        ⚠️ Requiere acción para activar
                                    </p>
                                </template>

                                <!-- Si ya tiene firma, botones de ver y descargar -->
                                <template v-else>
                                    <a 
                                        :href="route('portal.rentas.contrato.pdf', renta.id)"
                                        target="_blank"
                                        class="flex-1 flex items-center justify-center gap-3 px-8 py-4 bg-white dark:bg-gray-700 text-gray-700 dark:text-white border-2 border-gray-100 dark:border-gray-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-600 transition-all shadow-sm"
                                    >
                                        <font-awesome-icon icon="file-pdf" class="text-red-500 text-lg" />
                                        Descargar PDF
                                    </a>
                                    <div class="px-4 py-3 bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-800/50 flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-xs">
                                            ✓
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest">Firmado Digitalmente</p>
                                            <p class="text-[10px] font-bold text-gray-500">{{ formatDate(renta.firmado_at) }}</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="rentas.length === 0" class="py-24 text-center bg-white dark:bg-gray-800 rounded-[3rem] shadow-xl border-4 border-dashed border-gray-50 dark:border-gray-700">
                    <div class="w-24 h-24 bg-gray-50 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl shadow-inner">
                        🏪
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2">No se encontraron contratos</h3>
                    <p class="text-gray-500 dark:text-gray-400 font-medium max-w-md mx-auto mb-10">Aún no tiene equipos de punto de venta en renta registrados en nuestro sistema.</p>
                    <a :href="route('catalogo.index')" class="px-10 py-5 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl shadow-orange-500/20 hover:shadow-orange-500/40 hover:-translate-y-1 transition-all">
                        Explorar Equipos en Tienda
                    </a>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>

<style scoped>
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .7; }
}
</style>
