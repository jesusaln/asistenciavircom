<template>
  <div class="relative">
    <!-- Campo de búsqueda -->
    <div v-if="!proveedorSeleccionado" class="buscar-proveedor">
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          {{ labelBusqueda }}
          <span v-if="requerido" class="text-red-500">*</span>
        </label>
        <div class="relative">
          <input
            ref="inputBusqueda"
            type="text"
            v-model="busquedaProveedor"
            @input="handleInput"
            @focus="handleFocus"
            @blur="ocultarListaConRetraso"
            @keydown="manejarTeclas"
            :placeholder="placeholderBusqueda"
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-all duration-200"
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
              v-if="busquedaProveedor && !cargandoBusqueda"
              @click="limpiarBusqueda"
              type="button"
              class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100 transition-colors duration-200"
              title="Limpiar búsqueda"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
            <div v-else-if="cargandoBusqueda" class="animate-spin w-5 h-5 border-2 border-green-500 border-t-transparent rounded-full"></div>
            <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
        </div>
        <!-- Mensajes de error y ayuda -->
        <div v-if="errorBusqueda || (requerido && touched && validacionError)" class="mt-2 text-sm text-red-600">
          {{ errorBusqueda || validacionError }}
        </div>
        <div v-else-if="mensajeAyuda && !busquedaProveedor" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          {{ mensajeAyuda }}
        </div>
        <div v-if="busquedaProveedor && !mostrarListaProveedores && proveedoresFiltrados.length === 0 && !cargandoBusqueda" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          No se encontraron proveedores que coincidan con "{{ busquedaProveedor }}"
        </div>
        <!-- Filtros rápidos -->
        <div v-if="mostrarFiltrosRapidos && !proveedorSeleccionado" class="mt-3 flex flex-wrap gap-2">
          <button
            v-for="filtro in filtrosRapidos"
            :key="filtro.value"
            @click="aplicarFiltroRapido(filtro)"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors duration-200"
            :class="filtroActivo === filtro.value
              ? 'bg-green-100 text-green-800 border border-green-300'
              : 'bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200'"
          >
            <component :is="filtro.icon" class="w-3 h-3 mr-1" v-if="filtro.icon"/>
            {{ filtro.label }}
          </button>
        </div>
      </div>
    </div>
    <!-- Información del proveedor seleccionado -->
    <div v-if="proveedorSeleccionado" class="mt-6 p-6 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-lg shadow-sm">
      <div class="flex items-start justify-between mb-4">
        <h3 class="text-lg font-semibold text-green-900 flex items-center">
          <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ tituloProveedorSeleccionado }}
        </h3>
        <div class="flex items-center space-x-2">
          <!-- Botón de historial -->
          <button
            v-if="mostrarHistorial"
            @click="verHistorial"
            type="button"
            class="text-green-500 hover:text-green-700 hover:bg-green-100 p-1 rounded-full transition-colors duration-200"
            title="Ver historial"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </button>
          <!-- Botón de editar -->
          <button
            v-if="mostrarEditar"
            @click="editarProveedor"
            type="button"
            class="text-blue-500 hover:text-blue-700 hover:bg-blue-100 p-1 rounded-full transition-colors duration-200"
            title="Editar proveedor"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
          </button>
          <!-- Botón cambiar proveedor -->
          <button
            type="button"
            @click="limpiarProveedor"
            class="text-red-500 hover:text-red-700 hover:bg-red-100 p-1 rounded-full transition-colors duration-200"
            :title="`Cambiar ${tipoDocumento.toLowerCase()}`"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>
      <!-- Información básica del proveedor -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="space-y-2">
          <div class="flex items-center text-sm font-medium text-green-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Razón Social
          </div>
          <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ proveedorSeleccionado.nombre_razon_social }}</div>
        </div>
        <div class="space-y-2" v-if="proveedorSeleccionado.email">
          <div class="flex items-center text-sm font-medium text-green-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Email
          </div>
          <div class="text-gray-900 dark:text-white">{{ proveedorSeleccionado.email }}</div>
        </div>
        <div class="space-y-2" v-if="proveedorSeleccionado.telefono">
          <div class="flex items-center text-sm font-medium text-green-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            Teléfono
          </div>
          <div class="text-gray-900 dark:text-white">{{ proveedorSeleccionado.telefono }}</div>
        </div>
        <div class="space-y-2" v-if="proveedorSeleccionado.rfc">
          <div class="flex items-center text-sm font-medium text-green-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            RFC
          </div>
          <div class="text-gray-900 dark:text-white font-mono">{{ proveedorSeleccionado.rfc }}</div>
        </div>
        <!-- Estado del proveedor -->
        <div class="space-y-2" v-if="mostrarEstadoProveedor">
          <div class="flex items-center text-sm font-medium text-green-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Estado
          </div>
          <div class="flex items-center">
            <div
              class="w-3 h-3 rounded-full mr-2"
              :class="{
                'bg-green-500': proveedorSeleccionado.activo,
                'bg-red-500': !proveedorSeleccionado.activo
              }"
            ></div>
            <span class="text-gray-900 dark:text-white font-medium capitalize">
              {{ proveedorSeleccionado.activo ? 'Activo' : 'Inactivo' }}
            </span>
          </div>
        </div>
          <!-- Dirección completa -->
          <div class="space-y-2" v-if="direccionCompleta">
            <div class="flex items-center text-sm font-medium text-green-700">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Dirección
            </div>
            <div class="text-gray-900 dark:text-white text-sm">{{ direccionCompleta }}</div>
          </div>
          <!-- Información comercial -->
          <div class="space-y-2" v-if="proveedorSeleccionado.credito_limite && mostrarInfoComercial">
            <div class="flex items-center text-sm font-medium text-green-700">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
              </svg>
              Límite de Crédito
            </div>
            <div class="text-gray-900 dark:text-white font-semibold">{{ formatearMoneda(proveedorSeleccionado.credito_limite) }}</div>
          </div>
          <div class="space-y-2" v-if="proveedorSeleccionado.tipo_proveedor">
            <div class="flex items-center text-sm font-medium text-green-700">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
              </svg>
              Tipo de Proveedor
            </div>
            <div class="text-gray-900 dark:text-white capitalize">{{ proveedorSeleccionado.tipo_proveedor }}</div>
          </div>
      </div>
      <!-- Alertas del proveedor -->
      <div v-if="alertasProveedor.length > 0" class="mt-4 space-y-2">
        <div
          v-for="alerta in alertasProveedor"
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
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
      </svg>
      <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">{{ mensajeVacio }}</p>
      <p class="text-gray-400 text-sm mt-1">{{ submensajeVacio }}</p>
      <!-- Botón de acción rápida -->
      <div v-if="mostrarAccionRapida" class="mt-4">
        <button
          @click.stop="crearNuevoProveedor"
          class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors duration-200"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
          Crear Nuevo Proveedor
        </button>
      </div>
    </div>
    <!-- Lista de proveedores filtrados usando Teleport -->
    <Teleport to="#app">
      <div
        ref="listaProveedoresRef"
        v-if="mostrarListaProveedores && proveedoresFiltrados.length > 0"
        class="z-[9999] mt-1 bg-white dark:bg-slate-900 border border-gray-300 rounded-lg shadow-lg max-h-[60vh] overflow-y-auto pointer-events-auto"
        :style="{
          position: 'absolute',
          width: inputWidth + 'px',
          top: inputPosition.top + inputPosition.height + 'px',
          left: inputPosition.left + 'px',
          pointerEvents: 'auto'
        }"
      >
        <div class="p-2">
          <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 px-2 flex items-center justify-between">
            <span>{{ proveedoresFiltrados.length }} proveedor{{ proveedoresFiltrados.length !== 1 ? 'es' : '' }} encontrado{{ proveedoresFiltrados.length !== 1 ? 's' : '' }}</span>
            <span v-if="tiempoRespuesta" class="text-gray-400">({{ tiempoRespuesta }}ms)</span>
          </div>
        </div>
        <div
          v-for="(proveedor, index) in proveedoresFiltrados"
          :key="proveedor.id"
          @click="seleccionarProveedor(proveedor, $event)"
          @mouseenter="proveedorSeleccionadoIndex = index"
          class="px-4 py-3 hover:bg-green-50 cursor-pointer border-b border-gray-100 dark:border-slate-800 last:border-b-0 transition-colors duration-150 pointer-events-auto"
          :class="{ 'bg-green-50': proveedorSeleccionadoIndex === index }"
          :style="{ pointerEvents: 'auto' }"
        >
          <div class="flex items-center justify-between">
            <div class="flex-1">
              <div class="font-medium text-gray-900 dark:text-white mb-1 flex items-center">
                <span v-html="resaltarTexto(proveedor.nombre_razon_social, busquedaProveedor)"></span>
                <!-- Indicadores de estado -->
                <div class="ml-2 flex items-center space-x-1">
                  <div v-if="proveedor.estado"
                       class="w-2 h-2 rounded-full"
                       :class="{
                         'bg-green-500': proveedor.estado === 'activo',
                         'bg-yellow-500': proveedor.estado === 'suspendido',
                         'bg-red-500': proveedor.estado === 'inactivo'
                       }"
                       :title="proveedor.estado">
                  </div>
                </div>
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                <div v-if="proveedor.email" class="flex items-center" v-html="resaltarTexto(proveedor.email, busquedaProveedor)"></div>
                <div v-if="proveedor.telefono" class="flex items-center" v-html="resaltarTexto(proveedor.telefono, busquedaProveedor)"></div>
                <div v-if="proveedor.rfc" class="flex items-center font-mono text-xs" v-html="resaltarTexto(proveedor.rfc, busquedaProveedor)"></div>
                <!-- Tipo de proveedor -->
                <div v-if="proveedor.tipo_proveedor" class="text-xs text-green-600 capitalize">
                  Tipo: {{ proveedor.tipo_proveedor }}
                </div>
              </div>
            </div>
            <div v-if="proveedor.categoria" class="ml-3">
              <div class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full whitespace-nowrap" v-html="resaltarTexto(proveedor.categoria, busquedaProveedor)"></div>
            </div>
          </div>
        </div>
        <!-- Opción para crear nuevo proveedor -->
        <div v-if="busquedaProveedor && mostrarOpcionNuevoProveedor" class="border-t border-gray-200 dark:border-slate-800 p-3 bg-gray-50">
          <button
            @click.stop="crearNuevoProveedor"
            class="w-full text-left px-3 py-2 text-sm text-green-600 hover:text-green-800 hover:bg-green-50 rounded-md transition-colors duration-150 flex items-center"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Crear nuevo proveedor: "{{ busquedaProveedor }}"
          </button>
        </div>
      </div>
      <!-- Lista vacía -->
      <div
        ref="listaVaciaRef"
        v-if="mostrarListaProveedores && proveedoresFiltrados.length === 0 && busquedaTermino"
        class="z-[9999] mt-1 bg-white dark:bg-slate-900 border border-gray-300 rounded-lg shadow-lg p-4"
        :style="{
          position: 'absolute',
          width: inputWidth + 'px',
          top: inputPosition.top + inputPosition.height + 'px',
          left: inputPosition.left + 'px'
        }"
      >
        <div class="text-center text-gray-500 dark:text-gray-400">
          <p class="text-sm font-medium mb-1">No se encontraron proveedores</p>
          <p class="text-xs text-gray-400 mb-3">Intenta con otro término de búsqueda</p>
          <button
            @click.stop="crearNuevoProveedor"
            class="inline-flex items-center px-3 py-2 text-sm text-green-600 hover:text-green-800 hover:bg-green-50 rounded-md transition-colors duration-150"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Crear nuevo proveedor
          </button>
        </div>
      </div>
    </Teleport>

    <!-- Modal para crear nuevo proveedor -->
    <Teleport to="#app">
      <div v-if="mostrarModalCrearProveedor" class="fixed inset-0 z-[1000] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="cerrarModalCrearProveedor"></div>

          <!-- Modal panel -->
          <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white dark:bg-slate-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                  <!-- Header -->
                  <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                      Crear Nuevo Proveedor
                    </h3>
                    <button
                      @click="cerrarModalCrearProveedor"
                      class="text-gray-400 hover:text-gray-600 transition-colors duration-200"
                    >
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>

                  <!-- Formulario -->
                  <form @submit.prevent="guardarNuevoProveedor" class="space-y-6">
                    <!-- Información General -->
                    <div class="border-b border-gray-200 dark:border-slate-800 pb-4">
                      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Información General</h4>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                          <label for="modal-nombre_razon_social" class="block text-sm font-medium text-gray-700">
                            Nombre/Razón Social <span class="text-red-500">*</span>
                          </label>
                          <input
                            type="text"
                            id="modal-nombre_razon_social"
                            v-model="nuevoProveedor.nombre_razon_social"
                            @blur="toUpper('nombre_razon_social')"
                            autocomplete="off"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.nombre_razon_social ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                            required
                          />
                          <div v-if="errores.nombre_razon_social" class="mt-1 text-sm text-red-600">
                            {{ errores.nombre_razon_social }}
                          </div>
                        </div>

                        <div>
                          <label for="modal-tipo_persona" class="block text-sm font-medium text-gray-700">
                            Tipo de Persona <span class="text-red-500">*</span>
                          </label>
                          <select
                            id="modal-tipo_persona"
                            v-model="nuevoProveedor.tipo_persona"
                            @change="onTipoPersonaChange"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.tipo_persona ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                            required
                          >
                            <option value="">Selecciona una opción</option>
                            <option value="fisica">Persona Física</option>
                            <option value="moral">Persona Moral</option>
                          </select>
                          <div v-if="errores.tipo_persona" class="mt-1 text-sm text-red-600">
                            {{ errores.tipo_persona }}
                          </div>
                        </div>

                        <div>
                          <label for="modal-rfc" class="block text-sm font-medium text-gray-700">
                            RFC <span class="text-red-500">*</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                              ({{ nuevoProveedor.tipo_persona === 'fisica' ? '13 caracteres' : nuevoProveedor.tipo_persona === 'moral' ? '12 caracteres' : 'Selecciona tipo de persona' }})
                            </span>
                          </label>
                          <input
                            type="text"
                            id="modal-rfc"
                            :maxlength="nuevoProveedor.tipo_persona === 'fisica' ? 13 : 12"
                            :placeholder="nuevoProveedor.tipo_persona === 'fisica' ? 'ABCD123456EFG' : 'ABC123456EFG'"
                            :value="nuevoProveedor.rfc"
                            @input="onRfcInput"
                            :disabled="!nuevoProveedor.tipo_persona"
                            autocomplete="off"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.rfc ? 'border-red-300 bg-red-50' : 'border-gray-300',
                              !nuevoProveedor.tipo_persona ? 'bg-gray-100 text-gray-400' : ''
                            ]"
                            required
                          />
                          <div v-if="errores.rfc" class="mt-1 text-sm text-red-600">
                            {{ errores.rfc }}
                          </div>
                          <div v-if="!nuevoProveedor.tipo_persona" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Primero selecciona el tipo de persona
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Información Fiscal -->
                    <div class="border-b border-gray-200 dark:border-slate-800 pb-4">
                      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Información Fiscal</h4>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label for="modal-regimen_fiscal" class="block text-sm font-medium text-gray-700">
                            Régimen Fiscal <span class="text-red-500">*</span>
                          </label>
                          <select
                            id="modal-regimen_fiscal"
                            v-model="nuevoProveedor.regimen_fiscal"
                            :disabled="!nuevoProveedor.tipo_persona"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.regimen_fiscal ? 'border-red-300 bg-red-50' : 'border-gray-300',
                              !nuevoProveedor.tipo_persona ? 'bg-gray-100 text-gray-400' : ''
                            ]"
                            required
                          >
                            <option value="">Selecciona una opción</option>
                            <option
                              v-for="regimen in regimenesFiltrados"
                              :key="regimen.codigo"
                              :value="regimen.codigo"
                            >
                              {{ regimen.codigo }} - {{ regimen.descripcion }}
                            </option>
                          </select>
                          <div v-if="errores.regimen_fiscal" class="mt-1 text-sm text-red-600">
                            {{ errores.regimen_fiscal }}
                          </div>
                          <div v-if="!nuevoProveedor.tipo_persona" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Primero selecciona el tipo de persona
                          </div>
                        </div>

                        <div>
                          <label for="modal-uso_cfdi" class="block text-sm font-medium text-gray-700">
                            Uso CFDI <span class="text-red-500">*</span>
                          </label>
                          <select
                            id="modal-uso_cfdi"
                            v-model="nuevoProveedor.uso_cfdi"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.uso_cfdi ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                            required
                          >
                            <option value="">Selecciona una opción</option>
                            <option
                              v-for="uso in usosCFDI"
                              :key="uso.codigo"
                              :value="uso.codigo"
                            >
                              {{ uso.codigo }} - {{ uso.descripcion }}
                            </option>
                          </select>
                          <div v-if="errores.uso_cfdi" class="mt-1 text-sm text-red-600">
                            {{ errores.uso_cfdi }}
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Información de Contacto -->
                    <div class="border-b border-gray-200 dark:border-slate-800 pb-4">
                      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Información de Contacto</h4>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label for="modal-email" class="block text-sm font-medium text-gray-700">
                            Email <span class="text-red-500">*</span>
                          </label>
                          <input
                            type="email"
                            id="modal-email"
                            v-model="nuevoProveedor.email"
                            autocomplete="new-password"
                            placeholder="correo@ejemplo.com"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.email ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                            required
                          />
                          <div v-if="errores.email" class="mt-1 text-sm text-red-600">
                            {{ errores.email }}
                          </div>
                        </div>

                        <div>
                          <label for="modal-telefono" class="block text-sm font-medium text-gray-700">
                            Teléfono
                          </label>
                          <input
                            type="tel"
                            id="modal-telefono"
                            autocomplete="new-password"
                            v-model="nuevoProveedor.telefono"
                            placeholder="Opcional"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.telefono ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                          />
                          <div v-if="errores.telefono" class="mt-1 text-sm text-red-600">
                            {{ errores.telefono }}
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Dirección -->
                    <div>
                      <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Dirección</h4>
                      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                          <label for="modal-calle" class="block text-sm font-medium text-gray-700">
                            Calle <span class="text-red-500">*</span>
                          </label>
                          <input
                            type="text"
                            id="modal-calle"
                            v-model="nuevoProveedor.calle"
                            @blur="toUpper('calle')"
                            autocomplete="new-password"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.calle ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                            required
                          />
                          <div v-if="errores.calle" class="mt-1 text-sm text-red-600">
                            {{ errores.calle }}
                          </div>
                        </div>

                        <div>
                          <label for="modal-numero_exterior" class="block text-sm font-medium text-gray-700">
                            Número Exterior <span class="text-red-500">*</span>
                          </label>
                          <input
                            type="text"
                            id="modal-numero_exterior"
                            v-model="nuevoProveedor.numero_exterior"
                            @blur="toUpper('numero_exterior')"
                            autocomplete="new-password"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.numero_exterior ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                            required
                          />
                          <div v-if="errores.numero_exterior" class="mt-1 text-sm text-red-600">
                            {{ errores.numero_exterior }}
                          </div>
                        </div>

                        <div>
                          <label for="modal-numero_interior" class="block text-sm font-medium text-gray-700">
                            Número Interior
                          </label>
                          <input
                            type="text"
                            id="modal-numero_interior"
                            v-model="nuevoProveedor.numero_interior"
                            @blur="toUpper('numero_interior')"
                            autocomplete="new-password"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.numero_interior ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                          />
                          <div v-if="errores.numero_interior" class="mt-1 text-sm text-red-600">
                            {{ errores.numero_interior }}
                          </div>
                        </div>

                        <div>
                          <label for="modal-colonia" class="block text-sm font-medium text-gray-700">
                            Colonia <span class="text-red-500">*</span>
                          </label>
                          <select
                            id="modal-colonia"
                            v-model="nuevoProveedor.colonia"
                            :disabled="availableColonias.length === 0"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.colonia ? 'border-red-300 bg-red-50' : 'border-gray-300',
                              availableColonias.length === 0 ? 'bg-gray-100 text-gray-400' : ''
                            ]"
                            required
                          >
                            <option value="">
                              {{ availableColonias.length === 0 ? 'Ingresa un código postal primero' : 'Selecciona una colonia' }}
                            </option>
                            <option
                              v-for="colonia in availableColonias"
                              :key="colonia"
                              :value="colonia"
                            >
                              {{ colonia }}
                            </option>
                          </select>
                          <div v-if="errores.colonia" class="mt-1 text-sm text-red-600">
                            {{ errores.colonia }}
                          </div>
                          <div v-if="availableColonias.length === 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Primero ingresa un código postal válido para cargar las colonias disponibles
                          </div>
                        </div>

                        <div>
                          <label for="modal-codigo_postal" class="block text-sm font-medium text-gray-700">
                            Código Postal <span class="text-red-500">*</span>
                          </label>
                          <input
                            type="text"
                            id="modal-codigo_postal"
                            maxlength="5"
                            pattern="[0-9]{5}"
                            :value="nuevoProveedor.codigo_postal"
                            @input="onCpInput"
                            placeholder="12345"
                            autocomplete="new-password"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.codigo_postal ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                            required
                          />
                          <div v-if="errores.codigo_postal" class="mt-1 text-sm text-red-600">
                            {{ errores.codigo_postal }}
                          </div>
                          <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Al ingresar un código postal válido, se autocompletarán automáticamente el estado y municipio.
                          </div>
                        </div>

                        <div>
                          <label for="modal-municipio" class="block text-sm font-medium text-gray-700">
                            Municipio <span class="text-red-500">*</span>
                          </label>
                          <input
                            type="text"
                            id="modal-municipio"
                            v-model="nuevoProveedor.municipio"
                            autocomplete="new-password"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.municipio ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                            required
                          />
                          <div v-if="errores.municipio" class="mt-1 text-sm text-red-600">
                            {{ errores.municipio }}
                          </div>
                        </div>

                        <div>
                          <label for="modal-estado" class="block text-sm font-medium text-gray-700">
                            Estado
                          </label>
                          <select
                            id="modal-estado"
                            v-model="nuevoProveedor.estado"
                            class="mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border-gray-300"
                          >
                            <option value="">Selecciona una opción</option>
                            <option
                              v-for="estado in estados"
                              :key="estado.value"
                              :value="estado.value"
                            >
                              {{ estado.text || estado.label }}
                            </option>
                          </select>
                          <div v-if="errores.estado" class="mt-1 text-sm text-red-600">
                            {{ errores.estado }}
                          </div>
                          <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Opcional para proveedores extranjeros
                          </div>
                        </div>

                        <div>
                          <label for="modal-pais" class="block text-sm font-medium text-gray-700">
                            País
                          </label>
                          <input
                            type="text"
                            id="modal-pais"
                            v-model="nuevoProveedor.pais"
                            @blur="toUpper('pais')"
                            placeholder="MX (México por defecto)"
                            autocomplete="new-password"
                            :class="[
                              'mt-1 block w-full rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
                              errores.pais ? 'border-red-300 bg-red-50' : 'border-gray-300'
                            ]"
                          />
                          <div v-if="errores.pais" class="mt-1 text-sm text-red-600">
                            {{ errores.pais }}
                          </div>
                          <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Código de país (2-3 letras). México por defecto, cambia para proveedores extranjeros.
                          </div>
                        </div>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button
                type="button"
                @click="guardarNuevoProveedor"
                :disabled="guardandoProveedor"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
              >
                <span v-if="guardandoProveedor">Creando...</span>
                <span v-else>Crear Proveedor</span>
              </button>
              <button
                type="button"
                @click="cerrarModalCrearProveedor"
                :disabled="guardandoProveedor"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-slate-900 text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
              >
                Cancelar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

// Props del componente
const props = defineProps({
  proveedores: {
    type: Array,
    required: true,
    default: () => []
  },
  proveedorSeleccionado: {
    type: Object,
    default: null
  },
  mostrarOpcionNuevoProveedor: {
    type: Boolean,
    default: true
  },
  labelBusqueda: {
    type: String,
    default: 'Buscar Proveedor'
  },
  placeholderBusqueda: {
    type: String,
    default: 'Escribe para buscar proveedores...'
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
    default: 'Proveedor'
  },
  tituloProveedorSeleccionado: {
    type: String,
    default: 'Proveedor Seleccionado'
  },
  mensajeVacio: {
    type: String,
    default: 'No hay proveedor seleccionado'
  },
  submensajeVacio: {
    type: String,
    default: 'Busca y selecciona un proveedor para continuar'
  },
  mostrarAccionRapida: {
    type: Boolean,
    default: true
  },
  mostrarEstadoProveedor: {
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

// Función auxiliar para dirección completa
const getDireccionCompleta = () => {
  if (!props.proveedorSeleccionado) return null;

  const proveedor = props.proveedorSeleccionado;
  const partesDireccion = [
    proveedor.calle,
    proveedor.numero_exterior,
    proveedor.numero_interior,
    proveedor.colonia,
    proveedor.codigo_postal,
    proveedor.municipio,
    proveedor.estado,
    proveedor.pais
  ].filter(Boolean);

  return partesDireccion.length > 0 ? partesDireccion.join(', ') : null;
};

// Computada para dirección completa del proveedor
const direccionCompleta = computed(getDireccionCompleta);

// Eventos que emite el componente
const emit = defineEmits([
  'proveedor-seleccionado',
  'crear-nuevo-proveedor',
  'ver-historial',
  'editar-proveedor'
]);

// Referencias reactivas
const inputBusqueda = ref(null);
const listaProveedoresRef = ref(null);
const listaVaciaRef = ref(null);
const busquedaProveedor = ref('');
const busquedaTermino = ref('');
const mostrarListaProveedores = ref(false);
const proveedorSeleccionadoIndex = ref(-1);
const cargandoBusqueda = ref(false);
const errorBusqueda = ref('');
const validacionError = ref('');
const touched = ref(false);
const timeoutId = ref(null);
const debounceTimeout = ref(null);
const inputWidth = ref(0);
const inputPosition = ref({ top: 0, left: 0, height: 0 });
const filtroActivo = ref(null);
const tiempoRespuesta = ref(null);

// Variables para el modal de crear proveedor
const mostrarModalCrearProveedor = ref(false);
const guardandoProveedor = ref(false);
const nuevoProveedor = ref({
  nombre_razon_social: '',
  tipo_persona: '',
  rfc: '',
  regimen_fiscal: '',
  uso_cfdi: '',
  email: '',
  telefono: '',
  calle: '',
  numero_exterior: '',
  numero_interior: '',
  colonia: '',
  codigo_postal: '',
  municipio: '',
  estado: '',
  pais: 'MX'
});
const errores = ref({});
const availableColonias = ref([]);

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
const dividirTerminosBusqueda = (termino) => {
  return normalizarTexto(termino)
    .split(/\s+/)
    .filter(t => t.length > 0);
};

// Computada para filtrar proveedores
const proveedoresFiltrados = computed(() => {
  // if (!busquedaTermino.value || busquedaTermino.value.length < 1) {
  //   return [];
  // }
  
  // Si no hay término de búsqueda, devolver todos (limitados)
  if (!busquedaTermino.value || busquedaTermino.value.trim().length === 0) {
    // Si hay un filtro activo, aplicarlo
    let todos = props.proveedores;
    if (filtroActivo.value) {
      const filtro = props.filtrosRapidos.find(f => f.value === filtroActivo.value);
      if (filtro && filtro.filter) {
        todos = todos.filter(filtro.filter);
      }
    }
    return todos.slice(0, 50);
  }
  const terminos = dividirTerminosBusqueda(busquedaTermino.value);
  if (terminos.length === 0) return [];
  const inicioTiempo = performance.now();
  let proveedoresFilt = props.proveedores.filter(proveedor => {
    const textoNormalizado = [
      proveedor.nombre_razon_social,
      proveedor.email,
      proveedor.telefono,
      proveedor.rfc,
      proveedor.categoria,
      proveedor.tipo_proveedor
    ].map(normalizarTexto).join(' ');
    return terminos.every(termino => textoNormalizado.includes(termino));
  });
  // Aplicar filtro rápido si está activo
  if (filtroActivo.value) {
    const filtro = props.filtrosRapidos.find(f => f.value === filtroActivo.value);
    if (filtro && filtro.filter) {
      proveedoresFilt = proveedoresFilt.filter(filtro.filter);
    }
  }
  // Ordenar por relevancia
  proveedoresFilt = proveedoresFilt.sort((a, b) => {
    const nombreA = normalizarTexto(a.nombre_razon_social);
    const nombreB = normalizarTexto(b.nombre_razon_social);
    const primerTermino = terminos[0];
    const coincideInicioA = nombreA.startsWith(primerTermino);
    const coincideInicioB = nombreB.startsWith(primerTermino);
    if (coincideInicioA && !coincideInicioB) return -1;
    if (!coincideInicioA && coincideInicioB) return 1;
    return nombreA.localeCompare(nombreB);
  });
  const finTiempo = performance.now();
  tiempoRespuesta.value = Math.round(finTiempo - inicioTiempo);
  return proveedoresFilt.slice(0, 50); // Limitar resultados
});


// Computada para alertas del proveedor
const alertasProveedor = computed(() => {
  if (!props.proveedorSeleccionado) return [];
  const alertas = [];
  const proveedor = props.proveedorSeleccionado;
  if (proveedor.estado === 'suspendido') {
    alertas.push({
      id: 'suspendido',
      tipo: 'warning',
      mensaje: 'Este proveedor está suspendido temporalmente'
    });
  }
  if (proveedor.estado === 'inactivo') {
    alertas.push({
      id: 'inactivo',
      tipo: 'error',
      mensaje: 'Este proveedor está inactivo'
    });
  }
  if (proveedor.credito_limite && proveedor.saldo_pendiente > proveedor.credito_limite) {
    alertas.push({
      id: 'credito_excedido',
      tipo: 'error',
      mensaje: 'El proveedor ha excedido su límite de crédito'
    });
  }
  return alertas;
});

// Función para calcular posición del dropdown
const calcularPosicionInput = () => {
  if (!inputBusqueda.value) return;
  const rect = inputBusqueda.value.getBoundingClientRect();
  const scrollY = window.scrollY || document.documentElement.scrollTop;
  inputWidth.value = rect.width;
  inputPosition.value = {
    top: rect.top + scrollY,
    left: rect.left + window.scrollX,
    height: rect.height
  };
};

// Función para manejar input
const handleInput = () => {
  touched.value = true;
  filtrarProveedores();
};

// Función para manejar focus
const handleFocus = () => {
  touched.value = true;
  mostrarListaProveedores.value = true;
};

// Función para filtrar proveedores con debounce
const filtrarProveedores = () => {
  clearTimeout(debounceTimeout.value);
  debounceTimeout.value = setTimeout(() => {
    busquedaTermino.value = busquedaProveedor.value;
    errorBusqueda.value = '';
    
    // Siempre mostrar la lista si estamos buscando (o si se limpió el input)
    // El computed proveedoresFiltrados se encargará de mostrar todos si está vacío
    calcularPosicionInput();
    mostrarListaProveedores.value = true;
    proveedorSeleccionadoIndex.value = -1;
    
    // Anterior lógica estricta:
    // if (busquedaProveedor.value.length >= 1) {
    //   calcularPosicionInput();
    //   mostrarListaProveedores.value = true;
    //   proveedorSeleccionadoIndex.value = -1;
    // } else {
    //   mostrarListaProveedores.value = false;
    // }
  }, 300);
};

// Función para ocultar lista con retraso
const ocultarListaConRetraso = () => {
  timeoutId.value = setTimeout(() => {
    mostrarListaProveedores.value = false;
  }, 150);
};

// Función para manejar teclas
const manejarTeclas = (event) => {
  if (!mostrarListaProveedores.value || proveedoresFiltrados.value.length === 0) return;
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault();
      proveedorSeleccionadoIndex.value = Math.min(
        proveedorSeleccionadoIndex.value + 1,
        proveedoresFiltrados.value.length - 1
      );
      break;
    case 'ArrowUp':
      event.preventDefault();
      proveedorSeleccionadoIndex.value = Math.max(
        proveedorSeleccionadoIndex.value - 1,
        -1
      );
      break;
    case 'Enter':
      event.preventDefault();
      if (proveedorSeleccionadoIndex.value >= 0) {
        seleccionarProveedor(proveedoresFiltrados.value[proveedorSeleccionadoIndex.value], event);
      }
      break;
    case 'Escape':
      mostrarListaProveedores.value = false;
      inputBusqueda.value?.blur();
      break;
  }
};

// Función para seleccionar proveedor
const seleccionarProveedor = (proveedor, event) => {
  // Prevenir comportamiento por defecto
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }

  if (timeoutId.value) {
    clearTimeout(timeoutId.value);
  }
  mostrarListaProveedores.value = false;
  busquedaProveedor.value = proveedor.nombre_razon_social;
  validacionError.value = '';
  touched.value = true;
  emit('proveedor-seleccionado', proveedor);
};

// Función para limpiar búsqueda
const limpiarBusqueda = () => {
  busquedaProveedor.value = '';
  busquedaTermino.value = '';
  mostrarListaProveedores.value = false;
  errorBusqueda.value = '';
  validacionError.value = '';
  filtroActivo.value = null;
  touched.value = false;
};

// Función para limpiar proveedor seleccionado
const limpiarProveedor = () => {
  limpiarBusqueda();
  emit('proveedor-seleccionado', null); // null = limpiado
};


// Función para aplicar filtro rápido
const aplicarFiltroRapido = (filtro) => {
  if (filtroActivo.value === filtro.value) {
    filtroActivo.value = null;
  } else {
    filtroActivo.value = filtro.value;
  }
  if (busquedaTermino.value) {
    // Refiltrar con el nuevo filtro
    filtrarProveedores();
  }
};

// Función para resaltar texto de búsqueda
const resaltarTexto = (texto, busqueda) => {
  if (!texto || !busqueda) return texto;
  const terminos = dividirTerminosBusqueda(busqueda);
  let resultado = texto;
  terminos.forEach(termino => {
    if (termino.length > 1) {
      const regex = new RegExp(`(${termino})`, 'gi');
      resultado = resultado.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
    }
  });
  return resultado;
};

// Función para formatear moneda
const formatearMoneda = (valor) => {
  if (!valor) return '$0.00';
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(valor);
};

// Función para crear nuevo proveedor
const crearNuevoProveedor = () => {
  mostrarListaProveedores.value = false;
  nuevoProveedor.value.nombre_razon_social = busquedaProveedor.value || '';
  errores.value = {};
  mostrarModalCrearProveedor.value = true;
};

// Función para cerrar el modal de crear proveedor
const cerrarModalCrearProveedor = () => {
  mostrarModalCrearProveedor.value = false;
  nuevoProveedor.value = {
    nombre_razon_social: '',
    tipo_persona: '',
    rfc: '',
    regimen_fiscal: '',
    uso_cfdi: '',
    email: '',
    telefono: '',
    calle: '',
    numero_exterior: '',
    numero_interior: '',
    colonia: '',
    codigo_postal: '',
    municipio: '',
    estado: '',
    pais: 'MX'
  };
  errores.value = {};
  availableColonias.value = [];
};

// Mapeo de nombres de estados a claves SAT
const estadoMapping = {
  'Aguascalientes': 'AGU',
  'Baja California': 'BCN',
  'Baja California Sur': 'BCS',
  'Campeche': 'CAM',
  'Chihuahua': 'CHH',
  'Chiapas': 'CHP',
  'Ciudad de México': 'CMX',
  'Coahuila': 'COA',
  'Colima': 'COL',
  'Durango': 'DUR',
  'Guerrero': 'GRO',
  'Guanajuato': 'GUA',
  'Hidalgo': 'HID',
  'Jalisco': 'JAL',
  'Estado de México': 'MEX',
  'Michoacán': 'MIC',
  'Morelos': 'MOR',
  'Nayarit': 'NAY',
  'Nuevo León': 'NLE',
  'Oaxaca': 'OAX',
  'Puebla': 'PUE',
  'Querétaro': 'QUE',
  'Quintana Roo': 'ROO',
  'Sinaloa': 'SIN',
  'San Luis Potosí': 'SLP',
  'Sonora': 'SON',
  'Tabasco': 'TAB',
  'Tamaulipas': 'TAM',
  'Tlaxcala': 'TLA',
  'Veracruz': 'VER',
  'Yucatán': 'YUC',
  'Zacatecas': 'ZAC'
}

// Estados hardcodeados
const estados = [
  { value: 'AGU', text: 'AGU — Aguascalientes' },
  { value: 'BCN', text: 'BCN — Baja California' },
  { value: 'BCS', text: 'BCS — Baja California Sur' },
  { value: 'CAM', text: 'CAM — Campeche' },
  { value: 'CHH', text: 'CHH — Chihuahua' },
  { value: 'CHP', text: 'CHP — Chiapas' },
  { value: 'CMX', text: 'CMX — Ciudad de México' },
  { value: 'COA', text: 'COA — Coahuila' },
  { value: 'COL', text: 'COL — Colima' },
  { value: 'DUR', text: 'DUR — Durango' },
  { value: 'GRO', text: 'GRO — Guerrero' },
  { value: 'GUA', text: 'GUA — Guanajuato' },
  { value: 'HID', text: 'HID — Hidalgo' },
  { value: 'JAL', text: 'JAL — Jalisco' },
  { value: 'MEX', text: 'MEX — Estado de México' },
  { value: 'MIC', text: 'MIC — Michoacán' },
  { value: 'MOR', text: 'MOR — Morelos' },
  { value: 'NAY', text: 'NAY — Nayarit' },
  { value: 'NLE', text: 'NLE — Nuevo León' },
  { value: 'OAX', text: 'OAX — Oaxaca' },
  { value: 'PUE', text: 'PUE — Puebla' },
  { value: 'QUE', text: 'QUE — Querétaro' },
  { value: 'ROO', text: 'ROO — Quintana Roo' },
  { value: 'SIN', text: 'SIN — Sinaloa' },
  { value: 'SLP', text: 'SLP — San Luis Potosí' },
  { value: 'SON', text: 'SON — Sonora' },
  { value: 'TAB', text: 'TAB — Tabasco' },
  { value: 'TAM', text: 'TAM — Tamaulipas' },
  { value: 'TLA', text: 'TLA — Tlaxcala' },
  { value: 'VER', text: 'VER — Veracruz' },
  { value: 'YUC', text: 'YUC — Yucatán' },
  { value: 'ZAC', text: 'ZAC — Zacatecas' }
]

// Regímenes fiscales hardcodeados
const regimenesFiscales = [
  { codigo: '601', descripcion: 'General de Ley Personas Morales', persona_moral: true, persona_fisica: false },
  { codigo: '603', descripcion: 'Personas Morales con Fines no Lucrativos', persona_moral: true, persona_fisica: false },
  { codigo: '605', descripcion: 'Sueldos y Salarios e Ingresos Asimilados a Salarios', persona_moral: false, persona_fisica: true },
  { codigo: '606', descripcion: 'Arrendamiento', persona_moral: true, persona_fisica: true },
  { codigo: '607', descripcion: 'Régimen de Enajenación o Adquisición de Bienes', persona_moral: true, persona_fisica: true },
  { codigo: '608', descripcion: 'Demás ingresos', persona_moral: true, persona_fisica: true },
  { codigo: '609', descripcion: 'Consolidación', persona_moral: true, persona_fisica: false },
  { codigo: '610', descripcion: 'Residentes en el Extranjero sin Establecimiento Permanente en México', persona_moral: true, persona_fisica: true },
  { codigo: '611', descripcion: 'Ingresos por Dividendos (socios y accionistas)', persona_moral: true, persona_fisica: true },
  { codigo: '612', descripcion: 'Personas Físicas con Actividades Empresariales y Profesionales', persona_moral: false, persona_fisica: true },
  { codigo: '614', descripcion: 'Ingresos por intereses', persona_moral: true, persona_fisica: true },
  { codigo: '615', descripcion: 'Régimen de los ingresos por obtención de premios', persona_moral: true, persona_fisica: true },
  { codigo: '616', descripcion: 'Sin obligaciones fiscales', persona_moral: true, persona_fisica: true },
  { codigo: '620', descripcion: 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos', persona_moral: true, persona_fisica: false },
  { codigo: '621', descripcion: 'Incorporación Fiscal', persona_moral: true, persona_fisica: false },
  { codigo: '622', descripcion: 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras', persona_moral: true, persona_fisica: true },
  { codigo: '623', descripcion: 'Opcional para Grupos de Sociedades', persona_moral: true, persona_fisica: false },
  { codigo: '624', descripcion: 'Coordinados', persona_moral: true, persona_fisica: false },
  { codigo: '625', descripcion: 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas', persona_moral: false, persona_fisica: true },
  { codigo: '626', descripcion: 'Régimen Simplificado de Confianza', persona_moral: true, persona_fisica: true }
]

// Usos CFDI hardcodeados
const usosCFDI = [
  { codigo: 'G01', descripcion: 'Adquisición de mercancías' },
  { codigo: 'G02', descripcion: 'Devoluciones, descuentos o bonificaciones' },
  { codigo: 'G03', descripcion: 'Gastos en general' },
  { codigo: 'I01', descripcion: 'Construcciones' },
  { codigo: 'I02', descripcion: 'Mobiliario y equipo de oficina por inversiones' },
  { codigo: 'I03', descripcion: 'Equipo de transporte' },
  { codigo: 'I04', descripcion: 'Equipo de computo y accesorios' },
  { codigo: 'I05', descripcion: 'Dados, troqueles, moldes, matrices y herramental' },
  { codigo: 'I06', descripcion: 'Comunicaciones telefónicas' },
  { codigo: 'I07', descripcion: 'Comunicaciones satelitales' },
  { codigo: 'I08', descripcion: 'Otra maquinaria y equipo' },
  { codigo: 'D01', descripcion: 'Honorarios médicos, dentales y gastos hospitalarios' },
  { codigo: 'D02', descripcion: 'Gastos médicos por incapacidad o discapacidad' },
  { codigo: 'D03', descripcion: 'Gastos funerales' },
  { codigo: 'D04', descripcion: 'Donativos' },
  { codigo: 'D05', descripcion: 'Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación)' },
  { codigo: 'D06', descripcion: 'Aportaciones voluntarias al SAR' },
  { codigo: 'D07', descripcion: 'Primas por seguros de gastos médicos' },
  { codigo: 'D08', descripcion: 'Gastos de transportación escolar obligatoria' },
  { codigo: 'D09', descripcion: 'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones' },
  { codigo: 'D10', descripcion: 'Pagos por servicios educativos (colegiaturas)' },
  { codigo: 'P01', descripcion: 'Por definir' }
]

// Computed para regimenes filtrados
const regimenesFiltrados = computed(() => {
  if (!nuevoProveedor.value.tipo_persona) return []
  return regimenesFiscales.filter(r =>
    (nuevoProveedor.value.tipo_persona === 'moral' ? r.persona_moral : r.persona_fisica)
  )
})

// Funciones para el modal
const onTipoPersonaChange = () => {
  nuevoProveedor.value.rfc = ''
  nuevoProveedor.value.regimen_fiscal = ''
  errores.value = { ...errores.value, rfc: '', regimen_fiscal: '' }
}

const onRfcInput = (event) => {
  const value = event.target ? event.target.value : event
  const cleaned = String(value).toUpperCase().replace(/[^A-ZÑ&0-9]/g, '')
  const maxLen = nuevoProveedor.value.tipo_persona === 'fisica' ? 13 : 12
  nuevoProveedor.value.rfc = cleaned.slice(0, maxLen)
  if (errores.value.rfc) errores.value.rfc = ''
}

const onCpInput = async (event) => {
  const value = event.target ? event.target.value : event
  const digits = String(value).replace(/\D/g, '')
  nuevoProveedor.value.codigo_postal = digits.slice(0, 5)
  if (errores.value.codigo_postal) errores.value.codigo_postal = ''

  if (nuevoProveedor.value.codigo_postal.length === 5) {
    try {
      const response = await fetch(`/api/cp/${nuevoProveedor.value.codigo_postal}`)
      const data = await response.json()

      if (data.estado) {
        const estadoClave = estadoMapping[data.estado] || data.estado
        nuevoProveedor.value.estado = estadoClave
      }

      if (data.municipio) {
        nuevoProveedor.value.municipio = data.municipio
      }

      if (!nuevoProveedor.value.pais || nuevoProveedor.value.pais.trim() === '') {
        nuevoProveedor.value.pais = data.pais
      }

      availableColonias.value = data.colonias || []

      if (data.colonias && data.colonias.length === 1) {
        nuevoProveedor.value.colonia = data.colonias[0]
      } else if (data.colonias && data.colonias.length > 1) {
        nuevoProveedor.value.colonia = ''
      }

      errores.value = { ...errores.value, estado: '', municipio: '', pais: '' }
    } catch (error) {
      availableColonias.value = []
      nuevoProveedor.value.colonia = ''
    }
  } else {
    availableColonias.value = []
    nuevoProveedor.value.colonia = ''
  }
}

const toUpper = (campo) => {
  if (nuevoProveedor.value[campo] && typeof nuevoProveedor.value[campo] === 'string') {
    nuevoProveedor.value[campo] = nuevoProveedor.value[campo].toUpperCase().trim()
    if (errores.value[campo]) errores.value[campo] = ''
  }
}

// Función para guardar el nuevo proveedor
const guardarNuevoProveedor = async () => {
  guardandoProveedor.value = true;
  errores.value = {};

  try {
    const response = await fetch(route('proveedores.store'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(nuevoProveedor.value)
    });

    const data = await response.json();

    if (response.ok) {
      // Agregar el nuevo proveedor a la lista
      proveedoresList.value.push(data.proveedor || data);

      // Seleccionar automáticamente el nuevo proveedor
      seleccionarProveedor(data.proveedor || data);

      // Cerrar modal
      cerrarModalCrearProveedor();
    } else {
      // Mostrar errores de validación
      errores.value = data.errors || {};
    }
  } catch (error) {
    console.error('Error al crear proveedor:', error);
  } finally {
    guardandoProveedor.value = false;
  }
};

// Función para ver historial
const verHistorial = () => {
  emit('ver-historial', props.proveedorSeleccionado);
};

// Función para editar proveedor
const editarProveedor = () => {
  emit('editar-proveedor', props.proveedorSeleccionado);
};

// Función para validar campos requeridos
const validarCamposRequeridos = () => {
  if (props.requerido && !props.proveedorSeleccionado) {
    validacionError.value = 'Este campo es requerido';
    return false;
  }
  validacionError.value = '';
  return true;
};

// Función para manejar clicks fuera del componente
const manejarClickFuera = (event) => {
  const elemento = event.target;
  const dentroInput = inputBusqueda.value?.contains(elemento);
  const dentroLista = listaProveedoresRef.value?.contains(elemento) ||
                     listaVaciaRef.value?.contains(elemento);
  if (!dentroInput && !dentroLista) {
    mostrarListaProveedores.value = false;
  }
};

// Función para manejar redimensionamiento de ventana
const manejarRedimension = () => {
  if (mostrarListaProveedores.value) {
    calcularPosicionInput();
  }
};

// Watchers
watch(
  () => props.proveedorSeleccionado,
  (nuevo) => {
    if (nuevo) {
      busquedaProveedor.value = nuevo.nombre_razon_social || '';
      validacionError.value = '';
    } else {
      limpiarBusqueda();
    }
  },
  { immediate: true }
);

watch(
  () => props.requerido,
  () => {
    nextTick(() => {
      validarCamposRequeridos();
    });
  }
);

// Lifecycle hooks
onMounted(() => {
  document.addEventListener('click', manejarClickFuera);
  window.addEventListener('resize', manejarRedimension);
  window.addEventListener('scroll', manejarRedimension);
  // No validar estado inicial para evitar mostrar errores prematuramente
});

onUnmounted(() => {
  document.removeEventListener('click', manejarClickFuera);
  window.removeEventListener('resize', manejarRedimension);
  window.removeEventListener('scroll', manejarRedimension);
  if (timeoutId.value) {
    clearTimeout(timeoutId.value);
  }
  if (debounceTimeout.value) {
    clearTimeout(debounceTimeout.value);
  }
});

// Exponer funciones para uso del componente padre
defineExpose({
  validarCamposRequeridos,
  limpiarProveedor,
  enfocarBusqueda: () => inputBusqueda.value?.focus()
});
</script>

