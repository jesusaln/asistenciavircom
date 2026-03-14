<template>
  <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 overflow-hidden shadow-2xl shadow-slate-900/5">
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left border-separate border-spacing-0">
        <thead>
          <tr class="bg-slate-50/50 dark:bg-slate-950/50 transition-colors duration-300">
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">Código Operativo</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">Nombre del Kit / Descripción</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-center">Configuración</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-right">Comercialización</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-center">Estatus</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-right">Gestión</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
          <tr v-if="loading" v-for="i in 5" :key="i" class="animate-pulse">
              <td colspan="6" class="px-8 py-10">
                  <div class="h-4 bg-slate-100 dark:bg-slate-800 rounded-lg w-full"></div>
              </td>
          </tr>
          <tr v-else v-for="kit in items" :key="kit.id" class="group/row hover:bg-slate-50/80 dark:hover:bg-slate-800/30 transition-all duration-300">
            <!-- Código -->
            <td class="px-8 py-6">
                <div class="flex flex-col">
                    <span class="text-xs font-black text-blue-600 tracking-widest">{{ kit.codigo || 'N/A' }}</span>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Expediente: #{{ kit.id.toString().padStart(4, '0') }}</span>
                </div>
            </td>

            <!-- Nombre -->
            <td class="px-8 py-6">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover/row:text-blue-500 transition-colors overflow-hidden shrink-0">
                        <img v-if="kit.imagen" referrerpolicy="no-referrer" :src="kit.imagen.startsWith('http') ? kit.imagen : '/storage/' + kit.imagen" class="w-full h-full object-cover" />
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <div class="flex flex-col max-w-[300px]">
                        <span class="text-sm font-black text-slate-900 dark:text-white uppercase truncate tracking-tight group-hover/row:text-blue-600 dark:group-hover/row:text-blue-400 transition-colors">{{ kit.nombre }}</span>
                        <span class="text-[10px] font-bold text-slate-400 truncate opacity-70 leading-relaxed uppercase">{{ kit.descripcion || 'Sin descripción técnica' }}</span>
                    </div>
                </div>
            </td>

            <!-- Configuración -->
            <td class="px-8 py-6 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full border border-slate-200 dark:border-slate-700">
                    <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                    <span class="text-[9px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest">{{ kit.componentes_count }} Componentes</span>
                </div>
            </td>

            <!-- Precio -->
            <td class="px-8 py-6 text-right">
                <div class="flex flex-col">
                    <span class="text-sm font-black text-slate-900 dark:text-white tracking-widest">${{ formatCurrency(kit.precio_venta) }}</span>
                    <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Precio Especial</span>
                </div>
            </td>

            <!-- Estado -->
            <td class="px-8 py-6 text-center">
                <span :class="[
                  'inline-flex items-center px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest transition-all',
                  kit.estado === 'activo' 
                    ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20' 
                    : 'bg-slate-500/10 text-slate-500 border border-slate-500/20'
                ]">
                  {{ kit.estado === 'activo' ? 'Vigente' : 'Suspendido' }}
                </span>
            </td>

            <!-- Acciones -->
            <td class="px-8 py-6 text-right">
                <div class="flex items-center justify-end gap-2 text-red-600">
                    <button @click="$emit('ver', kit.id)" class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-blue-500 rounded-xl transition-all active:scale-90" title="Detalle Expandido">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                    
                    <Link :href="`/kits/${kit.id}/edit`" class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-amber-500 rounded-xl transition-all active:scale-90" title="Actualizar Configuración">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </Link>

                    <button @click="$emit('eliminar', kit.id)" class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-500 rounded-xl transition-all active:scale-90" title="Baja de Kit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && items.length === 0" class="flex flex-col items-center justify-center py-20 bg-white/50 dark:bg-slate-900/50 backdrop-blur-xl">
        <div class="w-20 h-20 rounded-3xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
        </div>
        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Catálogo Vacío</h3>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">No se han localizado kits con los criterios actuales</p>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  items: { type: Array, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits(['ver', 'eliminar'])

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0)
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.2); }
</style>
