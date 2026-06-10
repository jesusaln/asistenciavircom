<template>
  <Head title="Registro de Vacaciones" />
  <div class="min-h-screen bg-[var(--ui-surface)]">
    <div class="w-full px-6 py-8">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Registro de Vacaciones</h1>
          <p class="text-slate-500">Resumen por empleado y año</p>
        </div>
        <div class="flex gap-3">
          <input type="number" v-model.number="anioLocal" class="border rounded-xl px-3 py-2 w-28" />
          <input type="text" v-model="searchLocal" placeholder="Buscar nombre, depto o puesto" class="border rounded-xl px-3 py-2 w-72" />
          <button @click="applyFilters" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700">Filtrar</button>
          <Link :href="route('registro-vacaciones.export', { anio: anioLocal, search: searchLocal })" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700">Exportar CSV</Link>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Empleado</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Departamento</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Puesto</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Año</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Correspondientes</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Disponibles</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Utilizados</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Pendientes</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase">Actualizado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="r in registros.data" :key="r.id" class="hover:bg-white">
                <td class="px-4 py-3 text-sm text-slate-900">
                  <Link :href="route('registro-vacaciones.por-empleado', r.user_id)" class="text-sky-800 dark:text-sky-200 hover:underline">
                    {{ r.empleado?.name || '—' }}
                  </Link>
                </td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ r.empleado?.departamento || '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-700">{{ r.empleado?.puesto || '—' }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ r.anio }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ r.dias_correspondientes }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ r.dias_disponibles }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ r.dias_utilizados }}</td>
                <td class="px-4 py-3 text-sm text-slate-900">{{ r.dias_pendientes }}</td>
                <td class="px-4 py-3 text-sm text-slate-500 text-right">{{ formatDate(r.updated_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="p-4 border-t border-slate-200 flex items-center justify-between text-sm text-slate-500">
          <div>
            Mostrando {{ registros.from }} - {{ registros.to }} de {{ registros.total }}
          </div>
          <div class="flex gap-2">
            <button @click="go(registros.prev_page_url)" :disabled="!registros.prev_page_url" class="px-3 py-1 border rounded-xl disabled:opacity-50">Anterior</button>
            <button @click="go(registros.next_page_url)" :disabled="!registros.next_page_url" class="px-3 py-1 border rounded-xl disabled:opacity-50">Siguiente</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  anio: Number,
  search: String,
  sorting: Object,
  registros: Object,
})

const anioLocal = ref(props.anio)
const searchLocal = ref(props.search || '')

const applyFilters = () => {
  router.get(route('registro-vacaciones.index'), { anio: anioLocal.value, search: searchLocal.value, page: 1 }, { preserveState: true, preserveScroll: true })
}

const formatDate = (date) => {
  try { return new Date(date).toLocaleString('es-MX') } catch { return date }
}

const go = (url) => { if (url) router.visit(url, { preserveScroll: true }) }
</script>

<style scoped>
</style>


