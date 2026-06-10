<!-- Resources/js/Pages/OrdenesCompra/CrearOrdenCompra.vue -->
<template>
  <Head title="Crear Orden de Compra" />
  <div class="ordenes-compra-create min-h-screen bg-[var(--ui-surface)] p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-6">
        <Header
          title="Nueva Orden de Compra"
          description="Crea una nueva orden de compra para tus proveedores"
          :can-preview="proveedorSeleccionado && selectedProducts.length > 0"
          :back-url="route('ordenescompra.index')"
          :show-shortcuts="mostrarAtajos"
          @preview="handlePreview"
          @close-shortcuts="mostrarAtajos = false"
        />
        <div class="mt-4 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800/30 rounded-xl p-3">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-sky-800 dark:text-sky-200">
                <strong>Nota:</strong> El IVA se calcula automáticamente según la configuración de la empresa. Ingrese los precios SIN IVA.
              </p>
            </div>
          </div>
        </div>
      </div>

      <form @submit.prevent="crearOrdenCompra" class="space-y-6">
        <!-- Información General -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Información General
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Número de Orden -->
            <div>
              <label for="numero_orden" class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-2">
                Número de Orden *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-sky-100 text-sky-800 dark:text-sky-200 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Automático
                </span>
              </label>
              <div class="relative">
                <input
                  id="numero_orden"
                  :value="proximoNumero"
                  type="text"
                  class="w-full bg-white text-slate-500 cursor-not-allowed border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                  readonly
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-slate-500">
                Este número se asigna automáticamente al guardar la orden
              </p>
            </div>

            <!-- Fecha de Orden -->
            <div>
              <label for="fecha_orden" class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-2">
                Fecha de Orden *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-sky-100 text-sky-800 dark:text-sky-200 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Automática
                </span>
              </label>
              <div class="relative">
                <input
                  id="fecha_orden"
                  v-model="form.fecha_orden"
                  type="date"
                  class="w-full bg-white text-slate-500 cursor-not-allowed border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                  readonly
                  required
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-slate-500">
                Esta fecha se establece automáticamente con la fecha de creación
              </p>
            </div>

            <!-- Fecha de Entrega Esperada -->
            <div>
              <label for="fecha_entrega_esperada" class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-2">
                Fecha de Entrega Esperada
                <button
                  @click="mostrarInfoFechas"
                  type="button"
                  class="text-slate-400 hover:text-brand-600 transition-colors"
                  title="Opciones de fechas rápidas disponibles"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </button>
              </label>
              <input
                id="fecha_entrega_esperada"
                v-model="form.fecha_entrega_esperada"
                type="date"
                class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              />

              <!-- Botones de fechas rápidas -->
              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  @click="setFechaRapida('hoy')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-sky-50 dark:bg-sky-900/20 text-sky-800 dark:text-sky-200 text-sm font-medium rounded-xl hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Hoy
                </button>

                <button
                  @click="setFechaRapida('manana')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 text-sm font-medium rounded-xl hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  Mañana
                </button>

                <button
                  @click="setFechaRapida('3dias')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-brand-50 dark:bg-brand-900/20 text-brand-800 dark:text-brand-200 dark:text-brand-200 text-sm font-medium rounded-xl hover:bg-brand-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  +3 días
                </button>

                <button
                  @click="setFechaRapida('semana')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-purple-50 text-purple-700 text-sm font-medium rounded-xl hover:bg-purple-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  Una semana
                </button>

                <button
                  @click="setFechaRapida('mes')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-200 dark:text-rose-200 text-sm font-medium rounded-xl hover:bg-rose-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  Un mes
                </button>
              </div>
            </div>

            <!-- Prioridad -->
            <div>
              <label for="prioridad" class="block text-sm font-medium text-slate-700 mb-2">
                Prioridad
              </label>
              <select
                id="prioridad"
                v-model="form.prioridad"
                class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>

            <!-- Almacén -->
            <div>
              <label for="almacen_id" class="block text-sm font-medium text-slate-700 mb-2">
                Almacén de Destino
              </label>
              <select
                id="almacen_id"
                v-model="form.almacen_id"
                class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="">Seleccionar almacén...</option>
                <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                  {{ almacen.nombre }}
                </option>
              </select>
              <p class="mt-1 text-xs text-slate-500">
                Almacén donde se recibirán los productos
              </p>
            </div>
          </div>
        </div>

        <!-- Proveedor -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Información del Proveedor
            </h2>
          </div>
          <div class="p-6">
            <BuscarProveedor
              :proveedores="proveedoresList"
              :proveedor-seleccionado="proveedorSeleccionado"
              label-busqueda="Proveedor"
              placeholder-busqueda="Buscar proveedor por nombre, RFC, email..."
              requerido
              @proveedor-seleccionado="onProveedorSeleccionado"
            />

            <div class="mt-4 p-3 bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800/30 rounded-xl">
              <div class="flex items-center gap-2 text-sky-800 dark:text-sky-200 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">Nota:</span>
                <span>Las órdenes de compra solo incluyen productos físicos para inventario.</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Productos y Servicios -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Productos
            </h2>
          </div>
          <div class="p-6">
            <BuscarProducto
              ref="buscarProductoRef"
              :productos="productos"
              :servicios="[]"
              :validar-stock="false"
              @agregar-producto="agregarProducto"
            />
            <ProductosSeleccionados
              :selected-products="selectedProducts"
              :productos="productos"
              :servicios="[]"
              :quantities="quantities"
              :prices="prices"
              :discounts="discounts"
              @eliminar-producto="eliminarProducto"
              @update-quantity="updateQuantity"
              @update-discount="updateDiscount"
            />
          </div>
        </div>

        <!-- Condiciones de Entrega -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
              Condiciones de Entrega
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Dirección de Entrega -->
            <div class="md:col-span-2">
              <label for="direccion_entrega" class="block text-sm font-medium text-slate-700 mb-2">
                Dirección de Entrega
              </label>
              <textarea
                id="direccion_entrega"
                v-model="form.direccion_entrega"
                class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-vertical"
                rows="3"
                placeholder="Especifica la dirección donde se debe entregar el pedido..."
              ></textarea>
            </div>

            <!-- Términos de Pago -->
            <div>
              <label for="terminos_pago" class="block text-sm font-medium text-slate-700 mb-2">
                Términos de Pago
              </label>
              <select
                id="terminos_pago"
                v-model="form.terminos_pago"
                class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="contado">Contado</option>
                <option value="15_dias">15 días</option>
                <option value="30_dias">30 días</option>
                <option value="45_dias">45 días</option>
                <option value="60_dias">60 días</option>
                <option value="90_dias">90 días</option>
              </select>
            </div>

            <!-- Método de Pago -->
            <div>
              <label for="metodo_pago" class="block text-sm font-medium text-slate-700 mb-2">
                Método de Pago
              </label>
              <select
                id="metodo_pago"
                v-model="form.metodo_pago"
                class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
              >
                <option value="transferencia">Transferencia Bancaria</option>
                <option value="cheque">Cheque</option>
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta de Crédito</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Notas y Observaciones -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Notas y Observaciones
            </h2>
          </div>
          <div class="p-6">
            <textarea
              v-model="form.observaciones"
              class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent resize-vertical"
              rows="4"
              placeholder="Agrega observaciones, especificaciones técnicas, términos y condiciones especiales..."
            ></textarea>
          </div>
        </div>

        <!-- Descuento General -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
              </svg>
              Descuento General
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-6">
              <div>
                <label for="descuento_general" class="block text-sm font-medium text-slate-700 mb-2">
                  Descuento General ($)
                </label>
                <input
                  id="descuento_general"
                  type="number"
                  step="0.01"
                  min="0"
                  v-model="form.descuento_general"
                  class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                  placeholder="0.00"
                />
                <p class="mt-1 text-xs text-slate-500">
                  Este descuento se aplica al subtotal después de los descuentos por item
                </p>
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
          :enable-retencion-iva="props.defaults?.enableRetencionIva"
          :enable-retencion-isr="props.defaults?.enableRetencionIsr"
          :retencion-iva-default="retencionIvaDefault"
          :retencion-isr-default="retencionIsrDefault"
          v-model:aplicarRetencionIva="aplicarRetencionIva"
          v-model:aplicarRetencionIsr="aplicarRetencionIsr"
          @update:descuento-general="val => form.descuento_general = val"
        />

        <!-- Botones -->
        <BotonesAccion
          :back-url="route('ordenescompra.index')"
          :is-processing="form.processing"
          :can-submit="form.proveedor_id && selectedProducts.length > 0"
          :button-text="form.processing ? 'Guardando...' : 'Crear Orden de Compra'"
          @limpiar="limpiarFormulario"
        />
      </form>

      <!-- Atajos de teclado -->
      <button
        @click="mostrarAtajos = !mostrarAtajos"
        class="fixed bottom-4 left-4 bg-slate-600 text-white p-3 rounded-full shadow-xl hover:bg-slate-700 transition-colors duration-200"
        title="Mostrar atajos de teclado"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </button>

      <!-- Modal Vista Previa -->
      <VistaPreviaModal
        :show="mostrarVistaPrevia"
        type="ordenescompra"
        :proveedor="proveedorSeleccionado"
        :productos="selectedProducts"
        :totals="totales"
        :notas="form.observaciones"
        :orden-data="{
          numero_orden: proximoNumero,
          fecha_orden: form.fecha_orden,
          fecha_entrega_esperada: form.fecha_entrega_esperada,
          prioridad: form.prioridad,
          direccion_entrega: form.direccion_entrega,
          terminos_pago: form.terminos_pago,
          metodo_pago: form.metodo_pago
        }"
        @close="mostrarVistaPrevia = false"
        @print="() => window.print()"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import AppLayout from '@/Layouts/AppLayout.vue';
import Header from '@/Components/CreateComponents/Header.vue';
import BuscarProveedor from '@/Components/CreateComponents/BuscarProveedor.vue';
import BuscarProducto from '@/Components/CreateComponents/BuscarProducto.vue';
import ProductosSeleccionados from '@/Components/CreateComponents/ProductosSeleccionados.vue';
import Totales from '@/Components/CreateComponents/Totales.vue';
import BotonesAccion from '@/Components/CreateComponents/BotonesAccion.vue';
import VistaPreviaModal from '@/Components/Modals/VistaPreviaModal.vue';

// Inicializar notificaciones
const notyf = new Notyf({
  duration: 5000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10B981', icon: { className: 'notyf__icon--success', tagName: 'i', text: '✓' } },
    { type: 'error', background: '#EF4444', icon: { className: 'notyf__icon--error', tagName: 'i', text: '✗' } },
    { type: 'info', background: '#FF6B35', icon: { className: 'notyf__icon--info', tagName: 'i', text: 'ℹ' } },
  ],
});

const showNotification = (message, type = 'success') => {
  notyf.open({ type, message });
};

// Usar layout
defineOptions({ layout: AppLayout });

// Props
const props = defineProps({
  proveedores: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
  proximoNumero: { type: String, default: 'OC-001' },
  defaults: {
    type: Object,
    default: () => ({
      ivaPorcentaje: 16,
      enableIsr: false,
      enableRetencionIva: false,
      enableRetencionIsr: false,
      retencionIvaDefault: 10.6667,
      retencionIsrDefault: 10
    })
  },
  preselected: { type: Object, default: () => ({}) }
});

// Destructure props for easier access
const { proveedores, productos, almacenes, proximoNumero } = props;

// Copia reactiva de proveedores para evitar mutación de props
const proveedoresList = ref([...proveedores]);

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
  numero_orden: '',
  fecha_orden: getCurrentDate(),
  fecha_entrega_esperada: '',
  prioridad: 'media',
  proveedor_id: '',
  almacen_id: '',
  direccion_entrega: '',
  terminos_pago: '30_dias',
  metodo_pago: 'transferencia',
  subtotal: 0,
  descuento_items: 0,
  descuento_general: 0,
  iva: 0,
  iva: 0,
  retencion_iva: 0,
  retencion_isr: 0,
  aplicar_retencion_iva: false,
  aplicar_retencion_isr: false,
  total: 0,
  items: [],
  observaciones: '',
  estado: 'pendiente',
});

// Referencias
const buscarProductoRef = ref(null);
const proveedorSeleccionado = ref(null);
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const mostrarVistaPrevia = ref(false);
const mostrarAtajos = ref(true);

const aplicarRetencionIva = ref(false);
const aplicarRetencionIsr = ref(false);

const retencionIvaDefault = computed(() => parseFloat(props.defaults?.retencionIvaDefault ?? 10.6667));
const retencionIsrDefault = computed(() => parseFloat(props.defaults?.retencionIsrDefault ?? 10));

// Función para asegurar que la fecha sea siempre la actual
const asegurarFechaActual = () => {
  const fechaActual = getCurrentDate();
  if (form.fecha_orden !== fechaActual) {
    form.fecha_orden = fechaActual;
  }
};

// Función para establecer fechas rápidas
const setFechaRapida = (tipo) => {
  const hoy = new Date();
  let fechaCalculada = new Date(hoy);

  switch (tipo) {
    case 'hoy':
      // Ya es hoy, no cambiar
      break;
    case 'manana':
      fechaCalculada.setDate(hoy.getDate() + 1);
      break;
    case '3dias':
      fechaCalculada.setDate(hoy.getDate() + 3);
      break;
    case 'semana':
      fechaCalculada.setDate(hoy.getDate() + 7);
      break;
    case 'mes':
      fechaCalculada.setMonth(hoy.getMonth() + 1);
      break;
  }

  // Formatear la fecha como YYYY-MM-DD
  const year = fechaCalculada.getFullYear();
  const month = String(fechaCalculada.getMonth() + 1).padStart(2, '0');
  const day = String(fechaCalculada.getDate()).padStart(2, '0');
  const fechaFormateada = `${year}-${month}-${day}`;

  form.fecha_entrega_esperada = fechaFormateada;

  // Mostrar notificación
  const etiquetas = {
    hoy: 'Hoy',
    manana: 'Mañana',
    '3dias': 'En 3 días',
    semana: 'En una semana',
    mes: 'En un mes'
  };

  showNotification(`Fecha de entrega establecida: ${etiquetas[tipo]} (${fechaFormateada})`, 'success');
};

// Función para mostrar información sobre fechas rápidas
const mostrarInfoFechas = () => {
  showNotification('Usa los botones para establecer fechas de entrega comunes', 'info');
};

// Funciones de localStorage (comentadas porque no están disponibles en Claude)
const saveToLocalStorage = (key, data) => {
  // try {
  //   localStorage.setItem(key, JSON.stringify(data));
  // } catch (error) {
  //   console.warn('No se pudo guardar en localStorage:', error);
  // }
};

const loadFromLocalStorage = (key) => {
  // try {
  //   const item = localStorage.getItem(key);
  //   return item ? JSON.parse(item) : null;
  // } catch (error) {
  //   console.warn('No se pudo cargar desde localStorage:', error);
  //   return null;
  // }
  return null;
};

const removeFromLocalStorage = (key) => {
  // try {
  //   localStorage.removeItem(key);
  // } catch (error) {
  //   console.warn('No se pudo eliminar de localStorage:', error);
  // }
};

// Funciones
const handlePreview = () => {
  if (proveedorSeleccionado.value && selectedProducts.value.length > 0) {
    mostrarVistaPrevia.value = true;
  } else {
    showNotification('Selecciona un proveedor y al menos un producto', 'error');
  }
};

const onProveedorSeleccionado = (proveedor) => {
  if (!proveedor) {
    proveedorSeleccionado.value = null;
    form.proveedor_id = '';
    saveState();
    showNotification('Selección de proveedor limpiada', 'info');
    return;
  }
  if (proveedorSeleccionado.value?.id === proveedor.id) return;
  proveedorSeleccionado.value = proveedor;
  form.proveedor_id = proveedor.id;
  saveState();
  showNotification(`Proveedor seleccionado: ${proveedor.nombre_razon_social}`);
};

const agregarProducto = (item) => {
  if (!item || typeof item.id === 'undefined') {
    showNotification('Producto inválido', 'error');
    return;
  }

  // Solo permitir productos, no servicios
  const itemEntry = { id: item.id, tipo: 'producto' };
  const exists = selectedProducts.value.some(
    (entry) => entry.id === item.id && entry.tipo === 'producto'
  );

  if (!exists) {
    selectedProducts.value.push(itemEntry);
    const key = `producto-${item.id}`;
    quantities.value[key] = 1;

    // Solo productos, usar precio_compra con fallback a precio_venta
    let precio = 0;
    if (item.precio_compra && Number(item.precio_compra) > 0) {
      precio = Number(item.precio_compra);
    } else if (item.precio_venta && Number(item.precio_venta) > 0) {
      precio = Number(item.precio_venta);
    }
    prices.value[key] = precio;
    discounts.value[key] = 0;
    calcularTotal();
    saveState();
    showNotification(`Producto añadido: ${item.nombre || item.descripcion || 'Producto'}`);
  } else {
    showNotification('Este producto ya está en la lista', 'info');
  }
};

const eliminarProducto = (entry) => {
  if (!entry || typeof entry.id === 'undefined' || !entry.tipo) {
    return;
  }

  const key = `${entry.tipo}-${entry.id}`;
  selectedProducts.value = selectedProducts.value.filter(
    (item) => !(item.id === entry.id && item.tipo === entry.tipo)
  );
  delete quantities.value[key];
  delete prices.value[key];
  delete discounts.value[key];
  calcularTotal();
  saveState();
  showNotification(`Producto eliminado: ${entry.nombre || entry.descripcion || 'Producto'}`, 'info');
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

  const descuentoGeneral = parseFloat(form.descuento_general) || 0;
  const subtotalConDescuentos = Math.max(0, subtotal - descuentoItems);
  const subtotalConDescuentoGeneral = Math.max(0, subtotalConDescuentos - descuentoGeneral);
  
  const ivaRate = (props.defaults?.ivaPorcentaje ?? 16) / 100;
  const iva = subtotalConDescuentoGeneral * ivaRate;

  // Calculo de retenciones
  let retencionIva = 0;
  if (aplicarRetencionIva.value && props.defaults?.enableRetencionIva) {
      retencionIva = subtotalConDescuentoGeneral * (retencionIvaDefault.value / 100);
  }

  let retencionIsr = 0;
  if (aplicarRetencionIsr.value && props.defaults?.enableRetencionIsr) {
      retencionIsr = subtotalConDescuentoGeneral * (retencionIsrDefault.value / 100);
  }

  const total = subtotalConDescuentoGeneral + iva - retencionIva - retencionIsr;

  return {
    subtotal: parseFloat(subtotal.toFixed(2)),
    descuentoItems: parseFloat(descuentoItems.toFixed(2)),
    descuentoGeneral: descuentoGeneral,
    subtotalConDescuentos: parseFloat(subtotalConDescuentoGeneral.toFixed(2)),
    iva: parseFloat(iva.toFixed(2)),
    retencion_iva: parseFloat(retencionIva.toFixed(2)),
    retencion_isr: parseFloat(retencionIsr.toFixed(2)),
    total: parseFloat(total.toFixed(2)),
  };
});

const calcularTotal = () => {
  form.subtotal = totales.value.subtotal;
  form.descuento_items = totales.value.descuentoItems;
  form.iva = totales.value.iva;
  form.retencion_iva = totales.value.retencion_iva;
  form.retencion_isr = totales.value.retencion_isr;
  form.aplicar_retencion_iva = aplicarRetencionIva.value;
  form.aplicar_retencion_isr = aplicarRetencionIsr.value;
  form.total = totales.value.total;
};

const validarDatos = () => {
  if (!form.fecha_orden) {
    showNotification('Fecha de orden no disponible', 'error');
    return false;
  }

  if (!form.proveedor_id) {
    showNotification('Selecciona un proveedor', 'error');
    return false;
  }

  if (selectedProducts.value.length === 0) {
    showNotification('Agrega al menos un producto', 'error');
    return false;
  }

  for (const entry of selectedProducts.value) {
    const key = `${entry.tipo}-${entry.id}`;
    const discount = parseFloat(discounts.value[key]) || 0;
    const quantity = parseFloat(quantities.value[key]) || 0;
    const price = parseFloat(prices.value[key]) || 0;

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
  }

  return true;
};

const crearOrdenCompra = () => {
  if (!validarDatos()) {
    return;
  }

  // Asegurar que la fecha sea la actual antes de enviar
  asegurarFechaActual();

  form.items = selectedProducts.value.map((entry) => {
    const key = `producto-${entry.id}`;
    return {
      id: entry.id,
      tipo: 'producto',
      cantidad: parseFloat(quantities.value[key]) || 1,
      precio: parseFloat(prices.value[key]) || 0,
      descuento: parseFloat(discounts.value[key]) || 0,
    };
  });

  calcularTotal();

  // Asegurar que numero_orden se envíe (si no está definido, usar el próximo número)
  if (!form.numero_orden) {
    form.numero_orden = proximoNumero;
  }

  // Convertir almacen_id vacío a null
  if (!form.almacen_id || form.almacen_id === '') {
    form.almacen_id = null;
  }

  // Log de depuración
  console.log('Datos a enviar:', {
    numero_orden: form.numero_orden,
    fecha_orden: form.fecha_orden,
    fecha_entrega_esperada: form.fecha_entrega_esperada,
    prioridad: form.prioridad,
    proveedor_id: form.proveedor_id,
    almacen_id: form.almacen_id,
    direccion_entrega: form.direccion_entrega,
    terminos_pago: form.terminos_pago,
    metodo_pago: form.metodo_pago,
    subtotal: form.subtotal,
    descuento_items: form.descuento_items,
    descuento_general: form.descuento_general,
    descuento_general: form.descuento_general,
    iva: form.iva,
    retencion_iva: form.retencion_iva,
    retencion_isr: form.retencion_isr,
    aplicar_retencion_iva: form.aplicar_retencion_iva,
    aplicar_retencion_isr: form.aplicar_retencion_isr,
    total: form.total,
    items: form.items,
    observaciones: form.observaciones,
  });

  form.post(route('ordenescompra.store'), {
    onSuccess: () => {
      removeFromLocalStorage('ordenCompraEnProgreso');
      selectedProducts.value = [];
      quantities.value = {};
      prices.value = {};
      discounts.value = {};
      proveedorSeleccionado.value = null;
      form.reset();
      // Mantener la fecha de orden como automática (siempre la fecha actual)
      form.numero_orden = '';
      form.fecha_orden = getCurrentDate();
      form.prioridad = 'media';
      form.terminos_pago = '30_dias';
      form.metodo_pago = 'transferencia';
      form.estado = 'pendiente';
      showNotification('Orden de compra creada con éxito');

      // Redirigir al index para mostrar la nueva orden
      router.visit(route('ordenescompra.index'));
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors);

      // Verificar si es un error de CSRF token
      if (errors && typeof errors === 'object' && errors.message && errors.message.includes('CSRF token mismatch')) {
        showNotification('La sesión ha expirado. Refrescando la página...', 'error');
        setTimeout(() => {
          window.location.reload();
        }, 2000);
        return;
      }

      const firstError = Object.values(errors)[0];
      if (Array.isArray(firstError)) {
        showNotification(firstError[0], 'error');
      } else {
        showNotification('Hubo errores de validación', 'error');
      }
    },
  });
};

const limpiarFormulario = () => {
  proveedorSeleccionado.value = null;
  // Asegurar que la fecha sea la actual
  asegurarFechaActual();
  form.numero_orden = '';
  form.fecha_entrega_esperada = '';
  form.prioridad = 'media';
  form.proveedor_id = '';
  form.almacen_id = '';
  form.direccion_entrega = '';
  form.terminos_pago = '30_dias';
  form.metodo_pago = 'transferencia';
  form.observaciones = '';
  form.observaciones = '';
  form.descuento_general = 0;
  aplicarRetencionIva.value = false;
  aplicarRetencionIsr.value = false;
  selectedProducts.value = [];
  quantities.value = {};
  prices.value = {};
  discounts.value = {};
  removeFromLocalStorage('ordenCompraEnProgreso');
  showNotification('Formulario limpiado correctamente');
};

const saveState = () => {
  const stateToSave = {
    aplicar_retencion_iva: aplicarRetencionIva.value,
    aplicar_retencion_isr: aplicarRetencionIsr.value,
    fecha_orden: form.fecha_orden,
    fecha_entrega_esperada: form.fecha_entrega_esperada,
    prioridad: form.prioridad,
    proveedor_id: form.proveedor_id,
    almacen_id: form.almacen_id,
    proveedor: proveedorSeleccionado.value,
    direccion_entrega: form.direccion_entrega,
    terminos_pago: form.terminos_pago,
    metodo_pago: form.metodo_pago,
    observaciones: form.observaciones,
    descuento_general: form.descuento_general,
    selectedProducts: selectedProducts.value,
    quantities: quantities.value,
    prices: prices.value,
    discounts: discounts.value,
  };
  saveToLocalStorage('ordenCompraEnProgreso', stateToSave);
};

const handleBeforeUnload = (event) => {
  if (form.proveedor_id || selectedProducts.value.length > 0) {
    event.preventDefault();
    event.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
  }
};

// Lifecycle hooks
onMounted(() => {
  // Seleccionar automáticamente el almacén "General"
  const almacenGeneral = almacenes.find(a => 
    a.nombre && (a.nombre.toLowerCase() === 'general' || a.nombre.toLowerCase().includes('general'))
  );
  
  if (almacenGeneral) {
    form.almacen_id = almacenGeneral.id;
    console.log('✅ Almacén General seleccionado automáticamente:', almacenGeneral.nombre);
  } else if (almacenes.length > 0) {
    // Si no hay "General", seleccionar el primero
    form.almacen_id = almacenes[0].id;
    console.log('⚠️ No se encontró "General", usando:', almacenes[0].nombre);
  }

  // Debug: mostrar proveedores disponibles
  console.log('📊 Proveedores disponibles:', proveedores.length);
  if (proveedores.length === 0) {
    console.error('❌ NO HAY PROVEEDORES - Verifica que haya proveedores activos en la base de datos');
  } else {
    console.log('🔍 Lista de proveedores:', proveedores.map(p => ({
      id: p.id,
      nombre: p.nombre_razon_social || p.nombre,
      email: p.email
    })));
  }

  const savedData = loadFromLocalStorage('ordenCompraEnProgreso');
  if (savedData && typeof savedData === 'object') {
    try {
      // Mantener la fecha de orden como automática (siempre usar fecha actual)
      form.fecha_orden = getCurrentDate();
      form.fecha_entrega_esperada = savedData.fecha_entrega_esperada || '';
      form.prioridad = savedData.prioridad || 'media';
      form.proveedor_id = savedData.proveedor_id || '';
      // Solo sobrescribir almacen_id si existe en savedData, sino mantener el General seleccionado
      if (savedData.almacen_id) {
        form.almacen_id = savedData.almacen_id;
      }
      form.direccion_entrega = savedData.direccion_entrega || '';
      form.terminos_pago = savedData.terminos_pago || '30_dias';
      form.metodo_pago = savedData.metodo_pago || 'transferencia';
      form.observaciones = savedData.observaciones || '';
      form.descuento_general = savedData.descuento_general || 0;
      aplicarRetencionIva.value = savedData.aplicar_retencion_iva || false;
      aplicarRetencionIsr.value = savedData.aplicar_retencion_isr || false;
      proveedorSeleccionado.value = savedData.proveedor || null;
      selectedProducts.value = Array.isArray(savedData.selectedProducts) ? savedData.selectedProducts : [];
      quantities.value = savedData.quantities || {};
      prices.value = savedData.prices || {};
      discounts.value = savedData.discounts || {};
      calcularTotal();
    } catch (error) {
      removeFromLocalStorage('ordenCompraEnProgreso');
    }
  }

  // Handle preselected product from query params
  if (props.preselected?.producto_id) {
    const p = props.productos.find(x => x.id == props.preselected.producto_id);
    if (p) {
      console.log('✅ Pre-seleccionando producto:', p.nombre);
      // Wait a bit for components to be ready
      setTimeout(() => {
        agregarProducto(p);
      }, 500);
    }
  }

  // Verificar la fecha cada 5 minutos para mantenerla actual
  const fechaInterval = setInterval(() => {
    asegurarFechaActual();
  }, 5 * 60 * 1000); // 5 minutos

  window.addEventListener('beforeunload', handleBeforeUnload);

  // Limpiar el intervalo cuando el componente se desmonte
  onBeforeUnmount(() => {
    clearInterval(fechaInterval);
  });
});

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>

