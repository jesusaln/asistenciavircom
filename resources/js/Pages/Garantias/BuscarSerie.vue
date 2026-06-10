<template>
  <Head title="Garantías - Buscar Serie" />
  
  <div class="w-full p-6">
    <!-- Header Section -->
    <div class="mb-8 text-center">
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Gestión de Garantías</h1>
      <p class="text-slate-500 dark:text-slate-400">Busca una serie o selecciona una venta reciente para iniciar el proceso.</p>
    </div>

    <!-- Main Search Bar -->
    <div class="w-full mb-10">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
          </svg>
        </div>
          type="text"
          v-model="searchQuery"
          @keydown.enter="realizarBusqueda"
          class="block w-full pl-10 pr-4 py-3 border border-slate-300 dark:border-slate-700 rounded-xl leading-5 bg-white dark:bg-slate-800 dark:text-white placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:placeholder-slate-400 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 sm:text-lg shadow-sm transition duration-150 ease-in-out"
          placeholder="Escanear o escribir número de serie, cliente, producto..."
          autofocus
        />
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
          <button 
            @click="realizarBusqueda"
            class="bg-blue-600 text-white px-4 py-1.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors"
          >
            Buscar
          </button>
        </div>
      </div>
    </div>

    <!-- Resultado Exacto (Card Destacado) -->
    <div v-if="resultado" class="w-full mb-10 transform transition-all duration-200 ease-in-out">
      <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-blue-100 dark:border-slate-700 overflow-hidden">
        <div class="bg-sky-50 dark:bg-sky-900/20 dark:bg-slate-700 px-6 py-4 border-b border-blue-100 dark:border-slate-700 flex justify-between items-center">
          <h2 class="text-lg font-bold text-blue-900 dark:text-blue-100 flex items-center gap-2">
            <span class="text-2xl">🎯</span> Resultado Exacto
          </h2>
          <span 
            class="px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wide"
            :class="resultado.estado_serie === 'vendido' ? 'bg-rose-100 text-rose-800 dark:text-rose-200' : 'bg-emerald-100 text-emerald-800 dark:text-emerald-200'"
          >
            {{ resultado.estado_serie }}
          </span>
        </div>
        
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-6">
            <div>
              <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Número de Serie</label>
              <div class="text-xl font-mono font-bold text-slate-900 dark:text-white mt-1">{{ resultado.numero_serie }}</div>
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</label>
              <div class="text-base font-medium text-slate-900 dark:text-white mt-1">{{ resultado.producto_nombre }}</div>
            </div>
          </div>
          
          <div class="space-y-6">
            <div v-if="resultado.cliente_id">
              <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cliente</label>
              <div class="text-base font-medium text-slate-900 dark:text-white mt-1">{{ resultado.cliente_nombre }}</div>
              <div class="text-sm text-slate-500">{{ resultado.cliente_email }}</div>
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Venta</label>
              <div class="text-base font-medium text-slate-900 dark:text-white mt-1">
                {{ resultado.numero_venta ? '#' + resultado.numero_venta : 'No asociado' }}
                <span class="text-slate-400 text-sm font-normal" v-if="resultado.venta_fecha">
                  ({{ new Date(resultado.venta_fecha).toLocaleDateString() }})
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-slate-700/50 px-6 py-4 flex justify-end gap-3">
          <button
            v-if="!resultado.cita_id && resultado.cliente_id"
            @click="crearCita(resultado.producto_serie_id)"
            class="inline-flex items-center px-6 py-2.5 bg-emerald-600 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-wide hover:bg-emerald-700 active:bg-emerald-900 focus:outline-none focus:border-emerald-900 focus:ring focus:ring-brand-500 disabled:opacity-25 transition"
          >
            ✅ Crear Cita de Garantía
          </button>
          <Link
            v-else-if="resultado.cita_id"
            :href="route('citas.edit', resultado.cita_id)"
            class="inline-flex items-center px-6 py-2.5 bg-brand-500 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-wide hover:bg-brand-600 transition"
          >
            📅 Ver Cita Existente
          </Link>
          <span v-else class="text-sm text-brand-600 flex items-center">
            ⚠️ Serie sin venta asociada
          </span>
        </div>
      </div>
    </div>

    <!-- Lista de Series Vendidas -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 bg-[var(--ui-surface)] flex justify-between items-center">
        <h3 class="text-lg font-medium text-slate-900 dark:text-white">Historial de Series Vendidas</h3>
        <span class="text-sm text-slate-500" v-if="seriesVendidas.total > 0">
          {{ seriesVendidas.total }} registros encontrados
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead class="bg-slate-50 dark:bg-slate-800/50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Serie</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Producto</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Cliente</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Fecha</th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Garantía</th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-200 uppercase tracking-wider">Acción</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
            <tr v-for="item in seriesVendidas.data" :key="item.producto_serie_id" class="hover:bg-white dark:hover:bg-slate-700 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-bold text-slate-900 dark:text-white font-mono">{{ item.numero_serie }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-slate-900 dark:text-white font-medium">{{ item.producto_nombre }}</div>
                <div class="text-xs text-slate-500">{{ item.producto_codigo }}</div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-slate-900 dark:text-white">{{ item.cliente_nombre }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                {{ new Date(item.venta_fecha).toLocaleDateString() }}
              </td>
              <!-- Columna de estado de garantía -->
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="item.cita_id" class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-brand-100 text-amber-800">
                  📅 Cita #{{ item.cita_id }}
                </span>
                <span 
                  v-else-if="item.garantia_vigente" 
                  class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-800 dark:text-emerald-200"
                  :title="`Vence: ${item.fecha_vencimiento_garantia}`"
                >
                  ✓ {{ item.dias_restantes_garantia }}d
                </span>
                <span 
                  v-else 
                  class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium rounded-full bg-rose-100 text-rose-800 dark:text-rose-200"
                  :title="`Venció: ${item.fecha_vencimiento_garantia}`"
                >
                  ⚠️ Vencida
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  v-if="!item.cita_id && item.garantia_vigente"
                  @click="crearCita(item.producto_serie_id)"
                  class="text-emerald-600 hover:text-emerald-900 dark:text-slate-400 dark:hover:text-emerald-300 font-bold flex items-center justify-end gap-1 ml-auto"
                >
                  <span>Crear</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </button>
                <span
                  v-else-if="!item.cita_id && !item.garantia_vigente"
                  class="text-slate-400 flex items-center justify-end gap-1 ml-auto"
                  title="Garantía vencida"
                >
                  Sin garantía
                </span>
                <Link
                  v-else
                  :href="route('citas.edit', item.cita_id)"
                  class="text-brand-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300 flex items-center justify-end gap-1 ml-auto"
                >
                  <span>Ver Cita</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </Link>
              </td>
            </tr>
            <tr v-if="seriesVendidas.data.length === 0">
              <td colspan="6" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400">
                No se encontraron series que coincidan con la búsqueda.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Paginación -->
      <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between bg-[var(--ui-surface)]" v-if="seriesVendidas.prev_page_url || seriesVendidas.next_page_url">
         <Link 
           v-if="seriesVendidas.prev_page_url" 
           :href="seriesVendidas.prev_page_url"
           class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl shadow-sm text-sm font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-700 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors"
         >
          ← Anterior
         </Link>
         <div v-else></div>

         <Link 
           v-if="seriesVendidas.next_page_url" 
           :href="seriesVendidas.next_page_url"
           class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-xl shadow-sm text-sm font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-700 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-colors"
         >
          Siguiente →
         </Link>
      </div>
     </div>
   </div>

   <!-- Modal de elección -->
   <EleccionGarantiaModal 
       :show="mostrarModalEleccion"
       :serie="datosGarantiaParaModal?.serie"
       :cliente="datosGarantiaParaModal?.cliente"
       @close="mostrarModalEleccion = false"
       @select="onOpcionGarantiaSeleccionada"
   />
 </template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Swal from '@/Utils/Swal'
import EleccionGarantiaModal from '@/Components/Garantias/EleccionGarantiaModal.vue'

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

// Estado para el modal de elección
const mostrarModalEleccion = ref(false);
const datosGarantiaParaModal = ref(null);
const responseDataGarantia = ref(null);

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
      responseDataGarantia.value = data.data;
      datosGarantiaParaModal.value = {
          serie: data.data.numero_serie,
          cliente: data.data.cliente_nombre
      };
      mostrarModalEleccion.value = true;
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

const onOpcionGarantiaSeleccionada = (opcion) => {
    mostrarModalEleccion.value = false;
    const d = responseDataGarantia.value;
    
    if (opcion === 'cita') {
        const params = new URLSearchParams({
            cliente_id: d.cliente_id,
            numero_serie: d.numero_serie,
            descripcion: `Garantía - Serie: ${d.numero_serie} - Producto: ${d.producto_nombre}`,
            direccion: d.direccion,
            tipo_servicio: 'garantia',
            producto_serie_id: d.producto_serie_id
        });
        window.location.href = route('citas.create') + '?' + params.toString();
    } else {
        const params = new URLSearchParams({
            cliente_id: d.cliente_id,
            cliente_nombre: d.cliente_nombre,
            cliente_telefono: d.cliente_telefono,
            equipo_serie: d.numero_serie,
            equipo_modelo: d.producto_nombre,
            equipo_marca: d.marca_nombre || d.producto_nombre,
            problema_reportado: `Garantía - Serie: ${d.numero_serie} - Producto: ${d.producto_nombre}`
        });
        window.location.href = route('taller.create') + '?' + params.toString();
    }
};
</script>
