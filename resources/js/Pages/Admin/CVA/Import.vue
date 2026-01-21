<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import axios from 'axios'

defineOptions({ layout: AppLayout })

const props = defineProps({
  config: Object
})

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

// Estado de búsqueda
const filters = ref({
  desc: '',
  marca: '',
  grupo: '',
  page: 1
})

const loading = ref(false)
const productos = ref([])
const importing = ref(null) // ID del que se está importando

const search = async () => {
  if (loading.value) return
  loading.value = true
  try {
    const response = await axios.get(route('cva.search'), { params: filters.value })
    if (response.data && response.data.lista) {
      productos.value = response.data.lista
      if (productos.value.length === 0) {
        notyf.warning('No se encontraron productos con esos filtros')
      }
    } else {
        productos.value = []
        notyf.error('Error en el formato de respuesta de CVA')
    }
  } catch (error) {
    console.error('Search error:', error)
    notyf.error('Error al consultar el catálogo de CVA')
  } finally {
    loading.value = false
  }
}

const importProduct = async (item) => {
  if (importing.value) return
  importing.value = item.clave
  try {
    const response = await axios.post(route('cva.import-product'), { clave: item.clave })
    notyf.success('Producto importado correctamente: ' + item.descripcion)
    // Podríamos marcarlo como ya importado en la lista
    item.already_imported = true
  } catch (error) {
    const message = error.response?.data?.error || 'Error al importar producto'
    notyf.error(message)
  } finally {
    importing.value = null
  }
}

const formatNumber = (num) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num)

</script>

<template>
  <Head title="Importar de CVA" />

  <div class="py-12">
    <div class="w-full sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg border border-gray-100 dark:border-gray-700">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-2xl bg-blue-600 dark:bg-blue-800 flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
              </div>
              <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Importador de Productos CVA</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">Busca e importa productos directamente al catálogo local</p>
              </div>
            </div>
            <Link :href="route('productos.index')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-white dark:hover:bg-gray-600 transition-colors">
              Volver a Productos
            </Link>
          </div>
        </div>

        <!-- Filtros -->
        <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Descripción / Palabra clave</label>
              <input v-model="filters.desc" @keyup.enter="search" type="text" placeholder="Ej: Laptop, Disco Duro..." class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Marca</label>
              <input v-model="filters.marca" @keyup.enter="search" type="text" placeholder="Ej: HP, Dell, Kingston" class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200">
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Grupo / Categoría</label>
              <input v-model="filters.grupo" @keyup.enter="search" type="text" placeholder="Ej: Monitores, Almacenamiento" class="w-full border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200">
            </div>
            <div class="flex items-end">
              <button @click="search" :disabled="loading" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                <svg v-if="loading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="!loading">Buscar en CVA</span>
                <span v-else>Buscando...</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Tabla de Resultados -->
        <div class="overflow-x-auto">
          <table class="w-full text-left">
            <thead class="bg-white dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
              <tr>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Clave / SKU</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Imágen</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Producto</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Marca / Grupo</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Precio (CVA)</th>
                <th class="px-6 py-4 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
              <tr v-for="item in productos" :key="item.clave" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/20 transition-colors">
                <td class="px-6 py-4">
                  <span class="text-sm font-mono font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ item.clave }}</span>
                  <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">FAB: {{ item.codigo_fabricante }}</div>
                </td>
                <td class="px-6 py-4">
                  <img :src="item.imagen" alt="" class="w-12 h-12 object-contain rounded border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800" @error="$event.target.src = '/images/placeholder.png'">
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900 dark:text-gray-100 max-w-xs truncate" :title="item.descripcion">{{ item.descripcion }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-xs text-gray-600 dark:text-gray-300">{{ item.marca }}</div>
                  <div class="text-[10px] text-gray-400 dark:text-gray-500">{{ item.grupo }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-bold text-blue-600 dark:text-blue-400">${{ formatNumber(item.precio) }}</div>
                  <div class="text-[10px] text-gray-400 dark:text-gray-500">Exist: {{ item.disponible + item.disponibleCD }}</div>
                </td>
                <td class="px-6 py-4">
                  <button
                    @click="importProduct(item)"
                    :disabled="importing === item.clave || item.already_imported"
                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold transition-all shadow-sm"
                    :class="[
                      item.already_imported 
                        ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 cursor-default' 
                        : 'bg-blue-600 text-white hover:bg-blue-700 active:scale-95 disabled:opacity-50'
                    ]"
                  >
                   <svg v-if="importing === item.clave" class="animate-spin h-3 w-3 mr-1 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else-if="item.already_imported" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg v-else class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ item.already_imported ? 'Importado' : 'Importar' }}
                  </button>
                </td>
              </tr>
              <tr v-if="productos.length === 0 && !loading">
                <td colspan="6" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center">
                    <div class="w-16 h-16 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                      <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                      </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">Realiza una búsqueda para ver productos</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
