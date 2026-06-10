<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  herramientas: { type: Object, required: true },
})

const items = computed(() => props.herramientas?.data || [])

const getAlertasPorTipo = () => {
  const alertas = {
    mantenimiento_urgente: [],
    mantenimiento_proximo: [],
    vida_util_vencida: [],
    vida_util_proxima: [],
    herramientas_perdidas: [],
    herramientas_sin_categoria: [],
  }

  items.value.forEach(herramienta => {
    // Mantenimiento urgente (más de los días establecidos)
    if (herramienta.requiere_mantenimiento && herramienta.necesita_mantenimiento) {
      alertas.mantenimiento_urgente.push(herramienta)
    }

    // Mantenimiento próximo (más del 80% de los días establecidos)
    if (herramienta.requiere_mantenimiento &&
        herramienta.dias_desde_ultimo_mantenimiento >= (herramienta.dias_para_mantenimiento * 0.8) &&
        !herramienta.necesita_mantenimiento) {
      alertas.mantenimiento_proximo.push(herramienta)
    }

    // Vida útil vencida (más del 100% de la vida útil)
    if (herramienta.porcentaje_vida_util > 100) {
      alertas.vida_util_vencida.push(herramienta)
    }

    // Vida útil próxima a vencer (más del 80% de la vida útil)
    if (herramienta.porcentaje_vida_util >= 80 && herramienta.porcentaje_vida_util <= 100) {
      alertas.vida_util_proxima.push(herramienta)
    }

    // Herramientas perdidas
    if (herramienta.estado === 'perdida') {
      alertas.herramientas_perdidas.push(herramienta)
    }

    // Herramientas sin categoría
    if (!herramienta.categoria_id && !herramienta.categoria) {
      alertas.herramientas_sin_categoria.push(herramienta)
    }
  })

  return alertas
}

const alertas = computed(() => getAlertasPorTipo())

const getTipoAlertaColor = (tipo) => {
  const colors = {
    mantenimiento_urgente: 'bg-rose-100 border-rose-500 text-rose-800 dark:text-rose-200',
    mantenimiento_proximo: 'bg-brand-100 border-brand-500 text-orange-800',
    vida_util_vencida: 'bg-rose-100 border-rose-500 text-rose-800 dark:text-rose-200',
    vida_util_proxima: 'bg-brand-100 border-brand-500 text-brand-800 dark:text-amber-200',
    herramientas_perdidas: 'bg-rose-100 border-rose-500 text-rose-800 dark:text-rose-200',
    herramientas_sin_categoria: 'bg-sky-100 border-blue-500 text-sky-800 dark:text-sky-200',
  }
  return colors[tipo] || 'bg-slate-100 border-slate-500 text-slate-800'
}

const getTipoAlertaIcon = (tipo) => {
  const icons = {
    mantenimiento_urgente: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
    mantenimiento_proximo: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    vida_util_vencida: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
    vida_util_proxima: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    herramientas_perdidas: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
    herramientas_sin_categoria: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
  }
  return icons[tipo] || 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('es-ES')
}

const getTotalAlertas = () => {
  return Object.values(alertas.value).reduce((total, lista) => total + lista.length, 0)
}
</script>

<template>
  <Head title="Alertas de Herramientas" />

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-3xl font-bold text-slate-900">Alertas de Herramientas</h1>
    <div class="flex gap-3">
      <Link class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700" :href="route('herramientas.dashboard')">
        Dashboard
      </Link>
      <Link class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700" :href="route('herramientas.mantenimiento')">
        Gestionar Mantenimiento
      </Link>
    </div>
  </div>

  <!-- Resumen de alertas -->
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <div class="bg-white p-4 rounded-2xl shadow-sm border-l-4 border-rose-500">
      <div class="text-2xl font-bold text-rose-600">{{ alertas.mantenimiento_urgente.length }}</div>
      <div class="text-sm text-slate-500">Mant. Urgente</div>
    </div>
    <div class="bg-white p-4 rounded-2xl shadow-sm border-l-4 border-orange-500">
      <div class="text-2xl font-bold text-orange-600">{{ alertas.mantenimiento_proximo.length }}</div>
      <div class="text-sm text-slate-500">Mant. Próximo</div>
    </div>
    <div class="bg-white p-4 rounded-2xl shadow-sm border-l-4 border-rose-500">
      <div class="text-2xl font-bold text-rose-600">{{ alertas.vida_util_vencida.length }}</div>
      <div class="text-sm text-slate-500">Vida Útil Vencida</div>
    </div>
    <div class="bg-white p-4 rounded-2xl shadow-sm border-l-4 border-brand-500">
      <div class="text-2xl font-bold text-amber-600">{{ alertas.vida_util_proxima.length }}</div>
      <div class="text-sm text-slate-500">Vida Útil Próxima</div>
    </div>
    <div class="bg-white p-4 rounded-2xl shadow-sm border-l-4 border-rose-500">
      <div class="text-2xl font-bold text-rose-600">{{ alertas.herramientas_perdidas.length }}</div>
      <div class="text-sm text-slate-500">Perdidas</div>
    </div>
    <div class="bg-white p-4 rounded-2xl shadow-sm border-l-4 border-blue-500">
      <div class="text-2xl font-bold text-blue-600">{{ alertas.herramientas_sin_categoria.length }}</div>
      <div class="text-sm text-slate-500">Sin Categoría</div>
    </div>
  </div>

  <!-- Lista de alertas por tipo -->
  <div class="space-y-6">
    <!-- Mantenimiento Urgente -->
    <div v-if="alertas.mantenimiento_urgente.length > 0" class="bg-white rounded-2xl shadow-sm border">
      <div class="p-6 border-b border-rose-200 dark:border-rose-800/30">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-rose-800 dark:text-rose-200">Mantenimiento Urgente</h2>
            <p class="text-sm text-rose-600">{{ alertas.mantenimiento_urgente.length }} herramientas requieren mantenimiento inmediato</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="herramienta in alertas.mantenimiento_urgente" :key="herramienta.id" class="p-4 border border-rose-200 dark:border-rose-800/30 rounded-xl bg-rose-50 dark:bg-rose-900/20">
            <div class="flex items-center gap-2 mb-2">
              <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded-xl" />
              <div>
                <h3 class="font-medium text-rose-800 dark:text-rose-200">{{ herramienta.nombre }}</h3>
                <p class="text-sm text-rose-600">{{ herramienta.numero_serie }}</p>
              </div>
            </div>
            <div class="text-sm text-rose-800 dark:text-rose-200 dark:text-rose-200">
              <p><strong>Días desde último mantenimiento:</strong> {{ herramienta.dias_desde_ultimo_mantenimiento || 'N/A' }}</p>
              <p><strong>Días requeridos:</strong> {{ herramienta.dias_para_mantenimiento || 'N/A' }}</p>
            </div>
            <div class="mt-3 flex gap-2">
              <Link :href="route('herramientas.show', herramienta.id)" class="text-xs bg-rose-600 text-white px-2 py-1 rounded-xl hover:bg-rose-700">
                Ver Detalles
              </Link>
              <Link :href="route('herramientas.edit', herramienta.id)" class="text-xs bg-blue-600 text-white px-2 py-1 rounded-xl hover:bg-blue-700">
                Editar
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mantenimiento Próximo -->
    <div v-if="alertas.mantenimiento_proximo.length > 0" class="bg-white rounded-2xl shadow-sm border">
      <div class="p-6 border-b border-orange-200">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-orange-800">Mantenimiento Próximo</h2>
            <p class="text-sm text-orange-600">{{ alertas.mantenimiento_proximo.length }} herramientas requieren mantenimiento pronto</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="herramienta in alertas.mantenimiento_proximo" :key="herramienta.id" class="p-4 border border-orange-200 rounded-xl bg-orange-50">
            <div class="flex items-center gap-2 mb-2">
              <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded-xl" />
              <div>
                <h3 class="font-medium text-orange-800">{{ herramienta.nombre }}</h3>
                <p class="text-sm text-orange-600">{{ herramienta.numero_serie }}</p>
              </div>
            </div>
            <div class="text-sm text-orange-700">
              <p><strong>Días restantes:</strong> {{ herramienta.dias_para_proximo_mantenimiento || 'N/A' }}</p>
              <p><strong>Último mantenimiento:</strong> {{ formatDate(herramienta.fecha_ultimo_mantenimiento) }}</p>
            </div>
            <div class="mt-3">
              <Link :href="route('herramientas.show', herramienta.id)" class="text-xs bg-brand-600 text-white px-2 py-1 rounded-xl hover:bg-orange-700">
                Ver Detalles
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Vida útil próxima a vencer -->
    <div v-if="alertas.vida_util_proxima.length > 0" class="bg-white rounded-2xl shadow-sm border">
      <div class="p-6 border-b border-brand-200 dark:border-brand-800/30">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-brand-800 dark:text-amber-200">Vida Útil Próxima a Vencer</h2>
            <p class="text-sm text-amber-600">{{ alertas.vida_util_proxima.length }} herramientas requieren atención</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="herramienta in alertas.vida_util_proxima" :key="herramienta.id" class="p-4 border border-brand-200 dark:border-brand-800/30 rounded-xl bg-brand-50 dark:bg-brand-900/20">
            <div class="flex items-center gap-2 mb-2">
              <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded-xl" />
              <div>
                <h3 class="font-medium text-brand-800 dark:text-amber-200">{{ herramienta.nombre }}</h3>
                <p class="text-sm text-amber-600">{{ herramienta.numero_serie }}</p>
              </div>
            </div>
            <div class="text-sm text-brand-800 dark:text-brand-200 dark:text-amber-200">
              <p><strong>Vida útil utilizada:</strong> {{ herramienta.porcentaje_vida_util || 0 }}%</p>
              <p><strong>Último mantenimiento:</strong> {{ formatDate(herramienta.fecha_ultimo_mantenimiento) }}</p>
            </div>
            <div class="mt-3">
              <Link :href="route('herramientas.show', herramienta.id)" class="text-xs bg-brand-600 text-white px-2 py-1 rounded-xl hover:bg-amber-700">
                Ver Detalles
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Herramientas perdidas -->
    <div v-if="alertas.herramientas_perdidas.length > 0" class="bg-white rounded-2xl shadow-sm border">
      <div class="p-6 border-b border-rose-200 dark:border-rose-800/30">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-rose-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-rose-800 dark:text-rose-200">Herramientas Perdidas</h2>
            <p class="text-sm text-rose-600">{{ alertas.herramientas_perdidas.length }} herramientas reportadas como perdidas</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="herramienta in alertas.herramientas_perdidas" :key="herramienta.id" class="p-4 border border-rose-200 dark:border-rose-800/30 rounded-xl bg-rose-50 dark:bg-rose-900/20">
            <div class="flex items-center gap-2 mb-2">
              <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded-xl" />
              <div>
                <h3 class="font-medium text-rose-800 dark:text-rose-200">{{ herramienta.nombre }}</h3>
                <p class="text-sm text-rose-600">{{ herramienta.numero_serie }}</p>
              </div>
            </div>
            <div class="mt-3">
              <Link :href="route('herramientas.show', herramienta.id)" class="text-xs bg-rose-600 text-white px-2 py-1 rounded-xl hover:bg-rose-700">
                Ver Detalles
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Herramientas sin categoría -->
    <div v-if="alertas.herramientas_sin_categoria.length > 0" class="bg-white rounded-2xl shadow-sm border">
      <div class="p-6 border-b border-sky-200 dark:border-sky-800/30">
        <div class="flex items-center gap-2">
          <div class="w-10 h-10 bg-sky-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
          </div>
          <div>
            <h2 class="text-xl font-semibold text-sky-800 dark:text-sky-200">Herramientas sin Categoría</h2>
            <p class="text-sm text-blue-600">{{ alertas.herramientas_sin_categoria.length }} herramientas necesitan ser categorizadas</p>
          </div>
        </div>
      </div>
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="herramienta in alertas.herramientas_sin_categoria" :key="herramienta.id" class="p-4 border border-sky-200 dark:border-sky-800/30 rounded-xl bg-sky-50 dark:bg-sky-900/20">
            <div class="flex items-center gap-2 mb-2">
              <img v-if="herramienta.foto" :src="`/storage/${herramienta.foto}`" alt="Foto" class="w-10 h-10 object-cover rounded-xl" />
              <div>
                <h3 class="font-medium text-sky-800 dark:text-sky-200">{{ herramienta.nombre }}</h3>
                <p class="text-sm text-blue-600">{{ herramienta.numero_serie }}</p>
              </div>
            </div>
            <div class="mt-3">
              <Link :href="route('herramientas.edit', herramienta.id)" class="text-xs bg-blue-600 text-white px-2 py-1 rounded-xl hover:bg-blue-700">
                Categorizar
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Sin alertas -->
  <div v-if="getTotalAlertas() === 0" class="bg-white rounded-2xl shadow-sm border p-12 text-center">
    <svg class="w-16 h-16 text-emerald-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <h2 class="text-xl font-semibold text-slate-900 mb-2">¡Todo en orden!</h2>
    <p class="text-slate-500">No hay alertas pendientes en este momento.</p>
  </div>
</template>

