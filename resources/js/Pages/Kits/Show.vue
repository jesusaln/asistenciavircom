<template>
  <AppLayout :title="`Kit: ${kit.nombre}`">
    <div class="min-h-screen bg-slate-900 text-slate-100 font-sans selection:bg-amber-500 selection:text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- Header -->
        <div class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div>
            <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-orange-500 tracking-tight">
              {{ kit.nombre }}
            </h1>
            <p class="mt-2 text-slate-400 text-lg">Detalles del kit y desglose de componentes</p>
          </div>
          <div class="flex space-x-3">
            <Link :href="`/kits/${kit.id}/edit`" 
              class="inline-flex items-center px-5 py-2.5 border border-transparent rounded-xl shadow-lg shadow-amber-900/20 text-sm font-bold text-white bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 focus:ring-offset-slate-900 transition-all duration-300 transform hover:scale-105 active:scale-95">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Editar Kit
            </Link>
            <Link href="/kits" 
              class="inline-flex items-center px-5 py-2.5 border border-slate-600 rounded-xl shadow-sm text-sm font-medium text-slate-300 bg-slate-800 hover:bg-slate-700 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 focus:ring-offset-slate-900 transition-all duration-300">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Volver a Kits
            </Link>
          </div>
        </div>

        <!-- Kit Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
          <!-- Estado -->
          <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6 flex items-center">
            <div class="flex-shrink-0">
              <div :class="kit.estado === 'activo' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'" class="p-3 rounded-xl border border-opacity-20" :style="kit.estado === 'activo' ? 'border-color: rgba(16, 185, 129, 0.2)' : 'border-color: rgba(239, 68, 68, 0.2)'">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
            </div>
            <div class="ml-5">
              <p class="text-sm font-medium text-slate-400 uppercase tracking-widest">Estado</p>
              <p class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r" 
                 :class="kit.estado === 'activo' ? 'from-emerald-400 to-teal-400' : 'from-red-400 to-pink-500'">
                {{ kit.estado === 'activo' ? 'Activo' : 'Inactivo' }}
              </p>
            </div>
          </div>

          <!-- Precio de Venta -->
          <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6 flex items-center">
            <div class="flex-shrink-0">
              <div class="bg-blue-500/10 p-3 rounded-xl border border-blue-500/20">
                <svg class="h-8 w-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
            </div>
            <div class="ml-5">
              <p class="text-sm font-medium text-slate-400 uppercase tracking-widest">Precio de Venta</p>
              <p class="text-2xl font-bold text-white">{{ formatCurrency(kit.precio_venta) }}</p>
            </div>
          </div>

          <!-- Componentes -->
          <div class="bg-slate-800 rounded-2xl shadow-xl border border-slate-700 p-6 flex items-center">
            <div class="flex-shrink-0">
              <div class="bg-purple-500/10 p-3 rounded-xl border border-purple-500/20">
                <svg class="h-8 w-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
              </div>
            </div>
            <div class="ml-5">
              <p class="text-sm font-medium text-slate-400 uppercase tracking-widest">Componentes</p>
              <p class="text-2xl font-bold text-white">{{ kit.kit_items?.length || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- Alerta si no hay componentes -->
        <div v-if="!kit.kit_items || kit.kit_items.length === 0" 
             class="bg-amber-900/20 border border-amber-500/30 rounded-xl p-4 mb-8 flex items-start gap-4">
          <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-medium text-amber-400">Kit vacío</h3>
            <p class="mt-1 text-amber-200/80">Este kit no tiene componentes definidos. Agrega componentes para poder utilizarlo.</p>
          </div>
        </div>

        <!-- Detalles del Kit -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
           <!-- Info General -->
           <div class="lg:col-span-1 bg-slate-800 rounded-2xl shadow-xl border border-slate-700 overflow-hidden h-fit">
              <div class="px-6 py-4 border-b border-slate-700 bg-slate-800/50">
                <h3 class="text-lg font-bold text-white">Información General</h3>
              </div>
              <div class="px-6 py-6 space-y-6">
                 <div>
                    <dt class="text-xs font-bold text-slate-500 uppercase tracking-widest">Código</dt>
                    <dd class="mt-1 text-base text-white font-mono bg-slate-900/50 px-3 py-2 rounded-lg border border-slate-700/50 inline-block">{{ kit.codigo || 'N/A' }}</dd>
                 </div>
                 <div>
                    <dt class="text-xs font-bold text-slate-500 uppercase tracking-widest">Categoría</dt>
                    <dd class="mt-1 text-base text-white">{{ kit.categoria?.nombre || 'Sin categoría' }}</dd>
                 </div>
                 <div>
                    <dt class="text-xs font-bold text-slate-500 uppercase tracking-widest">Descripción</dt>
                    <dd class="mt-1 text-sm text-slate-300 leading-relaxed">{{ kit.descripcion || 'Sin descripción disponible.' }}</dd>
                 </div>
                 <div class="pt-4 border-t border-slate-700 grid grid-cols-2 gap-4">
                    <div>
                       <dt class="text-xs font-bold text-slate-500 uppercase tracking-widest">Creado</dt>
                       <dd class="mt-1 text-xs text-slate-400">{{ formatDate(kit.created_at) }}</dd>
                    </div>
                    <div>
                       <dt class="text-xs font-bold text-slate-500 uppercase tracking-widest">Actualizado</dt>
                       <dd class="mt-1 text-xs text-slate-400">{{ formatDate(kit.updated_at) }}</dd>
                    </div>
                 </div>
              </div>
           </div>

           <!-- Lista de Componentes -->
           <div class="lg:col-span-2 bg-slate-800 rounded-2xl shadow-xl border border-slate-700 overflow-hidden">
              <div class="px-6 py-4 border-b border-slate-700 bg-slate-800/50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Componentes del Kit</h3>
                <span class="px-3 py-1 bg-slate-700 text-slate-300 rounded-full text-xs font-medium">{{ kit.kit_items?.length || 0 }} items</span>
              </div>
              
              <div class="px-6 py-6" v-if="kit.kit_items && kit.kit_items.length > 0">
                 <div class="space-y-4">
                    <div v-for="item in kit.kit_items" :key="item.id"
                        class="bg-slate-700/20 border border-slate-700/50 rounded-xl p-4 hover:border-slate-600 transition-all duration-200">
                       <div class="flex flex-col sm:flex-row justify-between gap-4">
                          <div class="flex items-start gap-4">
                             <div class="flex-shrink-0 mt-1">
                                <div :class="isProducto(item) ? 'bg-slate-700 text-slate-400' : 'bg-blue-900/30 text-blue-400'" class="w-10 h-10 rounded-lg flex items-center justify-center border border-white/5">
                                   <svg v-if="isProducto(item)" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                   </svg>
                                   <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                   </svg>
                                </div>
                             </div>
                             <div>
                                <h4 class="text-sm font-bold text-white">{{ getItemName(item) }}</h4>
                                <div class="flex items-center gap-2 mt-1">
                                   <span class="text-xs font-mono text-slate-400 bg-slate-900/50 px-2 py-0.5 rounded">{{ getItemCode(item) }}</span>
                                   <span :class="isProducto(item) ? 'text-slate-400' : 'text-blue-400'" class="text-xs font-medium">
                                      {{ isProducto(item) ? 'Producto' : 'Servicio' }}
                                   </span>
                                </div>
                             </div>
                          </div>

                          <div class="flex items-center justify-between sm:justify-end gap-6 sm:text-right border-t sm:border-0 border-slate-700/50 pt-3 sm:pt-0">
                             <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wider mb-0.5">Cant.</p>
                                <p class="text-lg font-bold text-white">{{ item.cantidad }}</p>
                             </div>
                             <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wider mb-0.5">P. Unit.</p>
                                <p class="text-sm text-slate-300">{{ formatCurrency(getItemPrice(item)) }}</p>
                             </div>
                             <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wider mb-0.5">Subtotal</p>
                                <p class="text-lg font-bold text-emerald-400">{{ formatCurrency(getItemPrice(item) * item.cantidad) }}</p>
                             </div>
                          </div>
                       </div>
                    </div>
                 </div>

                 <!-- Resumen de Costos y Precios -->
                 <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Caja 1: Suma de Componentes -->
                    <div class="bg-gradient-to-r from-slate-800 to-slate-900 border border-slate-700/50 rounded-2xl p-6 shadow-md">
                      <h4 class="text-slate-300 font-bold mb-1">Total Precios de Componentes</h4>
                      <p class="text-xs text-slate-500 mb-4">Suma de la columna "P. Unitario" de los items</p>
                      <div class="flex justify-between items-end">
                        <div class="text-left">
                          <p class="text-sm text-slate-400">Subtotal (Suma Exacta)</p>
                          <div class="text-2xl font-bold text-white tracking-tight">{{ formatCurrency(costoTotalComponentes) }}</div>
                        </div>
                        <div class="text-right">
                          <p class="text-xs text-amber-500 font-bold uppercase tracking-wider mb-1">Total Sugerido c/IVA</p>
                          <div class="text-xl font-bold text-amber-400">{{ formatCurrency(costoTotalComponentes * 1.16) }}</div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Caja 2: Costo de Inventario API (Solo para calcular Margen Interno) -->
                    <div class="bg-gradient-to-r from-blue-900/40 to-indigo-900/40 border border-blue-500/20 rounded-2xl p-6 shadow-md relative">
                      <div class="absolute top-4 right-4">
                        <svg v-if="loadingCosto" class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                      </div>
                      <h4 class="text-blue-200 font-bold mb-1">Costo Base de Producción</h4>
                      <p class="text-xs text-blue-400/80 mb-4">Costo Histórico / FIFO Extraído de la API</p>
                      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-2">
                        <div class="text-left">
                          <p class="text-sm text-blue-300">Inversión (Costo Base)</p>
                          <div class="text-2xl font-bold text-white tracking-tight">{{ formatCurrency(costoFinal) }}</div>
                        </div>
                        <div class="text-left sm:text-right">
                          <p class="text-xs text-blue-400 font-bold uppercase tracking-wider mb-1">Margen vs Precio de Venta Kit</p>
                          <div class="flex items-center gap-2">
                             <div :class="{
                               'text-emerald-400': margen >= 20,
                               'text-amber-400': margen >= 10 && margen < 20,
                               'text-red-400': margen < 10
                             }" class="font-bold text-xl">{{ margen }}%</div>
                             <span v-if="margen >= 20" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Óptimo</span>
                             <span v-else-if="margen >= 10" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">Aceptable</span>
                             <span v-else class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20">Bajo</span>
                          </div>
                        </div>
                      </div>
                    </div>
                 </div>
              </div>

              <div v-else class="text-center py-16">
                 <div class="bg-slate-700/30 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="h-8 w-8 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                 </div>
                 <h3 class="text-lg font-medium text-white">No hay componentes</h3>
                 <p class="mt-2 text-slate-400">Este kit no tiene productos asignados actualmente.</p>
                 <Link :href="`/kits/${kit.id}/edit`" class="mt-6 inline-flex items-center px-4 py-2 border border-blue-500 rounded-lg text-sm font-medium text-blue-400 hover:bg-blue-500/10 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Modificar Kit
                 </Link>
              </div>
           </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'

const props = defineProps({
  kit: Object,
  costoEstimado: {
    type: Number,
    default: 0
  }
})

// Reactive state
const costoActual = ref(null)
const loadingCosto = ref(false)

// Computed
const costoTotalComponentes = computed(() => {
  if (!props.kit.kit_items) return 0

  return props.kit.kit_items.reduce((total, item) => {
    const precio = getItemPrice(item)
    return total + (precio * item.cantidad)
  }, 0)
})

const costoFinal = computed(() => {
  return costoActual.value !== null ? costoActual.value : (props.costoEstimado || costoTotalComponentes.value)
})

const isProducto = (item) => {
  return item.item_type === 'App\\Models\\Producto' || item.item_type === 'producto' || item.producto
}

const getItemName = (item) => {
  if (item.item) return item.item.nombre || 'Sin nombre'
  if (item.producto) return item.producto.nombre || 'Producto no encontrado'
  return 'Item no encontrado'
}

const getItemCode = (item) => {
  if (item.item) return item.item.codigo || 'N/A'
  if (item.producto) return item.producto.codigo || 'N/A'
  return 'N/A'
}

const getItemPrice = (item) => {
  if (item.precio_unitario) return item.precio_unitario
  
  if (item.item) {
    return isProducto(item) ? (item.item.precio_venta || 0) : (item.item.precio || 0)
  }
  
  if (item.producto) {
    return item.producto.precio_venta || 0
  }
  
  return 0
}

const margen = computed(() => {
  const precioVenta = props.kit.precio_venta || 0
  const costo = costoFinal.value

  if (costo > 0 && precioVenta > 0) {
    const precioVentaSinIVA = precioVenta / 1.16
    return ((precioVentaSinIVA - costo) / costo * 100).toFixed(1)
  }
  return 0
})

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(value || 0)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-MX', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Calcular costo actual en tiempo real
const calcularCostoActual = async () => {
  if (!props.kit.kit_items || props.kit.kit_items.length === 0) {
    return
  }

  loadingCosto.value = true

  try {
    const componentes = props.kit.kit_items
      .filter(item => item.item_type && item.item_id && item.cantidad > 0)
      .map(item => ({
        item_type: item.item_type === 'App\\Models\\Producto' ? 'producto' : 
                   item.item_type === 'App\\Models\\Servicio' ? 'servicio' : 
                   item.item_type,
        item_id: Number(item.item_id),
        cantidad: Number(item.cantidad),
        precio_unitario: item.precio_unitario
      }))

    if (componentes.length === 0) {
      return
    }

    const response = await axios.post('/kits/api/calcular-costo', {
      componentes: componentes,
      almacen_id: 1 // Almacén principal por defecto
    });

    const data = response.data;

    if (data.success) {
      costoActual.value = data.costo_total
    } else {
      console.error('Error calculando costo:', data.error)
    }
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loadingCosto.value = false
  }
}

onMounted(() => {
  calcularCostoActual()
})
</script>

<style scoped>
/* Transiciones suaves */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>
