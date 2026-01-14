<template>
  <div class="relative">
    <!-- Campo de búsqueda -->
    <div v-if="!clienteSeleccionado" class="buscar-cliente">
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          {{ labelBusqueda }}
          <span v-if="requerido" class="text-red-500">*</span>
        </label>
        <div class="relative">
          <input
            ref="inputBusqueda"
            type="text"
            v-model="busquedaCliente"
            @input="filtrarClientes"
            @focus="mostrarListaClientes = true"
            @blur="ocultarListaConRetraso"
            @keydown="manejarTeclas"
            :placeholder="placeholderBusqueda"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm transition-all duration-200"
            :class="{
              'border-red-300 focus:ring-red-500 focus:border-red-500': errorBusqueda || (requerido && validacionError),
              'pl-10': iconoBusqueda
            }"
            autocomplete="new-password"
            :disabled="deshabilitado"
          />
          <!-- Icono de búsqueda -->
          <div v-if="iconoBusqueda" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <!-- Indicador de carga / limpiar -->
          <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
            <button
              v-if="busquedaCliente && !cargandoBusqueda"
              @click="limpiarBusqueda"
              type="button"
              class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100 transition-colors duration-200"
              title="Limpiar búsqueda"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
            <div v-else-if="cargandoBusqueda" class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
            <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
        </div>

        <!-- Mensajes de error y ayuda -->
        <div v-if="errorBusqueda || (requerido && validacionError)" class="mt-2 text-sm text-red-600">
          {{ errorBusqueda || validacionError }}
        </div>
        <div v-else-if="mensajeAyuda && !busquedaCliente" class="mt-2 text-sm text-gray-500">
          {{ mensajeAyuda }}
        </div>
        <div v-if="busquedaCliente && !mostrarListaClientes && clientesFiltrados.length === 0 && !cargandoBusqueda" class="mt-2 text-sm text-gray-500">
          No se encontraron clientes que coincidan con "{{ busquedaCliente }}"
        </div>

        <!-- Filtros rápidos -->
        <div v-if="mostrarFiltrosRapidos && !clienteSeleccionado" class="mt-3 flex flex-wrap gap-2">
          <button
            v-for="filtro in filtrosRapidos"
            :key="filtro.value"
            @click="aplicarFiltroRapido(filtro)"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors duration-200"
            :class="filtroActivo === filtro.value
              ? 'bg-blue-100 text-blue-800 border border-blue-300'
              : 'bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200'"
          >
            <component :is="filtro.icon" class="w-3 h-3 mr-1" v-if="filtro.icon"/>
            {{ filtro.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Información del cliente seleccionado -->
    <div v-if="clienteSeleccionado" class="mt-6 p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-lg shadow-sm">
      <div class="flex items-start justify-between mb-4">
        <h3 class="text-lg font-semibold text-blue-900 flex items-center">
          <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ tituloClienteSeleccionado }}
        </h3>
        <div class="flex items-center space-x-2">
          <!-- Indicador de estado del cliente -->
          <div v-if="mostrarEstadoCliente" class="flex items-center">
            <div
              class="w-2 h-2 rounded-full mr-2"
              :class="{
                'bg-green-500': clienteSeleccionado.activo !== false,
                'bg-red-500': clienteSeleccionado.activo === false
              }"
            ></div>
            <span class="text-xs font-medium text-gray-600 capitalize">
              {{ clienteSeleccionado.activo === false ? 'Inactivo' : 'Activo' }}
            </span>
          </div>

          <!-- Botón de historial -->
          <button
            v-if="mostrarHistorial"
            @click="verHistorial"
            type="button"
            class="text-blue-500 hover:text-blue-700 hover:bg-blue-100 p-1 rounded-full transition-colors duration-200"
            title="Ver historial"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </button>

          <!-- Botón de editar -->
          <button
            v-if="mostrarEditar"
            @click="editarCliente"
            type="button"
            class="text-green-500 hover:text-green-700 hover:bg-green-100 p-1 rounded-full transition-colors duration-200"
            title="Editar cliente"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>

          <!-- Botón cambiar cliente -->
          <button
            type="button"
            @click="limpiarCliente"
            class="text-red-500 hover:text-red-700 hover:bg-red-100 p-1 rounded-full transition-colors duration-200"
            :title="`Cambiar ${tipoDocumento.toLowerCase()}`"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Información básica del cliente -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="space-y-2">
          <div class="flex items-center text-sm font-medium text-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Nombre
          </div>
          <div class="text-lg font-semibold text-gray-900">{{ clienteSeleccionado.nombre_razon_social }}</div>
        </div>

        <div class="space-y-2" v-if="clienteSeleccionado.email">
          <div class="flex items-center text-sm font-medium text-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Email
          </div>
          <div class="text-gray-900">{{ clienteSeleccionado.email }}</div>
        </div>

        <div class="space-y-2" v-if="clienteSeleccionado.telefono">
          <div class="flex items-center text-sm font-medium text-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            Teléfono
          </div>
          <div class="text-gray-900">{{ clienteSeleccionado.telefono }}</div>
        </div>

        <div class="space-y-2" v-if="clienteSeleccionado.rfc">
          <div class="flex items-center text-sm font-medium text-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            RFC
          </div>
          <div class="text-gray-900 font-mono">{{ clienteSeleccionado.rfc }}</div>
        </div>

        <!-- Información de crédito (consolidada) -->
        <div class="space-y-2" v-if="clienteSeleccionado.credito_activo && clienteSeleccionado.limite_credito && mostrarInfoComercial">
          <div class="flex items-center text-sm font-medium text-blue-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
            </svg>
            Crédito
          </div>
          <div class="flex items-center gap-2">
            <div class="text-center">
              <div class="font-semibold" :class="{
                'text-red-600': (clienteSeleccionado.credito_disponible ?? clienteSeleccionado.limite_credito) <= 0,
                'text-yellow-600': (clienteSeleccionado.credito_disponible ?? clienteSeleccionado.limite_credito) > 0 && (clienteSeleccionado.credito_disponible ?? clienteSeleccionado.limite_credito) < (clienteSeleccionado.limite_credito * 0.2),
                'text-green-600': (clienteSeleccionado.credito_disponible ?? clienteSeleccionado.limite_credito) >= (clienteSeleccionado.limite_credito * 0.2)
              }">
                {{ formatearMoneda(clienteSeleccionado.credito_disponible ?? clienteSeleccionado.limite_credito) }}
              </div>
              <div class="text-xs text-gray-500">Disponible</div>
            </div>
            <span class="text-gray-400">/</span>
            <div class="text-center">
              <div class="font-semibold text-gray-700">
                {{ formatearMoneda(clienteSeleccionado.limite_credito) }}
              </div>
              <div class="text-xs text-gray-500">Límite</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alertas del cliente -->
      <div v-if="alertasCliente.length > 0" class="mt-4 space-y-2">
        <div
          v-for="alerta in alertasCliente"
          :key="alerta.id"
          class="flex items-center p-3 rounded-lg text-sm"
          :class="{
            'bg-red-50 text-red-800 border border-red-200': alerta.tipo === 'error',
            'bg-yellow-50 text-yellow-800 border border-yellow-200': alerta.tipo === 'warning',
            'bg-blue-50 text-blue-800 border border-blue-200': alerta.tipo === 'info'
          }"
        >
          <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
          {{ alerta.mensaje }}
        </div>
      </div>
    </div>

    <!-- Estado vacío mejorado -->
    <div v-else class="mt-6 p-6 border-2 border-dashed border-gray-300 rounded-lg text-center">
      <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
      </svg>
      <p class="text-gray-500 text-lg font-medium">{{ mensajeVacio }}</p>
      <p class="text-gray-400 text-sm mt-1">{{ submensajeVacio }}</p>

      <!-- Botón de acción rápida -->
      <div v-if="mostrarAccionRapida && busquedaCliente" class="mt-4">
        <button
          @click="crearNuevoCliente"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200"
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
        class="z-50 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-[60vh] overflow-y-auto"
        :style="{
          position: 'absolute',
          width: inputWidth + 'px',
          top: inputPosition.top + inputPosition.height + 'px',
          left: inputPosition.left + 'px'
        }"
      >
        <div class="p-2">
          <div class="text-xs text-gray-500 mb-2 px-2 flex items-center justify-between">
            <span>{{ clientesFiltrados.length }} cliente{{ clientesFiltrados.length !== 1 ? 's' : '' }} encontrado{{ clientesFiltrados.length !== 1 ? 's' : '' }}</span>
            <span v-if="tiempoRespuesta" class="text-gray-400">({{ tiempoRespuesta }}ms)</span>
          </div>
        </div>
        <div
          v-for="(cliente, index) in clientesFiltrados"
          :key="cliente.id"
          @click="seleccionarCliente(cliente)"
          @mouseenter="clienteSeleccionadoIndex = index"
          class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 transition-colors duration-150"
          :class="{ 'bg-blue-50': clienteSeleccionadoIndex === index }"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="font-medium text-gray-900 mb-1 flex items-center">
                <span v-html="resaltarTexto(cliente.nombre_razon_social, busquedaCliente)"></span>
                <!-- Indicadores de estado -->
                <div class="ml-2 flex items-center space-x-1">
                  <div v-if="cliente.estado"
                       class="w-2 h-2 rounded-full"
                       :class="{
                         'bg-green-500': cliente.estado === 'activo',
                         'bg-yellow-500': cliente.estado === 'suspendido',
                         'bg-red-500': cliente.estado === 'inactivo'
                       }"
                       :title="cliente.estado">
                  </div>
                  <svg v-if="cliente.credito_disponible <= 0" class="w-3 h-3 text-red-500" fill="currentColor" viewBox="0 0 20 20" title="Sin crédito disponible">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"/>
                  </svg>
                </div>
              </div>
              <div class="text-sm text-gray-500 space-y-1">
                <div v-if="cliente.email" class="flex items-center" v-html="resaltarTexto(cliente.email, busquedaCliente)"></div>
                <div v-if="cliente.telefono" class="flex items-center" v-html="resaltarTexto(cliente.telefono, busquedaCliente)"></div>
                <div v-if="cliente.rfc" class="flex items-center font-mono text-xs" v-html="resaltarTexto(cliente.rfc, busquedaCliente)"></div>
                <!-- Información comercial en la lista -->
                <div v-if="cliente.limite_credito && mostrarInfoComercial" class="text-xs text-green-600">
                  Crédito: {{ formatearMoneda(cliente.credito_disponible || cliente.limite_credito) }}
                </div>
              </div>
            </div>
            <div v-if="cliente.empresa" class="ml-3">
              <div class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded-full whitespace-nowrap" v-html="resaltarTexto(cliente.empresa, busquedaCliente)"></div>
            </div>
          </div>
        </div>

        <!-- Opción para crear nuevo cliente -->
        <div v-if="busquedaCliente && mostrarOpcionNuevoCliente" class="border-t border-gray-200 p-3 bg-gray-50">
          <button
            @click="crearNuevoCliente"
            class="w-full text-left px-3 py-2 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors duration-150 flex items-center"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Crear nuevo cliente: "{{ busquedaCliente }}"
          </button>
        </div>
      </div>

      <!-- Lista vacía -->
      <div
        ref="listaVaciaRef"
        v-if="mostrarListaClientes && clientesFiltrados.length === 0 && busquedaTermino"
        class="z-50 mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-4"
        :style="{
          position: 'absolute',
          width: inputWidth + 'px',
          top: inputPosition.top + inputPosition.height + 'px',
          left: inputPosition.left + 'px'
        }"
      >
        <div class="text-center text-gray-500">
          <p class="text-sm font-medium mb-1">No se encontraron clientes</p>
          <p class="text-xs text-gray-400 mb-3">Intenta con otro término de búsqueda</p>
          <button
            @click="crearNuevoCliente"
            class="inline-flex items-center px-3 py-2 text-sm text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors duration-150"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Crear nuevo cliente
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>
<script setup>

import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

// Props del componente
const props = defineProps({
  clientes: {
    type: Array,
    required: true,
    default: () => []
  },
  clienteSeleccionado: {
    type: Object,
    default: null
  },
  mostrarOpcionNuevoCliente: {
    type: Boolean,
    default: true
  },
  labelBusqueda: {
    type: String,
    default: 'Buscar Cliente'
  },
  placeholderBusqueda: {
    type: String,
    default: 'Escribe para buscar clientes...'
  },
  requerido: {
    type: Boolean,
    default: false
  },
  iconoBusqueda: {
    type: Boolean,
    default: true
  },
  deshabilitado: {
    type: Boolean,
    default: false
  },
  mensajeAyuda: {
    type: String,
    default: ''
  },
  tipoDocumento: {
    type: String,
    default: 'Cliente'
  },
  tituloClienteSeleccionado: {
    type: String,
    default: 'Cliente Seleccionado'
  },
  mensajeVacio: {
    type: String,
    default: 'No hay cliente seleccionado'
  },
  submensajeVacio: {
    type: String,
    default: 'Busca y selecciona un cliente para continuar'
  },
  mostrarAccionRapida: {
    type: Boolean,
    default: true
  },
  mostrarEstadoCliente: {
    type: Boolean,
    default: true
  },
  mostrarHistorial: {
    type: Boolean,
    default: false
  },
  mostrarEditar: {
    type: Boolean,
    default: false
  },
  mostrarInfoComercial: {
    type: Boolean,
    default: true
  },
  mostrarFiltrosRapidos: {
    type: Boolean,
    default: false
  },
  filtrosRapidos: {
    type: Array,
    default: () => []
  }
});

// Eventos que emite el componente
const emit = defineEmits([
  'cliente-seleccionado',
  'crear-nuevo-cliente',
  'ver-historial',
  'editar-cliente'
]);

// Referencias reactivas
const inputBusqueda = ref(null);
const listaClientesRef = ref(null);
const listaVaciaRef = ref(null);
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

// Función para normalizar texto (quitar acentos y caracteres especiales)
const normalizarTexto = (texto) => {
  if (!texto) return '';
  return texto.toString()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^\w\s@.-]/g, '')
    .trim();
};

// Función para dividir términos de búsqueda
const dividirTerminos = (termino) => {
  return termino.split(/\s+/)
    .filter(t => t.length > 0)
    .map(t => normalizarTexto(t));
};

// Computed para clientes filtrados
const clientesFiltrados = computed(() => {
  if (!busquedaTermino.value || busquedaTermino.value.length < 1) {
    return [];
  }

  const tiempoInicio = performance.now();
  const terminos = dividirTerminos(busquedaTermino.value);

  if (terminos.length === 0) return [];

  const resultados = props.clientes.filter(cliente => {
    const textoCombinado = normalizarTexto([
      cliente.nombre_razon_social,
      cliente.email,
      cliente.telefono,
      cliente.rfc,
      cliente.empresa,
      cliente.calle
    ].join(' '));

    return terminos.every(termino => textoCombinado.includes(termino));
  });

  // Calcular score para ordenamiento
  const resultadosConScore = resultados.map(cliente => {
    let score = 0;
    const nombre = normalizarTexto(cliente.nombre_razon_social || '');

    terminos.forEach(termino => {
      if (nombre.startsWith(termino)) score += 100;
      else if (nombre.includes(termino)) score += 50;
      else if (normalizarTexto(cliente.email || '').includes(termino)) score += 30;
      else if (normalizarTexto(cliente.rfc || '').includes(termino)) score += 40;
      else if (normalizarTexto(cliente.telefono || '').includes(termino)) score += 20;
    });

    return { ...cliente, _score: score };
  }).sort((a, b) => b._score - a._score);

  const tiempoFin = performance.now();
  tiempoRespuesta.value = Math.round(tiempoFin - tiempoInicio);

  return resultadosConScore;
});

// Computed para alertas del cliente
const alertasCliente = computed(() => {
  if (!props.clienteSeleccionado) return [];

  const alertas = [];
  const cliente = props.clienteSeleccionado;

  // Alerta de crédito - solo mostrar si el cliente tiene crédito habilitado
  if (cliente.credito_activo) {
    // Cliente tiene crédito habilitado, verificar disponibilidad
    const creditoDisponible = cliente.credito_disponible ?? (cliente.limite_credito - (cliente.saldo_pendiente || 0));
    
    if (creditoDisponible <= 0) {
      alertas.push({
        id: 'credito-agotado',
        tipo: 'error',
        mensaje: 'Crédito agotado - límite alcanzado'
      });
    } else if (creditoDisponible < (cliente.limite_credito * 0.2)) {
      alertas.push({
        id: 'credito-bajo',
        tipo: 'warning',
        mensaje: `Crédito limitado: ${formatearMoneda(creditoDisponible)} disponible`
      });
    }
  }

  // Alerta de estado inactivo
  if (cliente.estado === 'inactivo') {
    alertas.push({
      id: 'cliente-inactivo',
      tipo: 'error',
      mensaje: 'Cliente inactivo'
    });
  } else if (cliente.estado === 'suspendido') {
    alertas.push({
      id: 'cliente-suspendido',
      tipo: 'warning',
      mensaje: 'Cliente suspendido'
    });
  }

  return alertas;
});

// Función para filtrar clientes con debounce
const filtrarClientes = () => {
  errorBusqueda.value = '';
  validacionError.value = '';

  if (debounceTimeout.value) {
    clearTimeout(debounceTimeout.value);
  }

  debounceTimeout.value = setTimeout(() => {
    cargandoBusqueda.value = true;
    busquedaTermino.value = busquedaCliente.value;

    setTimeout(() => {
      cargandoBusqueda.value = false;
    }, 200);

    mostrarListaClientes.value = true;
    clienteSeleccionadoIndex.value = -1;
  }, 300);
};

// Función para seleccionar cliente
const seleccionarCliente = (cliente) => {
  if (timeoutId.value) {
    clearTimeout(timeoutId.value);
  }

  emit('cliente-seleccionado', cliente);
  mostrarListaClientes.value = false;
};

// Función para limpiar cliente seleccionado
const limpiarCliente = () => {
  emit('cliente-seleccionado', null);
  limpiarBusqueda();
  nextTick(() => {
    inputBusqueda.value?.focus();
  });
};

// Función para ocultar lista con retraso
const ocultarListaConRetraso = () => {
  timeoutId.value = setTimeout(() => {
    mostrarListaClientes.value = false;
  }, 150);
};

// Función para manejar teclas de navegación
const manejarTeclas = (event) => {
  if (!mostrarListaClientes.value || clientesFiltrados.value.length === 0) {
    return;
  }

  const maxIndex = clientesFiltrados.value.length - 1;

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault();
      clienteSeleccionadoIndex.value = Math.min(clienteSeleccionadoIndex.value + 1, maxIndex);
      break;

    case 'ArrowUp':
      event.preventDefault();
      clienteSeleccionadoIndex.value = Math.max(clienteSeleccionadoIndex.value - 1, -1);
      break;

    case 'Enter':
      event.preventDefault();
      if (clienteSeleccionadoIndex.value >= 0) {
        seleccionarCliente(clientesFiltrados.value[clienteSeleccionadoIndex.value]);
      }
      break;

    case 'Escape':
      mostrarListaClientes.value = false;
      break;
  }
};

// Función para resaltar texto en resultados
const resaltarTexto = (texto, termino) => {
  if (!texto || !termino) return texto || '';

  const terminos = dividirTerminos(termino);
  if (terminos.length === 0) return texto;

  let textoResultado = texto.toString();

  terminos.forEach(t => {
    if (t.length > 0) {
      const escapedTerm = t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const regex = new RegExp(`(${escapedTerm})`, 'gi');
      textoResultado = textoResultado.replace(regex, '<mark class="resaltar">$1</mark>');
    }
  });

  return textoResultado;
};

// Función para crear nuevo cliente
const crearNuevoCliente = () => {
  mostrarListaClientes.value = false;
  emit('crear-nuevo-cliente', busquedaCliente.value);
};

// Función para limpiar búsqueda
const limpiarBusqueda = () => {
  busquedaCliente.value = '';
  busquedaTermino.value = '';
  mostrarListaClientes.value = false;
  clienteSeleccionadoIndex.value = -1;
  errorBusqueda.value = '';
  validacionError.value = '';
};

// Función para ver historial
const verHistorial = () => {
  emit('ver-historial', props.clienteSeleccionado);
};

// Función para editar cliente
const editarCliente = () => {
  emit('editar-cliente', props.clienteSeleccionado);
};

// Función para aplicar filtro rápido
const aplicarFiltroRapido = (filtro) => {
  filtroActivo.value = filtroActivo.value === filtro.value ? null : filtro.value;
  busquedaCliente.value = filtro.termino || '';
  filtrarClientes();
};

// Función para formatear moneda
const formatearMoneda = (valor) => {
  if (!valor) return '$0.00';
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(valor);
};

// Función para actualizar posición de la lista
const actualizarPosicionLista = () => {
  if (!inputBusqueda.value) return;

  const rect = inputBusqueda.value.getBoundingClientRect();
  inputWidth.value = rect.width;
  inputPosition.value = {
    top: rect.top + window.scrollY,
    left: rect.left + window.scrollX,
    height: rect.height
  };
};

// Watchers
watch(() => props.clienteSeleccionado, (nuevoCliente) => {
  if (nuevoCliente) {
    busquedaCliente.value = nuevoCliente.nombre_razon_social || '';
    mostrarListaClientes.value = false;
  } else {
    limpiarBusqueda();
    nextTick(() => {
      inputBusqueda.value?.focus();
    });
  }
});

watch(mostrarListaClientes, (esVisible) => {
  if (esVisible) {
    nextTick(actualizarPosicionLista);
  }
});

// Lifecycle hooks
onMounted(() => {
  window.addEventListener('resize', actualizarPosicionLista);
  window.addEventListener('scroll', actualizarPosicionLista);
});

onUnmounted(() => {
  window.removeEventListener('resize', actualizarPosicionLista);
  window.removeEventListener('scroll', actualizarPosicionLista);

  if (timeoutId.value) {
    clearTimeout(timeoutId.value);
  }

  if (debounceTimeout.value) {
    clearTimeout(debounceTimeout.value);
  }
});

// Expose para funciones públicas del componente
defineExpose({
  limpiarBusqueda,
  focus: () => nextTick(() => inputBusqueda.value?.focus())
});
</script>

