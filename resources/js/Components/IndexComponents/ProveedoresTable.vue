<template>
  <div class="proveedores-table-container">
    
    <!-- Table Container -->
    <div class="overflow-x-auto rounded-[2.5rem] bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl border border-slate-200/50 dark:border-slate-800/50 shadow-2xl shadow-slate-200/10 dark:shadow-none">
      <table class="min-w-full border-separate border-spacing-y-2 px-6 pb-6">
        <thead>
          <tr class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-400 dark:text-slate-500">
            <th class="px-6 py-8 text-left cursor-pointer hover:text-blue-600 transition-colors group" @click="onSort('created_at')">
                <div class="flex items-center gap-2">
                    REGISTRO
                    <svg v-if="sortBy.startsWith('created_at')" :class="['w-3 h-3 transition-transform duration-300', sortBy.endsWith('desc') ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </th>
            <th class="px-6 py-8 text-left cursor-pointer hover:text-blue-600 transition-colors group" @click="onSort('nombre_razon_social')">
                <div class="flex items-center gap-2">
                    PROVEEDOR
                    <svg v-if="sortBy.startsWith('nombre_razon_social')" :class="['w-3 h-3 transition-transform duration-300', sortBy.endsWith('desc') ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </th>
            <th class="px-6 py-8 text-left">RFC / CONTACTO</th>
            <th class="px-6 py-8 text-left">ESTADO</th>
            <th class="px-6 py-8 text-right">ACCIONES</th>
          </tr>
        </thead>

        <tbody class="space-y-4">
          <template v-if="items.length > 0">
            <tr
              v-for="prov in items"
              :key="prov.id"
              class="group hover:scale-[1.01] transition-all duration-500 ease-out"
              :class="!prov.activo ? 'opacity-50' : ''"
            >
              <!-- Fecha -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 first:rounded-l-[2rem] border-y border-l border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <div class="flex flex-col">
                  <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ formatearFecha(prov.created_at) }}</span>
                  <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Registrado</span>
                </div>
              </td>

              <!-- Proveedor -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 border-y border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500 dark:text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition-all duration-500 uppercase">
                        {{ (prov.nombre_razon_social || '?').charAt(0) }}
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight max-w-[200px] truncate">{{ prov.nombre_razon_social || 'Desconocido' }}</span>
                        <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">{{ prov.email || 'SIN EMAIL' }}</span>
                    </div>
                </div>
              </td>

              <!-- RFC / Contacto -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 border-y border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <div class="flex flex-col">
                    <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-[10px] font-black text-slate-900 dark:text-white font-mono w-fit mb-1">{{ prov.rfc || 'XAXX010101000' }}</span>
                    <span class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">{{ prov.telefono || 'SIN TELÉFONO' }}</span>
                </div>
              </td>

              <!-- Estado -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 border-y border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900">
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full" :class="prov.activo ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                    <span 
                        :class="[
                            'text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md border transition-all duration-300',
                            prov.activo ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 border-rose-500/20'
                        ]"
                    >
                        {{ prov.activo ? 'ACTIVO' : 'INACTIVO' }}
                    </span>
                </div>
              </td>

              <!-- Acciones -->
              <td class="px-6 py-5 bg-white dark:bg-slate-900/50 last:rounded-r-[2rem] border-y border-r border-slate-200/50 dark:border-slate-800/80 group-hover:border-blue-500/20 group-hover:bg-slate-50 dark:group-hover:bg-slate-900 text-right">
                <div class="flex items-center justify-end gap-1">
                    <button @click="onVerDetalles(prov)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-blue-600 hover:text-white transition-all duration-300" title="Detalles">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button @click="onEditar(prov.id)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-amber-500 hover:text-white transition-all duration-300" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button @click="onToggle(prov.id)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-green-600 hover:text-white transition-all duration-300" title="Activar/Desactivar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                    <button @click="onEliminar(prov.id)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:bg-rose-500 hover:text-white transition-all duration-300" title="Eliminar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
              </td>
            </tr>
          </template>

          <tr v-else>
            <td colspan="5" class="text-center py-20 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 shadow-2xl">
              <div class="flex flex-col items-center">
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-[2rem] flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                </div>
                <p class="text-sm font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">No hay proveedores disponibles</p>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest mt-2">Prueba cambiando los filtros de búsqueda</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  items: { type: Array, default: () => [] },
  sortBy: { type: String, default: 'created_at-desc' }
})

const emit = defineEmits(['ver-detalles', 'editar', 'eliminar', 'toggle', 'sort'])

const formatearFecha = (date) => {
  if (!date) return '---'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return 'Inválida'
  }
}

const onVerDetalles = (prov) => emit('ver-detalles', { raw: prov })
const onEditar = (id) => emit('editar', id)
const onEliminar = (id) => emit('eliminar', id)
const onToggle = (id) => emit('toggle', id)
const onSort = (field) => {
  const direction = props.sortBy === `${field}-desc` ? 'asc' : 'desc'
  emit('sort', `${field}-${direction}`)
}
</script>

<style scoped>
.proveedores-table-container {
  overflow: visible;
}
</style>
