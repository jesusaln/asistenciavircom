<template>
  <Head title="Auditoría de Actividades" />
  <div class="min-h-screen bg-[var(--ui-surface)] py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight">Auditoría Automática</h1>
          <p class="mt-2 text-sm text-slate-500">Registro automático de acciones realizadas en el sistema.</p>
        </div>
        <button 
          @click="confirmClear"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-2xl shadow-sm text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Limpiar Bitácora
        </button>
      </div>

      <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-slate-200">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Usuario</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Actividad / Acción</th>
                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Fecha y Hora</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="log in auditorias.data" :key="log.id" class="hover:bg-slate-50 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="h-8 w-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 text-xs font-bold">
                      {{ log.usuario ? log.usuario.charAt(0).toUpperCase() : 'S' }}
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-semibold text-slate-900">{{ log.usuario || 'Sistema' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-slate-700 font-medium">{{ log.actividad }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-xs text-slate-500 font-mono">{{ formatDate(log.created_at) }}</div>
                </td>
              </tr>
              <tr v-if="auditorias.data.length === 0">
                <td colspan="3" class="px-6 py-12 text-center">
                  <div class="flex flex-col items-center">
                    <svg class="h-12 w-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-slate-500 text-sm font-medium">No hay registros en la bitácora aún.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Paginación -->
        <div v-if="auditorias.links.length > 3" class="px-6 py-4 bg-[var(--ui-surface)] border-t border-slate-200 flex items-center justify-between">
            <div class="flex-1 flex justify-between sm:hidden">
                <Link v-if="auditorias.prev_page_url" :href="auditorias.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50"> Anterior </Link>
                <Link v-if="auditorias.next_page_url" :href="auditorias.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50"> Siguiente </Link>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-slate-700">
                        Mostrando <span class="font-bold">{{ auditorias.from }}</span> a <span class="font-bold">{{ auditorias.to }}</span> de <span class="font-bold">{{ auditorias.total }}</span> registros
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px">
                        <template v-for="(link, k) in auditorias.links" :key="k">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                class="relative inline-flex items-center px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-500 hover:bg-slate-50"
                                :class="{'bg-sky-50 dark:bg-sky-900/20 border-blue-500 text-blue-600 z-10': link.active}"
                            />
                            <span v-else v-html="link.label" class="relative inline-flex items-center px-4 py-2 border border-slate-300 bg-slate-100 text-sm font-medium text-slate-400 cursor-not-allowed"></span>
                        </template>
                    </nav>
                </div>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Swal from 'sweetalert2'

defineOptions({ layout: AppLayout })

const props = defineProps({
  auditorias: Object
})

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleString('es-MX', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const confirmClear = () => {
  Swal.fire({
    title: '¿Limpiar bitácora?',
    text: "Esta acción eliminará todos los registros de actividad. No se puede deshacer.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, borrar todo',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      router.delete(route('bitacora.clear'), {
        onSuccess: () => {
          Swal.fire('¡Borrado!', 'La bitácora ha sido limpiada.', 'success')
        }
      })
    }
  })
}
</script>
