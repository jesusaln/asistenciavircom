<template>
  <Head title="Garantías - Buscar Serie" />
  
  <div class="w-full p-6">
    <!-- Header Section -->
    <div class="mb-8 text-center">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Gestión de Garantías</h1>
      <p class="text-gray-600 dark:text-gray-400">Busca una serie o selecciona una venta reciente para iniciar el proceso.</p>
    </div>

    <!-- Main Search Bar -->
    <div class="w-full mb-10">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
          </svg>
        </div>
          type="text"
          v-model="searchQuery"
          @keydown.enter="realizarBusqueda"
          class="block w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl leading-5 bg-white dark:bg-gray-800 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-lg shadow-sm transition duration-150 ease-in-out"
          placeholder="Escanear o escribir número de serie, cliente, producto..."
          autofocus
        />
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
          <button 
            @click="realizarBusqueda"
            class="bg-blue-600 text-white px-4 py-1.5 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
          >
            Buscar
          </button>
        </div>
      </div>
    </div>

    <!-- Resultado Exacto (Card Destacado) -->
    <div v-if="resultado" class="w-full mb-10 transform transition-all duration-300 ease-in-out">
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-blue-100 dark:border-gray-700 overflow-hidden">
        <div class="bg-blue-50 dark:bg-gray-700 px-6 py-4 border-b border-blue-100 dark:border-gray-600 flex justify-between items-center">
          <h2 class="text-lg font-bold text-blue-900 dark:text-blue-100 flex items-center gap-2">
            <span class="text-2xl">🎯</span> Resultado Exacto
          </h2>
          <span 
            class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"
            :class="resultado.estado_serie === 'vendido' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
          >
            {{ resultado.estado_serie }}
          </span>
        </div>
        
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Número de Serie</label>
              <div class="text-xl font-mono font-bold text-gray-900 dark:text-white mt-1">{{ resultado.numero_serie }}</div>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Producto</label>
              <div class="text-base font-medium text-gray-900 dark:text-white mt-1">{{ resultado.producto_nombre }}</div>
            </div>
          </div>
          
          <div class="space-y-4">
            <div v-if="resultado.cliente_id">
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Cliente</label>
              <div class="text-base font-medium text-gray-900 dark:text-white mt-1">{{ resultado.cliente_nombre }}</div>
              <div class="text-sm text-gray-500">{{ resultado.cliente_email }}</div>
            </div>
            <div>
              <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Venta</label>
              <div class="text-base font-medium text-gray-900 dark:text-white mt-1">
                {{ resultado.numero_venta ? '#' + resultado.numero_venta : 'No asociado' }}
                <span class="text-gray-400 text-sm font-normal" v-if="resultado.venta_fecha">
                  ({{ new Date(resultado.venta_fecha).toLocaleDateString() }})
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-700/50 px-6 py-4 flex justify-end gap-3">
          <button
            v-if="!resultado.cita_id && resultado.cliente_id"
            @click="crearCita(resultado.producto_serie_id)"
            class="inline-flex items-center px-6 py-2.5 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-25 transition"
          >
            ✅ Crear Cita de Garantía
          </button>
          <Link
            v-else-if="resultado.cita_id"
            :href="route('citas.edit', resultado.cita_id)"
            class="inline-flex items-center px-6 py-2.5 bg-orange-500 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600 transition"
          >
            📅 Ver Cita Existente
          </Link>
          <span v-else class="text-sm text-amber-600 flex items-center">
            ⚠️ Serie sin venta asociada
          </span>
        </div>
      </div>
    </div>

    <!-- Lista de Series Vendidas -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Historial de Series Vendidas</h3>
        <span class="text-sm text-gray-500" v-if="seriesVendidas.total > 0">
          {{ seriesVendidas.total }} registros encontrados
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead class="bg-white dark:bg-gray-700">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Serie</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Producto</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Garantía</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="item in seriesVendidas.data" :key="item.producto_serie_id" class="hover:bg-white dark:hover:bg-gray-700 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-gray-900 dark:text-white font-mono">{{ item.numero_serie }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900 dark:text-white font-medium">{{ item.producto_nombre }}</div>
                <div class="text-xs text-gray-500">{{ item.producto_codigo }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900 dark:text-white">{{ item.cliente_nombre }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ new Date(item.venta_fecha).toLocaleDateString() }}
              </td>
              <!-- Columna de estado de garantía -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="item.cita_id" class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                  📅 Cita #{{ item.cita_id }}
                </span>
                <span 
                  v-else-if="item.garantia_vigente" 
                  class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800"
                  :title="`Vence: ${item.fecha_vencimiento_garantia}`"
                >
                  ✓ {{ item.dias_restantes_garantia }}d
                </span>
                <span 
                  v-else 
                  class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800"
                  :title="`Venció: ${item.fecha_vencimiento_garantia}`"
                >
                  ⚠️ Vencida
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  v-if="!item.cita_id && item.garantia_vigente"
                  @click="crearCita(item.producto_serie_id)"
                  class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 font-bold flex items-center justify-end gap-1 ml-auto"
                >
                  <span>Crear</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </button>
                <span
                  v-else-if="!item.cita_id && !item.garantia_vigente"
                  class="text-gray-400 flex items-center justify-end gap-1 ml-auto"
                  title="Garantía vencida"
                >
                  Sin garantía
                </span>
                <Link
                  v-else
                  :href="route('citas.edit', item.cita_id)"
                  class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300 flex items-center justify-end gap-1 ml-auto"
                >
                  <span>Ver Cita</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </Link>
              </td>
            </tr>
            <tr v-if="seriesVendidas.data.length === 0">
              <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                No se encontraron series que coincidan con la búsqueda.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Paginación -->
      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between bg-white dark:bg-gray-900" v-if="seriesVendidas.prev_page_url || seriesVendidas.next_page_url">
         <Link 
           v-if="seriesVendidas.prev_page_url" 
           :href="seriesVendidas.prev_page_url"
           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
         >
          ← Anterior
         </Link>
         <div v-else></div>

         <Link 
           v-if="seriesVendidas.next_page_url" 
           :href="seriesVendidas.next_page_url"
           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
         >
          Siguiente →
         </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Swal from 'sweetalert2'

defineOptions({
  layout: AppLayout,
})

const props = defineProps({
  serie: { type: String, default: '' },
  resultado: { type: Object, default: null },
  seriesVendidas: { type: Object, default: () => ({ data: [], total: 0 }) },
  filters: { type: Object, default: () => ({}) },
})

const searchQuery = ref(props.filters.search || props.serie || '')

// Actualizar el input si cambia la prop (ej. al navegar)
watch(() => props.filters.search, (newVal) => {
  searchQuery.value = newVal || ''
})

const realizarBusqueda = () => {
  router.get(route('garantias.create'), { search: searchQuery.value }, { 
    preserveState: true, 
    replace: true,
    preserveScroll: true 
  })
}

const crearCita = async (serieId) => {
  try {
    const response = await fetch(route('garantias.crear-cita', serieId), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    const data = await response.json()

    if (response.ok && data.success) {
      window.location.href = data.url
    } else {
      const mensaje = data.mensaje || data.error || 'No se pudo crear la cita'
      Swal.fire({
        icon: 'error',
        title: 'Error al crear cita',
        text: mensaje
      });
      if (data.cita_id) window.location.reload()
    }
  } catch (error) {
    console.error('Error:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error al procesar solicitud',
      text: 'Error al procesar la solicitud'
    });
  }
}
</script>


