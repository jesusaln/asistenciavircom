<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ClientLayout from '../Layout/ClientLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const props = defineProps({
    polizas: Array,
    empresa: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' });
};

const getStatusClasses = (estado) => {
    const maps = {
        'activa': 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'inactiva': 'bg-amber-50 text-amber-600 border-amber-100',
        'vencida': 'bg-red-50 text-red-600 border-red-100',
        'cancelada': 'bg-gray-50 text-gray-500 border-gray-100',
        'pendiente_pago': 'bg-purple-50 text-purple-600 border-purple-100',
    };
    return maps[estado] || 'bg-gray-50 text-gray-500 border-gray-100';
};
</script>

<template>
    <Head title="Mis Pólizas de Servicio" />

    <ClientLayout :empresa="empresa">
        <div class="px-2 sm:px-0">
            <div class="mb-10">
                <Link :href="route('portal.dashboard')" class="text-xs uppercase tracking-widest font-bold text-gray-400 hover:text-[var(--color-primary)] mb-4 inline-block transition-colors">
                    ← Volver al Panel
                </Link>
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Mis Pólizas de Servicio</h1>
                <p class="text-gray-500 font-medium">Consulte el estado, vigencia y beneficios de sus contratos activos.</p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="poliza in polizas" :key="poliza.id" class="bg-white rounded-[2rem] shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-14 h-14 bg-[var(--color-primary-soft)] rounded-2xl flex items-center justify-center text-[var(--color-primary)] text-xl group-hover:scale-110 transition-transform">
                                <font-awesome-icon icon="shield-alt" />
                            </div>
                            <span :class="['px-3 py-1 text-[10px] font-black rounded-full uppercase tracking-widest border', getStatusClasses(poliza.estado)]">
                                {{ poliza.estado?.replace('_', ' ') }}
                            </span>
                        </div>
                        
                        <h3 class="text-xl font-black text-gray-900 mb-1 group-hover:text-[var(--color-primary)] transition-colors">{{ poliza.nombre }}</h3>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-6 font-mono">{{ poliza.folio }}</p>

                        <div class="space-y-4 pt-6 border-t border-gray-50">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 font-medium">Vencimiento</span>
                                <span class="font-bold text-gray-900">{{ formatDate(poliza.fecha_fin) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 font-medium">Equipos</span>
                                <span class="font-bold text-gray-900">{{ poliza.equipos?.length || 0 }} activos</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-8 py-6 flex justify-between items-center group-hover:bg-[var(--color-primary-soft)] transition-colors">
                        <Link :href="route('portal.polizas.show', poliza.id)" class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-primary)] hover:underline flex items-center gap-2">
                             Ver Detalles <font-awesome-icon icon="arrow-right" />
                        </Link>
                        <a :href="route('portal.polizas.imprimir', poliza.id)" target="_blank" class="text-gray-400 hover:text-red-500 transition-colors" title="Descargar Contrato PDF">
                            <font-awesome-icon icon="file-pdf" size="lg" />
                        </a>
                    </div>
                </div>

                <div v-if="polizas.length === 0" class="col-span-full py-20 text-center bg-white rounded-[2rem] border-2 border-dashed border-gray-100">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-2xl text-gray-300">
                        <font-awesome-icon icon="file-contract" />
                    </div>
                    <h3 class="text-xl font-black text-gray-900 mb-2">Sin Pólizas Activas</h3>
                    <p class="text-gray-500 font-medium mb-8">Actualmente no tiene contratos de servicio vinculados a su cuenta.</p>
                    <Link :href="route('catalogo.polizas')" class="px-8 py-4 bg-[var(--color-primary)] text-white rounded-2xl font-black text-sm uppercase tracking-widest hover:shadow-xl transition-all inline-block">Explorar Planes</Link>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>
