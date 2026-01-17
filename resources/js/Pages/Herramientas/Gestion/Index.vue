<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  tecnicos: { type: [Array, Object], required: true },
})

const rows = computed(() => Array.isArray(props.tecnicos) ? props.tecnicos : [props.tecnicos])

const getEstadoColor = (estado) => {
  const colors = {
    'disponible': 'bg-green-100 text-green-800',
    'asignada': 'bg-blue-100 text-blue-800',
    'mantenimiento': 'bg-yellow-100 text-yellow-800',
    'baja': 'bg-red-100 text-red-800',
    'perdida': 'bg-red-100 text-red-800',
  }
  return colors[estado] || 'bg-gray-100 text-gray-800'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'disponible': 'Disponible',
    'asignada': 'Asignada',
    'mantenimiento': 'En Mant.',
    'baja': 'De Baja',
    'perdida': 'Perdida',
  }
  return labels[estado] || estado
}

const calcularEstadisticasTecnico = (tecnico) => {
  if (!tecnico.herramientas) return { total: 0, disponibles: 0, mantenimiento: 0, asignadas: 0 }

  const herramientas = tecnico.herramientas || []
  return {
    total: herramientas.length,
    disponibles: herramientas.filter(h => h.estado === 'disponible').length,
    mantenimiento: herramientas.filter(h => h.estado === 'mantenimiento').length,
    asignadas: herramientas.filter(h => h.estado === 'asignada').length,
  }
}
</script>

<template>
  <Head title="Gestión de Herramientas por Técnico" />

  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-3xl font-bold text-slate-900">Gestión de Herramientas</h1>
      <p class="text-gray-600 mt-1">Administrar herramientas asignadas por técnico</p>
    </div>
    <div class="flex gap-3">
      <Link class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" href="/herramientas/gestion/create">
        Nueva Asignación
      </Link>
      <Link class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" href="/herramientas-dashboard">
        Dashboard General
      </Link>
    </div>
  </div>

  <!-- Estadísticas generales -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-blue-600">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).total, 0) }}</div>
      <div class="text-sm text-gray-600">Total Herramientas</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-green-600">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).disponibles, 0) }}</div>
      <div class="text-sm text-gray-600">Disponibles</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-blue-600">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).asignadas, 0) }}</div>
      <div class="text-sm text-gray-600">Asignadas</div>
    </div>
    <div class="bg-white p-4 rounded-lg shadow-sm border">
      <div class="text-2xl font-bold text-orange-600">{{ rows.reduce((acc, t) => acc + calcularEstadisticasTecnico(t).mantenimiento, 0) }}</div>
      <div class="text-sm text-gray-600">En Mantenimiento</div>
    </div>
  </div>

  <!-- Lista de técnicos con herramientas -->
  <div class="space-y-4">
    <div v-for="tecnico in rows" :key="tecnico.id" class="bg-white rounded-lg shadow-sm border">
      <div class="p-6 border-b">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ tecnico.nombre_completo || tecnico.nombre }}</h2>
            <p class="text-gray-600">{{ tecnico.email || 'Sin email' }} • {{ tecnico.telefono || 'Sin teléfono' }}</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="text-right">
              <div class="text-sm text-gray-500">Herramientas asignadas</div>
              <div class="text-2xl font-bold text-blue-600">{{ calcularEstadisticasTecnico(tecnico).total }}</div>
            </div>
            <div class="flex gap-2">
              <Link
                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                :href="`/herramientas/gestion/${tecnico.id}/edit`"
              >
                Gestionar
              </Link>
              <Link
                v-if="calcularEstadisticasTecnico(tecnico).total > 0"
                class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                :href="`/herramientas/gestion/${tecnico.id}/exportar`"
              >
                Reporte
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Estadísticas del técnico -->
      <div class="p-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
          <div class="text-center p-3 bg-green-50 rounded-lg">
            <div class="text-lg font-bold text-green-600">{{ calcularEstadisticasTecnico(tecnico).disponibles }}</div>
            <div class="text-sm text-gray-600">Disponibles</div>
          </div>
          <div class="text-center p-3 bg-blue-50 rounded-lg">
            <div class="text-lg font-bold text-blue-600">{{ calcularEstadisticasTecnico(tecnico).asignadas }}</div>
            <div class="text-sm text-gray-600">Asignadas</div>
          </div>
          <div class="text-center p-3 bg-yellow-50 rounded-lg">
            <div class="text-lg font-bold text-yellow-600">{{ calcularEstadisticasTecnico(tecnico).mantenimiento }}</div>
            <div class="text-sm text-gray-600">En Mant.</div>
          </div>
          <div class="text-center p-3 bg-white rounded-lg">
            <div class="text-lg font-bold text-gray-600">{{ tecnico.email || 'Sin email' }}</div>
            <div class="text-sm text-gray-600">Contacto</div>
          </div>
        </div>

        <!-- Lista de herramientas del técnico (si las tiene) -->
        <div v-if="tecnico.herramientas && tecnico.herramientas.length > 0" class="mt-4">
          <h3 class="font-medium text-gray-900 mb-3">Herramientas Asignadas</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div v-for="herramienta in tecnico.herramientas.slice(0, 6)" :key="herramienta.id" class="p-3 border rounded-lg bg-white">
              <div class="flex items-center gap-3">
                <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded" />
                <div v-else class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <h4 class="font-medium text-sm">{{ herramienta.nombre }}</h4>
                  <p class="text-xs text-gray-600">{{ herramienta.numero_serie || 'N/A' }}</p>
                  <span :class="['text-xs px-2 py-1 rounded-full', getEstadoColor(herramienta.estado)]">
                    {{ getEstadoLabel(herramienta.estado) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div v-if="tecnico.herramientas.length > 6" class="mt-3 text-center">
            <span class="text-sm text-gray-600">Y {{ tecnico.herramientas.length - 6 }} herramientas más...</span>
          </div>
        </div>

        <!-- Sin herramientas asignadas -->
        <div v-else class="text-center py-8 text-gray-500">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
          </svg>
          <p>No tiene herramientas asignadas</p>
          <Link
            :href="`/herramientas/gestion/${tecnico.id}/edit`"
            class="text-blue-600 hover:underline text-sm mt-2 inline-block"
          >
            Asignar herramientas
          </Link>
        </div>
      </div>
    </div>
  </div>

  <!-- Sin técnicos -->
  <div v-if="rows.length === 0" class="bg-white rounded-lg shadow-sm border p-12 text-center">
    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
    </svg>
    <h2 class="text-xl font-semibold text-gray-900 mb-2">No hay técnicos disponibles</h2>
    <p class="text-gray-600">Agrega técnicos al sistema para gestionar sus herramientas.</p>
  </div>

  <!-- Acciones rápidas -->
  <div class="mt-8 bg-white rounded-lg shadow-sm border p-6">
    <h2 class="text-xl font-semibold mb-4">Acciones Rápidas</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <Link href="/herramientas/gestion/create" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
        <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        <span class="text-sm font-medium text-blue-800">Nueva Asignación</span>
      </Link>
      <Link :href="route('herramientas-mantenimiento')" class="flex flex-col items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
        <svg class="w-8 h-8 text-orange-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span class="text-sm font-medium text-orange-800">Gestionar Mantenimiento</span>
      </Link>
      <Link href="/herramientas-alertas" class="flex flex-col items-center p-4 bg-red-50 rounded-lg hover:bg-red-100 transition-colors">
        <svg class="w-8 h-8 text-red-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
        <span class="text-sm font-medium text-red-800">Ver Alertas</span>
      </Link>
      <Link href="/herramientas" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
        <svg class="w-8 h-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
        <span class="text-sm font-medium text-green-800">Catálogo General</span>
      </Link>
    </div>
  </div>
</template>




