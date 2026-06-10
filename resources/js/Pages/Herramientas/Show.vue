<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import useCompanyColors from '@/Composables/useCompanyColors'
import Swal from '@/Utils/Swal'
const { formatDateTime } = useFormatters();

defineOptions({ layout: AppLayout })

const props = defineProps({
  herramienta: { type: Object, required: true },
  estadisticas: { type: Object, default: () => ({}) },
  historial_completo: { type: Array, default: () => [] },
})

const { colors, headerGradientStyle } = useCompanyColors()

const showMantenimientoForm = ref(false)
const fechaMantenimiento = ref(new Date().toISOString().split('T')[0])
const costoMantenimiento = ref('')
const descripcionMantenimiento = ref('')
const proximoMantenimientoDias = ref('')

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' })
}

const formatFechaHoraLocal = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('es-ES', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

const getEstadoClase = (estado) => {
  const classes = {
    'disponible': 'bg-emerald-100 dark:bg-slate-800/30 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-800/50',
    'asignada': 'bg-blue-50 dark:bg-sky-900/20/30 text-sky-800 dark:text-sky-200 dark:text-blue-400 border-sky-200 dark:border-sky-800/30 dark:border-blue-800/50',
    'mantenimiento': 'bg-brand-50 dark:bg-brand-900/20/30 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 border-brand-200 dark:border-brand-800/30 dark:border-brand-800/50',
    'baja': 'bg-rose-50 dark:bg-rose-900/20/30 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400 border-rose-200 dark:border-rose-800/30 dark:border-rose-800/50',
    'perdida': 'bg-rose-50 dark:bg-rose-900/20/30 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400 border-rose-200 dark:border-rose-800/30 dark:border-rose-800/50',
  }
  return classes[estado] || 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border-slate-200'
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

const cambiarEstado = async (nuevoEstado) => {
  const { isConfirmed } = await Swal.fire({ title: 'Confirmar', text: `¿Estás seguro de cambiar el estado a "${nuevoEstado}"?`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí', cancelButtonText: 'No' })
  if (!isConfirmed) return
  router.post(`/herramientas/${props.herramienta.id}/cambiar-estado`, {
    estado: nuevoEstado,
    observaciones: `Cambio desde vista de detalles`,
  }, {
    preserveScroll: true,
  })
}

const estadisticasItems = computed(() => [
  { label: 'Total asignaciones', value: props.estadisticas.total_asignaciones || 0, icon: '📋' },
  { label: 'Uso promedio', value: `${props.estadisticas.promedio_dias_uso || 0}d`, icon: '⏳' },
  { label: 'Mantenimientos', value: props.historial_completo.filter(h => h.tipo_accion === 'mantenimiento').length, icon: '🛠️' },
])

const isDark = ref(false)
let observer = null

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark')
  })
  observer.observe(document.documentElement, { attributes: true })
})

onBeforeUnmount(() => { if (observer) observer.disconnect() })
</script>

<template>
  <Head :title="`Herramienta - ${props.herramienta.nombre}`" />

  <div class="min-h-screen bg-[var(--ui-surface)] py-8 px-4 transition-colors duration-200">
    <div class="max-w-6xl mx-auto">
      
      <!-- Top Bar -->
      <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Link href="/herramientas" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">{{ props.herramienta.nombre }}</h1>
              <span :class="[getEstadoClase(props.herramienta.estado), 'px-3 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide border shadow-sm']">
                {{ props.herramienta.estado }}
              </span>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-mono tracking-tighter">Serie: {{ props.herramienta.numero_serie || 'SIN SERIE' }}</p>
          </div>
        </div>
        <div class="flex gap-3">
          <Link :href="`/herramientas/${props.herramienta.id}/edit`" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-black uppercase tracking-wide rounded-xl hover:bg-blue-700 transition-all shadow-xl shadow-sky-200 dark:shadow-none active:scale-95">
            Editar Equipo
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Columna Izquierda: Detalles e Imagen -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Card: Vista Previa y Descripción -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden transition-all duration-200">
            <div :style="headerGradientStyle" class="h-2 opacity-20"></div>
            <div class="p-8 grid md:grid-cols-2 gap-8">
              <div class="aspect-square rounded-2xl overflow-hidden bg-[var(--ui-surface)]/50 border border-slate-100 dark:border-slate-700 relative group">
                <img v-if="props.herramienta.foto" :src="`/storage/${props.herramienta.foto}`" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300 gap-4">
                  <svg class="w-16 h-16 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                  <span class="text-[10px] font-black uppercase tracking-wide opacity-50">Sin Imagen Disponible</span>
                </div>
              </div>
              <div class="space-y-6">
                <div>
                  <label class="block text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-2">Categoría</label>
                  <p class="text-lg font-bold text-slate-900 dark:text-white">{{ props.herramienta.categoria_herramienta?.nombre || 'General' }}</p>
                </div>
                <div>
                  <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Descripción del Equipo</label>
                  <p class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed italic">{{ props.herramienta.descripcion || '— Sin descripción proporcionada —' }}</p>
                </div>
                <div class="pt-4 grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Costo Reemplazo</label>
                    <p class="text-base font-black text-slate-900 dark:text-white">${{ props.herramienta.costo_reemplazo || '0.00' }}</p>
                  </div>
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1">Días de Ciclo</label>
                    <p class="text-base font-black text-slate-900 dark:text-white">{{ props.herramienta.dias_para_mantenimiento || '—' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card: Mantenimiento -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 p-8 transition-all duration-200">
            <div class="flex items-center justify-between mb-8">
              <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-900/20/30 flex items-center justify-center text-brand-600 dark:text-brand-400 italic">M</span>
                Control de Mantenimiento
              </h2>
              <button @click="showMantenimientoForm = !showMantenimientoForm" :class="showMantenimientoForm ? 'bg-brand-500' : 'bg-emerald-600'" class="px-4 py-2 text-[10px] font-black text-white uppercase tracking-wide rounded-xl transition-all active:scale-95 shadow-xl">
                {{ showMantenimientoForm ? 'Cancelar' : 'Registrar Servicio' }}
              </button>
            </div>

            <!-- Formulario Animado -->
            <Transition name="fade">
              <div v-if="showMantenimientoForm" class="mb-8 p-6 bg-[var(--ui-surface)]/50 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Fecha</label>
                    <input v-model="fechaMantenimiento" type="date" class="w-full px-4 py-2 rounded-xl bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-sm" />
                  </div>
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Costo ($)</label>
                    <input v-model="costoMantenimiento" type="number" placeholder="0.00" class="w-full px-4 py-2 rounded-xl bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-sm" />
                  </div>
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Próximo en (días)</label>
                    <input v-model="proximoMantenimientoDias" type="number" placeholder="90" class="w-full px-4 py-2 rounded-xl bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-sm" />
                  </div>
                </div>
                <div>
                  <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Descripción de los trabajos</label>
                  <textarea v-model="descripcionMantenimiento" rows="3" class="w-full px-4 py-2 rounded-xl bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-sm" placeholder="Limpieza, cambio de carbones, lubricación..."></textarea>
                </div>
                <button @click="registrarMantenimiento" class="w-full py-3 bg-blue-600 text-white rounded-xl font-black uppercase tracking-wide text-[10px] shadow-xl shadow-sky-200 dark:shadow-none active:scale-95 transition-all">Guardar Registro de Mantenimiento</button>
              </div>
            </Transition>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="p-6 bg-[var(--ui-surface)]/50 rounded-3xl border border-slate-100 dark:border-slate-700/50 text-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1">Días desde el último</p>
                <p class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ props.herramienta.dias_desde_ultimo_mantenimiento || 0 }}</p>
                <p class="text-[10px] text-slate-500 mt-1 italic">{{ formatDate(props.herramienta.fecha_ultimo_mantenimiento) }}</p>
              </div>
              <div class="p-6 bg-[var(--ui-surface)]/50 rounded-3xl border border-slate-100 dark:border-slate-700/50 text-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1">Días restantes</p>
                <p :class="props.herramienta.dias_para_proximo_mantenimiento < 10 ? 'text-rose-500' : 'text-brand-500'" class="text-2xl font-black">{{ props.herramienta.dias_para_proximo_mantenimiento || 0 }}</p>
                <p class="text-[10px] text-slate-500 mt-1 italic">Ciclo de {{ props.herramienta.dias_para_mantenimiento || '—' }} días</p>
              </div>
              <div class="p-6 bg-[var(--ui-surface)]/50 rounded-3xl border border-slate-100 dark:border-slate-700/50 text-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1">Uso de Vida Útil</p>
                <p class="text-2xl font-black text-purple-600 dark:text-purple-400">{{ props.herramienta.porcentaje_vida_util || 0 }}%</p>
                <div class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full mt-3 overflow-hidden">
                  <div class="bg-purple-500 h-full transition-all duration-700" :style="{ width: `${props.herramienta.porcentaje_vida_util || 0}%` }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Columna Derecha: Estado y Asignación -->
        <div class="space-y-6">
          
          <!-- Card: Asignación Activa -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 p-8 transition-all duration-200 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-600/5 rounded-full -mr-16 -mt-16 blur-2xl"></div>
            <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide mb-6 flex items-center gap-2">
              <span class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-sky-900/20/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold">@</span>
              Custodia Actual
            </h2>
            
            <div v-if="props.herramienta.tecnico?.nombre" class="space-y-6">
              <div class="flex items-center gap-4 p-4 bg-sky-50 dark:bg-sky-900/20 dark:bg-blue-900/10 rounded-2xl border border-blue-100 dark:border-blue-800/50">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xl font-black shadow-xl shadow-sky-200 dark:shadow-none">{{ props.herramienta.tecnico.nombre.charAt(0) }}</div>
                <div>
                  <p class="text-sm font-black text-slate-900 dark:text-white">{{ props.herramienta.tecnico.nombre }}</p>
                  <p class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-wide">Técnico Autorizado</p>
                </div>
              </div>
              <div class="space-y-3 px-1">
                <div class="flex justify-between items-center text-xs">
                  <span class="text-slate-500 font-medium">Asignado:</span>
                  <span class="text-slate-900 dark:text-slate-200 font-bold">{{ formatDate(props.herramienta.fecha_asignacion) }}</span>
                </div>
                <div v-if="props.herramienta.fecha_recepcion" class="flex justify-between items-center text-xs">
                  <span class="text-slate-500 font-medium">Última entrega:</span>
                  <span class="text-slate-900 dark:text-slate-200 font-bold">{{ formatDate(props.herramienta.fecha_recepcion) }}</span>
                </div>
              </div>
            </div>
            <div v-else class="py-10 text-center space-y-6">
              <div class="w-16 h-16 bg-[var(--ui-surface)]/50 rounded-full flex items-center justify-center mx-auto text-slate-300">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </div>
              <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">Sin Asignación Activa</p>
            </div>
          </div>

          <!-- Card: Acciones de Estado -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 p-8 transition-all duration-200">
            <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide mb-6">Control de Estado</h2>
            <div class="grid grid-cols-2 gap-2">
              <button v-for="est in ['disponible', 'mantenimiento', 'baja', 'perdida']" :key="est" @click="cambiarEstado(est)" :class="props.herramienta.estado === est ? 'bg-blue-600 text-white border-blue-600 shadow-xl' : 'bg-[var(--ui-surface)] text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700 hover:bg-slate-100'" class="py-3 px-2 rounded-xl border text-[10px] font-black uppercase tracking-wide transition-all active:scale-95">
                {{ est }}
              </button>
            </div>
          </div>

          <!-- Card: Estadísticas Rápidas -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 p-8 transition-all duration-200">
            <div class="space-y-6">
              <div v-for="item in estadisticasItems" :key="item.label" class="flex items-center justify-between group">
                <div class="flex items-center gap-2">
                  <span class="text-xl group-hover:scale-125 transition-transform duration-200">{{ item.icon }}</span>
                  <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wide">{{ item.label }}</span>
                </div>
                <span class="text-lg font-black text-slate-900 dark:text-white">{{ item.value }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sección Historial Moderno -->
      <div v-if="historial_completo && historial_completo.length > 0" class="mt-8 bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden transition-all duration-200">
        <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between bg-slate-50/50 dark:bg-black/50">
          <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Línea de Tiempo / Historial</h2>
          <span class="px-3 py-1 bg-blue-50 dark:bg-sky-900/20/30 text-blue-600 dark:text-blue-400 rounded-full text-[10px] font-bold uppercase tracking-wide">{{ historial_completo.length }} Eventos</span>
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700">
          <div v-for="registro in historial_completo.slice(0, 10)" :key="registro.id" class="p-6 hover:bg-slate-50/20 dark:hover:bg-blue-900/10 transition-colors group">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-lg shadow-sm group-hover:bg-sky-100 dark:group-hover:bg-blue-900/40 transition-colors">
                  <span v-if="registro.tipo_accion === 'mantenimiento'">🛠️</span>
                  <span v-else-if="registro.tipo_accion === 'asignacion'">📦</span>
                  <span v-else-if="registro.tipo_accion === 'devolucion'">🔄</span>
                  <span v-else>📝</span>
                </div>
                <div>
                  <p class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ registro.tipo_accion }}</p>
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed italic">{{ registro.descripcion || 'Sin observaciones' }}</p>
                </div>
              </div>
              <div class="flex items-center gap-6 md:text-right">
                <div v-if="registro.costo" class="text-sm font-black text-emerald-600 dark:text-slate-400">
                  <span class="text-[9px] text-slate-400 uppercase mr-1">Costo:</span> ${{ registro.costo }}
                </div>
                <div class="text-right">
                  <p class="text-[11px] font-black text-slate-900 dark:text-white uppercase tracking-wide">{{ formatFechaHoraLocal(registro.fecha_accion) }}</p>
                  <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wide">{{ registro.usuario?.nombre || 'SISTEMA' }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="p-4 bg-[var(--ui-surface)]/50 text-center">
          <Link :href="`/herramientas/${props.herramienta.id}/estadisticas`" class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wide hover:underline">Ver Historial Completo y Gráficos de Rendimiento →</Link>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: all 0.4s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
