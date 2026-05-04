<template>
  <div v-if="show" class="fixed inset-0 z-[60] overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="$emit('close')"></div>
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-2xl bg-white rounded-xl shadow-2xl transform transition-all">
        <div class="bg-emerald-600 px-6 py-4 rounded-t-xl flex justify-between items-center">
          <h3 class="text-lg font-semibold text-white">Agregar Producto</h3>
          <button @click="$emit('close')" class="text-white hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6">
          <form @submit.prevent="saveProduct" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700">Nombre del Producto</label>
                <input v-model="productForm.nombre" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Código</label>
                <input v-model="productForm.codigo" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Código de Barras</label>
                <input v-model="productForm.codigo_barras" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Categoría</label>
                <div class="flex gap-2 mt-1">
                  <select v-model="productForm.categoria_id" class="flex-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required>
                    <option value="" disabled>Seleccionar...</option>
                    <option v-for="cat in catalogos.categorias" :key="cat.id" :value="cat.id">{{ cat.nombre }}</option>
                  </select>
                  <button
                    type="button"
                    @click="$emit('open-categoria')"
                    class="px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700"
                    title="Agregar categoría"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Marca</label>
                <div class="flex gap-2 mt-1">
                  <select v-model="productForm.marca_id" class="flex-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required>
                    <option value="" disabled>Seleccionar...</option>
                    <option v-for="marca in catalogos.marcas" :key="marca.id" :value="marca.id">{{ marca.nombre }}</option>
                  </select>
                  <button
                    type="button"
                    @click="$emit('open-marca')"
                    class="px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700"
                    title="Agregar marca"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                  </button>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Precio Compra</label>
                <input v-model.number="productForm.precio_compra" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Precio Venta</label>
                <input v-model.number="productForm.precio_venta" type="number" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Unidad</label>
                <select v-model="productForm.unidad_medida" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" required>
                  <option value="" disabled>Seleccionar...</option>
                  <option v-for="u in catalogos.unidades" :key="u.id" :value="u.nombre">{{ u.nombre }} ({{ u.abreviatura }})</option>
                  <option v-if="productForm.unidad_medida && !catalogos.unidades.some(u => u.nombre === productForm.unidad_medida)" :value="productForm.unidad_medida">{{ productForm.unidad_medida }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Stock Inicial</label>
                <input v-model.number="productForm.stock" type="number" step="1" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                <p class="text-xs text-gray-500 mt-1">Cantidad del XML: {{ currentConcept?.cantidad }}</p>
              </div>

              <div class="col-span-2 border-t pt-4 mt-2">
                <p class="text-sm font-medium text-gray-700 mb-3">Información SAT (del XML)</p>
                <div class="grid grid-cols-3 gap-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-600">Clave Producto/Servicio</label>
                    <input v-model="productForm.sat_clave_prod_serv" type="text" maxlength="8" placeholder="Ej: 43211503" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600">Clave Unidad SAT</label>
                    <input v-model="productForm.sat_clave_unidad" type="text" maxlength="3" placeholder="Ej: H87" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600">Objeto de Impuesto</label>
                    <select v-model="productForm.sat_objeto_imp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                      <option value="01">01 - No objeto de impuesto</option>
                      <option value="02">02 - Sí objeto de impuesto</option>
                      <option value="03">03 - Sí objeto, no obligado desglose</option>
                      <option value="04">04 - Sí objeto, IVA crédito PODEBI</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="flex items-center mt-2">
                <input v-model="productForm.requiere_serie" id="requiere_serie" type="checkbox" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                <label for="requiere_serie" class="ml-2 block text-sm text-gray-900">
                  Requiere número de serie
                </label>
              </div>

              <div v-if="productForm.requiere_serie" class="col-span-2 mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    <p class="text-sm font-medium text-amber-800">Captura de Series</p>
                  </div>
                  <span class="text-xs bg-amber-200 text-amber-800 px-2 py-1 rounded-full">
                    {{ productSerials.length }} / {{ productForm.stock }} series
                  </span>
                </div>

                <p class="text-xs text-amber-700 mb-3">
                  Escanee o ingrese {{ productForm.stock }} número(s) de serie. Presione Enter o escanee para agregar.
                </p>

                <div class="flex gap-2 mb-3">
                  <input
                    :ref="serialInput"
                    :value="currentSerial"
                    @input="$emit('update:currentSerial', $event.target.value)"
                    @keydown.enter.prevent="addSerial"
                    type="text"
                    :disabled="productSerials.length >= productForm.stock"
                    :placeholder="productSerials.length >= productForm.stock ? 'Series completas' : 'Escanee o escriba número de serie...'"
                    class="flex-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:bg-gray-100"
                    autofocus
                  />
                  <button
                    type="button"
                    @click="addSerial"
                    :disabled="!currentSerial.trim() || productSerials.length >= productForm.stock"
                    class="px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Agregar
                  </button>
                </div>

                <div v-if="productSerials.length > 0" class="max-h-32 overflow-y-auto space-y-1">
                  <div
                    v-for="(serial, idx) in productSerials"
                    :key="idx"
                    class="flex items-center justify-between bg-white px-3 py-1.5 rounded border border-gray-200 text-sm"
                  >
                    <span class="font-mono text-gray-700">{{ serial }}</span>
                    <button
                      type="button"
                      @click="removeSerial(idx)"
                      class="text-red-500 hover:text-red-700"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <p v-if="productSerials.length < productForm.stock && productForm.stock > 0" class="text-xs text-amber-600 mt-2">
                  ⚠️ Faltan {{ productForm.stock - productSerials.length }} serie(s) por capturar
                </p>
              </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
              <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
              <button
                type="submit"
                :disabled="savingProduct"
                class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 flex items-center"
              >
                <svg v-if="savingProduct" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Guardar Producto
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show: { type: Boolean, default: false },
  productForm: { type: Object, required: true },
  catalogos: { type: Object, required: true },
  currentConcept: { type: Object, default: null },
  productSerials: { type: Array, default: () => [] },
  currentSerial: { type: String, default: '' },
  savingProduct: { type: Boolean, default: false },
  serialInput: { type: Object, default: null },
  addSerial: { type: Function, required: true },
  removeSerial: { type: Function, required: true },
  saveProduct: { type: Function, required: true },
});

defineEmits(['close', 'open-categoria', 'open-marca', 'update:currentSerial']);
</script>
