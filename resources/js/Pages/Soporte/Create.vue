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
    tipo_servicio: 'garantia', // <<< NUEVO
    telefono_contacto: '',
    email_contacto: '',
    nombre_contacto: '',
    folio_manual: '',
    poliza_id: null,
});

// Popup de búsqueda de cliente
const terminoBusqueda = ref('');
const clienteEncontrado = ref(null);
const resultadosBusqueda = ref([]); // Para múltiples resultados
const buscando = ref(false);
const ticketsCliente = ref([]);
const polizaActiva = ref(null);
const showCategoryModal = ref(false);

const listaCategorias = ref([...props.categorias]);

const agregarCategoriaNueva = (nuevaCategoria) => {
    listaCategorias.value.push(nuevaCategoria);
    form.categoria_id = nuevaCategoria.id; // Seleccionar automáticamente
};

const buscarCliente = async () => {
    if (terminoBusqueda.value.length < 3) return;
    
    buscando.value = true;
    resultadosBusqueda.value = [];
    clienteEncontrado.value = null; // Resetear selección al buscar
    
    try {
        const response = await fetch(route('soporte.buscar-cliente') + `?query=${encodeURIComponent(terminoBusqueda.value)}`);
        const data = await response.json();
        
        if (data.found) {
            // Resultado único exacto
            seleccionarClienteEncontrado(data);
        } else if (data.results && data.results.length > 0) {
            // Múltiples resultados
            resultadosBusqueda.value = data.results;
        } else {
            // Nada encontrado
            resultadosBusqueda.value = [];
            // Opcional: mostrar mensaje "no encontrado"
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
            resultadosBusqueda.value = []; // Limpiar lista
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
    // Preferir celular si existe, sino telefono
    form.telefono_contacto = data.cliente.celular || data.cliente.telefono; 
    form.email_contacto = data.cliente.email;
    form.nombre_contacto = data.cliente.nombre; // Ahora viene nombre_razon_social del backend
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
    { value: 'baja', label: 'Baja', desc: 'Puede esperar', color: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' },
    { value: 'media', label: 'Media', desc: 'Normal', color: 'bg-blue-500/20 text-blue-400 border-blue-500/30' },
    { value: 'alta', label: 'Alta', desc: 'Importante', color: 'bg-orange-500/20 text-orange-400 border-orange-500/30' },
    { value: 'urgente', label: 'Urgente', desc: 'Crítico', color: 'bg-red-500/20 text-red-400 border-red-500/30' },
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

        <div class="min-h-screen bg-[#0F172A] text-slate-300 pb-12">
            <!-- Hero Header Section -->
            <div class="relative overflow-hidden bg-slate-900/50 border-b border-slate-800 pt-8 pb-12 mb-8">
                <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-96 h-96 bg-blue-600/10 blur-[100px] rounded-full"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <Link :href="route('soporte.index')" class="px-2 py-0.5 bg-slate-800 text-slate-400 hover:text-white text-[10px] font-black uppercase tracking-widest rounded-md border border-slate-700 hover:border-slate-500 transition-colors">
                                    ← Volver al Listado
                                </Link>
                                <span class="text-slate-500">•</span>
                                <span class="text-xs text-slate-400 font-medium">Mesa de Ayuda</span>
                            </div>
                            <h1 class="text-4xl font-black text-white tracking-tighter">
                                Nuevo <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-400">Ticket</span>
                            </h1>
                            <p class="text-slate-400 mt-2 font-medium">Registra una nueva solicitud de servicio o incidencia técnica.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Formulario principal -->
                    <div class="lg:col-span-2 space-y-8">
                        
                        <!-- Panel de Búsqueda de Cliente -->
                        <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl p-6 shadow-xl relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-purple-500/5 pointer-events-none"></div>
                            
                            <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                                <span class="p-2 bg-indigo-500/20 text-indigo-400 rounded-lg">🔍</span>
                                Identificación del Cliente
                            </h3>

                            <div class="flex gap-3 mb-6">
                                <input
                                    v-model="terminoBusqueda"
                                    type="text"
                                    placeholder="Buscar por Nombre, Empresa o Teléfono..."
                                    class="flex-1 bg-slate-900/50 border-slate-700 rounded-xl text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all font-medium"
                                    @keyup.enter="buscarCliente"
                                />
                                <button 
                                    type="button"
                                    @click="buscarCliente"
                                    :disabled="buscando"
                                    class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold shadow-lg shadow-indigo-900/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed border border-indigo-400/20"
                                >
                                    {{ buscando ? '...' : 'Buscar' }}
                                </button>
                            </div>

                            <!-- Resultados de Búsqueda -->
                            <div v-if="resultadosBusqueda.length > 0 && !clienteEncontrado" class="bg-slate-900/80 rounded-xl border border-slate-700 overflow-hidden mb-6">
                                <div class="px-4 py-2 bg-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider border-b border-slate-700">
                                    Resultados encontrados ({{ resultadosBusqueda.length }})
                                </div>
                                <ul class="divide-y divide-slate-700/50">
                                    <li v-for="res in resultadosBusqueda" :key="res.id" 
                                        @click="seleccionarDeLista(res)"
                                        class="px-4 py-3 hover:bg-slate-800/50 cursor-pointer flex justify-between items-center transition-colors group/item"
                                    >
                                        <div>
                                            <div class="font-bold text-slate-200 group-hover/item:text-indigo-400 transition-colors">{{ res.nombre }}</div>
                                            <div class="text-xs text-slate-500 font-mono">{{ res.email }} • {{ res.telefono }}</div>
                                        </div>
                                        <span class="text-indigo-500 text-xs font-bold uppercase tracking-widest opacity-0 group-hover/item:opacity-100 transition-opacity">Seleccionar →</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Cliente Seleccionado -->
                            <div v-if="clienteEncontrado" class="bg-slate-900/60 rounded-2xl p-5 border border-emerald-500/30 relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-4 opacity-50">
                                    <div class="w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl"></div>
                                </div>
                                
                                <div class="flex justify-between items-start relative z-10">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-indigo-900/30">
                                            {{ clienteEncontrado.nombre.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="text-lg font-black text-white leading-tight">{{ clienteEncontrado.nombre }}</div>
                                            <div class="text-sm text-slate-400 mb-1 flex items-center gap-2">
                                                <span>{{ clienteEncontrado.email }}</span>
                                                <span v-if="clienteEncontrado.telefono" class="w-1 h-1 bg-slate-600 rounded-full"></span>
                                                <span v-if="clienteEncontrado.telefono" class="font-mono text-xs">{{ clienteEncontrado.telefono }}</span>
                                            </div>
                                            
                                            <!-- Badge de Póliza -->
                                            <div v-if="polizaActiva" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mt-2 shadow-sm shadow-emerald-900/10">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                Póliza Activa: {{ polizaActiva.nombre }} <span class="font-mono opacity-70">({{ polizaActiva.folio }})</span>
                                            </div>
                                            <div v-else class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-400 border border-slate-700 mt-2">
                                                Sin Póliza Activa
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="limpiarCliente" class="p-2 text-slate-500 hover:text-white bg-slate-800/50 hover:bg-red-500/20 rounded-lg transition-all">
                                        ✕
                                    </button>
                                </div>
                                
                                <!-- Tickets Recientes -->
                                <div v-if="ticketsCliente.length > 0" class="mt-6 pt-4 border-t border-slate-700/50">
                                    <div class="text-[10px] uppercase font-black text-slate-500 tracking-widest mb-3">Historial Reciente</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <Link v-for="t in ticketsCliente.slice(0,4)" :key="t.id" 
                                            :href="route('soporte.show', t.id)" target="_blank"
                                            class="flex items-center justify-between p-2 rounded-lg bg-slate-800/50 hover:bg-slate-700/50 border border-slate-700/50 transition-colors text-xs group/ticket"
                                        >
                                            <div class="flex items-center gap-2 truncate">
                                                <span class="font-mono font-bold text-indigo-400">{{ t.numero }}</span>
                                                <span class="text-slate-300 truncate">{{ t.titulo }}</span>
                                            </div>
                                            <span :class="['px-1.5 py-0.5 rounded text-[9px] font-bold uppercase ml-2 select-none', t.estado === 'abierto' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400']">
                                                {{ t.estado }}
                                            </span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Formulario de Detalles -->
                        <form @submit.prevent="submit" class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl p-8 shadow-xl space-y-8">
                            
                            <!-- Titulo y Descripcion -->
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Asunto del Ticket *</label>
                                    <input
                                        v-model="form.titulo"
                                        type="text"
                                        required
                                        placeholder="Ej: Falla en servidor de archivos"
                                        class="w-full bg-slate-900 border-slate-700 rounded-xl text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-indigo-500/20 shadow-inner"
                                    />
                                    <p class="text-red-400 text-xs mt-1" v-if="form.errors.titulo">{{ form.errors.titulo }}</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Detalles del Problema *</label>
                                    <textarea
                                        v-model="form.descripcion"
                                        rows="5"
                                        required
                                        placeholder="Describe paso a paso lo que sucede..."
                                        class="w-full bg-slate-900 border-slate-700 rounded-xl text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-indigo-500/20 shadow-inner resize-none"
                                    ></textarea>
                                    <p class="text-red-400 text-xs mt-1" v-if="form.errors.descripcion">{{ form.errors.descripcion }}</p>
                                </div>
                            </div>

                            <!-- Selector de Equipo (Condicional) -->
                            <div v-if="clienteEncontrado && polizaActiva?.equipos?.length > 0" class="p-5 bg-slate-900/50 rounded-2xl border border-slate-700/50">
                                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Equipo Relacionado (Opcional)</label>
                                <select 
                                    v-model="form.producto_id" 
                                    class="w-full bg-slate-800 border-slate-700 rounded-xl text-white focus:border-indigo-500 focus:ring-indigo-500/20"
                                    :class="{'border-emerald-500/50 ring-1 ring-emerald-500/20': estaEquipadoCubierto}"
                                >
                                    <option value="">-- Seleccionar Equipo --</option>
                                    <option v-for="equipo in polizaActiva.equipos" :key="equipo.id" :value="equipo.id">
                                        📱 {{ equipo.nombre }} [S/N: {{ equipo.serie }}]
                                    </option>
                                </select>
                                
                                <div v-if="estaEquipadoCubierto" class="mt-3 flex items-center gap-2 text-xs font-bold text-emerald-400 bg-emerald-500/10 p-3 rounded-xl border border-emerald-500/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Equipo cubierto por póliza. Se aplicará garantía de servicio automáticamente.
                                </div>
                            </div>

                            <!-- Clasificación del Ticket -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Tipo de Servicio</label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button type="button" @click="form.tipo_servicio = 'garantia'" 
                                            :class="['p-3 rounded-xl border text-left transition-all', form.tipo_servicio === 'garantia' ? 'bg-indigo-500/20 border-indigo-500 text-indigo-400' : 'bg-slate-900 border-slate-700 text-slate-500 hover:border-slate-600']">
                                            <div class="font-bold text-sm">🛡️ Garantía</div>
                                            <div class="text-[10px] opacity-70">Sin costo (Póliza)</div>
                                        </button>
                                        <button type="button" @click="form.tipo_servicio = 'costo'" 
                                            :class="['p-3 rounded-xl border text-left transition-all', form.tipo_servicio === 'costo' ? 'bg-amber-500/20 border-amber-500 text-amber-400' : 'bg-slate-900 border-slate-700 text-slate-500 hover:border-slate-600']">
                                            <div class="font-bold text-sm">💰 Con Costo</div>
                                            <div class="text-[10px] opacity-70">Facturable</div>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Prioridad</label>
                                    <div class="grid grid-cols-4 gap-2">
                                        <button v-for="p in prioridades" :key="p.value" type="button"
                                            @click="form.prioridad = p.value"
                                            :class="['p-2 rounded-lg text-center border transition-all', form.prioridad === p.value ? p.color : 'bg-slate-900 border-slate-700 text-slate-500 hover:border-slate-600']">
                                            <div class="font-bold text-xs">{{ p.label }}</div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Categoría</label>
                                    <div class="flex gap-2">
                                        <select v-model="form.categoria_id" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                                            <option value="">-- General --</option>
                                            <option v-for="c in listaCategorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                                        </select>
                                        <button type="button" @click="showCategoryModal = true" class="px-3 bg-slate-800 text-slate-400 hover:text-white rounded-xl border border-slate-700 hover:bg-slate-700 transition-colors">
                                            +
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Origen</label>
                                    <select v-model="form.origen" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                                        <option v-for="o in origenes" :key="o.value" :value="o.value">{{ o.label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Asignar A</label>
                                    <select v-model="form.asignado_id" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white focus:border-indigo-500 focus:ring-indigo-500/20 text-sm">
                                        <option value="">-- Sin Asignar --</option>
                                        <option v-for="u in usuarios" :key="u.id" :value="u.id">{{ u.name }}</option>
                                    </select>
                                </div>
                            </div>

                             <!-- Contacto Manual (si no hay cliente) -->
                             <div v-if="!clienteEncontrado" class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-slate-800">
                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Nombre Contacto</label>
                                    <input v-model="form.nombre_contacto" type="text" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Email / Teléfono</label>
                                    <input v-model="form.email_contacto" type="text" class="w-full bg-slate-900 border-slate-700 rounded-xl text-white text-sm" />
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-800">
                                <Link :href="route('soporte.index')" class="px-6 py-3 text-slate-400 hover:text-white font-bold text-xs uppercase tracking-widest transition-colors">
                                    Cancelar
                                </Link>
                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-8 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-500 hover:to-red-500 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-orange-900/20 transition-all transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ form.processing ? 'Registrando...' : 'Crear Ticket' }}
                                </button>
                            </div>

                        </form>
                    </div>

                    <!-- Sidebar Info -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-3xl p-6 border border-slate-700/50 shadow-xl">
                            <h3 class="font-bold text-white mb-4 flex items-center gap-2">
                                <span class="text-xl">💡</span> Tips de Servicio
                            </h3>
                            <ul class="space-y-4">
                                <li class="flex gap-3 text-sm text-slate-400">
                                    <span class="text-indigo-500 font-bold">•</span>
                                    <span>Usa el <strong class="text-white">buscador de teléfonos</strong> para encontrar clientes mucho más rápido.</span>
                                </li>
                                <li class="flex gap-3 text-sm text-slate-400">
                                    <span class="text-indigo-500 font-bold">•</span>
                                    <span>Clasifica correctamente la <strong class="text-white">prioridad</strong> para no afectar los tiempos de respuesta (SLA).</span>
                                </li>
                                <li class="flex gap-3 text-sm text-slate-400">
                                    <span class="text-indigo-500 font-bold">•</span>
                                    <span>Verifica siempre si el equipo tiene <strong class="text-emerald-400">garantía vigente</strong> antes de cotizar.</span>
                                </li>
                            </ul>
                        </div>

                        <div class="bg-slate-900/50 rounded-3xl p-6 border border-slate-800">
                            <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-4">Tiempos de Respuesta (SLA)</h3>
                            <div class="space-y-3">
                                <div v-for="c in categorias.slice(0,5)" :key="c.id" class="flex justify-between items-center text-sm">
                                    <span class="text-slate-300">{{ c.nombre }}</span>
                                    <span class="font-mono font-bold text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded text-xs">{{ c.sla_horas }}h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Gestión de Categorías Simplificado -->
        <Modal :show="showCategoryModal" @close="showCategoryModal = false" maxWidth="md">
            <SimpleCategoryForm 
                @close="showCategoryModal = false" 
                @created="agregarCategoriaNueva"
            />
        </Modal>
    </AppLayout>
</template>
