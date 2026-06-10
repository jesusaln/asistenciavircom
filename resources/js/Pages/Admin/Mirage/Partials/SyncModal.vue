<template>
    <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md" @click="$emit('close')"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-5xl bg-[#0f0f0f] border border-white/10 rounded-[2.5rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="p-8 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                <div>
                    <h3 class="text-xl font-black text-white uppercase tracking-wider flex items-center gap-3">
                        <FontAwesomeIcon icon="sync" :class="{'animate-spin': processing}" class="text-red-500" />
                        Sincronizar con Mirage
                    </h3>
                    <p class="text-[10px] font-bold text-white/40 uppercase tracking-[0.2em] mt-1">
                        {{ processing ? 'El robot está trabajando en el portal de Mirage...' : 'Solicitudes pendientes detectadas en el portal' }}
                    </p>
                </div>
                <button @click="$emit('close')" class="w-10 h-10 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center text-white/40 hover:text-white transition-all">
                    <FontAwesomeIcon icon="times" />
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <!-- Loading State -->
                <div v-if="processing" class="py-20 flex flex-col items-center justify-center gap-6">
                    <div class="relative">
                        <div class="w-24 h-24 border-4 border-red-600/20 border-t-red-600 rounded-full animate-spin"></div>
                        <FontAwesomeIcon icon="robot" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-2xl text-red-600 animate-pulse" />
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-black text-white uppercase tracking-[0.3em]">Accediendo al Portal</p>
                        <p class="text-[10px] text-white/40 font-bold uppercase mt-2 italic">Esto puede tomar unos segundos mientras validamos sesiones...</p>
                    </div>
                </div>

                <!-- Empty/Initial State -->
                <div v-else-if="solicitudes.length === 0" class="py-20 flex flex-col items-center justify-center gap-4 opacity-20">
                    <FontAwesomeIcon icon="clipboard-list" class="text-6xl" />
                    <p class="text-sm font-bold text-white uppercase tracking-[0.3em]">No hay resultados pendientes</p>
                    <button @click="iniciarSincronizacion" class="mt-4 px-8 py-3 bg-red-600 hover:bg-red-500 rounded-xl text-[10px] font-black uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-[0_0_20px_rgba(220,38,38,0.3)]">
                        Iniciar Búsqueda
                    </button>
                </div>

                <!-- Results Table -->
                <div v-else class="space-y-6">
                    <div class="bg-white/[0.03] border border-white/10 rounded-3xl overflow-hidden">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white/[0.02]">
                                    <th class="px-6 py-4 text-[10px] font-black text-white/40 uppercase tracking-widest">Folio</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-white/40 uppercase tracking-widest">Cliente Mirage</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-white/40 uppercase tracking-widest">Fecha</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black text-white/40 uppercase tracking-widest">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/[0.03]">
                                <tr v-for="solicitud in solicitudes" :key="solicitud.mirage_id" class="group hover:bg-white/[0.02] transition-colors">
                                    <td class="px-6 py-5">
                                        <div class="text-xs font-black text-white tracking-wider">{{ solicitud.folio }}</div>
                                        <div class="text-[9px] text-emerald-400 font-bold uppercase mt-1">{{ solicitud.tipo }}</div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="text-sm font-bold text-white/90">{{ solicitud.cliente_nombre }}</div>
                                        <div class="text-[10px] text-white/40">{{ solicitud.telefono }}</div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="text-xs font-bold text-white/60">{{ solicitud.fecha }}</div>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <button @click="verDetalles(solicitud)" class="px-4 py-2 bg-red-600 hover:bg-red-500 rounded-lg text-[9px] font-black text-white uppercase tracking-widest transition-all shadow-lg shadow-red-600/20">
                                            Ver y Registrar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Sub-Modal -->
        <div v-if="selectedSolicitud" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="selectedSolicitud = null"></div>
            <div class="relative w-full max-w-lg bg-[#141414] border border-white/20 rounded-[2rem] p-10 shadow-2xl">
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <span class="px-3 py-1 bg-red-600/10 text-red-500 border border-red-600/20 rounded-lg text-[8px] font-black uppercase tracking-widest">Detalles del Cliente</span>
                        <h4 class="text-2xl font-black text-white mt-3 uppercase tracking-tighter">{{ selectedSolicitud.cliente_nombre }}</h4>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-white/30 uppercase tracking-widest">Folio Mirage</p>
                        <p class="text-lg font-black text-red-500 tracking-wider">{{ selectedSolicitud.folio }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-8 mb-12">
                    <div class="space-y-1">
                        <p class="text-[9px] font-black text-white/20 uppercase tracking-[0.2em]">Teléfono</p>
                        <p class="text-sm font-bold text-white">{{ selectedSolicitud.telefono }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[9px] font-black text-white/20 uppercase tracking-[0.2em]">Fecha</p>
                        <p class="text-sm font-bold text-white">{{ selectedSolicitud.fecha }}</p>
                    </div>
                    <div class="col-span-2 space-y-1">
                        <p class="text-[9px] font-black text-white/20 uppercase tracking-[0.2em]">Dirección Extraída</p>
                        <p class="text-xs font-bold text-white/60 leading-relaxed">{{ selectedSolicitud.direccion }}</p>
                    </div>
                </div>

                <div class="flex gap-3 pt-6 border-t border-white/5">
                    <button @click="selectedSolicitud = null" class="flex-1 px-6 py-4 bg-white/5 hover:bg-white/10 rounded-xl text-[10px] font-black text-white/40 hover:text-white uppercase tracking-widest transition-all">
                        Cancelar
                    </button>
                    <button @click="registrarCliente" :disabled="registering" class="flex-2 px-10 py-4 bg-red-600 hover:bg-red-500 disabled:opacity-50 rounded-xl text-[10px] font-black text-white uppercase tracking-widest transition-all shadow-xl shadow-red-600/20">
                        {{ registering ? 'Registrando...' : 'Confirmar Alta' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import axios from 'axios';
import Swal from 'sweetalert2';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean
});

const emit = defineEmits(['close', 'registered']);

const processing = ref(false);
const registering = ref(false);
const solicitudes = ref([]);
const selectedSolicitud = ref(null);

const iniciarSincronizacion = async () => {
    processing.value = true;
    solicitudes.value = [];
    try {
        const response = await axios.post(route('mirage.sync'));
        if (response.data.success) {
            solicitudes.value = response.data.data;
            if (solicitudes.value.length === 0) {
                Swal.fire({
                    title: 'Sin pendientes',
                    text: 'No hay nuevas solicitudes en Mirage.',
                    icon: 'info',
                    background: '#1a1a1a', color: '#fff', confirmButtonColor: '#e11d48'
                });
            }
        } else {
            Swal.fire({
                title: 'Aviso',
                text: response.data.message,
                icon: 'warning',
                background: '#1a1a1a', color: '#fff', confirmButtonColor: '#e11d48'
            });
        }
    } catch (e) {
        Swal.fire({
            title: 'Error',
            text: 'Hubo un problema con la conexión.',
            icon: 'error',
            background: '#1a1a1a', color: '#fff', confirmButtonColor: '#e11d48'
        });
    } finally {
        processing.value = false;
    }
};

const verDetalles = (sol) => {
    selectedSolicitud.value = sol;
};

const registrarCliente = async () => {
    registering.value = true;
    try {
        const response = await axios.post(route('mirage.store-client'), {
            folio: selectedSolicitud.value.folio,
            nombre: selectedSolicitud.value.cliente_nombre,
            telefono: selectedSolicitud.value.telefono,
            direccion: selectedSolicitud.value.direccion
        });

        if (response.data.success) {
            Swal.fire({
                title: '¡Registrado!',
                text: 'Redirigiendo al expediente del cliente...',
                icon: 'success',
                timer: 1000,
                background: '#1a1a1a', color: '#fff', showConfirmButton: false
            });
            
            selectedSolicitud.value = null;
            emit('close');
            
            // Redirigir al detalle del cliente
            router.visit(route('clientes.show', response.data.cliente_id));
        }
    } catch (e) {
        Swal.fire({
            title: 'Error',
            text: 'No se pudo completar el registro.',
            icon: 'error',
            background: '#1a1a1a', color: '#fff'
        });
    } finally {
        registering.value = false;
    }
};

onMounted(() => {
    if (props.show) {
        iniciarSincronizacion();
    }
});
</script>
