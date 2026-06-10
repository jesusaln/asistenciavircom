<template>
  <div>
    <div class="border border-slate-200 rounded-xl overflow-hidden mb-6">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-800/50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">
              <span class="flex items-center group relative cursor-help">
                Estado
                <svg class="w-4 h-4 ml-1 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <div class="absolute left-0 bottom-full mb-2 hidden group-hover:block w-64 p-2 bg-slate-800 text-white text-xs rounded-xl shadow-lg z-10">
                  <p class="font-medium mb-1">¿Qué significa el Estado?</p>
                  <p><span class="text-emerald-400">● Encontrado:</span> Producto existe en tu sistema</p>
                  <p><span class="text-amber-400">● Similar:</span> Producto parecido encontrado</p>
                  <p><span class="text-rose-400">● No encontrado:</span> Deberás agregarlo manualmente</p>
                </div>
              </span>
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Código</th>
            <th class="px-3 py-3 text-left text-xs font-medium text-slate-500 uppercase">Clave SAT</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">Descripción</th>
            <th class="px-3 py-3 text-center text-xs font-medium text-slate-500 uppercase">Unidad</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Cantidad</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Precio</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase">Importe</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
          <tr v-for="(concepto, index) in cfdiData.conceptos" :key="index" class="hover:bg-slate-50">
            <td class="px-4 py-3 whitespace-nowrap">
              <div class="flex flex-col space-y-1">
                <span v-if="concepto.match_type === 'exact'" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                  Encontrado
                </span>
                <span v-else-if="concepto.match_type === 'similar'" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-brand-100 text-amber-800">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  Similar ({{ concepto.match_confidence }}%)
                </span>
                <button
                  v-if="concepto.match_type === 'similar' && (concepto.match_confidence || 0) < 75"
                  @click="openProductModal(concepto, index)"
                  class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-colors mt-1"
                  title="Crear producto nuevo porque la coincidencia es menor al 75%"
                >
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Crear Nuevo
                </button>
                <div v-else-if="!concepto.match_type || concepto.match_type === 'none'" class="flex flex-col items-start space-y-1">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    No encontrado
                  </span>
                  <button
                    @click="openProductModal(concepto, index)"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-colors"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Agregar
                  </button>
                </div>

                <div v-if="concepto.producto_id && concepto.requiere_serie" class="mt-1">
                  <button
                    @click="openSerialModal(index)"
                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-xl transition-colors"
                    :class="(concepto.seriales?.length || 0) >= concepto.cantidad
                      ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
                      : 'bg-brand-100 text-brand-800 hover:bg-orange-200'"
                  >
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Series: {{ concepto.seriales?.length || 0 }}/{{ concepto.cantidad }}
                  </button>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-900">
              <div class="flex flex-col">
                <span :class="{ 'text-blue-600 font-mono text-xs italic': /^\d{4,8}-\d{1,4}-\d{3,6}$/.test(concepto.no_identificacion?.trim()) }">
                  {{ concepto.no_identificacion || '-' }}
                </span>
                <span v-if="/^\d{4,8}-\d{1,4}-\d{3,6}$/.test(concepto.no_identificacion?.trim())" class="text-[10px] text-blue-500 font-medium leading-tight">
                  (Serie detectada)
                </span>
              </div>
            </td>
            <td class="px-3 py-3 whitespace-nowrap text-sm">
              <span v-if="concepto.clave_prod_serv" class="inline-flex items-center px-2 py-0.5 rounded-xl bg-sky-100 text-sky-800 text-xs font-mono">
                {{ concepto.clave_prod_serv }}
              </span>
              <span v-else class="text-slate-400">-</span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-900">
              <div>{{ concepto.descripcion }}</div>
              <div v-if="concepto.producto_nombre" class="text-xs text-emerald-600 mt-1">
                → {{ concepto.producto_nombre }}
              </div>
            </td>
            <td class="px-3 py-3 whitespace-nowrap text-sm text-center">
              <span class="inline-flex items-center px-2 py-0.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-medium">
                {{ concepto.unidad || concepto.clave_unidad || 'PZA' }}
              </span>
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-900 text-right">
              {{ concepto.cantidad }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-900 text-right">
              ${{ formatMoney(concepto.valor_unitario) }}
            </td>
            <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-900 text-right font-medium">
              ${{ formatMoney(concepto.importe) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="cfdiData.mapeo_stats?.no_encontrados > 0" class="mb-4 bg-brand-50 border border-brand-200 rounded-xl p-4">
      <div class="flex items-start">
        <svg class="w-5 h-5 text-brand-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <div>
          <p class="font-medium text-amber-800">Algunos productos no fueron encontrados</p>
          <p class="text-sm text-brand-700 mt-1">
            Los productos marcados en rojo no existen en el sistema. Deberás seleccionarlos manualmente en el formulario de compra.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  cfdiData: { type: Object, required: true },
  formatMoney: { type: Function, required: true },
  openProductModal: { type: Function, required: true },
  openSerialModal: { type: Function, required: true },
});
</script>
