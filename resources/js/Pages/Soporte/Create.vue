<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';

import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import SimpleCategoryForm from '@/Components/Soporte/SimpleCategoryForm.vue';

const props = defineProps({
    categorias: Array,
    usuarios: Array,
});

const form = useForm({
    titulo: '',
    descripcion: '',
    prioridad: 'media',
    categoria_id: '',
    cliente_id: '',
    asignado_id: '',
    producto_id: '',
    origen: 'telefono',
    tipo_servicio: 'garantia',
    telefono_contacto: '',
    email_contacto: '',
    nombre_contacto: '',
    folio_manual: '',
    folio_externo: '',
    poliza_id: null,
});

// Popup de búsqueda de cliente
const terminoBusqueda = ref('');
const clienteEncontrado = ref(null);
const resultadosBusqueda = ref([]);
const buscando = ref(false);
const ticketsCliente = ref([]);
const polizaActiva = ref(null);
const showCategoryModal = ref(false);

const listaCategorias = ref([...props.categorias]);

const agregarCategoriaNueva = (nuevaCategoria) => {
    listaCategorias.value.push(nuevaCategoria);
    form.categoria_id = nuevaCategoria.id;
};

const buscarCliente = async () => {
    if (terminoBusqueda.value.length < 3) return;
    
    buscando.value = true;
    resultadosBusqueda.value = [];
    clienteEncontrado.value = null;
    
    try {
        const response = await fetch(route('soporte.buscar-cliente') + `?query=${encodeURIComponent(terminoBusqueda.value)}`);
        const data = await response.json();
        
        if (data.found) {
            seleccionarClienteEncontrado(data);
        } else if (data.results && data.results.length > 0) {
            resultadosBusqueda.value = data.results;
        } else {
            resultadosBusqueda.value = [];
        }
    } catch (error) {
        console.error('Error buscando cliente:', error);
    } finally {
        buscando.value = false;
    }
};

const seleccionarDeLista = async (cliente) => {
    buscando.value = true;
    try {
        const response = await fetch(route('soporte.buscar-cliente') + `?id=${cliente.id}`);
        const data = await response.json();
        if (data.found) {
            seleccionarClienteEncontrado(data);
            resultadosBusqueda.value = [];
        }
    } catch (error) {
        console.error('Error seleccionando cliente:', error);
    } finally {
        buscando.value = false;
    }
};

const seleccionarClienteEncontrado = (data) => {
    clienteEncontrado.value = data.cliente;
    ticketsCliente.value = data.tickets_recientes || [];
    
    form.cliente_id = data.cliente.id;
    form.telefono_contacto = data.cliente.celular || data.cliente.telefono; 
    form.email_contacto = data.cliente.email;
    form.nombre_contacto = data.cliente.nombre; 
    polizaActiva.value = data.poliza_activa;
    form.poliza_id = data.poliza_activa ? data.poliza_activa.id : null;
};

const limpiarCliente = () => {
    clienteEncontrado.value = null;
    resultadosBusqueda.value = [];
    ticketsCliente.value = [];
    polizaActiva.value = null;
    form.cliente_id = '';
    form.poliza_id = null;
    form.producto_id = '';
    terminoBusqueda.value = '';
};

const estaEquipadoCubierto = computed(() => {
    if (!polizaActiva.value || !form.producto_id) return false;
    return polizaActiva.value.equipos.some(e => e.id === form.producto_id);
});

watch(() => form.producto_id, (newVal) => {
    if (newVal && estaEquipadoCubierto.value) {
        form.tipo_servicio = 'garantia';
    }
});

const submit = () => {
    form.post(route('soporte.store'));
};

const prioridades = [
    { value: 'baja', label: '🟢 Baja', desc: 'SLA Extendido' },
    { value: 'media', label: '🟡 Media', desc: 'SLA Estándar' },
    { value: 'alta', label: '🟠 Alta', desc: 'SLA Prioritario' },
    { value: 'urgente', label: '🔴 Urgente', desc: 'Intervención Inmediata' },
];

const origenes = [
    { value: 'telefono', label: '📞 Teléfono' },
    { value: 'email', label: '📧 Email' },
    { value: 'whatsapp', label: '💬 WhatsApp' },
    { value: 'web', label: '🌐 Web' },
    { value: 'presencial', label: '🏢 Presencial' },
];
</script>

<template>
    <AppLayout title="Nuevo Ticket">
        <Head title="Nuevo Ticket de Soporte" />

        <div class="min-h-screen bg-[var(--ui-surface)] text-slate-800 dark:text-slate-200 py-12 px-4 sm:px-6 lg:px-8 transition-colors">
            <div class="max-w-[1400px] mx-auto">
                
                <!-- Header -->
                <div class="mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
                    <Link :href="route('soporte.index')" class="inline-flex items-center gap-2 text-[10px] font-black text-slate-400 dark:text-slate-500 hover:text-brand-600 dark:hover:text-brand-500 uppercase tracking-[0.2em] mb-8 transition-colors group">
                        <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Regresar a la Gestión de Tickets
                    </Link>
                    
                    <div class="flex items-center gap-6">
                        <div class="relative group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-brand-500 to-brand-600 rounded-2xl blur-md opacity-25 group-hover:opacity-50 transition duration-500"></div>
                            <div class="relative w-16 h-16 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 flex items-center justify-center shadow-xl dark:shadow-2xl backdrop-blur-xl">
                                <svg class="w-10 h-10 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            </div>
                        </div>
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter mb-1 uppercase">Generación de <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-500 to-brand-500 dark:from-brand-400 dark:to-orange-400">Expediente</span></h1>
                            <p class="text-slate-500 dark:text-slate-400 text-sm font-bold uppercase tracking-[0.2em] italic">Registro de nueva incidencia técnica</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-12 gap-12">
                    <!-- Left: Creation Form -->
                    <div class="xl:col-span-8">
                        <form @submit.prevent="submit" class="space-y-6 animate-in fade-in slide-in-from-bottom-8 duration-700 delay-150">
                            
                            <!-- Search & Identify Section (Scanner Style) -->
                            <div class="relative group">
                                <div class="absolute -inset-0.5 bg-gradient-to-br from-brand-500/10 via-brand-500/5 to-transparent rounded-[3rem] blur opacity-0 group-focus-within:opacity-100 transition duration-700"></div>
                                <div class="relative bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 p-10 rounded-[3rem] shadow-md dark:shadow-2xl overflow-hidden">
                                     <div class="absolute -right-20 -top-20 w-80 h-80 bg-brand-500/5 rounded-full blur-[100px]"></div>
                                     
                                     <div class="flex items-center justify-between mb-8">
                                         <h3 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-[0.4em] flex items-center gap-4">
                                             <div class="w-1 h-8 bg-brand-500"></div>
                                             Identificación Orbital
                                         </h3>
                                         <span v-if="buscando" class="flex gap-1">
                                             <div class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-bounce"></div>
                                             <div class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-bounce [animation-delay:0.2s]"></div>
                                             <div class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-bounce [animation-delay:0.4s]"></div>
                                         </span>
                                     </div>

                                     <div class="relative mb-6">
                                         <svg class="absolute left-6 top-6 w-7 h-7 text-brand-500/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                         <input
                                            v-model="terminoBusqueda"
                                            type="text"
                                            placeholder="SCANNER: NOMBRE, EMPRESA O TELÉFONO..."
                                            class="w-full pl-16 pr-24 py-6 bg-[var(--ui-surface)] dark:bg-slate-950/60 border border-slate-200 dark:border-white/5 rounded-[2rem] text-sm font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all shadow-inner"
                                            @keyup.enter="buscarCliente"
                                        />
                                        <button 
                                            type="button"
                                            @click="buscarCliente"
                                            :disabled="buscando || terminoBusqueda.length < 3"
                                            class="absolute right-3 top-3 bottom-3 px-8 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-black uppercase tracking-wide rounded-[1.5rem] transition-all disabled:opacity-20 active:scale-95 flex items-center gap-2"
                                        >
                                            {{ buscando ? 'SEARCHING' : 'SCAN' }}
                                        </button>
                                     </div>

                                     <!-- Multiple Search Results -->
                                     <div v-if="resultadosBusqueda.length > 0 && !clienteEncontrado" class="animate-in fade-in zoom-in-95 duration-500 mt-6 space-y-3">
                                         <div class="px-6 py-2 text-[9px] font-black text-brand-600 dark:text-brand-500/80 uppercase tracking-wide italic border-b border-slate-100 dark:border-white/5 mb-4">Múltiples coincidencias detectadas • {{ resultadosBusqueda.length }} resultados</div>
                                         <div 
                                            v-for="res in resultadosBusqueda" :key="res.id" 
                                            @click="seleccionarDeLista(res)"
                                            class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/40 hover:bg-slate-500/10 border border-slate-200 dark:border-white/5 hover:border-brand-500/30 rounded-2xl cursor-pointer transition-all group/item flex items-center justify-between"
                                         >
                                            <div class="flex items-center gap-6">
                                                <div class="w-10 h-10 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-xl flex items-center justify-center text-brand-600 dark:text-brand-500 font-black text-xs">{{ res.nombre?.charAt(0) }}</div>
                                                <div>
                                                    <div class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ res.nombre }}</div>
                                                    <div class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide mt-1">{{ res.email }} • {{ res.telefono }}</div>
                                                </div>
                                            </div>
                                            <span class="text-[9px] font-black text-brand-600 group-hover/item:text-brand-500 uppercase tracking-wide opacity-0 group-hover/item:opacity-100 transition-all -translate-x-4 group-hover/item:translate-x-0">Seleccionar Registro →</span>
                                         </div>
                                     </div>

                                     <!-- Identified Client -->
                                     <div v-if="clienteEncontrado" class="animate-in fade-in zoom-in-95 duration-500 bg-brand-500/5 border border-emerald-500/30 rounded-[2.5rem] p-8 relative overflow-hidden group/success">
                                         <div class="absolute -right-10 -bottom-10 text-9xl text-emerald-500 opacity-5 transform group-hover/success:rotate-12 transition-transform duration-700">🛡️</div>
                                         <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 relative z-10">
                                             <div class="flex gap-6">
                                                <div class="w-16 h-16 bg-brand-500/10 border border-emerald-500/20 rounded-2xl flex items-center justify-center text-emerald-500 dark:text-slate-400 text-2xl animate-pulse">
                                                    👤
                                                </div>
                                                <div>
                                                    <div class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wide mb-1">{{ clienteEncontrado.nombre }}</div>
                                                    <div class="flex flex-wrap items-center gap-4 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">
                                                        <span>{{ clienteEncontrado.email }}</span>
                                                        <span class="w-1 h-1 bg-slate-300 dark:bg-slate-700 rounded-full"></span>
                                                        <span>{{ clienteEncontrado.telefono || clienteEncontrado.celular }}</span>
                                                    </div>
                                                    
                                                    <!-- Policy Badge Premium -->
                                                    <div v-if="polizaActiva" class="mt-4 inline-flex items-center gap-2 px-5 py-2 rounded-full bg-emerald-600 text-white text-[9px] font-black uppercase tracking-wide border border-emerald-400/30 shadow-xl shadow-emerald-600/20">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                                        SISTEMA BAJO PÓLIZA: {{ polizaActiva.nombre }} ({{ polizaActiva.folio }})
                                                    </div>
                                                    <div v-else class="mt-4 inline-flex items-center gap-2 px-5 py-2 rounded-full bg-slate-200 dark:bg-slate-950 text-slate-500 text-[9px] font-black uppercase tracking-wide border border-slate-300 dark:border-white/5">
                                                        SIN COBERTURA ACTIVA
                                                    </div>
                                                </div>
                                             </div>
                                             <button type="button" @click="limpiarCliente" class="px-6 py-4 bg-slate-100 dark:bg-slate-950/50 hover:bg-slate-500/10 text-[9px] font-black text-rose-600 dark:text-rose-500 uppercase tracking-wide rounded-2xl border border-slate-200 dark:border-white/5 hover:border-brand-500/30 transition-all active:scale-95 flex items-center gap-2">
                                                 RESET SCAN ×
                                             </button>
                                         </div>
                                         
                                         <!-- Recent Tickets Subpanel -->
                                         <div v-if="ticketsCliente.length > 0" class="mt-8 pt-8 border-t border-emerald-500/20">
                                            <div class="text-[9px] font-black text-emerald-600 dark:text-emerald-500/60 uppercase tracking-wide mb-4 italic">EXPEDIENTES RECIENTES DEL CLIENTE</div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div v-for="t in ticketsCliente" :key="t.id" class="p-4 bg-[var(--ui-surface)] dark:bg-black/20 rounded-2xl border border-slate-100 dark:border-white/5 flex items-center justify-between group/ticket hover:bg-slate-100 dark:hover:bg-black/50 transition-all">
                                                    <div class="flex items-center gap-4">
                                                        <span class="font-mono text-[10px] text-brand-600 dark:text-amber-400">{{ t.numero }}</span>
                                                        <span class="text-[10px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider truncate max-w-[120px]">{{ t.titulo }}</span>
                                                    </div>
                                                    <Link :href="route('soporte.show', t.id)" target="_blank" class="w-10 h-10 rounded-xl bg-slate-200 dark:bg-white/5 flex items-center justify-center opacity-0 group-hover/ticket:opacity-100 transition-all text-slate-500 dark:text-white">
                                                       <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                                    </Link>
                                                </div>
                                            </div>
                                         </div>
                                     </div>
                                </div>
                            </div>

                            <!-- Main Input Artifacts -->
                            <div class="grid grid-cols-1 gap-8">
                                <!-- Title Block -->
                                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl overflow-hidden relative group">
                                     <div class="absolute -left-10 -top-10 w-40 h-40 bg-brand-500/5 rounded-full blur-[60px]"></div>
                                     <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.4em] mb-6 px-1 italic">Asunto de la Incidencia *</label>
                                     <input
                                        v-model="form.titulo"
                                        type="text"
                                        required
                                        placeholder="RESUMEN EJECUTIVO DEL PROBLEMA..."
                                        class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border-2 border-slate-200 dark:border-white/5 focus:border-brand-500/50 rounded-3xl py-6 px-8 text-xl font-black uppercase tracking-wide text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-800 transition-all shadow-inner focus:ring-2 focus:ring-brand-500/10"
                                    />
                                    <p v-if="form.errors.titulo" class="text-rose-500 text-[10px] font-black uppercase tracking-wide mt-4 px-2 italic">{{ form.errors.titulo }}</p>
                                </div>

                                <!-- Product Selection (Scoped to Identified Client) -->
                                <div v-if="clienteEncontrado" class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative overflow-hidden group animate-in slide-in-from-left-4 duration-500">
                                     <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-[60px]"></div>
                                     <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.4em] mb-6 px-1 italic">Vínculo con Artefacto / Equipo</label>
                                     <div class="relative">
                                         <select 
                                            v-model="form.producto_id" 
                                            class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border-2 border-slate-200 dark:border-white/5 rounded-3xl py-6 px-8 text-xs font-black uppercase tracking-wide text-slate-800 dark:text-white appearance-none cursor-pointer focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/10 transition-all"
                                            :class="{'border-emerald-500/50 ring-emerald-500/10': estaEquipadoCubierto}"
                                         >
                                            <option value="">SELECCIONAR EQUIPO VINCULADO (OPCIONAL)</option>
                                            <option v-for="equipo in polizaActiva?.equipos" :key="equipo.id" :value="equipo.id">
                                                🛡️ {{ equipo.nombre }} (S/N: {{ equipo.serie }}) - BAJO PÓLIZA
                                            </option>
                                        </select>
                                         <svg class="absolute right-8 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 dark:text-slate-500 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                     </div>
                                     
                                     <div v-if="estaEquipadoCubierto" class="mt-6 flex items-center gap-4 p-5 bg-brand-500/10 border border-emerald-500/30 rounded-2xl animate-pulse">
                                         <span class="text-2xl">⚡</span>
                                         <div class="text-[10px] font-black text-emerald-600 dark:text-slate-400 uppercase tracking-wide italic leading-tight">Hardware autenticado bajo póliza de servicio. Aplicando SLA prioritario de forma automática.</div>
                                     </div>
                                </div>

                                <!-- Description Artifact -->
                                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[4rem] p-10 md:p-14 shadow-md dark:shadow-2xl relative group overflow-hidden">
                                     <div class="absolute -right-20 -top-20 w-80 h-80 bg-white/5 rounded-full blur-[100px]"></div>
                                     <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.4em] mb-10 px-1 italic">Reporte Detallado *</label>
                                     <textarea
                                        v-model="form.descripcion"
                                        rows="10"
                                        required
                                        placeholder="INICIE EL REPORTE TÉCNICO DETALLADO AQUÍ..."
                                        class="w-full bg-transparent border-none focus:ring-0 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-800 text-lg md:text-xl font-medium leading-relaxed resize-none custom-scrollbar"
                                    ></textarea>
                                    <p v-if="form.errors.descripcion" class="text-rose-500 text-[10px] font-black uppercase tracking-wide mt-4 px-2 italic">{{ form.errors.descripcion }}</p>
                                </div>

                                <!-- Service Logic Artifact -->
                                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative overflow-hidden group">
                                     <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.4em] mb-10 px-1 italic">Atribución de Servicio</label>
                                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                         <button 
                                            type="button"
                                            @click="form.tipo_servicio = 'garantia'"
                                            :class="[
                                                'relative p-8 rounded-[2.5rem] border text-left transition-all duration-500 group overflow-hidden',
                                                form.tipo_servicio === 'garantia' 
                                                    ? 'bg-brand-600/10 border-brand-500 shadow-[0_20px_40px_-10px_rgba(245,158,11,0.2)] scale-[1.02]' 
                                                    : 'bg-slate-50 dark:bg-slate-950/40 border-slate-200 dark:border-white/5 grayscale hover:grayscale-0 hover:border-brand-500/30'
                                            ]"
                                        >
                                            <div class="absolute -right-6 -bottom-6 text-8xl opacity-10 transform group-hover:rotate-12 transition-transform duration-700">🛡️</div>
                                            <div class="text-xs font-black uppercase tracking-[0.2em] mb-2" :class="form.tipo_servicio === 'garantia' ? 'text-brand-600 dark:text-brand-500' : 'text-slate-400 dark:text-slate-500'">GARANTÍA / PÓLIZA</div>
                                            <p class="text-[9px] font-bold text-slate-500 dark:text-slate-500 leading-relaxed uppercase tracking-wide italic">Intervención sin costo comercial directo. Amparado bajo contrato de mantenimiento/póliza.</p>
                                        </button>
                                        <button 
                                            type="button"
                                            @click="form.tipo_servicio = 'costo'"
                                            :class="[
                                                'relative p-8 rounded-[2.5rem] border text-left transition-all duration-500 group overflow-hidden',
                                                form.tipo_servicio === 'costo' 
                                                    ? 'bg-indigo-600/10 border-indigo-500 shadow-[0_20px_40px_-10px_rgba(79,70,229,0.2)] scale-[1.02]' 
                                                    : 'bg-slate-50 dark:bg-slate-950/40 border-slate-200 dark:border-white/5 grayscale hover:grayscale-0 hover:border-brand-500/30'
                                            ]"
                                        >
                                            <div class="absolute -right-6 -bottom-6 text-8xl opacity-10 transform group-hover:rotate-12 transition-transform duration-700">💰</div>
                                            <div class="text-xs font-black uppercase tracking-[0.2em] mb-2" :class="form.tipo_servicio === 'costo' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500'">CON COSTO ADICIONAL</div>
                                            <p class="text-[9px] font-bold text-slate-500 dark:text-slate-500 leading-relaxed uppercase tracking-wide italic">Servicio extraordinario fuera de cobertura. Genera nota de venta y proceso de facturación.</p>
                                        </button>
                                     </div>
                                </div>

                                <!-- Configuration Matrix -->
                                <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative overflow-hidden group">
                                     <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.4em] mb-10 px-1 italic">Matriz de Configuración</label>
                                     <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                         <!-- Priority -->
                                         <div class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-[2rem] border border-slate-200 dark:border-white/5 group/box hover:border-brand-500/20 transition-all">
                                             <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4 block italic">Criticidad</label>
                                             <select v-model="form.prioridad" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase tracking-wide text-slate-800 dark:text-white appearance-none cursor-pointer focus:ring-2 focus:ring-brand-500/30 transition-all">
                                                <option v-for="p in prioridades" :key="p.value" :value="p.value">{{ p.label }}</option>
                                             </select>
                                         </div>
                                         <!-- Category -->
                                         <div class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-[2rem] border border-slate-200 dark:border-white/5 group/box hover:border-brand-500/20 transition-all">
                                             <div class="flex justify-between items-center mb-4">
                                                 <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide italic">Taxonomía</label>
                                                 <button type="button" @click="showCategoryModal = true" class="text-brand-600 dark:text-brand-500 hover:text-brand-500 dark:hover:text-brand-400 text-xs transition-colors">⊕ Add</button>
                                             </div>
                                             <select v-model="form.categoria_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase tracking-wide text-slate-800 dark:text-white appearance-none cursor-pointer focus:ring-2 focus:ring-brand-500/30 transition-all">
                                                <option value="">SIN CATEGORÍA</option>
                                                <option v-for="c in listaCategorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                                             </select>
                                         </div>
                                         <!-- Origin -->
                                         <div class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-[2rem] border border-slate-200 dark:border-white/5 group/box hover:border-brand-500/20 transition-all">
                                             <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4 block italic">Fuente / Origen</label>
                                             <select v-model="form.origen" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase tracking-wide text-slate-800 dark:text-white appearance-none cursor-pointer focus:ring-2 focus:ring-brand-500/30 transition-all">
                                                <option v-for="o in origenes" :key="o.value" :value="o.value">{{ o.label }}</option>
                                             </select>
                                         </div>
                                         <!-- Assignments -->
                                         <div class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-[2rem] border border-slate-200 dark:border-white/5 group/box hover:border-brand-500/20 transition-all lg:col-span-2">
                                             <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4 block italic">Asignar Unidad de Respuesta</label>
                                             <select v-model="form.asignado_id" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase tracking-wide text-slate-800 dark:text-white appearance-none cursor-pointer focus:ring-2 focus:ring-brand-500/30 transition-all">
                                                <option value="">AUTO-DERIVACIÓN / SIN ASIGNAR</option>
                                                <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                                             </select>
                                         </div>
                                          <!-- Folio Externo (cliente) -->
                                          <div class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-[2rem] border border-slate-200 dark:border-white/5 group/box hover:border-brand-500/20 transition-all">
                                              <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4 block italic">Folio del Cliente</label>
                                              <input v-model="form.folio_externo" type="text" placeholder="Ej: 131245537 (opcional)" class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase tracking-wide text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-800 transition-all" />
                                          </div>
                                          <!-- Folio Manual -->
                                          <div class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/50 rounded-[2rem] border border-slate-200 dark:border-white/5 group/box hover:border-brand-500/20 transition-all">
                                              <label class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4 block italic">Folio Físico Ext.</label>
                                              <input v-model="form.folio_manual" type="text" placeholder="ID EXTERNO..." class="w-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/5 rounded-2xl py-4 px-6 text-[10px] font-black uppercase tracking-wide text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-800 transition-all" />
                                          </div>
                                     </div>
                                </div>

                                <!-- Contact Information (If NO client found) -->
                                <div v-if="!clienteEncontrado" class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative overflow-hidden group animate-in zoom-in-95 duration-500">
                                     <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.4em] mb-10 px-1 italic">Datos de Contacto Directo</label>
                                     <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                         <div class="space-y-3">
                                             <label class="text-[9px] font-black text-slate-500 dark:text-slate-500 uppercase tracking-wide px-1">Nombre Completo</label>
                                             <input v-model="form.nombre_contacto" type="text" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border border-slate-200 dark:border-white/5 rounded-2xl py-5 px-8 text-sm font-black uppercase tracking-wide text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-800 focus:border-brand-500/50 transition-all shadow-inner" />
                                         </div>
                                         <div class="space-y-3">
                                             <label class="text-[9px] font-black text-slate-500 dark:text-slate-500 uppercase tracking-wide px-1">Correo Electrónico</label>
                                             <input v-model="form.email_contacto" type="email" class="w-full bg-[var(--ui-surface)] dark:bg-slate-950/60 border border-slate-200 dark:border-white/5 rounded-2xl py-5 px-8 text-sm font-black uppercase tracking-wide text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-800 focus:border-brand-500/50 transition-all shadow-inner" />
                                         </div>
                                     </div>
                                </div>
                            </div>

                            <!-- Final Actions -->
                            <div class="flex flex-col sm:flex-row justify-end gap-6 pt-12 border-t border-slate-200 dark:border-white/5">
                                <Link :href="route('soporte.index')" class="px-12 py-6 text-[10px] font-black text-slate-400 dark:text-slate-500 hover:text-slate-800 dark:hover:text-white uppercase tracking-wide transition-all">
                                    Abortar Registro
                                </Link>
                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-16 py-6 bg-brand-600 hover:bg-brand-700 text-white text-[10px] font-black uppercase tracking-[0.3em] rounded-[2rem] transition-all shadow-[0_20px_50px_-10px_rgba(245,158,11,0.3)] flex items-center justify-center gap-4 active:scale-95 disabled:opacity-30 disabled:cursor-not-allowed group/submit"
                                >
                                    <svg v-if="form.processing" class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span v-else class="flex items-center gap-4">
                                        {{ form.processing ? 'SYNCHRONIZING...' : 'INICIALIZAR EXPEDIENTE' }}
                                        <svg class="w-4 h-4 transform group-submit:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Right Sidebar: Helpers -->
                    <div class="xl:col-span-4 space-y-6 animate-in fade-in slide-in-from-right-8 duration-700 delay-300">
                        
                        <!-- Intelligence Card -->
                        <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative group overflow-hidden">
                             <div class="absolute -left-10 -top-10 w-40 h-40 bg-brand-500/5 rounded-full blur-[60px]"></div>
                             <h3 class="text-[10px] font-black text-slate-800 dark:text-white uppercase tracking-[0.4em] mb-10 flex items-center gap-4">
                                <span class="text-brand-500 text-lg">💡</span>
                                Guía Operativa
                             </h3>
                             <ul class="space-y-6">
                                <li v-for="tip in [
                                    { t: 'VALIDACIÓN DE IDENTIDAD', d: 'Utilice el scanner por teléfono para una detección instantánea del cliente y sus pólizas.' },
                                    { t: 'ESTRATIFICACIÓN SLA', d: 'Asegúrese de seleccionar el nivel de prioridad correcto para garantizar los tiempos de respuesta orbital.' },
                                    { t: 'RIQUEZA DE DATOS', d: 'Incluya números de serie y fotos en los comentarios posteriores para una resolución acelerada.' }
                                ]" :key="tip.t" class="p-6 bg-[var(--ui-surface)] dark:bg-slate-950/40 border border-slate-200 dark:border-white/5 rounded-3xl group/tip hover:border-brand-500/20 transition-all duration-500">
                                    <div class="text-[9px] font-black text-brand-600 dark:text-brand-500 uppercase tracking-wide mb-2 group-hover/tip:translate-x-1 transition-transform">{{ tip.t }}</div>
                                    <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide italic leading-relaxed group-hover/tip:text-slate-500 dark:group-hover:text-slate-400 transition-colors">{{ tip.d }}</div>
                                </li>
                             </ul>
                        </div>

                        <!-- SLA Matrix Status -->
                        <div class="bg-white dark:bg-slate-800/50 backdrop-blur-xl border border-slate-200 dark:border-white/5 rounded-[3rem] p-10 shadow-md dark:shadow-2xl relative group overflow-hidden">
                             <h3 class="text-[10px] font-black text-slate-800 dark:text-white uppercase tracking-[0.4em] mb-10 flex items-center gap-4">
                                <span class="text-brand-500 text-lg">⏱️</span>
                                SLA por Categoría
                             </h3>
                             <div class="space-y-2">
                                <div v-for="c in categorias" :key="c.id" class="flex justify-between items-center p-4 bg-[var(--ui-surface)] dark:bg-slate-950/20 hover:bg-slate-100 dark:hover:bg-slate-950 border border-slate-200 dark:border-white/5 rounded-2xl transition-all group/row">
                                    <span class="text-[10px] font-black text-slate-500 dark:text-slate-500 uppercase tracking-wide group-hover/row:text-slate-700 dark:group-hover/row:text-slate-200 transition-colors">{{ c.nombre }}</span>
                                    <span class="text-[11px] font-mono font-black text-brand-600 dark:text-brand-500">{{ c.sla_horas }}H</span>
                                </div>
                             </div>
                             <div class="mt-8 pt-8 border-t border-slate-100 dark:border-white/5">
                                 <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] italic text-center leading-loose">Todos los tiempos están sujetos a la zona horaria del sistema de detección.</p>
                             </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Category Management Modal Premium -->
        <Modal :show="showCategoryModal" @close="showCategoryModal = false" maxWidth="md">
            <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-white/10 rounded-[3rem] shadow-[0_50px_100px_-20px_rgba(0,0,0,0.5)] overflow-hidden">
                <div class="p-10 bg-white dark:bg-slate-800">
                    <SimpleCategoryForm 
                        @close="showCategoryModal = false" 
                        @created="agregarCategoriaNueva"
                    />
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.1);
}
</style>
