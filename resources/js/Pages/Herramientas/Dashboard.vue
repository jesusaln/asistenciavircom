<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  estadisticas: { type: Object, required: true },
  mantenimiento_urgente: { type: Array, default: () => [] },
  vida_util_proxima: { type: Array, default: () => [] },
  por_categoria: { type: Array, default: () => [] },
  mas_utilizadas: { type: Array, default: () => [] },
})

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES')
}

const getEstadoColor = (estado) => {
  const colors = {
    'disponible': 'bg-green-100 text-green-800',
    'asignada': 'bg-blue-100 text-blue-800',
    'mantenimiento': 'bg-yellow-100 text-yellow-800',
    'baja': 'bg-red-100 text-red-800',
    'perdida': 'bg-red-100 text-red-800',
  }
  return colors[estado] || 'bg-gray-100 text-gray-800 dark:text-gray-100'
}
</script>

<template>
  <Head title="Dashboard de Herramientas" />

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-slate-900">Dashboard de Herramientas</h1>
    <Link class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" :href="route('herramientas.index')">
      Ver Todas las Herramientas
    </Link>
  </div>

  <!-- Estadísticas principales -->
  <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8">
    <div class="bg-white dark:bg-slate-900 p-6 rounded-lg shadow-sm border">
      <div class="text-3xl font-bold text-blue-600">{{ estadisticas.total_herramientas || 0 }}</div>
      <div class="text-sm text-gray-600 dark:text-gray-300">Total Herramientas</div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-lg shadow-sm border">
      <div class="text-3xl font-bold text-green-600">{{ estadisticas.herramientas_disponibles || 0 }}</div>
      <div class="text-sm text-gray-600 dark:text-gray-300">Disponibles</div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-lg shadow-sm border">
      <div class="text-3xl font-bold text-blue-600">{{ estadisticas.herramientas_asignadas || 0 }}</div>
      <div class="text-sm text-gray-600 dark:text-gray-300">Asignadas</div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-lg shadow-sm border">
      <div class="text-3xl font-bold text-yellow-600">{{ estadisticas.herramientas_mantenimiento || 0 }}</div>
      <div class="text-sm text-gray-600 dark:text-gray-300">En Mantenimiento</div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-lg shadow-sm border">
      <div class="text-3xl font-bold text-red-600">{{ estadisticas.herramientas_baja || 0 }}</div>
      <div class="text-sm text-gray-600 dark:text-gray-300">De Baja</div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-lg shadow-sm border">
      <div class="text-3xl font-bold text-red-600">{{ estadisticas.herramientas_perdidas || 0 }}</div>
      <div class="text-sm text-gray-600 dark:text-gray-300">Perdidas</div>
    </div>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-lg shadow-sm border">
      <div class="text-3xl font-bold text-orange-600">{{ estadisticas.herramientas_requieren_mantenimiento || 0 }}</div>
      <div class="text-sm text-gray-600 dark:text-gray-300">Requieren Mant.</div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Herramientas que requieren mantenimiento urgente -->
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border">
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold text-red-600">Mantenimiento Urgente</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">Herramientas que requieren mantenimiento inmediato</p>
      </div>
      <div class="p-6">
        <div v-if="mantenimiento_urgente.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          No hay herramientas que requieran mantenimiento urgente
        </div>
        <div v-else class="space-y-4">
          <div v-for="herramienta in mantenimiento_urgente" :key="herramienta.id" class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="font-medium">{{ herramienta.nombre }}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-300">{{ herramienta.numero_serie }}</p>
              <p class="text-sm text-red-600">
                Días desde último mantenimiento: {{ herramienta.dias_desde_ultimo_mantenimiento || 'N/A' }}
              </p>
            </div>
            <Link :href="route('herramientas.show', herramienta.id)" class="text-blue-600 hover:underline text-sm">
              Ver
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Herramientas próximas a vencer vida útil -->
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border">
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold text-orange-600">Próximas a Vencer Vida Útil</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">Herramientas que requieren atención pronto</p>
      </div>
      <div class="p-6">
        <div v-if="vida_util_proxima.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          No hay herramientas próximas a vencer su vida útil
        </div>
        <div v-else class="space-y-4">
          <div v-for="herramienta in vida_util_proxima" :key="herramienta.id" class="flex items-center gap-3 p-3 bg-orange-50 rounded-lg">
            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="font-medium">{{ herramienta.nombre }}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-300">{{ herramienta.numero_serie }}</p>
              <p class="text-sm text-orange-600">
                Vida útil utilizada: {{ herramienta.porcentaje_vida_util || 0 }}%
              </p>
            </div>
            <Link :href="route('herramientas.show', herramienta.id)" class="text-blue-600 hover:underline text-sm">
              Ver
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Estadísticas por categoría -->
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border">
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold">Herramientas por Categoría</h2>
      </div>
      <div class="p-6">
        <div v-if="por_categoria.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          No hay datos de categorías disponibles
        </div>
        <div v-else class="space-y-3">
          <div v-for="categoria in por_categoria" :key="categoria.categoria" class="flex items-center justify-between">
            <span class="font-medium">{{ categoria.categoria }}</span>
            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">{{ categoria.total }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Herramientas más utilizadas -->
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border">
      <div class="p-6 border-b">
        <h2 class="text-xl font-semibold">Herramientas Más Utilizadas</h2>
      </div>
      <div class="p-6">
        <div v-if="mas_utilizadas.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
          No hay datos de uso disponibles
        </div>
        <div v-else class="space-y-3">
          <div v-for="herramienta in mas_utilizadas" :key="herramienta.id" class="flex items-center justify-between">
            <div>
              <h3 class="font-medium">{{ herramienta.nombre }}</h3>
              <p class="text-sm text-gray-600 dark:text-gray-300">{{ herramienta.numero_serie }}</p>
            </div>
            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">{{ herramienta.usos || 0 }} usos</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Acciones rápidas -->
  <div class="mt-8 bg-white dark:bg-slate-900 rounded-lg shadow-sm border p-6">
    <h2 class="text-xl font-semibold mb-4">Acciones Rápidas</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <Link :href="route('herramientas.create')" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
        <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <span class="text-sm font-medium text-blue-800">Nueva Herramienta</span>
      </Link>
      <Link :href="route('herramientas-mantenimiento')" class="flex flex-col items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
        <svg class="w-8 h-8 text-orange-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span class="text-sm font-medium text-orange-800">Gestionar Mantenimiento</span>
      </Link>
      <Link :href="route('herramientas.gestion.index')" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
        <svg class="w-8 h-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <span class="text-sm font-medium text-green-800">Asignar Herramientas</span>
      </Link>
      <Link :href="route('herramientas.index', { mantenimiento: 'requiere' })" class="flex flex-col items-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
        <svg class="w-8 h-8 text-red-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
        <span class="text-sm font-medium text-red-800">Ver Alertas</span>
      </Link>
    </div>
  </div>
</template>

