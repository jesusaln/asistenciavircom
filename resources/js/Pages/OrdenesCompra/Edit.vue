<!-- Resources/js/Pages/OrdenesCompra/Edit.vue -->
<template>
  <div>
    <Head title="Editar Orden de Compra" />
    <div class="ordenes-compra-edit min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-6">
        <Header
          title="Editar Orden de Compra"
          description="Modifica los detalles de la orden de compra existente"
          :can-preview="proveedorSeleccionado && selectedProducts.length > 0"
          :back-url="route('ordenescompra.index')"
          :show-shortcuts="mostrarAtajos"
          @preview="handlePreview"
          @close-shortcuts="mostrarAtajos = false"
        />
        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-md p-3">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                <strong>Nota:</strong> El IVA se calcula automáticamente según la configuración de la empresa. Ingrese los precios SIN IVA.
              </p>
            </div>
          </div>
        </div>
      </div>

      <form @submit.prevent="updatePurchaseOrder" class="space-y-8">
        <!-- Información General -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Información General
              <span v-if="cargandoDatos" class="ml-2 inline-flex items-center gap-1 px-2 py-1 bg-indigo-100 text-indigo-700 text-xs font-medium rounded-full">
                <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Cargando...
              </span>
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Número de Orden -->
            <div>
              <label for="numero_orden" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Número de Orden *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Automático
                </span>
              </label>
              <div class="relative">
                <input
                  id="numero_orden"
                  v-model="form.numero_orden"
                  type="text"
                  class="w-full bg-white dark:bg-slate-900 text-gray-500 dark:text-gray-400 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  readonly
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </div>
              </div>
              <div class="mt-2 flex items-center gap-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  Este número es fijo para esta orden de compra
                </p>
                <button
                  @click="copiarNumeroOrden"
                  class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded hover:bg-gray-200 transition-colors"
                  title="Copiar número de orden"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                  Copiar
                </button>
              </div>
            </div>

            <!-- Fecha de Orden -->
            <div>
              <label for="fecha_orden" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Fecha de Orden *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
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
                  class="w-full bg-white dark:bg-slate-900 text-gray-500 dark:text-gray-400 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  readonly
                  required
                />
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Esta fecha se establece automáticamente con la fecha de creación
              </p>
            </div>

            <!-- Fecha de Entrega Esperada -->
            <div>
              <label for="fecha_entrega_esperada" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Fecha de Entrega Esperada
                <button
                  @click="mostrarInfoFechas"
                  type="button"
                  class="text-gray-400 hover:text-gray-600 dark:text-gray-300 transition-colors"
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
                :disabled="cargandoDatos"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
              />

              <!-- Botones de fechas rápidas -->
              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  @click="setFechaRapida('hoy')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-blue-50 text-blue-700 text-sm font-medium rounded-lg hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Hoy
                </button>

                <button
                  @click="setFechaRapida('manana')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-green-50 text-green-700 text-sm font-medium rounded-lg hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  Mañana
                </button>

                <button
                  @click="setFechaRapida('3dias')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-yellow-50 text-yellow-700 text-sm font-medium rounded-lg hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  +3 días
                </button>

                <button
                  @click="setFechaRapida('semana')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-purple-50 text-purple-700 text-sm font-medium rounded-lg hover:bg-purple-100 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  Una semana
                </button>

                <button
                  @click="setFechaRapida('mes')"
                  type="button"
                  class="inline-flex items-center gap-1 px-3 py-2 bg-red-50 text-red-700 text-sm font-medium rounded-lg hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
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
              <label for="prioridad" class="block text-sm font-medium text-gray-700 mb-2">
                Prioridad
              </label>
              <select
                id="prioridad"
                v-model="form.prioridad"
                :disabled="cargandoDatos"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
              >
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
                <option value="urgente">Urgente</option>
              </select>
            </div>

            <!-- Almacén -->
            <div>
              <label for="almacen_id" class="block text-sm font-medium text-gray-700 mb-2">
                Almacén de Destino
              </label>
              <select
                id="almacen_id"
                v-model="form.almacen_id"
                :disabled="cargandoDatos"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
              >
                <option value="">Seleccionar almacén...</option>
                <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
                  {{ almacen.nombre }}
                </option>
              </select>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Almacén donde se recibirán los productos
              </p>
            </div>
          </div>
        </div>

        <!-- Proveedor -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-center gap-2 text-blue-700 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">Nota:</span>
                <span>Las órdenes de compra solo incluyen productos físicos para inventario.</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Productos -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Productos
            </h2>
          </div>
          <div class="p-6">
            <BuscarProducto
              ref="buscarProductoRef"
              :productos="props.productos"
              :servicios="[]"
              :validar-stock="false"
              @agregar-producto="agregarProducto"
            />
            <ProductosSeleccionados
              :selected-products="selectedProducts"
              :productos="props.productos"
              :servicios="[]"
              :quantities="quantities"
              :prices="prices"
              :discounts="discounts"
              @eliminar-producto="eliminarProducto"
              @update-quantity="updateQuantity"
              @update-discount="updateDiscount"
              @update-price="updatePrice"
              @ver-historial-precios="verHistorialPrecios"
            />
          </div>
        </div>

        <!-- Condiciones de Entrega -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
              Condiciones de Entrega
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Dirección de Entrega -->
            <div class="md:col-span-2">
              <label for="direccion_entrega" class="block text-sm font-medium text-gray-700 mb-2">
                Dirección de Entrega
              </label>
              <textarea
                id="direccion_entrega"
                v-model="form.direccion_entrega"
                :disabled="cargandoDatos"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-vertical disabled:bg-gray-100 disabled:cursor-not-allowed"
                rows="3"
                placeholder="Especifica la dirección donde se debe entregar el pedido..."
              ></textarea>
            </div>

            <!-- Términos de Pago -->
            <div>
              <label for="terminos_pago" class="block text-sm font-medium text-gray-700 mb-2">
                Términos de Pago
              </label>
              <select
                id="terminos_pago"
                v-model="form.terminos_pago"
                :disabled="cargandoDatos"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-orange-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
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
              <label for="metodo_pago" class="block text-sm font-medium text-gray-700 mb-2">
                Método de Pago
              </label>
              <select
                id="metodo_pago"
                v-model="form.metodo_pago"
                :disabled="cargandoDatos"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-orange-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
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
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Notas y Observaciones
            </h2>
          </div>
          <div class="p-6">
            <textarea
              v-model="form.observaciones"
              :disabled="cargandoDatos"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-vertical disabled:bg-gray-100 disabled:cursor-not-allowed"
              rows="4"
              placeholder="Agrega observaciones, especificaciones técnicas, términos y condiciones especiales..."
            ></textarea>
          </div>
        </div>

        <!-- Descuento General -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
              </svg>
              Descuento General
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div>
                <label for="descuento_general" class="block text-sm font-medium text-gray-700 mb-2">
                  Descuento General ($)
                </label>
                <input
                  id="descuento_general"
                  type="number"
                  step="0.01"
                  min="0"
                  v-model="form.descuento_general"
                  :disabled="cargandoDatos"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                  placeholder="0.00"
                />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
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
        />

        <!-- Botones -->
        <BotonesAccion
          :back-url="route('ordenescompra.index')"
          :is-processing="form.processing"
          :can-submit="form.proveedor_id && selectedProducts.length > 0"
          :button-text="form.processing ? 'Actualizando...' : 'Actualizar Orden de Compra'"
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

      <!-- Modal Vista Previa -->
      <VistaPreviaModal
        :show="mostrarVistaPrevia"
        type="ordenescompra"
        :proveedor="proveedorSeleccionado"
        :productos="selectedProducts"
        :totals="totales"
        :notas="form.observaciones"
        :orden-data="{
          numero_orden: form.numero_orden,
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
</div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
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
    { type: 'info', background: '#3B82F6', icon: { className: 'notyf__icon--info', tagName: 'i', text: 'ℹ' } },
  ],
});

const showNotification = (message, type = 'success') => {
  notyf.open({ type, message });
};

// Usar layout
defineOptions({ layout: AppLayout });

// Props
const props = defineProps({
  ordenCompra: {
    type: Object,
    required: true,
  },
  proveedores: {
    type: Array,
    default: () => [],
  },
  productos: {
    type: Array,
    default: () => [],
  },
  almacenes: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
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
  }
});

// Copia reactiva de proveedores para evitar mutación de props
const proveedoresList = ref([...props.proveedores]);

// Formulario con datos de la orden existente
const form = useForm({
  numero_orden: '',
  fecha_orden: '',
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
  total: 0,
  items: [],
  items: [],
  observaciones: '',
  aplicar_retencion_iva: false,
  aplicar_retencion_isr: false,
  retencion_iva: 0,
  retencion_isr: 0,
});

// Función para formatear fechas correctamente
const formatearFecha = (fecha) => {
  if (!fecha) return '';

  try {
    // Si ya está en formato YYYY-MM-DD, devolver tal cual
    if (typeof fecha === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
      return fecha;
    }

    // Si es un objeto Date o string de fecha, convertir
    const dateObj = new Date(fecha);
    if (isNaN(dateObj.getTime())) {
      return '';
    }

    const year = dateObj.getFullYear();
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const day = String(dateObj.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  } catch (error) {
    return '';
  }
};


// Función para inicializar el formulario con datos de la orden
const inicializarFormulario = () => {
  if (props.ordenCompra) {
    form.numero_orden = props.ordenCompra.numero_orden || '';
    form.fecha_orden = formatearFecha(props.ordenCompra.fecha_orden) || formatearFecha(new Date());
    form.fecha_entrega_esperada = formatearFecha(props.ordenCompra.fecha_entrega_esperada);
    form.prioridad = props.ordenCompra.prioridad || 'media';
    form.proveedor_id = props.ordenCompra.proveedor_id || '';
    form.almacen_id = props.ordenCompra.almacen_id || '';
    form.direccion_entrega = props.ordenCompra.direccion_entrega || '';
    form.terminos_pago = props.ordenCompra.terminos_pago || '30_dias';
    form.metodo_pago = props.ordenCompra.metodo_pago || 'transferencia';
    form.subtotal = parseFloat(props.ordenCompra.subtotal) || 0;
    form.descuento_items = parseFloat(props.ordenCompra.descuento_items) || 0;
    form.descuento_general = parseFloat(props.ordenCompra.descuento_general) || 0;
    form.iva = parseFloat(props.ordenCompra.iva) || 0;
    form.total = parseFloat(props.ordenCompra.total) || 0;
    form.observaciones = props.ordenCompra.observaciones || '';
    
    // Configs fiscales
    form.aplicar_retencion_iva = !!props.ordenCompra.aplicar_retencion_iva;
    form.aplicar_retencion_isr = !!props.ordenCompra.aplicar_retencion_isr;
    
    aplicarRetencionIva.value = form.aplicar_retencion_iva;
    aplicarRetencionIsr.value = form.aplicar_retencion_isr;

  } else {
    // Si no hay datos de ordenCompra, generar valores por defecto
    form.numero_orden = '';
    form.fecha_orden = formatearFecha(new Date());
    form.fecha_entrega_esperada = '';
    form.prioridad = 'media';
    form.proveedor_id = '';
    form.almacen_id = '';
    form.direccion_entrega = '';
    form.terminos_pago = '30_dias';
    form.metodo_pago = 'transferencia';
    form.subtotal = 0;
    form.descuento_items = 0;
    form.descuento_general = 0;
    form.iva = 0;
    form.retencion_iva = 0;
    form.retencion_isr = 0;
    form.aplicar_retencion_iva = false;
    form.aplicar_retencion_isr = false;
    form.total = 0;
    form.observaciones = '';
    
    aplicarRetencionIva.value = false;
    aplicarRetencionIsr.value = false;
  }
};

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
const cargandoDatos = ref(true);


// Función para mostrar información sobre fechas rápidas
const mostrarInfoFechas = () => {
  showNotification('Usa los botones para establecer fechas de entrega comunes', 'info');
};

// Función para copiar el número de orden al portapapeles
const copiarNumeroOrden = async () => {
  const numeroACopiar = form.numero_orden.trim();

  try {
    await navigator.clipboard.writeText(numeroACopiar);
    showNotification(`Número copiado: ${numeroACopiar}`, 'success');
  } catch (error) {
    // Fallback para navegadores antiguos
    const textArea = document.createElement('textarea');
    textArea.value = numeroACopiar;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    showNotification(`Número copiado: ${numeroACopiar}`, 'success');
  }
};

// Función para asegurar que la fecha sea siempre la actual
const asegurarFechaActual = () => {
  const fechaActual = formatearFecha(new Date());
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

// Funciones principales
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
    showNotification('Selección de proveedor limpiada', 'info');
    return;
  }
  if (proveedorSeleccionado.value?.id === proveedor.id) return;
  proveedorSeleccionado.value = proveedor;
  form.proveedor_id = proveedor.id;
  showNotification(`Proveedor seleccionado: ${proveedor.nombre_razon_social}`);
};

const agregarProducto = (item) => {
  if (!item || typeof item.id === 'undefined') {
    showNotification('Producto inválido', 'error');
    return;
  }

  // Solo permitir productos, no servicios
  const exists = selectedProducts.value.some(
    (entry) => entry.id === item.id && entry.tipo === 'producto'
  );

  if (!exists) {
    const itemEntry = {
      id: item.id,
      tipo: 'producto',
      nombre: item.nombre,
      descripcion: item.descripcion || '',
      precio: item.precio_compra || 0,
      precio_compra: item.precio_compra || 0
    };
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
  showNotification(`Producto eliminado: ${entry.nombre || entry.descripcion || 'Producto'}`, 'info');
};

const updateQuantity = (key, quantity) => {
  const numQuantity = parseFloat(quantity);
  if (isNaN(numQuantity) || numQuantity < 0) {
    return;
  }
  quantities.value[key] = numQuantity;
  calcularTotal();
};

const updateDiscount = (key, discount) => {
  const numDiscount = parseFloat(discount);
  if (isNaN(numDiscount) || numDiscount < 0 || numDiscount > 100) {
    return;
  }
  discounts.value[key] = numDiscount;
  calcularTotal();
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

const updatePurchaseOrder = () => {
  if (!validarDatos()) {
    return;
  }

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

  form.put(route('ordenescompra.update', props.ordenCompra.id), {
    onSuccess: () => {
      showNotification('Orden de compra actualizada con éxito');
      router.visit(route('ordenescompra.index'));
    },
    onError: (errors) => {
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
  // No limpiar datos críticos de la orden existente
  form.fecha_entrega_esperada = '';
  form.prioridad = 'media';
  form.almacen_id = '';
  form.direccion_entrega = '';
  form.terminos_pago = '30_dias';
  form.metodo_pago = 'transferencia';
  form.observaciones = '';
  form.descuento_general = 0;
  selectedProducts.value = [];
  quantities.value = {};
  prices.value = {};
  discounts.value = {};
  showNotification('Formulario limpiado correctamente');
};

// Función para inicializar productos existentes
const inicializarProductosExistentes = () => {
  if (props.ordenCompra?.productos && Array.isArray(props.ordenCompra.productos)) {
    props.ordenCompra.productos.forEach((item, index) => {
      // Verificar si es un producto (por tipo o por pivot)
      const isProduct = item.tipo === 'producto' ||
                       item.pivot?.item_type === 'App\\Models\\Producto' ||
                       item.pivot?.item_type === 'App\\\\Models\\\\Producto';

      if (isProduct) {

        const itemEntry = {
          id: item.id,
          tipo: 'producto',
          nombre: item.nombre,
          descripcion: item.descripcion || '',
          precio: item.pivot?.precio || 0,
          precio_compra: item.pivot?.precio || 0
        };
        selectedProducts.value.push(itemEntry);

        const key = `producto-${item.id}`;
        quantities.value[key] = item.pivot?.cantidad || 1;
        prices.value[key] = item.pivot?.precio || 0;
        discounts.value[key] = item.pivot?.descuento || 0;
      }
    });

    // Calcular totales después de inicializar productos
    calcularTotal();
  }
};

// Función para inicializar proveedor
const inicializarProveedor = () => {
  if (props.ordenCompra?.proveedor) {
    proveedorSeleccionado.value = props.ordenCompra.proveedor;
  }

  // También buscar el proveedor en la lista de proveedores si no está en ordenCompra.proveedor
  if (!proveedorSeleccionado.value && form.proveedor_id) {
    const proveedorEncontrado = proveedoresList.value.find(p => p.id === form.proveedor_id);
    if (proveedorEncontrado) {
      proveedorSeleccionado.value = proveedorEncontrado;
    }
  }
};

// Función para actualizar precio
const updatePrice = (key, price) => {
  prices.value[key] = price;
  calcularTotal();
};

// Función para ver historial de precios
const verHistorialPrecios = (entry) => {
  // Navegar a la página completa de historial de precios
  window.open(route('reportes.historial-precios', entry.id), '_blank');
};

// Lifecycle hooks
onMounted(() => {
  // Establecer el ID de la orden de compra para el componente hijo
  if (props.ordenCompra?.id) {
    window.ordenCompraId = props.ordenCompra.id;
  }

  // Inicializar datos en orden correcto
  inicializarFormulario();
  inicializarProveedor();
  inicializarProductosExistentes();

  // Marcar que la carga ha terminado
  cargandoDatos.value = false;

  // Verificar la fecha cada 5 minutos para mantenerla actual
  const fechaInterval = setInterval(() => {
    asegurarFechaActual();
  }, 5 * 60 * 1000); // 5 minutos

  window.addEventListener('beforeunload', (event) => {
    if (form.isDirty) {
      event.preventDefault();
      event.returnValue = '¿Estás seguro de que quieres salir? Los cambios no guardados se perderán.';
    }
  });

  // Limpiar el intervalo cuando el componente se desmonte
  onBeforeUnmount(() => {
    clearInterval(fechaInterval);
  });
});

// Watcher para props.ordenCompra en caso de que llegue tarde
watch(() => props.ordenCompra, (nuevaOrden) => {
  if (nuevaOrden && !form.fecha_orden) {
    inicializarFormulario();
    inicializarProveedor();
    inicializarProductosExistentes();
    cargandoDatos.value = false;
  }
}, { immediate: false });

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', () => {});
});
</script>

<style scoped>
/* Estilos específicos para el modo edición */
.ordenes-compra-edit {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  min-height: 100vh;
}

/* Estilos para campos de solo lectura */
input[readonly] {
  background-color: #f9fafb !important;
  color: #6b7280 !important;
  cursor: not-allowed !important;
  border-color: #d1d5db !important;
}

/* Estilos para indicadores de estado */
.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 500;
  border-radius: 0.375rem;
}

.status-readonly {
  background-color: #f3f4f6;
  color: #374151;
}

/* Mejoras visuales para el modo edición */
.edit-mode-indicator {
  position: relative;
}

.edit-mode-indicator::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
  border-radius: 0.375rem 0.375rem 0 0;
}

/* Animaciones de carga */
@keyframes pulse-edit {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.loading-edit {
  animation: pulse-edit 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Estilos responsivos mejorados */
@media (max-width: 768px) {
  .ordenes-compra-edit {
    padding: 1rem;
  }

  .status-indicator {
    font-size: 0.6875rem;
    padding: 0.1875rem 0.5rem;
  }
}
</style>

