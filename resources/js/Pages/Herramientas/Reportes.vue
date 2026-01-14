<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  herramientas: { type: Array, default: () => [] },
  estadisticas: { type: Object, default: () => ({}) },
})

const reporteSeleccionado = ref('general')
const fechaInicio = ref('')
const fechaFin = ref('')
const categoriaSeleccionada = ref('')
const estadoSeleccionado = ref('')

const reportes = [
  { id: 'general', nombre: 'Reporte General', descripcion: 'Resumen completo del estado de herramientas' },
  { id: 'mantenimiento', nombre: 'Reporte de Mantenimiento', descripcion: 'Herramientas que requieren mantenimiento' },
  { id: 'uso', nombre: 'Reporte de Uso', descripcion: 'Estadísticas de uso y asignaciones' },
  { id: 'categoria', nombre: 'Reporte por Categoría', descripcion: 'Análisis por categorías de herramientas' },
  { id: 'vida_util', nombre: 'Reporte de Vida Útil', descripcion: 'Herramientas próximas a vencer su vida útil' },
]

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES')
}

const formatCurrency = (amount) => {
  if (!amount) return '$0.00'
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(amount)
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

const generarReporte = () => {
  // Aquí se implementaría la lógica para generar el reporte
  console.log('Generando reporte:', reporteSeleccionado.value)
}

const exportarReporte = (formato) => {
  // Aquí se implementaría la lógica para exportar el reporte
  console.log('Exportando reporte en formato:', formato)
}
</script>

<template>
  <Head title="Reportes de Herramientas" />

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-slate-900">Reportes de Herramientas</h1>
    <div class="flex gap-3">
      <Link class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" :href="route('herramientas.dashboard')">
        Dashboard
      </Link>
      <Link class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700" :href="route('herramientas.index')">
        Ver Herramientas
      </Link>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Panel de configuración -->
    <div class="lg:col-span-1">
      <div class="bg-white rounded-lg shadow-sm border p-6 sticky top-6">
        <h2 class="text-xl font-semibold mb-4">Configuración de Reporte</h2>

        <!-- Tipo de reporte -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Reporte</label>
          <select v-model="reporteSeleccionado" class="w-full border rounded px-3 py-2">
            <option v-for="reporte in reportes" :key="reporte.id" :value="reporte.id">
              {{ reporte.nombre }}
            </option>
          </select>
        </div>

        <!-- Filtros de fecha -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
          <div class="space-y-2">
            <input v-model="fechaInicio" type="date" placeholder="Fecha inicio" class="w-full border rounded px-3 py-2" />
            <input v-model="fechaFin" type="date" placeholder="Fecha fin" class="w-full border rounded px-3 py-2" />
          </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
          <select v-model="categoriaSeleccionada" class="w-full border rounded px-3 py-2">
            <option value="">Todas las categorías</option>
            <option value="electrica">Eléctrica</option>
            <option value="manual">Manual</option>
            <option value="medicion">Medición</option>
            <option value="seguridad">Seguridad</option>
            <option value="limpieza">Limpieza</option>
            <option value="jardineria">Jardinería</option>
            <option value="construccion">Construcción</option>
            <option value="electronica">Electrónica</option>
            <option value="otra">Otra</option>
          </select>
        </div>

        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
          <select v-model="estadoSeleccionado" class="w-full border rounded px-3 py-2">
            <option value="">Todos los estados</option>
            <option value="disponible">Disponible</option>
            <option value="asignada">Asignada</option>
            <option value="mantenimiento">En Mantenimiento</option>
            <option value="baja">De Baja</option>
            <option value="perdida">Perdida</option>
          </select>
        </div>

        <!-- Botones de acción -->
        <div class="space-y-3">
          <button @click="generarReporte" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Generar Reporte
          </button>
          <button @click="exportarReporte('pdf')" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            Exportar PDF
          </button>
          <button @click="exportarReporte('excel')" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
            Exportar Excel
          </button>
        </div>
      </div>
    </div>

    <!-- Contenido del reporte -->
    <div class="lg:col-span-3">
      <div class="bg-white rounded-lg shadow-sm border">
        <!-- Encabezado del reporte -->
        <div class="p-6 border-b">
          <h2 class="text-xl font-semibold">{{ reportes.find(r => r.id === reporteSeleccionado)?.nombre }}</h2>
          <p class="text-gray-600">{{ reportes.find(r => r.id === reporteSeleccionado)?.descripcion }}</p>
          <p class="text-sm text-gray-500 mt-2">Generado el: {{ formatDate(new Date()) }}</p>
        </div>

        <!-- Contenido según el tipo de reporte -->
        <div class="p-6">
          <!-- Reporte General -->
          <div v-if="reporteSeleccionado === 'general'" class="space-y-6">
            <!-- Estadísticas generales -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ estadisticas.total_herramientas || 0 }}</div>
                <div class="text-sm text-gray-600">Total Herramientas</div>
              </div>
              <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ estadisticas.herramientas_disponibles || 0 }}</div>
                <div class="text-sm text-gray-600">Disponibles</div>
              </div>
              <div class="text-center p-4 bg-yellow-50 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600">{{ estadisticas.herramientas_mantenimiento || 0 }}</div>
                <div class="text-sm text-gray-600">En Mantenimiento</div>
              </div>
              <div class="text-center p-4 bg-red-50 rounded-lg">
                <div class="text-2xl font-bold text-red-600">{{ estadisticas.herramientas_requieren_mantenimiento || 0 }}</div>
                <div class="text-sm text-gray-600">Requieren Mant.</div>
              </div>
            </div>

            <!-- Lista de herramientas -->
            <div>
              <h3 class="text-lg font-semibold mb-3">Lista de Herramientas</h3>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Herramienta</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último Mant.</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Costo</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="herramienta in herramientas" :key="herramienta.id">
                      <td class="px-6 py-4">
                        <div>
                          <div class="font-medium text-gray-900">{{ herramienta.nombre }}</div>
                          <div class="text-sm text-gray-500">{{ herramienta.numero_serie }}</div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getEstadoColor(herramienta.estado)]">
                          {{ herramienta.estado }}
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <span class="text-sm text-gray-900">{{ herramienta.categoria_herramienta?.nombre || 'Sin categoría' }}</span>
                      </td>
                      <td class="px-6 py-4">
                        <span class="text-sm text-gray-900">{{ formatDate(herramienta.fecha_ultimo_mantenimiento) }}</span>
                      </td>
                      <td class="px-6 py-4">
                        <span class="text-sm font-medium">{{ formatCurrency(herramienta.costo_reemplazo) }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Reporte de Mantenimiento -->
          <div v-if="reporteSeleccionado === 'mantenimiento'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="text-center p-4 bg-red-50 rounded-lg">
                <div class="text-2xl font-bold text-red-600">{{ herramientas.filter(h => h.necesita_mantenimiento).length }}</div>
                <div class="text-sm text-gray-600">Requieren Mant. Urgente</div>
              </div>
              <div class="text-center p-4 bg-orange-50 rounded-lg">
                <div class="text-2xl font-bold text-orange-600">{{ herramientas.filter(h => h.dias_para_proximo_mantenimiento <= 30 && h.dias_para_proximo_mantenimiento > 0).length }}</div>
                <div class="text-sm text-gray-600">Próximo Mes</div>
              </div>
              <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ herramientas.filter(h => !h.requiere_mantenimiento).length }}</div>
                <div class="text-sm text-gray-600">Al Día</div>
              </div>
            </div>

            <div>
              <h3 class="text-lg font-semibold mb-3">Herramientas que Requieren Mantenimiento</h3>
              <div class="space-y-3">
                <div v-for="herramienta in herramientas.filter(h => h.necesita_mantenimiento)" :key="herramienta.id" class="p-4 border border-red-200 rounded-lg bg-red-50">
                  <div class="flex items-center justify-between">
                    <div>
                      <h4 class="font-medium text-red-800">{{ herramienta.nombre }}</h4>
                      <p class="text-sm text-red-600">{{ herramienta.numero_serie }}</p>
                      <p class="text-sm text-red-700">
                        Días desde último mantenimiento: {{ herramienta.dias_desde_ultimo_mantenimiento || 'N/A' }}
                      </p>
                    </div>
                    <div class="text-right">
                      <div class="text-sm font-medium text-red-800">{{ formatCurrency(herramienta.costo_reemplazo) }}</div>
                      <div class="text-sm text-red-600">{{ herramienta.categoria_herramienta?.nombre || 'Sin categoría' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Reporte de Uso -->
          <div v-if="reporteSeleccionado === 'uso'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ herramientas.filter(h => h.estado === 'asignada').length }}</div>
                <div class="text-sm text-gray-600">Actualmente Asignadas</div>
              </div>
              <div class="text-center p-4 bg-purple-50 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">{{ estadisticas.total_asignaciones || 0 }}</div>
                <div class="text-sm text-gray-600">Total Asignaciones</div>
              </div>
              <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ estadisticas.promedio_dias_uso || 0 }}</div>
                <div class="text-sm text-gray-600">Promedio Días Uso</div>
              </div>
            </div>

            <div>
              <h3 class="text-lg font-semibold mb-3">Herramientas Más Utilizadas</h3>
              <div class="space-y-3">
                <div v-for="herramienta in herramientas.slice(0, 10)" :key="herramienta.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <h4 class="font-medium">{{ herramienta.nombre }}</h4>
                    <p class="text-sm text-gray-600">{{ herramienta.numero_serie }}</p>
                  </div>
                  <div class="text-right">
                    <div class="font-medium">{{ herramienta.estadisticas?.total_asignaciones || 0 }} asignaciones</div>
                    <div class="text-sm text-gray-600">{{ herramienta.estado }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Placeholder para otros reportes -->
          <div v-if="['categoria', 'vida_util'].includes(reporteSeleccionado)" class="text-center py-12">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V17a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Reporte en Desarrollo</h3>
            <p class="text-gray-600">Esta funcionalidad estará disponible próximamente.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

