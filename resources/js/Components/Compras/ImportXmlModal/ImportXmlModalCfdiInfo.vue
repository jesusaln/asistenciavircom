<template>
  <div>
    <div v-if="!cfdiData.es_factura_valida" class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
      <div class="flex items-start">
        <svg class="w-5 h-5 text-amber-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <div>
          <p class="font-medium text-amber-800">Este XML no es una factura de productos</p>
          <p class="text-sm text-amber-700 mt-1">
            Tipo: {{ cfdiData.tipo_comprobante_nombre }}. Solo se pueden importar facturas de Ingreso (I).
          </p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Folio</p>
        <p class="font-semibold text-gray-900">{{ cfdiData.serie }}{{ cfdiData.folio }}</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Fecha</p>
        <p class="font-semibold text-gray-900">{{ formatDate(cfdiData.fecha) }}</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Subtotal</p>
        <p class="font-semibold text-gray-900">${{ formatMoney(cfdiData.subtotal) }}</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">IVA (16%)</p>
        <p class="font-semibold text-blue-600">${{ formatMoney(cfdiData.impuestos?.total_impuestos_trasladados || 0) }}</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
        <p class="font-semibold text-emerald-600">${{ formatMoney(cfdiData.total) }}</p>
      </div>
    </div>

    <div v-if="cfdiData.descuento > 0" class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-red-700">Descuento aplicado:</span>
        <span class="font-semibold text-red-600">-${{ formatMoney(cfdiData.descuento) }}</span>
      </div>
    </div>

    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <p class="text-xs text-blue-600 uppercase tracking-wide font-medium mb-2">Proveedor (Emisor)</p>
          <p class="font-semibold text-gray-900 text-lg">{{ cfdiData.emisor?.nombre }}</p>
          <div class="mt-2 space-y-1">
            <p class="text-sm text-gray-600">
              <span class="font-medium">RFC:</span> {{ cfdiData.emisor?.rfc }}
              <span v-if="cfdiData.emisor?.rfc?.length === 12" class="ml-2 px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 rounded">Persona Moral</span>
              <span v-else-if="cfdiData.emisor?.rfc?.length === 13" class="ml-2 px-1.5 py-0.5 text-xs bg-purple-100 text-purple-700 rounded">Persona Física</span>
            </p>
            <p v-if="cfdiData.emisor?.regimen_fiscal" class="text-sm text-gray-600">
              <span class="font-medium">Régimen Fiscal:</span> {{ cfdiData.emisor?.regimen_fiscal }} - {{ getRegimenFiscalNombre(cfdiData.emisor?.regimen_fiscal) }}
            </p>
            <p v-if="cfdiData.lugar_expedicion" class="text-sm text-gray-600">
              <span class="font-medium">CP Expedición:</span> {{ cfdiData.lugar_expedicion }}
            </p>
          </div>
        </div>
        <div v-if="cfdiData.proveedor_encontrado" class="flex items-center text-green-600 bg-green-50 px-3 py-2 rounded-lg">
          <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          <span class="text-sm font-medium">Proveedor encontrado</span>
        </div>
        <div v-else class="flex flex-col items-end space-y-3 w-full max-w-sm">
          <div class="flex items-center text-amber-600 mb-1">
            <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium">Proveedor no registrado</span>
          </div>

          <div class="w-full space-y-2 bg-white p-3 rounded-lg border border-gray-200">
            <p class="text-xs text-gray-500 mb-2">Datos adicionales (opcionales):</p>
            <input
              :value="newProviderEmail"
              type="email"
              placeholder="Email de contacto"
              class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
              @input="$emit('update:newProviderEmail', $event.target.value)"
            />
            <input
              :value="newProviderPhone"
              type="text"
              placeholder="Teléfono"
              class="w-full text-sm rounded border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
              @input="$emit('update:newProviderPhone', $event.target.value)"
            />
          </div>

          <button
            @click="registrarProveedorExpress"
            :disabled="registrandoProveedor"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed w-full justify-center"
          >
            <svg v-if="!registrandoProveedor" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ registrandoProveedor ? 'Registrando...' : 'Registrar Proveedor con estos datos' }}
          </button>
          <p class="text-xs text-gray-400 text-center">Se usará: RFC, Nombre y Régimen Fiscal del XML</p>
        </div>
      </div>
    </div>

    <div class="mb-4 bg-indigo-50 border border-indigo-200 rounded-lg p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          <div class="flex flex-col">
            <label class="text-sm font-medium text-indigo-800">Almacén destino *</label>
            <span class="text-xs text-indigo-600 mt-1">
              Método Pago Detectado: <strong>{{ cfdiData?.metodo_pago || 'N/A' }}</strong>
            </span>
          </div>
        </div>
        <select
          :value="selectedAlmacenId"
          class="w-64 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          required
          @change="$emit('update:selectedAlmacenId', $event.target.value)"
        >
          <option value="">Seleccionar almacén...</option>
          <option v-for="almacen in almacenes" :key="almacen.id" :value="almacen.id">
            {{ almacen.nombre }}
          </option>
        </select>
      </div>
    </div>

    <div v-if="cfdiData?.metodo_pago === 'PUE'" class="mb-4 bg-emerald-50 border border-emerald-200 rounded-lg p-4">
      <div class="flex items-center justify-between">
        <label class="flex items-center cursor-pointer">
          <input
            type="checkbox"
            :checked="puePagado"
            class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded"
            @change="$emit('update:puePagado', $event.target.checked)"
          />
          <span class="ml-2 text-sm font-medium text-emerald-800">¿Ya está pagada esta factura? (PUE Detectado)</span>
        </label>
        <div class="text-xs text-emerald-600 italic">
          Las facturas PUE se consideran pagadas de contado
        </div>
      </div>

      <div v-if="puePagado" class="grid grid-cols-2 gap-4 mt-3 pt-3 border-t border-emerald-100">
        <div>
          <label class="block text-xs font-medium text-emerald-700 mb-1">Cuenta Bancaria *</label>
          <select
            :value="pueCuentaBancariaId"
            class="w-full text-sm rounded border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500"
            required
            @change="$emit('update:pueCuentaBancariaId', $event.target.value)"
          >
            <option value="">Seleccionar cuenta...</option>
            <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
              {{ cuenta.banco }} - {{ cuenta.numero_cuenta }} (${{ formatMoney(cuenta.saldo_actual) }})
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-emerald-700 mb-1">Método de Pago *</label>
          <select
            :value="pueMetodoPago"
            class="w-full text-sm rounded border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500"
            required
            @change="$emit('update:pueMetodoPago', $event.target.value)"
          >
            <option value="transferencia">Transferencia</option>
            <option value="efectivo">Efectivo</option>
            <option value="tarjeta">Tarjeta</option>
            <option value="cheque">Cheque</option>
          </select>
        </div>
      </div>
    </div>

    <div class="mb-4 flex items-center justify-between bg-gray-100 rounded-lg p-3">
      <span class="text-sm font-medium text-gray-700">Productos del CFDI</span>
      <div class="flex items-center space-x-4 text-sm">
        <span class="flex items-center text-green-600">
          <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
          {{ cfdiData.mapeo_stats?.mapeados || 0 }} encontrados
        </span>
        <span v-if="cfdiData.mapeo_stats?.similares" class="flex items-center text-amber-600">
          <span class="w-2 h-2 bg-amber-500 rounded-full mr-1"></span>
          {{ cfdiData.mapeo_stats.similares }} similares
        </span>
        <span v-if="cfdiData.mapeo_stats?.no_encontrados" class="flex items-center text-red-600">
          <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
          {{ cfdiData.mapeo_stats.no_encontrados }} no encontrados
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  cfdiData: { type: Object, required: true },
  formatDate: { type: Function, required: true },
  formatMoney: { type: Function, required: true },
  getRegimenFiscalNombre: { type: Function, required: true },
  newProviderEmail: { type: String, default: '' },
  newProviderPhone: { type: String, default: '' },
  registrandoProveedor: { type: Boolean, default: false },
  registrarProveedorExpress: { type: Function, required: true },
  selectedAlmacenId: { type: [String, Number], default: '' },
  almacenes: { type: Array, default: () => [] },
  puePagado: { type: Boolean, default: false },
  pueCuentaBancariaId: { type: [String, Number], default: '' },
  pueMetodoPago: { type: String, default: 'transferencia' },
  cuentasBancarias: { type: Array, default: () => [] },
});

defineEmits([
  'update:newProviderEmail',
  'update:newProviderPhone',
  'update:selectedAlmacenId',
  'update:puePagado',
  'update:pueCuentaBancariaId',
  'update:pueMetodoPago',
]);
</script>
