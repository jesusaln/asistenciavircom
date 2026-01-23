<template>
  <Head title="Editar Compra" />
  <div class="compras-edit min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-6">
    <div class="w-full">
      <!-- Header -->
      <div class="mb-6">
        <Header
          title="Editar Compra"
          :description="`Editando compra ${compra.numero_compra}`"
          :can-preview="proveedorSeleccionado && form.almacen_id && selectedProducts.length > 0"
          :back-url="route('compras.index')"
          :show-shortcuts="mostrarAtajos"
          @preview="handlePreview"
          @close-shortcuts="mostrarAtajos = false"
        />
        <div class="mt-4 bg-amber-50 border border-amber-200 rounded-md p-3">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-amber-700">
                <strong>Modo Edición:</strong> Puedes agregar o quitar productos, modificar cantidades y precios. El proveedor y almacén no pueden cambiarse.
              </p>
            </div>
          </div>
        </div>
      </div>

      <form @submit.prevent="actualizarCompra" class="space-y-8">
        <!-- Información General -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Información General
            </h2>
          </div>
          <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Número de Compra -->
            <div>
              <label for="numero_compra" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Número de Compra *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Bloqueado
                </span>
              </label>
              <div class="relative">
                <input
                  id="numero_compra"
                  v-model="form.numero_compra"
                  type="text"
                  class="w-full bg-gray-100 text-gray-600 dark:text-gray-300 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3"
                  readonly
                  required
                />
              </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                El número de compra no puede modificarse
              </p>
            </div>

            <!-- Fecha de Compra -->
            <div>
              <label for="fecha_compra" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Fecha de Compra *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Bloqueado
                </span>
              </label>
              <div class="relative">
                <input
                  id="fecha_compra"
                  v-model="form.fecha_compra"
                  type="date"
                  class="w-full bg-gray-100 text-gray-600 dark:text-gray-300 cursor-not-allowed border border-gray-300 rounded-lg px-4 py-3"
                  readonly
                  required
                />
              </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                La fecha original se mantiene
              </p>
            </div>

            <!-- Metodo de Pago -->
            <div>
              <label for="metodo_pago" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Método de Pago *
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Requerido
                </span>
              </label>
              <div class="relative">
                <select
                  id="metodo_pago"
                  v-model="form.metodo_pago"
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                  required
                >
                  <option value="">Selecciona un método</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="transferencia">Transferencia</option>
                  <option value="cheque">Cheque</option>
                  <option value="tarjeta">Tarjeta</option>
                  <option value="credito">Crédito</option>
                  <option value="otros">Otros</option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Cuenta Bancaria -->
            <div class="md:col-span-3">
              <label for="cuenta_bancaria" class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                Cuenta Bancaria de Origen
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-medium rounded-full">
                  Opcional
                </span>
              </label>
              <select
                id="cuenta_bancaria"
                v-model="form.cuenta_bancaria_id"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
              >
                <option value="">Sin especificar (no descuenta de banco)</option>
                <option 
                  v-for="cuenta in cuentasBancarias" 
                  :key="cuenta.id" 
                  :value="cuenta.id"
                  :disabled="parseFloat(cuenta.saldo_actual) < totales.total"
                >
                  🏦 {{ cuenta.banco }} - {{ cuenta.numero_cuenta }} | Saldo: {{ formatearMoneda(cuenta.saldo_actual) }}
                  <template v-if="parseFloat(cuenta.saldo_actual) < totales.total"> (Saldo insuficiente)</template>
                </option>
              </select>
              
              <!-- Mensaje de saldo -->
              <div v-if="cuentaSeleccionada" class="mt-2">
                <div 
                  v-if="parseFloat(cuentaSeleccionada.saldo_actual) < totales.total"
                  class="p-3 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2"
                >
                  <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-red-700">Saldo Insuficiente</p>
                    <p class="text-xs text-red-600">
                      Saldo: <strong>{{ formatearMoneda(cuentaSeleccionada.saldo_actual) }}</strong> |
                      Necesario: <strong>{{ formatearMoneda(totales.total) }}</strong> |
                      Faltan: <strong>{{ formatearMoneda(totales.total - parseFloat(cuentaSeleccionada.saldo_actual)) }}</strong>
                    </p>
                  </div>
                </div>
                <div 
                  v-else
                  class="p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-2"
                >
                  <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-blue-700">Se descontará de esta cuenta al guardar</p>
                    <p class="text-xs text-blue-600">
                      Saldo actual: <strong>{{ formatearMoneda(cuentaSeleccionada.saldo_actual) }}</strong> →
                      Nuevo saldo: <strong>{{ formatearMoneda(parseFloat(cuentaSeleccionada.saldo_actual) - totales.total) }}</strong>
                    </p>
                  </div>
                </div>
              </div>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Selecciona de qué cuenta sale el dinero para esta compra
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
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ proveedorSeleccionado?.nombre_razon_social || 'Sin proveedor' }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    RFC: {{ proveedorSeleccionado?.rfc || 'N/A' }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    Email: {{ proveedorSeleccionado?.email || 'N/A' }}
                  </p>
                </div>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  No modificable
                </span>
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              El proveedor no puede cambiarse después de crear la compra
            </p>
          </div>
        </div>

        <!-- Almacén de Recepción -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              Almacén de Recepción
            </h2>
          </div>
          <div class="p-6">
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ almacenNombre }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Los productos se recibieron en este almacén
                  </p>
                </div>
                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  No modificable
                </span>
              </div>
            </div>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              El almacén no puede cambiarse porque el inventario ya fue ajustado
            </p>
          </div>
        </div>

        <!-- Productos Disponibles -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
          <div class="bg-gradient-to-r from-green-500 to-green-600 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              Productos
            </h2>
          </div>
          <div class="p-6">
            <div class="mb-6 text-sm text-gray-600 dark:text-gray-300">
              Usa el buscador para agregar más productos o modifica los existentes.
            </div>

            <div class="pt-2 border-t border-gray-200 dark:border-slate-800">
              <BuscarProducto
                ref="buscarProductoRef"
                :productos="props.productos"
                :servicios="props.servicios"
                :validar-stock="false"
                @agregar-producto="agregarProducto"
              />
            </div>

            <!-- Productos seleccionados -->
            <ProductosSeleccionados
              :selected-products="selectedProducts"
              :productos="props.productos"
              :servicios="props.servicios"
              :quantities="quantities"
              :prices="prices"
              :discounts="discounts"
              :serials="serialsMap"
              @open-serials="openSerials"
              @eliminar-producto="eliminarProducto"
              @update-quantity="updateQuantity"
              @update-discount="updateDiscount"
              @update-serials="updateSerials"
            />

            <!-- Modal para capturar series -->
            <div v-if="showSerialsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
              <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl w-full max-w-md mx-4">
                <div class="p-6">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Capturar Series - {{ currentSerialProduct?.nombre }}
                  </h3>

                  <div class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                      Cantidad: {{ currentSerialQty }} unidades
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      Debe capturar exactamente {{ currentSerialQty }} series únicas
                    </p>
                  </div>

                  <div class="space-y-2 max-h-60 overflow-y-auto">
                    <div
                      v-for="(serie, index) in serialsForEntry"
                      :key="index"
                      class="flex items-center space-x-2"
                    >
                      <span class="text-sm font-medium text-gray-500 dark:text-gray-400 w-6">{{ index + 1 }}.</span>
                      <input
                        v-model="serialsForEntry[index]"
                        type="text"
                        :placeholder="`Serie ${index + 1}`"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                      />
                    </div>
                  </div>

                  <div class="flex justify-end space-x-3 mt-6">
                    <button
                      @click="cancelSerials"
                      class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
                    >
                      Cancelar
                    </button>
                    <button
                      @click="saveSerials"
                      class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                    >
                      Guardar Series
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notas -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
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
              placeholder="Agrega notas adicionales, términos y condiciones, o información relevante para la compra..."
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
                  class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent"
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
          @update:descuento-general="val => form.descuento_general = val"
        />

        <!-- Botones -->
        <BotonesAccion
          :back-url="route('compras.index')"
          :is-processing="form.processing"
          :can-submit="form.proveedor_id && form.almacen_id && selectedProducts.length > 0"
          :button-text="form.processing ? 'Actualizando...' : 'Actualizar Compra'"
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
        type="compra"
        :proveedor="proveedorSeleccionado"
        :items="selectedProducts"
        :totals="totales"
        :notas="form.notas"
        @close="mostrarVistaPrevia = false"
        @print="() => window.print()"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Header from '@/Components/CreateComponents/Header.vue';
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
  compra: { type: Object, required: true },
  proveedores: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  servicios: { type: Array, default: () => [] },
  almacenes: { type: Array, default: () => [] },
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

// Referencias
const buscarProductoRef = ref(null);
const proveedorSeleccionado = ref(null);
const selectedProducts = ref([]);
const quantities = ref({});
const prices = ref({});
const discounts = ref({});
const serialsMap = ref({});
const showSerialsModal = ref(false);
const serialsForEntry = ref([]);
const currentSerialKey = ref('');
const currentSerialQty = ref(1);
const currentSerialProduct = ref(null);
const mostrarVistaPrevia = ref(false);
const mostrarAtajos = ref(false);
const cuentasBancarias = ref([]);

const form = useForm({
  numero_compra: '',
  fecha_compra: '',
  proveedor_id: '',
  almacen_id: '',
  metodo_pago: '',
  cuenta_bancaria_id: '',
  descuento_general: 0,
  subtotal: 0,
  descuento_items: 0,
  iva: 0,
  total: 0,
  productos: [],
  notas: '',
  aplicar_retencion_iva: false,
  aplicar_retencion_isr: false,
  retencion_iva: 0,
  retencion_isr: 0,
  isr: 0,
});

// Reactividad para configuración fiscal
const aplicarRetencionIva = ref(false);
const aplicarRetencionIsr = ref(false);

const retencionIvaDefault = computed(() => parseFloat(props.defaults?.retencionIvaDefault ?? 10.6667));
const retencionIsrDefault = computed(() => parseFloat(props.defaults?.retencionIsrDefault ?? 10));

// Computed
const almacenNombre = computed(() => {
  const almacen = props.almacenes.find(a => a.id === form.almacen_id);
  return almacen?.nombre || props.compra.almacen?.nombre || 'N/A';
});

// Cuenta bancaria seleccionada
const cuentaSeleccionada = computed(() => {
  if (!form.cuenta_bancaria_id) return null;
  return cuentasBancarias.value.find(c => c.id === parseInt(form.cuenta_bancaria_id));
});

// Formatear moneda
const formatearMoneda = (valor) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(valor || 0);
};

// Cargar cuentas bancarias
const fetchCuentasBancarias = async () => {
  try {
    const response = await axios.get('/api/cuentas-bancarias/activas');
    cuentasBancarias.value = response.data;
  } catch (error) {
    console.error('Error al obtener cuentas bancarias:', error);
    try {
      const webResponse = await axios.get('/cuentas-bancarias/activas');
      cuentasBancarias.value = webResponse.data;
    } catch (e) {
      console.error('Error al obtener cuentas bancarias (web):', e);
    }
  }
};

// Funciones
const handlePreview = () => {
  if (proveedorSeleccionado.value && form.almacen_id && selectedProducts.value.length > 0) {
    mostrarVistaPrevia.value = true;
  } else {
    showNotification('Selecciona un proveedor, almacén y al menos un producto', 'error');
  }
};

const agregarProducto = (item) => {
  if (!item || typeof item.id === 'undefined' || !item.tipo) {
    showNotification('Producto inválido', 'error');
    return;
  }

  const itemEntry = { id: item.id, tipo: item.tipo };
  const exists = selectedProducts.value.some(
    (entry) => entry.id === item.id && entry.tipo === item.tipo
  );

  if (!exists) {
    selectedProducts.value.push(itemEntry);
    const key = `${item.tipo}-${item.id}`;
    quantities.value[key] = 1;

    let precio = 0;
    if (item.tipo === 'producto') {
      precio = parseFloat(item.precio_compra) || 0;
    } else {
      precio = parseFloat(item.precio) || 0;
    }

    prices.value[key] = precio;
    discounts.value[key] = 0;
    calcularTotal();
    showNotification(`Producto añadido: ${item.nombre || item.descripcion || 'Item'}`);
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
  delete serialsMap.value[key];
  calcularTotal();
  showNotification(`Producto eliminado: ${entry.nombre || entry.descripcion || 'Item'}`, 'info');
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

const updateSerials = (key, serials) => {
  serialsMap.value[key] = serials;
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
  const subtotalConDescuentos = Math.max(0, subtotal - descuentoItems - descuentoGeneral);
  const ivaRate = (props.defaults?.ivaPorcentaje ?? 16) / 100;
  const iva = subtotalConDescuentos * ivaRate;

  // Calculo de retenciones
  let retencionIva = 0;
  if (aplicarRetencionIva.value && props.defaults?.enableRetencionIva) {
      retencionIva = subtotalConDescuentos * (retencionIvaDefault.value / 100);
  }

  let retencionIsr = 0;
  if (aplicarRetencionIsr.value && props.defaults?.enableRetencionIsr) {
      retencionIsr = subtotalConDescuentos * (retencionIsrDefault.value / 100);
  }

  // ISR simple
  let isr = 0;

  const total = subtotalConDescuentos + iva - retencionIva - retencionIsr;

  return {
    subtotal: parseFloat(subtotal.toFixed(2)),
    descuentoItems: parseFloat(descuentoItems.toFixed(2)),
    descuentoGeneral: parseFloat(descuentoGeneral.toFixed(2)),
    subtotalConDescuentos: parseFloat(subtotalConDescuentos.toFixed(2)),
    iva: parseFloat(iva.toFixed(2)),
    retencion_iva: parseFloat(retencionIva.toFixed(2)),
    retencion_isr: parseFloat(retencionIsr.toFixed(2)),
    isr: parseFloat(isr.toFixed(2)),
    total: parseFloat(total.toFixed(2)),
  };
});

const calcularTotal = () => {
  const t = totales.value;
  form.subtotal = t.subtotal;
  form.descuento_items = t.descuentoItems;
  form.iva = t.iva;
  form.retencion_iva = t.retencion_iva;
  form.retencion_isr = t.retencion_isr;
  form.total = t.total;

  form.aplicar_retencion_iva = aplicarRetencionIva.value;
  form.aplicar_retencion_isr = aplicarRetencionIsr.value;
};

const validarDatos = () => {
  if (!proveedorSeleccionado.value) {
    showNotification('Debes seleccionar un proveedor', 'error');
    return false;
  }

  if (!form.almacen_id) {
    showNotification('Debes seleccionar un almacén', 'error');
    return false;
  }

  if (selectedProducts.value.length === 0) {
    showNotification('Debes agregar al menos un producto', 'error');
    return false;
  }

  // Validar cantidades y precios
  for (const entry of selectedProducts.value) {
    const key = `${entry.tipo}-${entry.id}`;
    const cantidad = parseFloat(quantities.value[key]) || 0;
    const precio = parseFloat(prices.value[key]) || 0;
    const descuento = parseFloat(discounts.value[key]) || 0;

    if (cantidad <= 0) {
      showNotification(`La cantidad del producto "${entry.nombre}" debe ser mayor a 0`, 'error');
      return false;
    }

    if (precio < 0) {
      showNotification(`El precio del producto "${entry.nombre}" no puede ser negativo`, 'error');
      return false;
    }

    if (descuento < 0 || descuento > 100) {
      showNotification('Los descuentos deben estar entre 0% y 100%.', 'error');
      return false;
    }

    // ✅ FIX #11: Validar series en frontend
    if (entry.tipo === 'producto' && entry.requiere_serie) {
      const seriales = serialsMap.value[key] || [];
      
      if (seriales.length !== cantidad) {
        showNotification(
          `El producto "${entry.nombre}" requiere exactamente ${cantidad} series, pero tiene ${seriales.length}`,
          'error'
        );
        return false;
      }
      
      // Validar unicidad de series
      const uniqueSerials = [...new Set(seriales.map(s => (s || '').trim()).filter(Boolean))];
      if (uniqueSerials.length !== seriales.length) {
        showNotification(
          `El producto "${entry.nombre}" tiene series duplicadas o vacías`,
          'error'
        );
        return false;
      }
    }
  }

  return true;
};

const actualizarCompra = () => {
  if (!validarDatos()) {
    return;
  }

  // ✅ FIX: Eliminar campo 'tipo' del payload - backend solo espera productos
  form.productos = selectedProducts.value
    .filter(entry => entry.tipo === 'producto') // Solo productos, no servicios
    .map((entry) => {
      const key = `${entry.tipo}-${entry.id}`;
      const seriales = serialsMap.value[key];
      const productoData = {
        id: entry.id,
        cantidad: parseFloat(quantities.value[key]) || 1,
        precio: parseFloat(prices.value[key]) || 0,
        descuento: parseFloat(discounts.value[key]) || 0,
      };

      if (seriales && Array.isArray(seriales) && seriales.length > 0) {
        productoData.seriales = seriales;
      }

      return productoData;
    });

  calcularTotal();

  // Mantener metodo_pago en el payload (validacion backend lo requiere)
  if (!form.metodo_pago) {
    form.metodo_pago = 'efectivo';
  }

  form.put(route('compras.update', props.compra.id), {
    onSuccess: () => {
      showNotification('Compra actualizada con éxito');
    },
    onError: (errors) => {
      console.error('Errores de validación:', errors);
      
      // ✅ FIX: Mostrar errores específicos por campo
      if (typeof errors === 'object' && errors !== null) {
        const errorMessages = [];
        
        for (const [field, messages] of Object.entries(errors)) {
          if (Array.isArray(messages)) {
            errorMessages.push(...messages);
          } else if (typeof messages === 'string') {
            errorMessages.push(messages);
          }
        }
        
        if (errorMessages.length > 0) {
          // Mostrar el primer error más específico
          showNotification(errorMessages[0], 'error');
        } else {
          showNotification('Hubo errores de validación. Por favor revisa los datos.', 'error');
        }
      } else if (typeof errors === 'string') {
        showNotification(errors, 'error');
      } else {
        showNotification('Hubo un error al actualizar la compra', 'error');
      }
    },
  });
};

// Captura de series
const openSerials = (entry) => {
  const key = `${entry.tipo}-${entry.id}`;
  currentSerialKey.value = key;
  currentSerialQty.value = Number(quantities.value[key]) || 1;

  const producto = props.productos.find(p => p.id === entry.id);
  currentSerialProduct.value = producto || null;

  const existentes = serialsMap.value[key] || [];
  serialsForEntry.value = Array.from({ length: currentSerialQty.value }, (_, i) => existentes[i] || '');
  showSerialsModal.value = true;
};

const saveSerials = () => {
  const serials = serialsForEntry.value.map(s => (s || '').trim()).filter(Boolean);
  if (serials.length !== currentSerialQty.value) {
    showNotification(`Debes capturar exactamente ${currentSerialQty.value} series.`, 'error');
    return;
  }
  if ((new Set(serials)).size !== serials.length) {
    showNotification('Las series deben ser únicas.', 'error');
    return;
  }
  serialsMap.value[currentSerialKey.value] = serials;
  showSerialsModal.value = false;
  showNotification(`Series capturadas correctamente para ${currentSerialProduct.value?.nombre || 'el producto'}`, 'success');
};

const cancelSerials = () => {
  showSerialsModal.value = false;
  currentSerialProduct.value = null;
  serialsForEntry.value = [];
  currentSerialKey.value = '';
  currentSerialQty.value = 1;
};

const limpiarFormulario = () => {
  if (confirm('¿Estás seguro de que quieres limpiar el formulario? Se perderán los cambios no guardados.')) {
    cargarDatosIniciales();
    showNotification('Formulario restaurado a los datos originales', 'info');
  }
};

// Cargar datos iniciales
const cargarDatosIniciales = () => {
  form.numero_compra = props.compra.numero_compra;
  form.proveedor_id = props.compra.proveedor_id;
  form.almacen_id = props.compra.almacen_id;
  form.metodo_pago = props.compra.metodo_pago;
  form.cuenta_bancaria_id = props.compra.cuenta_bancaria_id;
  form.fecha_compra = props.compra.fecha_compra;
  form.descuento_general = parseFloat(props.compra.descuento_general) || 0;
  form.notas = props.compra.notas;
  form.aplicar_retencion_iva = !!props.compra.aplicar_retencion_iva;
  form.aplicar_retencion_isr = !!props.compra.aplicar_retencion_isr;
  
  // Set refs used by UI
  aplicarRetencionIva.value = !!props.compra.aplicar_retencion_iva;
  aplicarRetencionIsr.value = !!props.compra.aplicar_retencion_isr;

  proveedorSeleccionado.value = props.proveedores.find(p => p.id === props.compra.proveedor_id);

  // Cargar productos
  selectedProducts.value = [];
  quantities.value = {};
  prices.value = {};
  discounts.value = {};
  serialsMap.value = {};

  if (props.compra.productos && Array.isArray(props.compra.productos)) {
    props.compra.productos.forEach((item) => {
      const entry = { id: item.id, tipo: item.tipo || 'producto', nombre: item.nombre, requiere_serie: item.requiere_serie };
      selectedProducts.value.push(entry);
      
      const key = `${entry.tipo}-${entry.id}`;
      quantities.value[key] = parseFloat(item.cantidad) || 0;
      prices.value[key] = parseFloat(item.precio) || 0;
      discounts.value[key] = parseFloat(item.descuento) || 0;
      
      if (item.seriales && Array.isArray(item.seriales)) {
        serialsMap.value[key] = item.seriales;
      }
    });
  }

  calcularTotal();
  showNotification('Datos de compra cargados correctamente', 'info');
};

// Lifecycle
onMounted(() => {
  cargarDatosIniciales();
  fetchCuentasBancarias();
});
</script>

