<script setup>
import { useFormatters } from '@/Composables/useFormatters';
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
    'disponible': 'bg-emerald-100 text-emerald-800 dark:text-emerald-200',
    'asignada': 'bg-sky-100 text-sky-800 dark:text-sky-200',
    'mantenimiento': 'bg-brand-100 text-brand-800 dark:text-amber-200',
    'baja': 'bg-rose-100 text-rose-800 dark:text-rose-200',
    'perdida': 'bg-rose-100 text-rose-800 dark:text-rose-200',
  }
  return colors[estado] || 'bg-slate-100 text-slate-800'
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
      <Link class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700" :href="route('herramientas.dashboard')">
        Dashboard
      </Link>
      <Link class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700" :href="route('herramientas.index')">
        Ver Herramientas
      </Link>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Panel de configuración -->
    <div class="lg:col-span-1">
      <div class="bg-white rounded-2xl shadow-sm border p-6 sticky top-6">
        <h2 class="text-xl font-semibold mb-4">Configuración de Reporte</h2>

        <!-- Tipo de reporte -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-slate-700 mb-2">Tipo de Reporte</label>
          <select v-model="reporteSeleccionado" class="w-full border rounded-xl px-3 py-2">
            <option v-for="reporte in reportes" :key="reporte.id" :value="reporte.id">
              {{ reporte.nombre }}
            </option>
          </select>
        </div>

        <!-- Filtros de fecha -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-slate-700 mb-2">Período</label>
          <div class="space-y-2">
            <input v-model="fechaInicio" type="date" placeholder="Fecha inicio" class="w-full border rounded-xl px-3 py-2" />
            <input v-model="fechaFin" type="date" placeholder="Fecha fin" class="w-full border rounded-xl px-3 py-2" />
          </div>
        </div>

        <!-- Filtros adicionales -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-slate-700 mb-2">Categoría</label>
          <select v-model="categoriaSeleccionada" class="w-full border rounded-xl px-3 py-2">
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
          <label class="block text-sm font-medium text-slate-700 mb-2">Estado</label>
          <select v-model="estadoSeleccionado" class="w-full border rounded-xl px-3 py-2">
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
          <button @click="generarReporte" class="w-full px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700">
            Generar Reporte
          </button>
          <button @click="exportarReporte('pdf')" class="w-full px-4 py-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700">
            Exportar PDF
          </button>
          <button @click="exportarReporte('excel')" class="w-full px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700">
            Exportar Excel
          </button>
        </div>
      </div>
    </div>

    <!-- Contenido del reporte -->
    <div class="lg:col-span-3">
      <div class="bg-white rounded-2xl shadow-sm border">
        <!-- Encabezado del reporte -->
        <div class="p-6 border-b">
          <h2 class="text-xl font-semibold">{{ reportes.find(r => r.id === reporteSeleccionado)?.nombre }}</h2>
          <p class="text-slate-500">{{ reportes.find(r => r.id === reporteSeleccionado)?.descripcion }}</p>
          <p class="text-sm text-slate-500 mt-2">Generado el: {{ formatDate(new Date()) }}</p>
        </div>

        <!-- Contenido según el tipo de reporte -->
        <div class="p-6">
          <!-- Reporte General -->
          <div v-if="reporteSeleccionado === 'general'" class="space-y-6">
            <!-- Estadísticas generales -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="text-center p-4 bg-sky-50 dark:bg-sky-900/20 rounded-xl">
                <div class="text-2xl font-bold text-blue-600">{{ estadisticas.total_herramientas || 0 }}</div>
                <div class="text-sm text-slate-500">Total Herramientas</div>
              </div>
              <div class="text-center p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                <div class="text-2xl font-bold text-emerald-600">{{ estadisticas.herramientas_disponibles || 0 }}</div>
                <div class="text-sm text-slate-500">Disponibles</div>
              </div>
              <div class="text-center p-4 bg-brand-50 dark:bg-brand-900/20 rounded-xl">
                <div class="text-2xl font-bold text-amber-600">{{ estadisticas.herramientas_mantenimiento || 0 }}</div>
                <div class="text-sm text-slate-500">En Mantenimiento</div>
              </div>
              <div class="text-center p-4 bg-rose-50 dark:bg-rose-900/20 rounded-xl">
                <div class="text-2xl font-bold text-rose-600">{{ estadisticas.herramientas_requieren_mantenimiento || 0 }}</div>
                <div class="text-sm text-slate-500">Requieren Mant.</div>
              </div>
            </div>

            <!-- Lista de herramientas -->
            <div>
              <h3 class="text-lg font-semibold mb-3">Lista de Herramientas</h3>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                  <thead class="bg-slate-50 dark:bg-slate-800/50">
                    <tr>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Herramienta</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Categoría</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Último Mant.</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Costo</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                    <tr v-for="herramienta in herramientas" :key="herramienta.id">
                      <td class="px-6 py-4">
                        <div>
                          <div class="font-medium text-slate-900">{{ herramienta.nombre }}</div>
                          <div class="text-sm text-slate-500">{{ herramienta.numero_serie }}</div>
                        </div>
                      </td>
                      <td class="px-6 py-4">
                        <span :class="['px-2.5 py-0.5 text-xs font-medium rounded-full', getEstadoColor(herramienta.estado)]">
                          {{ herramienta.estado }}
                        </span>
                      </td>
                      <td class="px-6 py-4">
                        <span class="text-sm text-slate-900">{{ herramienta.categoria_herramienta?.nombre || 'Sin categoría' }}</span>
                      </td>
                      <td class="px-6 py-4">
                        <span class="text-sm text-slate-900">{{ formatDate(herramienta.fecha_ultimo_mantenimiento) }}</span>
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
              <div class="text-center p-4 bg-rose-50 dark:bg-rose-900/20 rounded-xl">
                <div class="text-2xl font-bold text-rose-600">{{ herramientas.filter(h => h.necesita_mantenimiento).length }}</div>
                <div class="text-sm text-slate-500">Requieren Mant. Urgente</div>
              </div>
              <div class="text-center p-4 bg-orange-50 rounded-xl">
                <div class="text-2xl font-bold text-orange-600">{{ herramientas.filter(h => h.dias_para_proximo_mantenimiento <= 30 && h.dias_para_proximo_mantenimiento > 0).length }}</div>
                <div class="text-sm text-slate-500">Próximo Mes</div>
              </div>
              <div class="text-center p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                <div class="text-2xl font-bold text-emerald-600">{{ herramientas.filter(h => !h.requiere_mantenimiento).length }}</div>
                <div class="text-sm text-slate-500">Al Día</div>
              </div>
            </div>

            <div>
              <h3 class="text-lg font-semibold mb-3">Herramientas que Requieren Mantenimiento</h3>
              <div class="space-y-3">
                <div v-for="herramienta in herramientas.filter(h => h.necesita_mantenimiento)" :key="herramienta.id" class="p-4 border border-rose-200 dark:border-rose-800/30 rounded-xl bg-rose-50 dark:bg-rose-900/20">
                  <div class="flex items-center justify-between">
                    <div>
                      <h4 class="font-medium text-rose-800 dark:text-rose-200">{{ herramienta.nombre }}</h4>
                      <p class="text-sm text-rose-600">{{ herramienta.numero_serie }}</p>
                      <p class="text-sm text-rose-800 dark:text-rose-200 dark:text-rose-200">
                        Días desde último mantenimiento: {{ herramienta.dias_desde_ultimo_mantenimiento || 'N/A' }}
                      </p>
                    </div>
                    <div class="text-right">
                      <div class="text-sm font-medium text-rose-800 dark:text-rose-200">{{ formatCurrency(herramienta.costo_reemplazo) }}</div>
                      <div class="text-sm text-rose-600">{{ herramienta.categoria_herramienta?.nombre || 'Sin categoría' }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Reporte de Uso -->
          <div v-if="reporteSeleccionado === 'uso'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="text-center p-4 bg-sky-50 dark:bg-sky-900/20 rounded-xl">
                <div class="text-2xl font-bold text-blue-600">{{ herramientas.filter(h => h.estado === 'asignada').length }}</div>
                <div class="text-sm text-slate-500">Actualmente Asignadas</div>
              </div>
              <div class="text-center p-4 bg-purple-50 rounded-xl">
                <div class="text-2xl font-bold text-purple-600">{{ estadisticas.total_asignaciones || 0 }}</div>
                <div class="text-sm text-slate-500">Total Asignaciones</div>
              </div>
              <div class="text-center p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl">
                <div class="text-2xl font-bold text-emerald-600">{{ estadisticas.promedio_dias_uso || 0 }}</div>
                <div class="text-sm text-slate-500">Promedio Días Uso</div>
              </div>
            </div>

            <div>
              <h3 class="text-lg font-semibold mb-3">Herramientas Más Utilizadas</h3>
              <div class="space-y-3">
                <div v-for="herramienta in herramientas.slice(0, 10)" :key="herramienta.id" class="flex items-center justify-between p-3 bg-white rounded-xl">
                  <div>
                    <h4 class="font-medium">{{ herramienta.nombre }}</h4>
                    <p class="text-sm text-slate-500">{{ herramienta.numero_serie }}</p>
                  </div>
                  <div class="text-right">
                    <div class="font-medium">{{ herramienta.estadisticas?.total_asignaciones || 0 }} asignaciones</div>
                    <div class="text-sm text-slate-500">{{ herramienta.estado }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Placeholder para otros reportes -->
          <div v-if="['categoria', 'vida_util'].includes(reporteSeleccionado)" class="py-12 text-center">
            <svg class="w-16 h-16 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V17a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-slate-900 mb-2">Reporte en Desarrollo</h3>
            <p class="text-slate-500">Esta funcionalidad estará disponible próximamente.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

