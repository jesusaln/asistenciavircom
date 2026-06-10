<template>
  <AppLayout title="Mirage Postventa">
    <template #header>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-white uppercase tracking-wider flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-600 rounded-xl flex items-center justify-center shadow-lg shadow-red-600/20">
                        <FontAwesomeIcon icon="robot" class="text-lg" />
                    </div>
                    Mirage Postventa
                </h2>
                <p class="text-[10px] font-bold text-white/40 uppercase tracking-[0.2em] mt-1 ml-13">
                    Gestión de solicitudes importadas
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    @click="showSyncModal = true"
                    class="px-6 py-3 bg-red-500 hover:bg-red-400 border border-red-400/30 rounded-xl text-[10px] font-black text-white uppercase tracking-widest transition-all flex items-center gap-2 group shadow-[0_0_20px_rgba(239,68,68,0.2)] hover:shadow-[0_0_30px_rgba(239,68,68,0.4)] active:scale-95"
                >
                    <FontAwesomeIcon icon="sync" class="group-hover:rotate-180 transition-transform duration-700" />
                    Sincronizar Mirage
                </button>
            </div>
        </div>
    </template>

    <div class="py-12 px-4 lg:px-10">
        <!-- Listado -->
        <div class="bg-white/[0.02] backdrop-blur-xl border border-white/10 rounded-[2.5rem] overflow-hidden shadow-2xl">
            <div class="overflow-x-auto overflow-y-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 bg-white/[0.02]">
                            <th class="px-6 py-5 text-[10px] font-black text-white/40 uppercase tracking-widest">Folio Mirage</th>
                            <th class="px-6 py-5 text-[10px] font-black text-white/40 uppercase tracking-widest">Cliente</th>
                            <th class="px-6 py-5 text-[10px] font-black text-white/40 uppercase tracking-widest">Teléfono</th>
                            <th class="px-6 py-5 text-[10px] font-black text-white/40 uppercase tracking-widest">Tipo</th>
                            <th class="px-6 py-5 text-[10px] font-black text-white/40 uppercase tracking-widest">Fecha Registro</th>
                            <th class="px-6 py-5 text-[10px] font-black text-white/40 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.05]">
                        <tr v-for="solicitud in solicitudes.data" :key="solicitud.id" class="group hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-5">
                                <span class="text-sm font-black text-white tracking-wider">{{ solicitud.folio }}</span>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-white/90">{{ solicitud.cliente?.nombre_razon_social }}</span>
                                    <span class="text-[10px] text-white/40 truncate max-w-xs">{{ solicitud.direccion_servicio }}</span>
                                </div>
                            </td>
                             <td class="px-6 py-5">
                                <span class="text-sm font-bold text-white/60 tracking-wider">{{ solicitud.cliente?.telefono || 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="px-3 py-1 bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-lg text-[9px] font-black uppercase tracking-widest">
                                    {{ solicitud.tipo_servicio?.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-sm text-white/60">
                                {{ new Date(solicitud.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-5 text-right space-x-2">
                                <Link :href="route('citas.show', solicitud.id)" class="px-4 py-2 bg-white/[0.05] hover:bg-white/10 rounded-lg text-[9px] font-black text-white/60 hover:text-white uppercase tracking-widest transition-all">
                                    Expediente
                                </Link>
                                <Link v-if="solicitud.cliente_id" :href="route('clientes.show', solicitud.cliente_id)" class="px-4 py-2 bg-emerald-500/10 hover:bg-emerald-500/20 rounded-lg text-[9px] font-black text-emerald-400 uppercase tracking-widest transition-all">
                                    Gestionar Cliente
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="solicitudes.data.length === 0">
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-4 opacity-20">
                                    <FontAwesomeIcon icon="box-open" class="text-5xl" />
                                    <p class="text-sm font-bold text-white uppercase tracking-[0.3em]">No hay solicitudes importadas</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="solicitudes.links.length > 3" class="px-6 py-6 border-t border-white/10 bg-white/[0.01] flex justify-center">
                <Pagination :links="solicitudes.links" />
            </div>
        </div>
    </div>

    <!-- Modal de Sincronización -->
    <SyncModal 
        :show="showSyncModal" 
        @close="showSyncModal = false"
        @registered="handleRegistered"
    />
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Pagination from '@/Components/Pagination.vue';
import SyncModal from './Partials/SyncModal.vue';
import { ref } from 'vue';

const props = defineProps({
    solicitudes: Object
});

const showSyncModal = ref(false);

const handleRegistered = () => {
    // Recargar la lista principal para mostrar el cliente recién vinculado
    router.reload({ only: ['solicitudes'] });
};
</script>
