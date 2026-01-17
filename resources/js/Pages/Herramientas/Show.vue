<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  herramienta: { type: Object, required: true },
  estadisticas: { type: Object, default: () => ({}) },
  historial_completo: { type: Array, default: () => [] },
})

const showMantenimientoForm = ref(false)
const fechaMantenimiento = ref(new Date().toISOString().split('T')[0])
const costoMantenimiento = ref('')
const descripcionMantenimiento = ref('')
const proximoMantenimientoDias = ref('')

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES')
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('es-ES')
}

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
    'mantenimiento': 'En Mantenimiento',
    'baja': 'De Baja',
    'perdida': 'Perdida',
  }
  return labels[estado] || estado
}

const registrarMantenimiento = () => {
  router.post(`/herramientas/${props.herramienta.id}/mantenimiento`, {
    fecha_mantenimiento: fechaMantenimiento.value,
    costo_mantenimiento: costoMantenimiento.value,
    descripcion_mantenimiento: descripcionMantenimiento.value,
    proximo_mantenimiento_dias: proximoMantenimientoDias.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showMantenimientoForm.value = false
      fechaMantenimiento.value = new Date().toISOString().split('T')[0]
      costoMantenimiento.value = ''
      descripcionMantenimiento.value = ''
      proximoMantenimientoDias.value = ''
    }
  })
}

const cambiarEstado = (nuevoEstado) => {
  if (confirm(`¿Estás seguro de cambiar el estado a "${getEstadoLabel(nuevoEstado)}"?`)) {
    router.post(`/herramientas/${props.herramienta.id}/cambiar-estado`, {
      estado: nuevoEstado,
      observaciones: `Cambio desde vista de detalles`,
    }, {
      preserveScroll: true,
    })
  }
}

const estadisticasItems = computed(() => [
  { label: 'Total de asignaciones', value: props.estadisticas.total_asignaciones || 0 },
  { label: 'Asignaciones activas', value: props.estadisticas.asignaciones_activas || 0 },
  { label: 'Promedio días de uso', value: `${props.estadisticas.promedio_dias_uso || 0} días` },
  { label: 'Devoluciones por desgaste', value: props.estadisticas.devoluciones_por_desgaste || 0 },
  { label: 'Devoluciones por daño', value: props.estadisticas.devoluciones_por_danio || 0 },
  { label: 'Devoluciones por pérdida', value: props.estadisticas.devoluciones_por_perdida || 0 },
])
</script>

<template>
  <Head :title="`Herramienta - ${props.herramienta.nombre}`" />

  <div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
      <Link href="/herramientas" class="text-blue-600 hover:underline">
        ← Volver al listado
      </Link>
      <h1 class="text-3xl font-bold text-slate-900">{{ props.herramienta.nombre }}</h1>
    </div>
    <div class="flex gap-3">
      <Link :href="`/herramientas/${props.herramienta.id}/estadisticas`" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
        Estadísticas
      </Link>
      <Link :href="`/herramientas/${props.herramienta.id}/edit`" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
        Editar
      </Link>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Información principal -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Información básica -->
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-xl font-semibold mb-4">Información General</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Número de Serie</label>
              <p class="text-lg font-semibold">{{ props.herramienta.numero_serie || 'N/A' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Estado</label>
              <div class="flex items-center gap-2 mt-1">
                <span :class="['px-3 py-1 rounded-full text-sm font-medium', getEstadoColor(props.herramienta.estado)]">
                  {{ getEstadoLabel(props.herramienta.estado) }}
                </span>
                <select @change="cambiarEstado($event.target.value)" class="text-sm border rounded px-2 py-1">
                  <option value="">Cambiar estado</option>
                  <option value="disponible">Disponible</option>
                  <option value="asignada">Asignada</option>
                  <option value="mantenimiento">En Mantenimiento</option>
                  <option value="baja">De Baja</option>
                  <option value="perdida">Perdida</option>
                </select>
              </div>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Categoría</label>
              <p class="text-lg">{{ props.herramienta.categoria_herramienta?.nombre || 'Sin categoría' }}</p>
            </div>
            <div>
              <label class="text-sm font-medium text-gray-500">Fecha de Creación</label>
              <p class="text-lg">{{ formatDate(props.herramienta.created_at) }}</p>
            </div>
          </div>
          <div class="space-y-4">
            <div>
              <label class="text-sm font-medium text-gray-500">Foto</label>
              <div class="mt-2">
                <img v-if="props.herramienta.foto"
                     :src="`/storage/${props.herramienta.foto}`"
                     alt="Foto de la herramienta"
                     class="w-full max-w-sm h-48 object-cover rounded-lg border" />
                <div v-else class="w-full max-w-sm h-48 bg-gray-100 rounded-lg border flex items-center justify-center">
                  <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                  </svg>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-6">
          <label class="text-sm font-medium text-gray-500">Descripción</label>
          <p class="mt-1 text-gray-900 whitespace-pre-wrap">{{ props.herramienta.descripcion || 'No hay descripción disponible' }}</p>
        </div>
      </div>

      <!-- Información de mantenimiento -->
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-semibold">Información de Mantenimiento</h2>
          <button @click="showMantenimientoForm = !showMantenimientoForm" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            {{ showMantenimientoForm ? 'Cancelar' : 'Registrar Mantenimiento' }}
          </button>
        </div>

        <!-- Formulario de mantenimiento -->
        <div v-if="showMantenimientoForm" class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
          <h3 class="font-medium text-green-800 mb-3">Nuevo Registro de Mantenimiento</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Mantenimiento</label>
              <input v-model="fechaMantenimiento" type="date" class="w-full border rounded px-3 py-2" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Costo</label>
              <input v-model="costoMantenimiento" type="number" step="0.01" placeholder="0.00" class="w-full border rounded px-3 py-2" />
            </div>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Próximo Mantenimiento (días)</label>
            <input v-model="proximoMantenimientoDias" type="number" placeholder="90" class="w-full border rounded px-3 py-2" />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción del Mantenimiento</label>
            <textarea v-model="descripcionMantenimiento" rows="3" placeholder="Describe el mantenimiento realizado..." class="w-full border rounded px-3 py-2"></textarea>
          </div>
          <button @click="registrarMantenimiento" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            Guardar Mantenimiento
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="text-center p-4 bg-white rounded-lg">
            <div class="text-2xl font-bold text-blue-600">{{ props.herramienta.dias_desde_ultimo_mantenimiento || 0 }}</div>
            <div class="text-sm text-gray-600">Días desde último mantenimiento</div>
          </div>
          <div class="text-center p-4 bg-white rounded-lg">
            <div class="text-2xl font-bold text-orange-600">{{ props.herramienta.dias_para_proximo_mantenimiento || 0 }}</div>
            <div class="text-sm text-gray-600">Días para próximo mantenimiento</div>
          </div>
          <div class="text-center p-4 bg-white rounded-lg">
            <div class="text-2xl font-bold text-purple-600">{{ props.herramienta.porcentaje_vida_util || 0 }}%</div>
            <div class="text-sm text-gray-600">Vida útil utilizada</div>
          </div>
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-medium text-gray-500">Último Mantenimiento</label>
            <p class="text-lg">{{ formatDate(props.herramienta.fecha_ultimo_mantenimiento) }}</p>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-500">Requiere Mantenimiento</label>
            <p class="text-lg">
              <span v-if="props.herramienta.requiere_mantenimiento" class="text-red-600 font-medium">Sí</span>
              <span v-else class="text-green-600 font-medium">No</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Información de asignación -->
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-xl font-semibold mb-4">Información de Asignación</h2>
        <div v-if="props.herramienta.tecnico?.nombre" class="space-y-3">
          <div class="flex justify-between">
            <span class="text-gray-600">Técnico asignado:</span>
            <span class="font-medium">{{ props.herramienta.tecnico.nombre }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Fecha de asignación:</span>
            <span class="font-medium">{{ formatDate(props.herramienta.fecha_asignacion) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Fecha de recepción:</span>
            <span class="font-medium">{{ formatDate(props.herramienta.fecha_recepcion) || 'No recibida' }}</span>
          </div>
        </div>
        <div v-else class="text-center py-8 text-gray-500">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
          <p>No hay asignación activa</p>
        </div>
      </div>

      <!-- Estadísticas básicas -->
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-xl font-semibold mb-4">Estadísticas de Uso</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div v-for="stat in estadisticasItems" :key="stat.label" class="text-center p-3 bg-white rounded-lg">
            <div class="text-xl font-bold text-blue-600">{{ stat.value }}</div>
            <div class="text-sm text-gray-600">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel lateral -->
    <div class="space-y-6">
      <!-- Acciones rápidas -->
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold mb-4">Acciones Rápidas</h3>
        <div class="space-y-3">
          <button @click="showMantenimientoForm = !showMantenimientoForm" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            Registrar Mantenimiento
          </button>
          <Link href="/herramientas-mantenimiento" class="block w-full px-4 py-2 bg-orange-600 text-white text-center rounded-lg hover:bg-orange-700">
            Ver Mantenimiento
          </Link>
          <Link href="/herramientas-alertas" class="block w-full px-4 py-2 bg-red-600 text-white text-center rounded-lg hover:bg-red-700">
            Ver Alertas
          </Link>
        </div>
      </div>

      <!-- Información técnica -->
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold mb-4">Especificaciones Técnicas</h3>
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-gray-600">Vida útil (meses):</span>
            <span class="font-medium">{{ props.herramienta.vida_util_meses || 'N/A' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Costo de reemplazo:</span>
            <span class="font-medium">${{ props.herramienta.costo_reemplazo || 'N/A' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Próxima a vencer vida útil:</span>
            <span class="font-medium">
              <span v-if="props.herramienta.vida_util_proxima_a_vencer" class="text-red-600">Sí</span>
              <span v-else class="text-green-600">No</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Estado de alertas -->
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-semibold mb-4">Estado de Alertas</h3>
        <div class="space-y-3">
          <div v-if="props.herramienta.necesita_mantenimiento" class="p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
              </svg>
              <span class="text-sm font-medium text-red-800">Requiere mantenimiento urgente</span>
            </div>
          </div>
          <div v-if="props.herramienta.vida_util_proxima_a_vencer" class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-sm font-medium text-yellow-800">Próxima a vencer vida útil</span>
            </div>
          </div>
          <div v-if="!props.herramienta.necesita_mantenimiento && !props.herramienta.vida_util_proxima_a_vencer" class="p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-sm font-medium text-green-800">Estado normal</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Historial reciente (si está disponible) -->
  <div v-if="historial_completo && historial_completo.length > 0" class="mt-6 bg-white rounded-lg shadow-sm border p-6">
    <h2 class="text-xl font-semibold mb-4">Historial Reciente</h2>
    <div class="space-y-3">
      <div v-for="registro in historial_completo.slice(0, 5)" :key="registro.id" class="flex items-center justify-between p-3 bg-white rounded-lg">
        <div>
          <div class="font-medium">{{ registro.tipo_accion }}</div>
          <div class="text-sm text-gray-600">{{ formatDateTime(registro.fecha_accion) }}</div>
          <div v-if="registro.descripcion" class="text-sm text-gray-500">{{ registro.descripcion }}</div>
        </div>
        <div class="text-right">
          <div v-if="registro.costo" class="font-medium">${{ registro.costo }}</div>
          <div v-if="registro.usuario" class="text-sm text-gray-500">{{ registro.usuario.nombre }}</div>
        </div>
      </div>
    </div>
    <div class="mt-4 text-center">
      <Link :href="`/herramientas/${props.herramienta.id}/estadisticas`" class="text-blue-600 hover:underline">
        Ver historial completo →
      </Link>
    </div>
  </div>
</template>


