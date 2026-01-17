<!-- /resources/js/Pages/Ventas/Create.vue -->
<template>
  <Head title="Crear Venta" />
  <div class="ventas-create min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="w-full">
      <!-- Header -->
      <Header
        title="Nueva Venta"
        description="Crea una nueva venta para tus clientes"
        :can-preview="clienteSeleccionado && selectedProducts.length > 0"
        :back-url="route('ventas.index')"
        :show-shortcuts="mostrarAtajos"
        @preview="handlePreview"
        @close-shortcuts="closeShortcuts"
      />

      <form @submit.prevent="abrirModalPago" class="space-y-8">
        <!-- Información General -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Información General
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Número de Venta -->
            <div>
              <label for="numero_venta" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Número de Venta *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                  Auto-generado
                </span>
              </label>
              <div class="relative">
                <input
                  id="numero_venta"
                  v-model="form.numero_venta"
                  type="text"
                  class="w-full bg-white text-gray-500 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  placeholder="V0001"
                  readonly
                  required
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Este número se genera automáticamente para cada venta nueva
              </p>
            </div>

            <!-- Fecha de Venta -->
            <div>
              <label for="fecha" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Fecha de Venta *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Automática
                </span>
              </label>
              <div class="relative">
                <input
                  id="fecha"
                  v-model="form.fecha"
                  type="date"
                  class="w-full bg-white text-gray-500 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  readonly
                  required
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Esta fecha se establece automáticamente con la fecha de creación
              </p>
            </div>

            <!-- almacén -->
            <div>
              <label for="almacen_id" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                almacén *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  Requerido
                </span>
              </label>
              <div class="relative">
                <select
                  id="almacen_id"
                  v-model="form.almacen_id"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  required
                >
                  <option value="">Selecciona un almacén</option>
                  <option
                    v-for="almacen in almacenes"
                    :key="almacen.id"
                    :value="almacen.id"
                    :selected="almacen.id === userAlmacenPredeterminado"
                  >
                    {{ almacen.nombre }}
                    <span v-if="almacen.id === userAlmacenPredeterminado" class="text-xs text-blue-600">(Predeterminado)</span>
                  </option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Selecciona el almacén desde donde se venderán los productos
                <span v-if="userAlmacenPredeterminado" class="text-blue-600">
                  · Tu almacén predeterminado está preseleccionado
                </span>
              </p>
            </div>

            <!-- Lista de Precios -->
            <div>
              <label for="price_list_id" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Lista de Precios
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01m-.01-6.5h.01M12 2.25a.75.75 0 00-.75.75v1.5c0 .414.336.75.75.75h.75a.75.75 0 00.75-.75V3a.75.75 0 00-.75-.75H12zM12 9a.75.75 0 00-.75.75v1.5c0 .414.336.75.75.75h.75a.75.75 0 00.75-.75V9.75A.75.75 0 0012.75 9H12z"/>
                  </svg>
                  Opcional
                </span>
              </label>
              <div class="relative">
                <select
                  id="price_list_id"
                  v-model="form.price_list_id"
                  @change="onPriceListChange"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                >
                  <option value="">Lista general (público)</option>
                  <option
                    v-for="priceList in priceLists"
                    :key="priceList.id"
                    :value="priceList.id"
                  >
                    {{ priceList.nombre }}
                  </option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                Selecciona una lista de precios específica. Si no seleccionas ninguna, se usarán los precios estándar.
              </p>
            </div>

            <!-- Vendedor (para comisiones) -->
            <div>
              <label for="vendedor" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Vendedor
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Comisiones
                </span>
              </label>
              <div class="relative">
                <select
                  id="vendedor"
                  @change="onVendedorChange"
                  v-model="vendedorSeleccionado"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                >
                  <option value="">Selecciona un vendedor</option>
                  <optgroup label="Vendedores">
                    <option
                      v-for="v in vendedoresFiltrados.filter(x => x.type === 'user')"
                      :key="`u-${v.id}`"
                      :value="`user-${v.id}`"
                    >
                      {{ v.nombre }}
                    </option>
                  </optgroup>
                  <optgroup label="Técnicos">
                    <option
                      v-for="v in vendedoresFiltrados.filter(x => x.type === 'tecnico')"
                      :key="`t-${v.id}`"
                      :value="`tecnico-${v.id}`"
                    >
                      {{ v.nombre }}
                    </option>
                  </optgroup>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500">
                El vendedor seleccionado recibirá la comisión por esta venta
              </p>
            </div>

          </div>
        </div>

        <!-- Cliente -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Información del Cliente
            </h2>
          </div>
          <div class="p-6">
            <BuscarCliente
              ref="buscarClienteRef"
              :clientes="clientesList"
              :cliente-seleccionado="clienteSeleccionado"
              @cliente-seleccionado="onClienteSeleccionado"
              @crear-nuevo-cliente="crearNuevoCliente"
            />
          </div>
        </div>

        <!-- Productos y Servicios -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Productos y Servicios
            </h2>
          </div>
          <div class="p-6">
            <BuscarProducto
              ref="buscarProductoRef"
              :productos="productos"
              :servicios="servicios"
              :almacen-id="form.almacen_id"
              :price-list-id="form.price_list_id"
              @agregar-producto="agregarProducto"
            />
            <PySSeleccionados
              :selectedProducts="selectedProducts"
              :productos="productos"
              :servicios="servicios"
              :quantities="quantities"
              :prices="prices"
              :discounts="discounts"
              :serials="serialsMap"
              @eliminar-producto="eliminarProducto"
              @update-quantity="updateQuantity"
              @update-discount="updateDiscount"
              @update-serials="updateSerials"
              @open-serials="openSerials"
              @open-kit-serials="handleKitComponentsSeries"
            />
          </div>
        </div>

        <!-- Notas -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Notas Adicionales
            </h2>
          </div>
          <div class="p-6">
            <textarea
              v-model="form.notas"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-vertical"
              rows="4"
              placeholder="Agrega notas adicionales, términos y condiciones, o información relevante para la venta..."
            ></textarea>
          </div>
        </div>

        <!-- Advertencia de Márgenes -->
        <div v-if="requiereConfirmacionMargen" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <div class="flex-1">
              <h3 class="text-sm font-medium text-yellow-800 mb-2">⚠️ Productos con margen insuficiente</h3>
              <div class="text-sm text-yellow-700 mb-3 whitespace-pre-line">{{ mensajeAdvertenciaMargen }}</div>
              <div class="flex gap-3">
                <button
                  @click="aceptarAjusteMargen"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                  Ajustar automáticamente
                </button>
                <button
                  @click="cancelarAjusteMargen"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                  Revisar precios
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Totales -->
        <Totales
          :show-margin-calculator="false"
          :margin-data="{ costoTotal: 0, precioVenta: 0, ganancia: 0, margenPorcentaje: 0 }"
          :totals="totales"
          :item-count="selectedProducts.length"
          :total-quantity="Object.values(quantities).reduce((sum, qty) => sum + (qty || 0), 0)"
          :iva-porcentaje="props.defaults?.ivaPorcentaje ?? 16"
          :enable-retencion-iva="props.defaults?.enableRetencionIva"
          :enable-retencion-isr="props.defaults?.enableRetencionIsr"
          :retencion-iva-default="Number(props.defaults?.retencionIvaDefault || 0)"
          :retencion-isr-default="Number(props.defaults?.retencionIsrDefault || 0)"
          v-model:aplicarRetencionIva="aplicarRetencionIva"
          v-model:aplicarRetencionIsr="aplicarRetencionIsr"
          @update:descuento-general="val => form.descuento_general = val"
        />

        <!-- Botones -->
        <BotonesAccion
          :back-url="route('ventas.index')"
          :is-processing="form.processing"
          :can-submit="form.cliente_id && form.almacen_id && selectedProducts.length > 0"
          :button-text="form.processing ? 'Guardando...' : 'Crear Venta'"
            @limpiar="limpiarFormulario"
        />
      </form>

      <!-- Atajos de teclado -->
      <button
        @click="mostrarAtajos = !mostrarAtajos"
        class="fixed bottom-4 left-4 bg-gray-600 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition-colors duration-200"
        title="Mostrar atajos de teclado"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </button>
    </div>
  </div>

  <!-- Modal Vista Previa -->
  <VistaPreviaModal
    :show="mostrarVistaPrevia"
    type="venta"
    :cliente="clienteSeleccionado"
    :items="selectedProducts"
    :totals="totales"
    :notas="form.notas"
    @close="mostrarVistaPrevia = false"
    @print="() => window.print()"
  />

  <!-- Modal Crear Cliente -->
  <CrearClienteModal
    :show="mostrarModalCliente"
    :catalogs="catalogs"
    :nombre-inicial="nombreClienteBuscado"
    @close="mostrarModalCliente = false"
    @cliente-creado="onClienteCreado"
  />

  <!-- Modal de errores -->
  <transition name="fade">
    <div
      v-if="showErrorModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeErrorModal"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Aviso</h3>
          <button @click="closeErrorModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="px-6 py-4">
          <p class="text-sm text-gray-700 mb-3">Revisa los siguientes puntos antes de continuar:</p>
          <ul class="list-disc list-inside space-y-2 text-sm text-gray-800">
            <li v-for="(msg, idx) in errorModalMessages" :key="idx">{{ msg }}</li>
          </ul>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
          <button
            type="button"
            @click="closeErrorModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-colors"
          >
            Aceptar
          </button>
        </div>
      </div>
    </div>
  </transition>

  <!-- Modal Seleccionar Series -->
  <div v-if="showSeriesPicker" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="closeSeriesPicker">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Seleccionar series: {{ pickerProducto?.nombre || '' }}</h3>
        <button @click="closeSeriesPicker" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="p-6">
        <div class="mb-3 text-sm text-gray-700">
          Selecciona exactamente {{ pickerRequired }} {{ pickerRequired === 1 ? 'serie' : 'series' }}. Seleccionadas: {{ selectedSeries.length }}.
        </div>
        <div class="mb-3 grid grid-cols-1 md:grid-cols-2 gap-3">
          <input v-model.trim="pickerSearch" type="text" placeholder="Buscar número de serie" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500" />
          <div class="text-xs text-gray-500 self-center">
            <span class="inline-block px-2 py-1 bg-emerald-100 text-emerald-700 rounded">En stock: {{ pickerSeries.length }}</span>
          </div>
        </div>
        <div class="max-h-72 overflow-y-auto border border-gray-200 rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-white">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Sel</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Número de serie</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">almacén</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="s in filteredPickerSeries" :key="s.id">
                <td class="px-4 py-2 text-sm">
                  <input type="checkbox" :checked="selectedSeries.includes(s.numero_serie)" @change="toggleSerie(s.numero_serie)" :disabled="!selectedSeries.includes(s.numero_serie) && selectedSeries.length >= pickerRequired" />
                </td>
                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ s.numero_serie }}</td>
                <td class="px-4 py-2 text-sm text-gray-700">{{ nombreAlmacen(s.almacen_id) }}</td>
              </tr>
              <tr v-if="filteredPickerSeries.length === 0">
                <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">Sin series disponibles</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-gray-200 bg-white text-right">
        <button @click="closeSeriesPicker" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors mr-2">Cancelar</button>
        <button @click="confirmSeries" :disabled="selectedSeries.length !== pickerRequired" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors disabled:opacity-50">Usar {{ selectedSeries.length }}/{{ pickerRequired }} series</button>
      </div>
    </div>
  </div>

  <!-- Modal Productos Sin Precio en Lista -->
  <transition name="fade">
    <div
      v-if="showFallbackPriceModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="closeFallbackPriceModal"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-amber-50">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-semibold text-gray-900">Aviso de Precios</h3>
          </div>
          <button @click="closeFallbackPriceModal" class="text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="px-6 py-4">
          <p class="text-sm text-gray-700 mb-3">
            Los siguientes productos <strong>no tienen precio definido</strong> en la lista de precios seleccionada. 
            Se usará el <strong>precio de venta base</strong>:
          </p>
          <ul class="list-disc list-inside space-y-2 text-sm text-gray-800 max-h-48 overflow-y-auto bg-white rounded-lg p-3">
            <li v-for="(prod, idx) in fallbackPriceProducts" :key="idx" class="flex justify-between items-center">
              <span class="truncate flex-1">{{ prod.nombre }}</span>
              <span class="text-amber-600 font-medium ml-2">${{ formatNumber(prod.precioUsado) }}</span>
            </li>
          </ul>
          <p class="text-xs text-gray-500 mt-3">
            💡 Puedes configurar precios específicos para cada lista de precios en el módulo de Productos.
          </p>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-white flex justify-end gap-3">
          <button
            type="button"
            @click="closeFallbackPriceModal"
            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"
          >
            Revisar Productos
          </button>
          <button
            type="button"
            @click="acceptFallbackPriceAndContinue"
            class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Aceptar y Continuar
          </button>
        </div>
      </div>
    </div>
  </transition>

  <!-- Modal Confirmación de Pago Instantáneo -->
  <transition name="fade">
    <div
      v-if="showPaymentConfirmationModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600">
          <h3 class="text-lg font-semibold text-white flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Confirmar Forma de Pago
          </h3>
        </div>
        
        <!-- Body -->
        <div class="px-6 py-4 space-y-4">
          <!-- Info de la venta a crear -->
          <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
            <p class="text-sm font-medium text-blue-900">Total de la venta</p>
            <p class="text-2xl font-bold text-blue-700 mt-1">{{ formatCurrency(form.total) }}</p>
          </div>

          <!-- Selector de forma de pago -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Forma de Pago *</label>
            <select 
              v-model="metodoPagoInmediato" 
              @change="onMetodoPagoChange"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Selecciona una opción</option>
              <option value="efectivo">💵 Efectivo</option>
              <option value="transferencia">🏦 Transferencia</option>
              <option value="tarjeta">💳 Tarjeta</option>
              <option value="cheque">📝 Cheque</option>
              <option value="credito">📅 Crédito</option>
            </select>
          </div>

          <!-- Calculadora de Cambio (solo para efectivo) -->
          <div v-if="metodoPagoInmediato === 'efectivo'" class="space-y-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="text-sm font-semibold text-blue-900 flex items-center">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              Calculadora de Cambio
            </h4>
            
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Importe Recibido</label>
              <div class="relative">
                <span class="absolute left-3 top-2.5 text-gray-500">$</span>
                <input
                  ref="inputEfectivo"
                  v-model="importeRecibido"
                  @input="calcularCambio"
                  type="text"
                  inputmode="decimal"
                  pattern="[0-9]*\.?[0-9]*"
                  class="w-full pl-7 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                  placeholder="0.00"
                  @keypress="validarSoloNumeros"
                />
              </div>
            </div>

            <!-- Resultado del cambio -->
            <div class="grid grid-cols-2 gap-3 text-sm">
              <div class="bg-white p-3 rounded border border-gray-200">
                <p class="text-xs text-gray-500">Total</p>
                <p class="text-lg font-bold text-gray-900">{{ formatCurrency(form.total) }}</p>
              </div>
              <div class="bg-white p-3 rounded border" :class="cambio < 0 ? 'border-red-300 bg-red-50' : cambio === 0 ? 'border-yellow-300 bg-yellow-50' : 'border-green-300 bg-green-50'">
                <p class="text-xs" :class="cambio < 0 ? 'text-red-600' : cambio === 0 ? 'text-yellow-600' : 'text-green-600'">
                  {{ cambio < 0 ? 'Falta' : cambio === 0 ? 'Exacto' : 'Cambio' }}
                </p>
                <p class="text-lg font-bold" :class="cambio < 0 ? 'text-red-700' : cambio === 0 ? 'text-yellow-700' : 'text-green-700'">
                  {{ formatCurrency(Math.abs(cambio)) }}
                </p>
              </div>
            </div>

            <!-- Advertencia si falta dinero -->
            <div v-if="cambio < 0" class="flex items-start gap-2 p-2 bg-red-100 border border-red-300 rounded text-xs text-red-800">
              <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.964-1.333-2.732 0L3.268 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              <span>El importe recibido es insuficiente</span>
            </div>
          </div>

          <!-- Opción de cuenta bancaria (para transferencia, tarjeta, etc.) -->
          <div v-if="metodoPagoInmediato && metodoPagoInmediato !== 'efectivo' && metodoPagoInmediato !== 'credito'">
            <label class="block text-sm font-medium text-gray-700 mb-2">Cuenta Bancaria (opcional)</label>
            <select v-model="cuentaBancariaInmediata" class="w-full border border-gray-300 rounded-lg px-4 py-2">
              <option value="">Sin especificar</option>
              <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                {{ cuenta.nombre }} - {{ cuenta.banco }}
              </option>
            </select>
          </div>

          <!-- Notas opcionales -->
          <div v-if="metodoPagoInmediato">
            <label class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
            <textarea 
              v-model="notasPagoInmediato" 
              rows="2" 
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" 
              placeholder="Agrega notas del pago..."
            ></textarea>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-white border-t border-gray-200 flex justify-end gap-3">
          <button
            @click="cerrarModalPago"
            class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"
          >
            Cancelar
          </button>
          <button
            v-if="metodoPagoInmediato"
            @click="crearVentaConPago"
            :disabled="(metodoPagoInmediato === 'efectivo' && cambio < 0) || form.processing"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
          >
            <span v-if="form.processing">Creando venta...</span>
            <span v-else-if="metodoPagoInmediato === 'efectivo' && cambio > 0">
              Crear Venta (Cambio: {{ formatCurrency(cambio) }})
            </span>
            <span v-else>
              Crear Venta
            </span>
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import { resolverPrecio, detectarProductosSinPrecioEnLista } from '@/Utils/precioHelper'; // ✅ Importar helper
import AppLayout from '@/Layouts/AppLayout.vue';
import Header from '@/Components/CreateComponents/Header.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';
import PySSeleccionados from '@/Components/CreateComponents/PySSeleccionados.vue';
import Totales from '@/Components/CreateComponents/Totales.vue';
import BotonesAccion from '@/Components/CreateComponents/BotonesAccion.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';
import CrearClienteModal from '@/Components/Modals/CrearClienteModal.vue';

// Inicializar notificaciones
const notyf = new Notyf({
  duration: 5000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10B981', icon: { className: 'notyf__icon--success', tagName: 'i', text: '✓' } },
    { type: 'warning', background: '#F59E0B', icon: { className: 'notyf__icon--warning', tagName: 'i', text: '!' } },
    { type: 'info', background: '#3B82F6', icon: { className: 'notyf__icon--info', tagName: 'i', text: 'ℹ' } },
  ],
});

const showErrorModal = ref(false);
const errorModalMessages = ref([]);

const closeErrorModal = () => {
  showErrorModal.value = false;
  errorModalMessages.value = [];
};

// Modal de precios fallback
const showFallbackPriceModal = ref(false);
const fallbackPriceProducts = ref([]);
const fallbackPriceAccepted = ref(false);

const closeFallbackPriceModal = () => {
  showFallbackPriceModal.value = false;
};

const acceptFallbackPriceAndContinue = () => {
  fallbackPriceAccepted.value = true;
  showFallbackPriceModal.value = false;
  
  // Continuar con el modal de pago
  // Calcular totales
  calcularTotal();
  
  // Resetear valores del modal de pago
  metodoPagoInmediato.value = '';
  cuentaBancariaInmediata.value = '';
  notasPagoInmediato.value = '';
  importeRecibido.value = 0;
  cambio.value = 0;
  
  // Mostrar modal de pago
  showPaymentConfirmationModal.value = true;
};

const formatNumber = (value) => {
  return parseFloat(value || 0).toFixed(2);
};

const parseStockErrors = (message) => {
  // Si no es un mensaje de stock múltiple, devolver como array simple
  if (!message.includes('Stock insuficiente para componente')) {
    return [message];
  }

  // Separar por "Stock insuficiente" para obtener cada error individual
  // Usamos una regex que busca el inicio de cada mensaje de error
  const errors = message.split(/,\s*(?=Stock insuficiente)/);
  
  const formattedErrors = [];
  let currentHeader = '';
  
  errors.forEach(err => {
    // Regex para extraer partes del mensaje:
    // "Stock insuficiente para componente 'NOMBRE' del kit 'KIT' en ALMACEN. Disponible: X, Necesario: Y"
    const match = err.match(/componente '([^']+)' del kit '([^']+)' en (.+)\. Disponible: (\d+), Necesario: (\d+)/);
    
    if (match) {
      const [_, componente, kit, almacen, disponible, necesario] = match;
      
      // Crear un encabezado si cambia el kit o almacén
      const header = `Stock insuficiente en ${almacen} para el kit ${kit}:`;
      if (header !== currentHeader) {
        formattedErrors.push(header);
        currentHeader = header;
      }
      
      // Agregar el detalle del componente con viñeta
      formattedErrors.push(`• ${componente} (Necesario: ${necesario}, Disponible: ${disponible})`);
    } else {
      // Si no coincide con el patrón, mostrar tal cual
      formattedErrors.push(err.trim());
    }
  });
  
  return formattedErrors;
};

const openErrorModal = (messages) => {
  let list = [];
  
  if (Array.isArray(messages)) {
    list = messages;
  } else if (typeof messages === 'string') {
    // Intentar parsear si es un string largo de errores de stock
    if (messages.includes('Stock insuficiente')) {
      list = parseStockErrors(messages);
    } else {
      list = messages.split(';').map(m => m.trim()).filter(Boolean);
    }
  } else {
    list = [String(messages || 'Ocurrió un error')];
  }

  errorModalMessages.value = list.length ? list : ['Ocurrió un error'];
  showErrorModal.value = true;
};

const showNotification = (message, type = 'success') => {
  if (type === 'error') {
    openErrorModal(message);
    return;
  }
  notyf.open({ type, message });
};

// Usar layout
defineOptions({ layout: AppLayout });

// Props
const props = defineProps({
  clientes: Array,
  productos: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  catalogs: { type: Object, default: () => ({}) },
  almacenes: { type: Array, default: () => [] },
  priceLists: { type: Array, default: () => [] },
  vendedores: { type: Array, default: () => [] }, // ✅ NEW: Lista de vendedores y técnicos para comisiones
  user: { type: Object, default: () => ({}) },
  pedido: { type: Object, default: () => null },
  cita: { type: Object, default: () => null },
  defaults: { type: Object, default: () => ({ ivaPorcentaje: 16, isrPorcentaje: 1.25 }) },
});

// Copia reactiva de clientes para evitar mutación de props
const clientesList = ref([...props.clientes]);

// Catalogs para el modal
const catalogs = computed(() => props.catalogs);

// almacén predeterminado del usuario
const userAlmacenPredeterminado = computed(() => props.user?.almacen_venta_id || null);

// Número de venta (se obtiene del backend)
const numeroVentaFijo = ref('V0001');

// Obtener fecha actual en formato YYYY-MM-DD (zona horaria local)
const getCurrentDate = () => {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Formulario
const form = useForm({
  numero_venta: numeroVentaFijo,
  fecha: getCurrentDate(),
  cliente_id: '',
  price_list_id: '',
  almacen_id: userAlmacenPredeterminado.value || '',
  vendedor_type: 'App\\Models\\User', // ✅ NEW: Tipo de vendedor para comisiones
  vendedor_id: '', // ✅ NEW: ID del vendedor para comisiones
  metodo_pago: '',
  forma_pago_sat: '',    // ✅ Clave SAT c_FormaPago (01, 03, 04, etc.)
  metodo_pago_sat: '',   // ✅ Clave SAT c_MetodoPago (PUE, PPD)
  subtotal: 0,
  descuento_items: 0,
  iva: 0,
  retencion_iva: 0,
  retencion_isr: 0,
  total: 0,
  productos: [],
  servicios: [],
  notas: '',
  estado: 'borrador',
  cuenta_bancaria_id: '', // ✅ Initialize
  cita_id: '',
});

// Referencias
const buscarClienteRef = ref(null);
const buscarProductoRef = ref(null);

// Estado fiscal
const aplicarRetencionIva = ref(false);
const aplicarRetencionIsr = ref(false);
const retencionIvaDefault = computed(() => Number(props.defaults?.retencionIvaDefault || 0));
const retencionIsrDefault = computed(() => Number(props.defaults?.retencionIsrDefault || 0));

// Estado
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const serialsMap = ref({});
const clienteSeleccionado = ref(null);
const mostrarVistaPrevia = ref(false);
const mostrarAtajos = ref(true);
const requiereConfirmacionMargen = ref(false);
const mensajeAdvertenciaMargen = ref('');
const mostrarModalCliente = ref(false);
const nombreClienteBuscado = ref('');

// Vendedor seleccionado para comisiones
const vendedorSeleccionado = ref('');
const vendedoresFiltrados = computed(() => props.vendedores || []);

// Auto-seleccionar vendedor predeterminado
// - Si el usuario logueado es vendedor → se selecciona a sí mismo
// - Si es admin/user → se selecciona Jesus Lopez
const seleccionarVendedorPredeterminado = () => {
  const currentUserId = props.user?.id;
  const currentUserInList = vendedoresFiltrados.value.find(v => v.id === currentUserId && v.type === 'user');
  
  if (currentUserInList) {
    // El usuario logueado está en la lista de vendedores → se selecciona a sí mismo
    vendedorSeleccionado.value = `user-${currentUserId}`;
  } else {
    // El usuario no es vendedor → seleccionar Jesus Lopez
    const jesusLopez = vendedoresFiltrados.value.find(v => 
      v.nombre.toLowerCase().includes('jesus') && v.nombre.toLowerCase().includes('lopez')
    );
    if (jesusLopez) {
      vendedorSeleccionado.value = `${jesusLopez.type}-${jesusLopez.id}`;
    }
  }
  onVendedorChange();
};

// Handler para cambio de vendedor
const onVendedorChange = () => {
  const sel = vendedorSeleccionado.value;
  if (!sel) {
    form.vendedor_type = '';
    form.vendedor_id = '';
    return;
  }
  const [type, id] = sel.split('-');
  form.vendedor_type = type === 'user' ? 'App\\Models\\User' : 'App\\Models\\Tecnico';
  form.vendedor_id = parseInt(id);
};

// Estado para modal de pago inmediato
const showPaymentConfirmationModal = ref(false);
const metodoPagoInmediato = ref('');
const cuentaBancariaInmediata = ref('');
const notasPagoInmediato = ref('');
const cuentasBancarias = ref([]);
const importeRecibido = ref(''); // Vacío para que usuario teclee
const cambio = ref(0);
const inputEfectivo = ref(null); // Ref para auto-focus
const notasEfectivoAgregadas = ref(false); // ✅ Control para evitar duplicación de notas




// Función para manejar localStorage de forma segura
const saveToLocalStorage = (key, data) => {
  try {
    localStorage.setItem(key, JSON.stringify(data));
  } catch (error) {
    console.warn('No se pudo guardar en localStorage:', error);
  }
};

const loadFromLocalStorage = (key) => {
  try {
    const item = localStorage.getItem(key);
    return item ? JSON.parse(item) : null;
  } catch (error) {
    console.warn('No se pudo cargar desde localStorage:', error);
    return null;
  }
};

const removeFromLocalStorage = (key) => {
  try {
    localStorage.removeItem(key);
  } catch (error) {
    console.warn('No se pudo eliminar de localStorage:', error);
  }
};

// --- FUNCIONES DEL MODAL DE PAGO ---

// Validar que solo se ingresen números y punto decimal
const validarSoloNumeros = (event) => {
  const char = event.key;
  // Permitir números, punto decimal, y teclas de control
  if (!/[0-9.]/.test(char) && event.key !== 'Backspace' && event.key !== 'Delete' && event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
    event.preventDefault();
  }
  // Prevenir más de un punto decimal
  if (char === '.' && importeRecibido.value && importeRecibido.value.toString().includes('.')) {
    event.preventDefault();
  }
};

// Calcular cambio automáticamente
const calcularCambio = () => {
  const total = form.total || 0;
  const recibido = parseFloat(importeRecibido.value) || 0;
  cambio.value = recibido - total;
};

// Cargar cuentas bancarias
const cargarCuentasBancarias = async () => {
  try {
    const response = await axios.get('/api/cuentas-bancarias/activas');
    cuentasBancarias.value = response.data;
  } catch (error) {
    console.error('Error cargando cuentas bancarias:', error);
  }
};

// Abrir modal de pago (se llama desde el botón "Crear Venta")
const abrirModalPago = async () => {
  // 1. Validar datos básicos
  if (!validarDatos()) return;
  
  // 2. ✅ Verificar productos con precio fallback (sin precio en lista)
  if (form.price_list_id && !fallbackPriceAccepted.value) {
    const productosSinPrecio = detectarProductosSinPrecioEnLista(
      selectedProducts.value,
      props.productos,
      form.price_list_id
    );
    
    if (productosSinPrecio.length > 0) {
      // Mostrar modal informativo con los productos que usan precio base
      fallbackPriceProducts.value = productosSinPrecio;
      showFallbackPriceModal.value = true;
      return; // Esperar a que el usuario acepte
    }
  }
  
  // 3. Calcular totales
  calcularTotal();
  
  // 4. Resetear valores del modal de pago
  metodoPagoInmediato.value = '';
  cuentaBancariaInmediata.value = '';
  notasPagoInmediato.value = '';
  importeRecibido.value = '';
  cambio.value = 0;
  notasEfectivoAgregadas.value = false; // ✅ Resetear control de notas
  
  // 5. Mostrar modal de pago
  showPaymentConfirmationModal.value = true;
};

// Cerrar modal sin crear venta
const cerrarModalPago = () => {
  showPaymentConfirmationModal.value = false;
  metodoPagoInmediato.value = '';
  importeRecibido.value = '';
  cambio.value = 0;
  notasEfectivoAgregadas.value = false; // ✅ Resetear control de notas
};

// Cuando cambia el método de pago
const onMetodoPagoChange = () => {
  if (metodoPagoInmediato.value === 'efectivo') {
    // NO pre-llenar, solo limpiar y hacer focus
    importeRecibido.value = '';
    cambio.value = 0;
    // Auto-focus en el input después de un pequeño delay
    nextTick(() => {
      inputEfectivo.value?.focus();
    });
  } else {
    importeRecibido.value = '';
    cambio.value = 0;
  }
};

// Crear venta con el método de pago selecciona do
const crearVentaConPago = () => {
  console.log('🔍 DEBUG - crearVentaConPago iniciado');
  console.log('metodoPagoInmediato.value:', metodoPagoInmediato.value);
  
  // Validar que se haya seleccionado un método de pago
  if (!metodoPagoInmediato.value || metodoPagoInmediato.value === '') {
    showNotification('Debes seleccionar una forma de pago', 'error');
    return;
  }

  // Validar que si es efectivo, el importe sea suficiente
  if (metodoPagoInmediato.value === 'efectivo' && cambio.value < 0) {
    showNotification('El importe recibido es insuficiente', 'error');
    return;
  }

  console.log('✅ Validaciones pasadas');
  console.log('form.metodo_pago ANTES:', form.metodo_pago);
  
  // Asignar método de pago al formulario
  form.metodo_pago = metodoPagoInmediato.value;
  
  // ✅ Mapear forma de pago interna a clave SAT c_FormaPago
  const mapeoFormaPagoSat = {
    'efectivo': '01',       // Efectivo
    'transferencia': '03',  // Transferencia electrónica de fondos
    'tarjeta': '04',        // Tarjeta de crédito (o 28 para débito)
    'cheque': '02',         // Cheque nominativo
    'credito': '99',        // Por definir (PPD)
  };
  form.forma_pago_sat = mapeoFormaPagoSat[metodoPagoInmediato.value] || '99';
  
  // ✅ Asignar método de pago SAT (PUE o PPD)
  // PUE = Pago en Una sola Exhibición (pago inmediato)
  // PPD = Pago en Parcialidades o Diferido (crédito)
  form.metodo_pago_sat = metodoPagoInmediato.value === 'credito' ? 'PPD' : 'PUE';
  
  // ✅ Asignar cuenta bancaria si aplica
  if (metodoPagoInmediato.value !== 'efectivo' &&
      metodoPagoInmediato.value !== 'credito' &&
      cuentaBancariaInmediata.value) {
    form.cuenta_bancaria_id = cuentaBancariaInmediata.value;
  } else {
    form.cuenta_bancaria_id = null;
  }

  console.log('form.metodo_pago DESPUÉS:', form.metodo_pago);
  console.log('form.cuenta_bancaria_id:', form.cuenta_bancaria_id);
  console.log('form completo:', form);
  
  // ✅ Agregar info de cambio a las notas SOLO si es efectivo y no se ha agregado antes
  if (metodoPagoInmediato.value === 'efectivo' && importeRecibido.value > 0 && !notasEfectivoAgregadas.value) {
    const infoEfectivo = `Recibido: ${formatCurrency(importeRecibido.value)} - Cambio: ${formatCurrency(Math.abs(cambio.value))}`;
    form.notas = form.notas ? `${form.notas}\n${infoEfectivo}` : infoEfectivo;
    notasEfectivoAgregadas.value = true; // Marcar como agregado
  }
  
  // Cerrar modal
  showPaymentConfirmationModal.value = false;
  
  console.log('📤 Llamando a submitVentaAfterValidation con form.metodo_pago =', form.metodo_pago);
  
  // Llamar a la función de crear venta original
  submitVentaAfterValidation();
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

// --- FUNCIONES ---

// Obtener el siguiente número de venta del backend
const fetchNextNumeroVenta = async () => {
  try {
    const response = await axios.get('/ventas/siguiente-numero');
    if (response.data && response.data.siguiente_numero) {
      numeroVentaFijo.value = response.data.siguiente_numero;
      form.numero_venta = response.data.siguiente_numero;
    }
  } catch (error) {
    console.error('Error al obtener el número de venta:', error);
   numeroVentaFijo.value = 'V0001';
    form.numero_venta = 'V0001';
  }
};

// ✅ NEW: Cargar datos desde pedido
const loadFromPedido = () => {
  if (!props.pedido) return;

  const p = props.pedido;
  showNotification(`Cargando datos del pedido #${p.numero_pedido}...`, 'info');

  // 1. Cargar Cliente
  if (p.cliente) {
    onClienteSeleccionado(p.cliente);
  }

  // 2. Cargar almacén (si el pedido tiene uno y coincide con los disponibles, o usar el del usuario)
  // Nota: Los pedidos no siempre tienen almacén explícito, pero si lo tuvieran:
  // if (p.almacen_id) form.almacen_id = p.almacen_id;

  // 3. Cargar Notas
  if (p.notas) {
    form.notas = `[Desde Pedido #${p.numero_pedido}] ${p.notas}`;
  } else {
    form.notas = `Generado desde Pedido #${p.numero_pedido}`;
  }

  // 4. Cargar Productos y Servicios
  if (Array.isArray(p.items)) {
    p.items.forEach(item => {
      // Determinar si es producto o servicio
      const tipo = (item.pedible_type && (item.pedible_type.includes('Producto') || item.pedible_type === 'producto')) ? 'producto' : 'servicio';
      
      // Buscar el objeto completo en los catálogos locales para tener toda la info (precios, stock, etc)
      let catalogoItem = null;
      if (tipo === 'producto') {
        catalogoItem = props.productos.find(x => x.id === item.pedible_id);
      } else {
        catalogoItem = props.servicios.find(x => x.id === item.pedible_id);
      }

      // Si no está en el catálogo (ej. inactivo), usar datos básicos del item
      const itemData = catalogoItem ? {
        id: catalogoItem.id,
        tipo: tipo,
        nombre: catalogoItem.nombre,
        precio_venta: catalogoItem.precio_venta, // Para productos
        precio: catalogoItem.precio, // Para servicios
        requiere_serie: catalogoItem.requiere_serie, // Importante
        tipo_producto: catalogoItem.tipo_producto,
      } : {
        id: item.pedible_id,
        tipo: tipo,
        nombre: item.pedible?.nombre || 'Item desconocido',
        precio: item.precio,
        requiere_serie: false,
        tipo_producto: item.pedible?.tipo_producto,
      };

      // Agregar al carrito
      agregarProducto(itemData);

      // Actualizar cantidad y precio específicos del pedido (pueden ser diferentes al catálogo)
      const key = `${tipo}-${itemData.id}`;
      
      // Forzar cantidad del pedido
      quantities.value[key] = parseFloat(item.cantidad);
      
      // Forzar precio del pedido (respetar precio pactado)
      prices.value[key] = parseFloat(item.precio);
      
      // Forzar descuento del pedido
      if (item.descuento) {
        discounts.value[key] = parseFloat(item.descuento);
      }
    });

    // Recalcular totales
    calcularTotal();
    
    // Aplicar descuento general del pedido si existe
    if (p.descuento_general) {
      // Calcular porcentaje aproximado o monto fijo? 
      // El componente Totales emite update:descuento-general como monto o porcentaje dependiendo de la implementación.
      // Asumiremos que form.descuento_general es MONTO en este contexto si el backend lo maneja así, 
      // pero usualmente en frontend se maneja porcentaje o monto. 
      // Revisando VentaController, descuento_general es monto.
      // Revisando Totales.vue (no visible pero inferido), usualmente es input de monto.
      form.descuento_general = parseFloat(p.descuento_general);
    }
  }
};

const loadFromCita = () => {
  if (!props.cita) return;

  const c = props.cita;
  showNotification(`Cargando datos de la cita #${c.id}...`, 'info');

  form.cita_id = c.id;

  // 1. Cargar Cliente
  if (c.cliente) {
    onClienteSeleccionado(c.cliente);
  }

  // 2. Cargar Notas
  form.notas = `Generado desde Cita #${c.id}. ${c.descripcion || ''}`;

  // 3. Cargar Productos y Servicios
  if (Array.isArray(c.items)) {
    c.items.forEach(async (item) => {
      const tipo = (item.citable_type && (item.citable_type.includes('Producto') || item.citable_type === 'producto')) ? 'producto' : 'servicio';
      
      let catalogoItem = null;
      if (tipo === 'producto') {
        catalogoItem = props.productos.find(x => x.id === item.citable_id);
      } else {
        catalogoItem = props.servicios.find(x => x.id === item.citable_id);
      }

      const itemData = catalogoItem ? {
        id: catalogoItem.id,
        tipo: tipo,
        nombre: catalogoItem.nombre,
        precio_venta: catalogoItem.precio_venta,
        precio: catalogoItem.precio,
        requiere_serie: catalogoItem.requiere_serie,
        tipo_producto: catalogoItem.tipo_producto,
      } : {
        id: item.citable_id,
        tipo: tipo,
        nombre: item.citable?.nombre || 'Item desconocido',
        precio: item.precio,
        requiere_serie: false,
        tipo_producto: item.citable?.tipo_producto,
      };

      await agregarProducto(itemData);

      const key = `${tipo}-${itemData.id}`;
      // Usar la cantidad de la cita
      if (typeof item.cantidad !== 'undefined') {
        setTimeout(() => {
          quantities.value[key] = item.cantidad;
          if (typeof item.precio !== 'undefined') {
            prices.value[key] = item.precio;
          }
          if (typeof item.descuento !== 'undefined') {
            discounts.value[key] = item.descuento;
          }
          calcularTotal();
        }, 300);
      }
    });

    // Recalcular totales después de cargar todos
    setTimeout(() => {
        calcularTotal();
    }, 1000);
  }
};

// Header
const handlePreview = () => {
  if (clienteSeleccionado.value && selectedProducts.value.length > 0) {
    mostrarVistaPrevia.value = true;
  } else {
    showNotification('Selecciona un cliente y al menos un producto', 'error');
  }
};

// Actualizar series desde el componente hijo
const updateSerials = (key, serials) => {
  serialsMap.value[key] = serials;
};

// Selector de series
const showSeriesPicker = ref(false);
const pickerKey = ref('');
const pickerProducto = ref(null);
const pickerSeries = ref([]); // en_stock
const pickerSearch = ref('');
const selectedSeries = ref([]);
const pickerRequiredOverride = ref(null);
const pickerRequired = computed(() => {
  if (pickerRequiredOverride.value !== null) return pickerRequiredOverride.value;
  if (!pickerKey.value) return 0;
  const q = quantities.value[pickerKey.value];
  return Number.parseFloat(q) || 0;
});

const nombreAlmacen = (id) => {
  if (!id) return 'N/D';
  // Buscar en props.almacenes si está disponible
  const a = props.almacenes?.find(x => String(x.id) === String(id));
  return a ? a.nombre : `ID ${id}`;
};

const filteredPickerSeries = computed(() => {
  const q = (pickerSearch.value || '').toLowerCase();
  let list = pickerSeries.value || [];

  // Filtrar estrictamente por almacén seleccionado en el formulario
  if (form.almacen_id) {
    list = list.filter(s => String(s.almacen_id) === String(form.almacen_id));
  }

  return q ? list.filter(s => (s.numero_serie || '').toLowerCase().includes(q)) : list;
});

const openSerials = async (entry) => {
  try {
    pickerRequiredOverride.value = null; // Limpiar override para productos normales
    pickerKey.value = `${entry.tipo}-${entry.id}`;
    pickerProducto.value = props.productos.find(p => p.id === entry.id) || { id: entry.id, nombre: entry.nombre || 'Producto' };
    // cargar series del backend filtradas por almacén
    let url = '';
    try { url = route('productos.series', entry.id) } catch (e) { url = `/productos/${entry.id}/series`; }
    url += `?almacen_id=${form.almacen_id}`;
    const res = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
    if (!res.ok) { showNotification('No se pudieron cargar las series', 'error'); return; }
    const data = await res.json();
    pickerSeries.value = data?.series?.en_stock || [];
    const prev = serialsMap.value[pickerKey.value] || [];
    selectedSeries.value = Array.isArray(prev) ? prev.slice(0, pickerRequired.value) : [];
    showSeriesPicker.value = true;
  } catch (e) {
    console.error('Error al abrir selector de series:', e);
    showNotification('Error al abrir selector de series', 'error');
  }
};

const closeSeriesPicker = () => {
  showSeriesPicker.value = false;
  pickerKey.value = '';
  pickerProducto.value = null;
  pickerSeries.value = [];
  pickerSearch.value = '';
  selectedSeries.value = [];
  pickerRequiredOverride.value = null; // Reset override
};

const toggleSerie = (numero) => {
  const idx = selectedSeries.value.indexOf(numero);
  if (idx >= 0) {
    selectedSeries.value.splice(idx, 1);
  } else if (selectedSeries.value.length < pickerRequired.value) {
    const serieObj = pickerSeries.value.find(s => s.numero_serie === numero);
    if (serieObj && serieObj.almacen_id && String(form.almacen_id) !== String(serieObj.almacen_id)) {
      const nombre = nombreAlmacen(serieObj.almacen_id);
      showNotification('La serie pertenece al almacén "' + nombre + '". Cambia el almacén de la venta para usarla.', 'error');
      return;
    }
    selectedSeries.value.push(numero);

    // Auto-confirm when required number is reached
    if (selectedSeries.value.length === pickerRequired.value) {
      setTimeout(() => {
        confirmSeries();
      }, 300); // Small delay to show the selection
    }
  }
};

const confirmSeries = () => {
  if (!pickerKey.value) return;
  if (selectedSeries.value.length !== pickerRequired.value) {
    showNotification(`Debes seleccionar ${pickerRequired.value} series`, 'error');
    return;
  }
  serialsMap.value[pickerKey.value] = selectedSeries.value.slice();
  closeSeriesPicker();
  notyf.success('Series seleccionadas');
};

// Manejar series para componentes de kit
const handleKitComponentsSeries = async (kit) => {
  try {
    // Obtener detalles del kit con componentes
    const response = await axios.get(`/kits/api/${kit.id}`);
    const kitData = response.data || {};

    if (!Array.isArray(kitData.kit_items) || kitData.kit_items.length === 0) {
      showNotification('Este kit no tiene componentes con series configuradas.', 'info');
      return;
    }

    // ✅ FIX Error #2: Cargar snapshot de TODAS las series disponibles de UNA VEZ
    // Esto reduce la ventana de race conditions
    const componentsNeedingSeries = kitData.kit_items.filter(kitItem => {
      const componente = kitItem.item || kitItem.producto;
      return componente && kitItem.item_type === 'producto' && 
             (componente.requiere_serie || componente.maneja_series || componente.expires);
    });

    if (componentsNeedingSeries.length === 0) {
      return;
    }

    // Cargar todas las series en una sola transacción (snapshot)
    const seriesSnapshot = {};
    const seriesPromises = componentsNeedingSeries.map(async (kitItem) => {
      const componente = kitItem.item || kitItem.producto;
      try {
        let componentUrl = `/productos/${componente.id}/series`;
        componentUrl += `?almacen_id=${form.almacen_id}`;
        
        const seriesResponse = await fetch(componentUrl, {
          method: 'GET',
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin'
        });
        
        const seriesData = await seriesResponse.json();
        seriesSnapshot[componente.id] = seriesData?.series?.en_stock || [];
      } catch (error) {
        console.error(`Error cargando series para componente ${componente.id}:`, error);
        seriesSnapshot[componente.id] = [];
      }
    });

    // Esperar a que todas las series se carguen
    await Promise.all(seriesPromises);

    // ✅ Ahora procesar componentes secuencialmente usando el snapshot
    for (const kitItem of componentsNeedingSeries) {
      const componente = kitItem.item || kitItem.producto;
      const cantidadNecesaria = kitItem.cantidad * (quantities.value[`producto-${kit.id}`] || 1);
      
      // Usar series del snapshot en lugar de cargar individualmente
      await openSerialsForKitComponent(
        kit, 
        componente, 
        cantidadNecesaria, 
        kitItem,
        seriesSnapshot[componente.id] || [] // ✅ Pasar snapshot
      );
    }
  } catch (error) {
    console.error('Error al cargar componentes del kit:', error);
    const mensaje = error.response?.data?.message || 'Error al verificar componentes del kit';
    showNotification(mensaje, 'error');
  }
};

// Abrir selector de series para componente de kit
// ✅ FIX Error #2: Ahora acepta series pre-cargadas como parámetro
const openSerialsForKitComponent = async (kit, componente, cantidadNecesaria, kitItem, seriesSnapshot = null) => {
  return new Promise((resolve) => {
    const componentKey = `kit-${kit.id}-component-${componente.id}`;

    // Configurar el picker para este componente
    pickerKey.value = componentKey;
    pickerProducto.value = {
      id: componente.id,
      nombre: `${componente.nombre} (Componente de ${kit.nombre})`,
      tipo: 'producto'
    };

    // Usar override para establecer la cantidad requerida
    pickerRequiredOverride.value = cantidadNecesaria;

    // ✅ FIX Error #2: Si se provee snapshot, usarlo directamente
    if (seriesSnapshot !== null) {
      pickerSeries.value = seriesSnapshot;
      selectedSeries.value = serialsMap.value[componentKey] || [];
      showSeriesPicker.value = true;

      // Esperar a que se confirme la selección
      const checkClosed = setInterval(() => {
        if (!showSeriesPicker.value) {
          clearInterval(checkClosed);
          resolve();
        }
      }, 100);
    } else {
      // Fallback: Cargar series individualmente (comportamiento antiguo)
      let componentUrl = '';
      try { componentUrl = route('productos.series', componente.id) } catch (e) { componentUrl = `/productos/${componente.id}/series`; }
      componentUrl += `?almacen_id=${form.almacen_id}`;

      fetch(componentUrl, {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      })
      .then(res => res.json())
      .then(data => {
        pickerSeries.value = data?.series?.en_stock || [];
        selectedSeries.value = serialsMap.value[componentKey] || [];
        showSeriesPicker.value = true;

        // Esperar a que se confirme la selección
        const checkClosed = setInterval(() => {
          if (!showSeriesPicker.value) {
            clearInterval(checkClosed);
            resolve();
          }
        }, 100);
      })
      .catch(error => {
        console.error('Error al cargar series del componente:', error);
        showNotification(`Error al cargar series de ${componente.nombre}`, 'error');
        resolve();
      });
    }
  });
};

const closeShortcuts = () => {
  mostrarAtajos.value = false;
};

// Cliente
const onClienteSeleccionado = (cliente) => {
  if (!cliente) {
    clienteSeleccionado.value = null;
    form.cliente_id = '';
    saveState();
    showNotification('Selección de cliente limpiada', 'info');
    return;
  }
  if (clienteSeleccionado.value?.id === cliente.id) return;
  clienteSeleccionado.value = cliente;
  form.cliente_id = cliente.id;

  // Establecer lista de precios del cliente como predeterminada
  if (cliente.price_list_id) {
    form.price_list_id = cliente.price_list_id;
    // Recalcular precios con la nueva lista
    recalcularPreciosPorLista();
  }

  saveState();
  showNotification(`Cliente seleccionado: ${cliente.nombre_razon_social}`);
};

// Cambiar lista de precios
const onPriceListChange = () => {
  fallbackPriceAccepted.value = false; // ✅ Reset para revalidar con nueva lista
  fallbackPriceProducts.value = [];
  recalcularPreciosPorLista();
};

// Recalcular precios según la lista seleccionada
const recalcularPreciosPorLista = async () => {
  if (selectedProducts.value.length === 0) return;
  if (!form.almacen_id) {
    showNotification('Selecciona un almacén antes de recalcular precios', 'warning');
    return;
  }

  try {
    const response = await axios.post('/productos/recalcular-precios', {
      productos: selectedProducts.value.map(entry => ({
        id: entry.id,
        tipo: entry.tipo
      })),
      price_list_id: form.price_list_id || null,
      almacen_id: form.almacen_id
    });

    if (response.data && response.data.precios) {
      // Actualizar precios en el estado
      Object.keys(response.data.precios).forEach(key => {
        if (response.data.precios[key] !== undefined) {
          prices.value[key] = parseFloat(response.data.precios[key]);
        }
      });

      calcularTotal();
      saveState();
      showNotification('Precios actualizados según la lista seleccionada', 'info');
    }
  } catch (error) {
    console.error('Error recalculando precios:', error);
    showNotification('Error al recalcular precios', 'error');
  }
};

const crearNuevoCliente = (nombreBuscado) => {
  nombreClienteBuscado.value = nombreBuscado;
  mostrarModalCliente.value = true;
};

const onClienteCreado = (nuevoCliente) => {
  // Actualizar la copia reactiva en lugar de mutar props
  if (!clientesList.value.some(c => c.id === nuevoCliente.id)) {
    clientesList.value.push(nuevoCliente);
  }

  onClienteSeleccionado(nuevoCliente);
};

// Productos
const agregarProducto = async (item) => {
  if (!item || typeof item.id === 'undefined' || !item.tipo) {
    showNotification('Producto inválido', 'error');
    return;
  }

  const itemEntry = {
    id: item.id,
    tipo: item.tipo,
    nombre: item.nombre || item.descripcion || 'Producto',
    tipo_producto: item.tipo_producto,
    requiere_serie: item.requiere_serie,
    maneja_series: item.maneja_series,
    expires: item.expires,
  };
  const exists = selectedProducts.value.some(
    (entry) => entry.id === item.id && entry.tipo === item.tipo
  );

  if (!exists) {
    selectedProducts.value.push(itemEntry);
    const key = `${item.tipo}-${item.id}`;
    quantities.value[key] = 1;

    // Validar precios con fallbacks seguros - usar parseFloat para manejar strings y numbers
    let precio = 0;
    if (item.tipo === 'producto') {
      // ✅ Resolver precio según lista seleccionada
      precio = resolverPrecio(item, form.price_list_id);
    } else {
      precio = parseFloat(item.precio) || 0;
    }

    prices.value[key] = precio;
    discounts.value[key] = 0;
    calcularTotal();
    saveState();
    showNotification(`Producto añadido: ${item.nombre || item.descripcion || 'Item'}`);

    // Si el producto requiere serie, abrir el selector de series inmediatamente
    if (item.requiere_serie) {
      openSerials(itemEntry);
    }

    // Si es un kit, verificar si tiene componentes que requieren series
    if (item.tipo === 'producto' && item.tipo_producto === 'kit') {
      await handleKitComponentsSeries(item);
    }

    // Recalcular precios segǹn la lista seleccionada (si aplica)
    if (form.price_list_id || clienteSeleccionado.value?.price_list_id) {
      await recalcularPreciosPorLista();
    }
  }
};

const eliminarProducto = (entry) => {
  if (!entry || typeof entry.id === 'undefined' || !entry.tipo) {
    return;
  }

  const key = `${entry.tipo}-${entry.id}`;
  selectedProducts.value = selectedProducts.value.filter(
    (item) => !(item.id === entry.id && item.tipo === item.tipo)
  );
  delete quantities.value[key];
  delete prices.value[key];
  delete discounts.value[key];
  calcularTotal();
  saveState();
  showNotification(`Producto eliminado: ${entry.nombre || entry.descripcion || 'Item'}`, 'info');
};

// En el script del componente padre:
// En el script
const limpiarFormulario = () => {
  // Limpiar cliente
  clienteSeleccionado.value = null;
  form.cliente_id = '';

  // Limpiar productos
  selectedProducts.value = [];

  // Reiniciar cantidades, precios y descuentos
  quantities.value = {};
  prices.value = {};
  discounts.value = {};

  // Limpiar almacén
  form.almacen_id = '';

  // Limpiar notas
  form.notas = '';

  // Restablecer número y fecha
  form.numero_venta = numeroVentaFijo.value;
  form.fecha = getCurrentDate();

  // Limpiar variables de margen
  requiereConfirmacionMargen.value = false;
  mensajeAdvertenciaMargen.value = '';

  // Limpiar localStorage si es necesario
  localStorage.removeItem(`venta_edit_${props.venta?.id}`);

  // Notificación
  notyf.success('Formulario limpiado correctamente');

  // Si necesitas forzar actualización de algún componente
  // keyComponent.value += 1;
};

const updateQuantity = (key, quantity) => {
  const numQuantity = parseFloat(quantity);
  if (isNaN(numQuantity) || numQuantity < 0) {
    return;
  }
  quantities.value[key] = numQuantity;
  calcularTotal();
  saveState();
};

const updateDiscount = (key, discount) => {
  const numDiscount = parseFloat(discount);
  if (isNaN(numDiscount) || numDiscount < 0 || numDiscount > 100) {
    return;
  }
  discounts.value[key] = numDiscount;
  calcularTotal();
  saveState();
};

// Cálculos - ✅ FIX: Unified ISR calculation logic
const totales = computed(() => {
  let subtotal = 0;
  let descuentoItems = 0;

  selectedProducts.value.forEach(entry => {
    const key = `${entry.tipo}-${entry.id}`;
    const cantidad = parseFloat(quantities.value[key]) || 0;
    const precio = parseFloat(prices.value[key]) || 0;
    const descuento = parseFloat(discounts.value[key]) || 0;

    if (cantidad > 0 && precio >= 0) {
      const subtotalItem = cantidad * precio;
      descuentoItems += subtotalItem * (descuento / 100);
      subtotal += subtotalItem;
    }
  });

  const subtotalConDescuentos = Math.max(0, subtotal - descuentoItems);
  const ivaRate = (props.defaults?.ivaPorcentaje ?? 16) / 100;
  const iva = subtotalConDescuentos * ivaRate;

  // Calculate Retencion IVA
  let retencionIva = 0;
  if (aplicarRetencionIva.value) {
    const retIvaRate = retencionIvaDefault.value / 100;
    retencionIva = subtotalConDescuentos * retIvaRate;
  }

  // Calculate Retencion ISR
  // Unified logic: Backend expects 'retencion_isr', 'isr' field is legacy/deprecated
  let retencionIsrMonto = 0; 

  // Caso 1: Retención ISR Explícita (Manual Toggle)
  if (aplicarRetencionIsr.value) {
    const retIsrRate = retencionIsrDefault.value / 100;
    retencionIsrMonto = subtotalConDescuentos * retIsrRate;
  } 
  // Caso 2: Fallback a ISR automático para PM si no está la retención explícita activa
  else if (props.defaults?.enableIsr && clienteSeleccionado.value?.tipo_persona === 'moral') {
    const isrRate = (props.defaults?.isrPorcentaje ?? 1.25) / 100;
    retencionIsrMonto = subtotalConDescuentos * isrRate; // ✅ Use retencion_isr field
  }

  // Calculate final total
  // Total = Subtotal + IVA - Retenciones
  const total = subtotalConDescuentos + iva - retencionIva - retencionIsrMonto;

  return {
    subtotal: parseFloat(subtotal.toFixed(2)),
    descuentoItems: parseFloat(descuentoItems.toFixed(2)),
    subtotalConDescuentos: parseFloat(subtotalConDescuentos.toFixed(2)),
    iva: parseFloat(iva.toFixed(2)),
    isr: 0, // ✅ Legacy field always 0
    retencion_iva: parseFloat(retencionIva.toFixed(2)),
    retencion_isr: parseFloat(retencionIsrMonto.toFixed(2)),
    total: parseFloat(total.toFixed(2)),
  };
});

const calcularTotal = () => {
  form.subtotal = totales.value.subtotal;
  form.descuento_items = totales.value.descuentoItems;
  form.iva = totales.value.iva;
  form.isr = totales.value.isr; // Legacy
  form.retencion_iva = totales.value.retencion_iva;
  form.retencion_isr = totales.value.retencion_isr;
  form.total = totales.value.total;
};

// Validar datos antes de crear venta
const validarDatos = () => {
  if (!form.cliente_id) {
    return false;
  }

  if (!form.almacen_id) {
    showNotification('Selecciona un almacén', 'error');
    return false;
  }

  if (selectedProducts.value.length === 0) {
    showNotification('Agrega al menos un producto o servicio', 'error');
    return false;
  }

  // Validar descuentos
  for (const entry of selectedProducts.value) {
    const key = `${entry.tipo}-${entry.id}`;
    const discount = parseFloat(discounts.value[key]) || 0;
    const quantity = parseFloat(quantities.value[key]) || 0;
    const price = parseFloat(prices.value[key]) || 0;
    const producto = props.productos.find(p => p.id === entry.id);

    if (discount < 0 || discount > 100) {
      showNotification('Los descuentos deben estar entre 0% y 100%.', 'error');
      return false;
    }

    if (quantity <= 0) {
      showNotification('Las cantidades deben ser mayores a 0', 'error');
      return false;
    }

    if (price < 0) {
      showNotification('Los precios no pueden ser negativos', 'error');
      return false;
    }

    if (entry.tipo === 'producto' && producto && producto.requiere_serie) {
      const serials = serialsMap.value[key] || [];
      if (!Array.isArray(serials) || serials.length !== quantity) {
        showNotification(`El producto "${producto.nombre}" requiere ${quantity} series.`, 'error');
        return false;
      }
      const unique = new Set(serials.map(s => (s || '').trim()).filter(Boolean));
      if (unique.size !== serials.length) {
        showNotification(`Las series del producto "${producto.nombre}" deben ser únicas.`, 'error');
        return false;
      }
    }

    // ✅ FIX Error #6: Validar series de componentes para kits
    if (entry.tipo === 'producto' && entry.tipo_producto === 'kit') {
      const kitCantidad = quantity;
      
      // Verificar las claves en serialsMap que corresponden a este kit
      const componentKeys = Object.keys(serialsMap.value).filter(key => 
        key.startsWith(`kit-${entry.id}-component-`)
      );
      
      // Si hay componentes serializados registrados, validar sus series
      for (const componentKey of componentKeys) {
        const componentSeries = serialsMap.value[componentKey] || [];
        
        if (!Array.isArray(componentSeries) || componentSeries.length === 0) {
          showNotification(
            `Faltan series para un componente del kit "${entry.nombre || (producto && producto.nombre) || 'Kit'}". Por favor, selecciona todas las series necesarias.`,
            'error'
          );
          return false;
        }
      }
    }
  }

  return true;
};

// Funciones de margen
const aceptarAjusteMargen = () => {
  // Agregar el parámetro para ajustar márgenes automáticamente
  form.ajustar_margen = true;
  requiereConfirmacionMargen.value = false;
  mensajeAdvertenciaMargen.value = '';
  crearVenta();
};

const cancelarAjusteMargen = () => {
  requiereConfirmacionMargen.value = false;
  mensajeAdvertenciaMargen.value = '';
  showNotification('Revisa los precios de los productos antes de continuar', 'info');
};

// Crear venta
const crearVenta = async () => {
  if (!validarDatos()) return;

  // ✅ NEW: Verificar productos con precio fallback (sin precio en lista)
  if (form.price_list_id && !fallbackPriceAccepted.value) {
    const productosSinPrecio = detectarProductosSinPrecioEnLista(
      selectedProducts.value,
      props.productos,
      form.price_list_id
    );
    
    if (productosSinPrecio.length > 0) {
      // Mostrar modal informativo con los productos que usan precio base
      fallbackPriceProducts.value = productosSinPrecio;
      showFallbackPriceModal.value = true;
      return; // Esperar a que el usuario acepte
    }
  }

  // Continuar con el proceso normal de creación
  await submitVentaAfterValidation();
};

// Función separada para enviar la venta después de validaciones
const submitVentaAfterValidation = async () => {
  console.log('📥 submitVentaAfterValidation - form.metodo_pago:', form.metodo_pago);
  console.log('📥 form.data():', form.data());

  // ✅ FIX Error #3: Revalidar series antes de enviar
  const seriesParaValidar = [];
  
  for (const entry of selectedProducts.value) {
    if (entry.tipo === 'producto') {
      const key = `${entry.tipo}-${entry.id}`;
      const producto = props.productos.find(p => p.id === entry.id);
      
      if (producto && producto.requiere_serie) {
        const serials = serialsMap.value[key] || [];
        if (serials.length > 0) {
          seriesParaValidar.push({
            id: entry.id,
            series: serials
          });
        }
      }
      
      // También validar series de componentes de kits
      if (entry.tipo_producto === 'kit') {
        const componentKeys = Object.keys(serialsMap.value).filter(k => 
          k.startsWith(`kit-${entry.id}-component-`)
        );
        
        for (const compKey of componentKeys) {
          const match = compKey.match(/component-(\d+)/);
          if (match && serialsMap.value[compKey]) {
            const componentId = parseInt(match[1]);
            seriesParaValidar.push({
              id: componentId,
              series: serialsMap.value[compKey]
            });
          }
        }
      }
    }
  }
  
  // Si hay series para validar, revalidar en backend
  if (seriesParaValidar.length > 0 && form.almacen_id) {
    try {
      const response = await axios.post(route('ventas.validar-series'), {
        almacen_id: form.almacen_id,
        productos: seriesParaValidar
      });
      
      if (!response.data.valid) {
        const erroresMsg = response.data.errors.join('<br>');
        showNotification(`Las siguientes series ya no están disponibles:<br>${erroresMsg}`, 'error');
        return;
      }
    } catch (error) {
      console.error('Error validando series:', error);
      showNotification('Error al validar series. Por favor, intenta nuevamente.', 'error');
      return;
    }
  }

  // ✅ FIX: Separar productos de servicios para validación correcta en backend
  const productosParaEnviar = [];
  const serviciosParaEnviar = [];

  selectedProducts.value.forEach((entry) => {
    const key = `${entry.tipo}-${entry.id}`;
    const item = {
      id: entry.id,
      cantidad: parseFloat(quantities.value[key]) || 1,
      precio: parseFloat(prices.value[key]) || 0,
      descuento: parseFloat(discounts.value[key]) || 0,
    };

    if (entry.tipo === 'producto') {
      // Es un producto
      const series = serialsMap.value[key];
      if (series && Array.isArray(series) && series.length > 0) {
        item.series = series;
      }

      // Incluir series de componentes para kits
      if (entry.tipo_producto === 'kit') {
        const componentSeries = {};
        Object.keys(serialsMap.value).forEach(mapKey => {
          if (mapKey.startsWith(`kit-${entry.id}-component-`)) {
            // ✅ FIX Error #4: Convertir ID a entero para coincidir con backend
            const componentId = parseInt(mapKey.split('-').pop());
            const series = serialsMap.value[mapKey];
            
            // Solo agregar si hay series seleccionadas
            if (Array.isArray(series) && series.length > 0) {
              componentSeries[componentId] = series;
            }
          }
        });
        if (Object.keys(componentSeries).length > 0) {
          item.componentes_series = componentSeries;
        }
      }

      productosParaEnviar.push(item);
    } else {
      // Es un servicio
      serviciosParaEnviar.push(item);
    }
  });

  // Asignar a los campos separados del formulario
  form.productos = productosParaEnviar;
  form.servicios = serviciosParaEnviar;

  // Calcular totales
  calcularTotal();

  // Enviar formulario
  form.post(route('ventas.store'), {
    onSuccess: (page) => {
      removeFromLocalStorage('ventaEnProgreso');
      selectedProducts.value = [];
      quantities.value = {};
      prices.value = {};
      discounts.value = {};
      serialsMap.value = {};
      clienteSeleccionado.value = null;
      form.reset();
      requiereConfirmacionMargen.value = false;
      mensajeAdvertenciaMargen.value = '';
      fallbackPriceAccepted.value = false; // ✅ Reset para siguiente venta
      fallbackPriceProducts.value = [];
      showNotification('Venta creada exitosamente', 'success');
    },
    onError: (errors) => {
      // ✅ HIGH PRIORITY FIX: NO limpiar localStorage ni formulario en errores
      // removeFromLocalStorage('ventaEnProgreso'); // COMENTADO para preservar datos
      
      console.error('Errores al crear venta:', errors);

      // Verificar si es un error de margen
      if (errors.warning && errors.requiere_confirmacion_margen) {
        requiereConfirmacionMargen.value = true;
        mensajeAdvertenciaMargen.value = errors.warning;
        showNotification('Se requiere confirmación de márgenes', 'warning');
        return;
      }

      // Obtener mensaje de error
      let mensajeError = 'Hubo un error al procesar la venta.';
      
      // Si el backend devuelve un mensaje directo (response()->json(['message' => ...]))
      if (errors.message) {
        mensajeError = errors.message;
      } 
      // Si son errores de validación (array/objeto)
      else {
        const firstError = Object.values(errors)[0];
        mensajeError = Array.isArray(firstError) ? firstError[0] : (typeof firstError === 'string' ? firstError : 'Error desconocido');
      }

      // Mostrar Modal de Error Persistente
      openErrorModal(mensajeError);
    },
  });
};

// Manejo de eventos del navegador
const handleBeforeUnload = (event) => {
  if (form.cliente_id || selectedProducts.value.length > 0) {
    event.preventDefault();
    event.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
  }
};

// Guardar estado automáticamente
const saveState = () => {
  const stateToSave = {
    numero_venta: numeroVentaFijo.value,
    fecha: form.fecha,
    cliente_id: form.cliente_id,
    cliente: clienteSeleccionado.value,
    selectedProducts: selectedProducts.value,
    quantities: quantities.value,
    prices: prices.value,
    discounts: discounts.value,
    serials: serialsMap.value,
  };
  saveToLocalStorage('ventaEnProgreso', stateToSave);
};

// Función para asegurar que la fecha sea siempre la actual
const asegurarFechaActual = () => {
  const fechaActual = getCurrentDate();
  if (form.fecha !== fechaActual) {
    form.fecha = fechaActual;
  }
};

// ✅ FIX Error #5: Watcher para detectar cambios en cantidad de kits e invalidar series
watch(() => quantities.value, (newQuantities, oldQuantities) => {
  if (!oldQuantities) return; // Primera carga
  
  Object.keys(newQuantities).forEach(key => {
    const [tipo, id] = key.split('-');
    
    if (tipo === 'producto') {
      const producto = selectedProducts.value.find(p => p.id === parseInt(id) && p.tipo === 'producto');
      
      if (producto && producto.tipo_producto === 'kit') {
        const oldQty = oldQuantities[key] || 0;
        const newQty = newQuantities[key] || 0;
        
        // Solo actuar si la cantidad cambió y había una cantidad anterior
        if (oldQty > 0 && newQty > 0 && oldQty !== newQty) {
          // Invalidar series de componentes del kit
          const componentKeys = Object.keys(serialsMap.value).filter(mapKey =>
            mapKey.startsWith(`kit-${id}-component-`)
          );
          
          if (componentKeys.length > 0) {
            // Eliminar las series de los componentes
            componentKeys.forEach(componentKey => {
              delete serialsMap.value[componentKey];
            });
            
            showNotification(
              `La cantidad del kit cambió de ${oldQty} a ${newQty}. Por favor, vuelve a seleccionar las series de los componentes.`,
              'warning'
            );
          }
        }
      }
    }
  });
}, { deep: true });

// ✅ FIX: Watcher para limpiar series cuando cambia el almacén
watch(() => form.almacen_id, (newAlmacenId, oldAlmacenId) => {
  if (!oldAlmacenId || !newAlmacenId) return; // Primera carga o limpieza
  
  if (String(newAlmacenId) !== String(oldAlmacenId)) {
    // Verificar si hay series seleccionadas
    const seriesKeys = Object.keys(serialsMap.value).filter(key => {
      const series = serialsMap.value[key];
      return Array.isArray(series) && series.length > 0;
    });
    
    if (seriesKeys.length > 0) {
      // Limpiar todas las series seleccionadas
      serialsMap.value = {};
      
      const nombreAlmacenNuevo = nombreAlmacen(newAlmacenId);
      showNotification(
        `Se cambió el almacén a "${nombreAlmacenNuevo}". Las series seleccionadas fueron limpiadas. Por favor, selecciona las series del nuevo almacén.`,
        'warning'
      );
    }
  }
});

// Lifecycle hooks
onMounted(async () => {
  // Mostrar mensajes flash del servidor (errores de backend)
  try {
    const page = usePage();
    const flash = page?.props?.flash;
    if (flash?.success) showNotification(flash.success, 'success');
    if (flash?.error) showNotification(flash.error, 'error');
    if (flash?.warning) showNotification(flash.warning, 'warning');
  } catch (e) { /* noop */ }

  // Obtener el siguiente número de venta
  await fetchNextNumeroVenta();

  // Cargar cuentas bancarias para el modal de pago
  await cargarCuentasBancarias();

  // Seleccionar vendedor predeterminado (Jesus Lopez)
  seleccionarVendedorPredeterminado();

  // ✅ NEW: Verificar si hay pedido para cargar (prioridad sobre localStorage)
  if (props.pedido) {
    loadFromPedido();
    // Si cargamos desde pedido, no cargamos localStorage para evitar conflictos
    return;
  }

  if (props.cita) {
    loadFromCita();
    return;
  }

  const savedData = loadFromLocalStorage('ventaEnProgreso');
  if (savedData && typeof savedData === 'object') {
    try {
      // Usar número guardado o el generado automáticamente
      form.numero_venta = savedData.numero_venta || numeroVentaFijo.value;
      form.fecha = getCurrentDate(); // Siempre usar fecha actual

      form.cliente_id = savedData.cliente_id || '';
      clienteSeleccionado.value = savedData.cliente || null;
      selectedProducts.value = Array.isArray(savedData.selectedProducts) ? savedData.selectedProducts : [];
      quantities.value = savedData.quantities || {};
      prices.value = savedData.prices || {};
      discounts.value = savedData.discounts || {};
      serialsMap.value = savedData.serials || {};
      calcularTotal();
    } catch (error) {
      console.warn('Error al cargar datos guardados:', error);
      removeFromLocalStorage('ventaEnProgreso');
    }
  }

  // Verificar la fecha cada 5 minutos para mantenerla actual
  const fechaInterval = setInterval(() => {
    asegurarFechaActual();
  }, 5 * 60 * 1000); // 5 minutos

  window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);

  // Limpiar el intervalo de fecha si existe
  if (typeof fechaInterval !== 'undefined') {
    clearInterval(fechaInterval);
  }
});
</script>

