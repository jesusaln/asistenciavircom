<template>
  <div class="relative buscar-cliente-container">
    <!-- Campo de búsqueda -->
    <div v-if="!clienteSeleccionado" class="buscar-cliente">
      <div class="mb-4">
        <label class="block text-xs font-black text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase tracking-widest mb-2 transition-colors">
          {{ labelBusqueda }}
          <span v-if="requerido" class="text-red-500">*</span>
        </label>
        <div class="relative group">
          <input
            ref="inputBusqueda"
            type="text"
            v-model="busquedaCliente"
            @input="filtrarClientes"
            @focus="mostrarListaClientes = true"
            @blur="ocultarListaConRetraso"
            @keydown="manejarTeclas"
            :placeholder="placeholderBusqueda"
            class="w-full pl-11 pr-11 py-3 bg-white dark:bg-slate-950 border rounded-2xl shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm transition-all duration-300 text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-gray-500"
            :class="{
              'border-red-300 dark:border-red-900/50 ring-red-50 dark:ring-red-900/10': errorBusqueda || (requerido && validacionError),
              'border-gray-200 dark:border-slate-800 dark:border-slate-800': !errorBusqueda && !(requerido && validacionError)
            }"
            autocomplete="new-password"
            :disabled="deshabilitado"
          />
          <!-- Icono de búsqueda -->
          <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-500 text-gray-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <!-- Indicador de carga / limpiar -->
          <div class="absolute inset-y-0 right-4 flex items-center">
            <button
              v-if="busquedaCliente && !cargandoBusqueda"
              @click="limpiarBusqueda"
              type="button"
              class="text-gray-400 hover:text-red-500 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200"
              title="Limpiar búsqueda"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
            <div v-else-if="cargandoBusqueda" class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
          </div>
        </div>

        <!-- Mensajes de error y ayuda -->
        <div v-if="errorBusqueda || (requerido && validacionError)" class="mt-2 text-[11px] font-bold text-red-600 dark:text-red-400 flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
          {{ errorBusqueda || validacionError }}
        </div>
        <div v-else-if="mensajeAyuda && !busquedaCliente" class="mt-2 text-[10px] text-gray-500 dark:text-gray-400 dark:text-gray-500 dark:text-gray-400 italic">
          {{ mensajeAyuda }}
        </div>

        <!-- Filtros rápidos -->
        <div v-if="mostrarFiltrosRapidos && !clienteSeleccionado" class="mt-3 flex flex-wrap gap-2">
          <button
            v-for="filtro in filtrosRapidos"
            :key="filtro.value"
            @click="aplicarFiltroRapido(filtro)"
            class="inline-flex items-center px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all duration-200 shadow-sm border"
            :class="filtroActivo === filtro.value
              ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-500/20'
              : 'bg-white dark:bg-slate-900/50 text-gray-600 dark:text-slate-400 border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-slate-800'"
          >
            <component :is="filtro.icon" class="w-3 h-3 mr-1.5" v-if="filtro.icon"/>
            {{ filtro.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Información del cliente seleccionado -->
    <div v-if="clienteSeleccionado" class="mt-2 group p-6 bg-white dark:bg-slate-900/40 border border-gray-100 dark:border-slate-800 rounded-2xl shadow-xl shadow-black/5 transition-all hover:shadow-2xl">
      <div class="flex items-start justify-between mb-6">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100 transition-colors">
              {{ clienteSeleccionado.nombre_razon_social }}
            </h3>
            <div class="flex items-center gap-2 mt-1">
              <span class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest">{{ tituloClienteSeleccionado }}</span>
              <div v-if="mostrarEstadoCliente" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-gray-50 dark:bg-slate-950 border border-gray-100 dark:border-slate-800">
                <div class="w-1.5 h-1.5 rounded-full" :class="clienteSeleccionado.activo !== false ? 'bg-green-500' : 'bg-red-500'"></div>
                <span class="text-[9px] font-black uppercase text-gray-500 dark:text-slate-400">
                  {{ clienteSeleccionado.activo === false ? 'Inactivo' : 'Activo' }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="flex items-center bg-gray-50 dark:bg-gray-900/50 p-1.5 rounded-2xl border border-gray-100 dark:border-slate-800 dark:border-gray-700 gap-1">
          <button v-if="mostrarHistorial" @click="verHistorial" type="button" class="w-9 h-9 flex items-center justify-center text-blue-500 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-xl transition-all" title="Historial">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </button>
          <button v-if="mostrarEditar" @click="editarCliente" type="button" class="w-9 h-9 flex items-center justify-center text-green-500 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-xl transition-all" title="Editar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </button>
          <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-1"></div>
          <button type="button" @click="limpiarCliente" class="w-9 h-9 flex items-center justify-center text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-xl transition-all" title="Cambiar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- Información básica del cliente -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="space-y-1" v-if="clienteSeleccionado.email">
          <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest">Email</p>
          <div class="flex items-center gap-2 group/info">
            <div class="w-7 h-7 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center text-gray-400 group-hover/info:text-blue-500 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ clienteSeleccionado.email }}</span>
          </div>
        </div>

        <div class="space-y-1" v-if="clienteSeleccionado.telefono">
          <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest">Teléfono</p>
          <div class="flex items-center gap-2 group/info">
            <div class="w-7 h-7 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center text-gray-400 group-hover/info:text-green-500 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ clienteSeleccionado.telefono }}</span>
          </div>
        </div>

        <div class="space-y-1" v-if="clienteSeleccionado.rfc">
          <p class="text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">RFC</p>
          <div class="flex items-center gap-2 group/info">
            <div class="w-7 h-7 rounded-lg bg-gray-50 dark:bg-slate-950 flex items-center justify-center text-gray-400 group-hover/info:text-blue-500 transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <span class="text-sm font-bold text-gray-700 dark:text-slate-300 font-mono">{{ clienteSeleccionado.rfc }}</span>
          </div>
        </div>

        <!-- Información de crédito -->
        <div class="lg:col-span-3 mt-4" v-if="clienteSeleccionado.credito_activo && clienteSeleccionado.limite_credito && mostrarInfoComercial">
           <div class="p-4 bg-gray-50/50 dark:bg-gray-900/30 rounded-2xl border border-gray-100 dark:border-slate-800 dark:border-gray-700">
             <div class="flex items-center justify-between mb-3">
               <div class="flex items-center gap-2">
                 <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                 <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Resumen de Crédito</span>
               </div>
               <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ Math.round(((clienteSeleccionado.credito_disponible ?? clienteSeleccionado.limite_credito) / clienteSeleccionado.limite_credito) * 100) }}% Disponible</span>
             </div>
             
             <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden mb-3">
               <div class="h-full bg-blue-600 transition-all duration-500" 
                    :style="{ width: `${((clienteSeleccionado.credito_disponible ?? clienteSeleccionado.limite_credito) / clienteSeleccionado.limite_credito) * 100}%` }"></div>
             </div>

             <div class="grid grid-cols-2 gap-4">
               <div>
                  <p class="text-[9px] font-black text-gray-400 uppercase mb-1">Disponible</p>
                  <p class="text-lg font-black text-gray-900 dark:text-white dark:text-white" :class="{
                    'text-red-600': (clienteSeleccionado.credito_disponible ?? clienteSeleccionado.limite_credito) <= 0
                  }">{{ formatearMoneda(clienteSeleccionado.credito_disponible ?? clienteSeleccionado.limite_credito) }}</p>
               </div>
               <div class="text-right">
                  <p class="text-[9px] font-black text-gray-400 uppercase mb-1">Límite Total</p>
                  <p class="text-lg font-black text-gray-700 dark:text-gray-300">{{ formatearMoneda(clienteSeleccionado.limite_credito) }}</p>
               </div>
             </div>
           </div>
        </div>
      </div>

      <!-- Alertas del cliente -->
      <div v-if="alertasCliente.length > 0" class="mt-6 space-y-2">
        <div
          v-for="alerta in alertasCliente"
          :key="alerta.id"
          class="flex items-center p-3.5 rounded-2xl text-xs font-bold uppercase tracking-wider border shadow-sm transition-all animate-in fade-in slide-in-from-top-1"
          :class="{
            'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-100 dark:border-red-900/30': alerta.tipo === 'error',
            'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-yellow-100 dark:border-yellow-900/30': alerta.tipo === 'warning',
            'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-100 dark:border-blue-900/30': alerta.tipo === 'info'
          }"
        >
          <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
          {{ alerta.mensaje }}
        </div>
      </div>
    </div>

    <!-- Estado vacío mejorado -->
    <div v-else class="mt-2 p-10 border-2 border-dashed border-gray-200 dark:border-slate-800 dark:border-gray-700 rounded-3xl text-center bg-gray-50/30 dark:bg-gray-900/20 transition-colors">
      <div class="w-16 h-16 bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 dark:border-gray-700 flex items-center justify-center mx-auto mb-4 text-gray-400">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
      </div>
      <p class="text-gray-900 dark:text-white dark:text-white text-lg font-black">{{ mensajeVacio }}</p>
      <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm mt-1 font-medium">{{ submensajeVacio }}</p>

      <div v-if="mostrarAccionRapida && busquedaCliente" class="mt-6">
        <button
          @click="crearNuevoCliente"
          class="inline-flex items-center px-6 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl hover:bg-blue-700 transition-all shadow-lg shadow-blue-200 dark:shadow-none active:scale-95"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          Crear Nuevo Cliente
        </button>
      </div>
    </div>

    <!-- Lista de clientes filtrados usando Teleport -->
    <Teleport to="#app">
      <div
        ref="listaClientesRef"
        v-if="mostrarListaClientes && clientesFiltrados.length > 0"
        class="z-[100] mt-1 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-2xl shadow-2xl shadow-black/50 max-h-[50vh] overflow-hidden flex flex-col transition-all animate-in fade-in zoom-in-95"
        :style="{
          position: 'fixed',
          width: inputWidth + 'px',
          top: (inputPosition.top + inputPosition.height + 4) + 'px',
          left: inputPosition.left + 'px'
        }"
      >
        <div class="px-4 py-2.5 border-b border-gray-50 dark:border-slate-800/60 bg-gray-50/50 dark:bg-slate-900">
            <span class="text-[9px] font-black text-gray-400 dark:text-slate-500 uppercase tracking-widest">
                {{ clientesFiltrados.length }} {{ clientesFiltrados.length === 15 ? 'Primeros resultados' : 'Resultados encontrados' }}
            </span>
            <span v-if="tiempoRespuesta" class="text-[9px] font-bold text-gray-300 dark:text-slate-600">{{ tiempoRespuesta }}ms</span>
        </div>
        
        <div class="overflow-y-auto custom-scrollbar flex-1">
          <div
            v-for="(cliente, index) in clientesFiltrados"
            :key="cliente.id"
            @mousedown.prevent="seleccionarCliente(cliente)"
            @mouseenter="clienteSeleccionadoIndex = index"
            class="px-4 py-3.5 hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer border-b border-gray-50 dark:border-gray-700 last:border-b-0 transition-all flex items-center gap-3"
            :class="{ 'bg-blue-50 dark:bg-blue-900/20 ring-inset ring-2 ring-blue-500/10': clienteSeleccionadoIndex === index }"
          >
            <div
              class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-gray-400 text-xs font-bold transition-all border border-transparent"
              :class="{'bg-indigo-600/20 text-indigo-400 border-indigo-500/30 scale-110': clienteSeleccionadoIndex === index}"
            >
              {{ cliente.nombre_razon_social.substring(0, 2).toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="font-bold text-gray-900 dark:text-white dark:text-white text-sm truncate flex items-center gap-2">
                <span v-html="resaltarTexto(cliente.nombre_razon_social, busquedaCliente)"></span>
                <div v-if="cliente.estado" class="w-1.5 h-1.5 rounded-full" :class="{ 'bg-green-500': cliente.estado === 'activo', 'bg-yellow-500': cliente.estado === 'suspendido', 'bg-red-500': cliente.estado === 'inactivo' }"></div>
              </div>
              <div class="text-[11px] text-gray-500 dark:text-gray-400 dark:text-gray-400 flex flex-wrap gap-x-3 gap-y-1 mt-0.5 font-medium">
                <span v-if="cliente.email" class="flex items-center gap-1"><span v-html="resaltarTexto(cliente.email, busquedaCliente)"></span></span>
                <span v-if="cliente.telefono" class="flex items-center gap-1">| <span v-html="resaltarTexto(cliente.telefono, busquedaCliente)"></span></span>
                <span v-if="cliente.rfc" class="flex items-center gap-1 font-mono text-[10px]">| <span v-html="resaltarTexto(cliente.rfc, busquedaCliente)"></span></span>
              </div>
            </div>
          </div>
        </div>

        <div v-if="busquedaCliente && mostrarOpcionNuevoCliente" class="p-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-slate-800 dark:border-gray-700 flex items-center justify-center">
          <button @click="crearNuevoCliente" class="w-full py-2.5 px-4 bg-white dark:bg-slate-900 dark:bg-gray-800 border border-blue-100 dark:border-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-black uppercase tracking-widest rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all flex items-center justify-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Crear "{{ busquedaCliente }}"
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  clientes: { type: Array, required: true, default: () => [] },
  clienteSeleccionado: { type: Object, default: null },
  mostrarOpcionNuevoCliente: { type: Boolean, default: true },
  labelBusqueda: { type: String, default: 'Buscar Cliente' },
  placeholderBusqueda: { type: String, default: 'Escribe para buscar clientes...' },
  requerido: { type: Boolean, default: false },
  iconoBusqueda: { type: Boolean, default: true },
  deshabilitado: { type: Boolean, default: false },
  mensajeAyuda: { type: String, default: '' },
  tipoDocumento: { type: String, default: 'Cliente' },
  tituloClienteSeleccionado: { type: String, default: 'Cliente Seleccionado' },
  mensajeVacio: { type: String, default: 'No hay cliente seleccionado' },
  submensajeVacio: { type: String, default: 'Busca y selecciona un cliente para continuar' },
  mostrarAccionRapida: { type: Boolean, default: true },
  mostrarEstadoCliente: { type: Boolean, default: true },
  mostrarHistorial: { type: Boolean, default: false },
  mostrarEditar: { type: Boolean, default: false },
  mostrarInfoComercial: { type: Boolean, default: true },
  mostrarFiltrosRapidos: { type: Boolean, default: false },
  filtrosRapidos: { type: Array, default: () => [] }
});

const emit = defineEmits(['cliente-seleccionado', 'crear-nuevo-cliente', 'ver-historial', 'editar-cliente']);

const inputBusqueda = ref(null);
const listaClientesRef = ref(null);
const busquedaCliente = ref('');
const busquedaTermino = ref('');
const mostrarListaClientes = ref(false);
const clienteSeleccionadoIndex = ref(-1);
const cargandoBusqueda = ref(false);
const errorBusqueda = ref('');
const validacionError = ref('');
const timeoutId = ref(null);
const debounceTimeout = ref(null);
const inputWidth = ref(0);
const inputPosition = ref({ top: 0, left: 0, height: 0 });
const filtroActivo = ref(null);
const tiempoRespuesta = ref(null);

const normalizarTexto = (texto) => {
  if (!texto) return '';
  return texto.toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^\w\s@.-]/g, '').trim();
};

const dividirTerminos = (termino) => {
  return termino.split(/\s+/).filter(t => t.length > 0).map(t => normalizarTexto(t));
};

const clientesFiltrados = computed(() => {
  if (!busquedaTermino.value || busquedaTermino.value.length < 1) return [];
  const tiempoInicio = performance.now();
  const terminos = dividirTerminos(busquedaTermino.value);
  if (terminos.length === 0) return [];

  const resultados = props.clientes.filter(cliente => {
    const textoCombinado = normalizarTexto([cliente.nombre_razon_social, cliente.email, cliente.telefono, cliente.rfc, cliente.empresa, cliente.calle].join(' '));
    return terminos.every(termino => textoCombinado.includes(termino));
  });

  const resultadosConScore = resultados.map(cliente => {
    let score = 0;
    const nombre = normalizarTexto(cliente.nombre_razon_social || '');
    terminos.forEach(termino => {
      if (nombre.startsWith(termino)) score += 100;
      else if (nombre.includes(termino)) score += 50;
      else if (normalizarTexto(cliente.email || '').includes(termino)) score += 30;
      else if (normalizarTexto(cliente.rfc || '').includes(termino)) score += 40;
    });
    return { ...cliente, _score: score };
  }).sort((a, b) => b._score - a._score);

  tiempoRespuesta.value = Math.round(performance.now() - tiempoInicio);
  // Limitar a los primeros 15 resultados para mantener el rendimiento premium
  return resultadosConScore.slice(0, 15);
});

const alertasCliente = computed(() => {
  if (!props.clienteSeleccionado) return [];
  const alertas = [];
  const cliente = props.clienteSeleccionado;
  if (cliente.credito_activo) {
    const creditoDisponible = cliente.credito_disponible ?? (cliente.limite_credito - (cliente.saldo_pendiente || 0));
    if (creditoDisponible <= 0) alertas.push({ id: 'credito-agotado', tipo: 'error', mensaje: 'Crédito agotado - límite alcanzado' });
    else if (creditoDisponible < (cliente.limite_credito * 0.2)) alertas.push({ id: 'credito-bajo', tipo: 'warning', mensaje: `Crédito limitado: ${formatearMoneda(creditoDisponible)} disponible` });
  }
  if (cliente.estado === 'inactivo') alertas.push({ id: 'cliente-inactivo', tipo: 'error', mensaje: 'Cliente inactivo' });
  else if (cliente.estado === 'suspendido') alertas.push({ id: 'cliente-suspendido', tipo: 'warning', mensaje: 'Cliente suspendido' });
  return alertas;
});

const filtrarClientes = () => {
  errorBusqueda.value = '';
  validacionError.value = '';
  if (debounceTimeout.value) clearTimeout(debounceTimeout.value);
  debounceTimeout.value = setTimeout(() => {
    cargandoBusqueda.value = true;
    busquedaTermino.value = busquedaCliente.value;
    setTimeout(() => { cargandoBusqueda.value = false; }, 200);
    mostrarListaClientes.value = true;
    clienteSeleccionadoIndex.value = -1;
  }, 300);
};

const seleccionarCliente = (cliente) => {
  if (timeoutId.value) clearTimeout(timeoutId.value);
  emit('cliente-seleccionado', cliente);
  mostrarListaClientes.value = false;
};

const limpiarCliente = () => {
  emit('cliente-seleccionado', null);
  limpiarBusqueda();
  nextTick(() => { inputBusqueda.value?.focus(); });
};

const ocultarListaConRetraso = () => {
  timeoutId.value = setTimeout(() => { mostrarListaClientes.value = false; }, 150);
};

const manejarTeclas = (event) => {
  if (!mostrarListaClientes.value || clientesFiltrados.value.length === 0) return;
  const maxIndex = clientesFiltrados.value.length - 1;
  switch (event.key) {
    case 'ArrowDown': event.preventDefault(); clienteSeleccionadoIndex.value = Math.min(clienteSeleccionadoIndex.value + 1, maxIndex); break;
    case 'ArrowUp': event.preventDefault(); clienteSeleccionadoIndex.value = Math.max(clienteSeleccionadoIndex.value - 1, -1); break;
    case 'Enter': event.preventDefault(); if (clienteSeleccionadoIndex.value >= 0) seleccionarCliente(clientesFiltrados.value[clienteSeleccionadoIndex.value]); break;
    case 'Escape': mostrarListaClientes.value = false; break;
  }
};

const resaltarTexto = (texto, termino) => {
  if (!texto || !termino) return texto || '';
  const terminos = dividirTerminos(termino);
  if (terminos.length === 0) return texto;
  let textoResultado = texto.toString();
  terminos.forEach(t => {
    if (t.length > 0) {
      const regex = new RegExp(`(${t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
      textoResultado = textoResultado.replace(regex, '<mark class="resaltar-text">$1</mark>');
    }
  });
  return textoResultado;
};

const crearNuevoCliente = () => {
  mostrarListaClientes.value = false;
  emit('crear-nuevo-cliente', busquedaCliente.value);
};

const limpiarBusqueda = () => {
  busquedaCliente.value = '';
  busquedaTermino.value = '';
  mostrarListaClientes.value = false;
  clienteSeleccionadoIndex.value = -1;
};

const verHistorial = () => emit('ver-historial', props.clienteSeleccionado);
const editarCliente = () => emit('editar-cliente', props.clienteSeleccionado);
const aplicarFiltroRapido = (filtro) => {
  filtroActivo.value = filtroActivo.value === filtro.value ? null : filtro.value;
  busquedaCliente.value = filtro.termino || '';
  filtrarClientes();
};

const formatearMoneda = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0);

const actualizarPosicionLista = () => {
  if (!inputBusqueda.value) return;
  const rect = inputBusqueda.value.getBoundingClientRect();
  inputWidth.value = rect.width;
  inputPosition.value = { top: rect.top, left: rect.left, height: rect.height };
};

watch(() => props.clienteSeleccionado, (nuevo) => {
  if (nuevo) { busquedaCliente.value = nuevo.nombre_razon_social || ''; mostrarListaClientes.value = false; }
  else { limpiarBusqueda(); }
}, { immediate: true });

watch(mostrarListaClientes, (v) => { if (v) nextTick(actualizarPosicionLista); });

onMounted(() => {
  window.addEventListener('resize', actualizarPosicionLista);
  window.addEventListener('scroll', actualizarPosicionLista);
});

onUnmounted(() => {
  window.removeEventListener('resize', actualizarPosicionLista);
  window.removeEventListener('scroll', actualizarPosicionLista);
  if (timeoutId.value) clearTimeout(timeoutId.value);
  if (debounceTimeout.value) clearTimeout(debounceTimeout.value);
});

defineExpose({ limpiarBusqueda, focus: () => nextTick(() => inputBusqueda.value?.focus()) });
</script>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }

.resaltar-text {
  background-color: transparent;
  color: #3b82f6;
  font-weight: 800;
  padding: 0;
}

.animate-in { animation-duration: 0.3s; animation-fill-mode: both; }
@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes zoom-in-95 { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.fade-in { animation-name: fade-in; }
.zoom-in-95 { animation-name: zoom-in-95; }
</style>
