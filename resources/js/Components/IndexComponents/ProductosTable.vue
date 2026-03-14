<template>
  <div class="group relative bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 overflow-hidden shadow-2xl shadow-slate-900/5">
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full text-left border-separate border-spacing-0">
        <thead>
          <tr class="bg-slate-50/50 dark:bg-slate-950/50 transition-colors duration-300">
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">Expediente</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">Especificación Técnica</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800">Código / SAT</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-right">Comercialización</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-center">Nivel Stock</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-center">Visibilidad</th>
            <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 dark:border-slate-800 text-right">Gestión</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
          <tr v-for="producto in items" :key="producto.id" class="group/row hover:bg-slate-50/80 dark:hover:bg-slate-800/30 transition-all duration-300">
            <!-- Fecha / Expediente -->
            <td class="px-8 py-6">
                <div class="flex flex-col">
                    <span class="text-xs font-black text-slate-900 dark:text-white tracking-widest">{{ formatearFecha(producto.raw.created_at) }}</span>
                    <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">ID: #{{ producto.id.toString().padStart(4, '0') }}</span>
                </div>
            </td>

            <!-- Producto -->
            <td class="px-8 py-6">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover/row:text-blue-500 transition-colors overflow-hidden shrink-0">
                        <img v-if="producto.raw.imagen" referrerpolicy="no-referrer" :src="producto.raw.imagen.startsWith('http') ? producto.raw.imagen : '/storage/' + producto.raw.imagen" class="w-full h-full object-cover" />
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <div class="flex flex-col max-w-[200px]">
                        <span class="text-sm font-black text-slate-900 dark:text-white uppercase truncate tracking-tight group-hover/row:text-blue-600 dark:group-hover/row:text-blue-400 transition-colors">{{ producto.titulo }}</span>
                        <span class="text-[10px] font-bold text-slate-400 truncate opacity-70 leading-relaxed uppercase">{{ producto.subtitulo }}</span>
                    </div>
                </div>
            </td>

            <!-- Código / SAT -->
            <td class="px-8 py-6">
                <div class="flex flex-col gap-1">
                    <span class="text-[11px] font-black text-slate-600 dark:text-slate-300 tracking-widest uppercase">{{ producto.raw.codigo || 'N/A' }}</span>
                    <button 
                        @click="$emit('open-sat', producto.raw)"
                        class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-md inline-block w-fit transition-all"
                        :class="producto.raw.sat_clave_prod_serv ? 'bg-blue-500/10 text-blue-500 hover:bg-blue-500/20' : 'bg-rose-500/10 text-rose-500 hover:bg-rose-500/20'"
                    >
                        SAT: {{ producto.raw.sat_clave_prod_serv || 'SIN CLAVE' }}
                    </button>
                </div>
            </td>

            <!-- Precio -->
            <td class="px-8 py-6 text-right">
                <div class="flex flex-col">
                    <span class="text-sm font-black text-slate-900 dark:text-white tracking-widest">${{ formatNumber(producto.raw.precio_venta || 0) }}</span>
                    <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">Liquidación</span>
                </div>
            </td>

            <!-- Stock -->
            <td class="px-8 py-6 text-center">
                <div class="flex flex-col items-center">
                    <span 
                        @click="$emit('ver-stock', producto.raw)"
                        class="text-sm font-black cursor-pointer hover:underline transition-all tracking-widest"
                        :class="producto.raw.stock > 0 ? 'text-blue-600' : 'text-rose-500'"
                    >
                        {{ producto.raw.stock || 0 }}
                    </span>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Existencia</span>
                </div>
            </td>

            <!-- Inicio (Destacado) -->
            <td class="px-8 py-6 text-center">
                <button
                    @click="$emit('toggle-destacado', producto.id)"
                    class="transition-all duration-300 transform"
                    :class="producto.raw.destacado ? 'text-amber-500 scale-125' : 'text-slate-200 dark:text-slate-800 hover:text-amber-300 scale-100'"
                >
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </button>
            </td>

            <!-- Acciones -->
            <td class="px-8 py-6 text-right">
                <div class="flex items-center justify-end gap-2">
                    <button @click="$emit('ver', producto)" class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-blue-500 rounded-xl transition-all active:scale-90" title="Detalles">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    </button>
                    
                    <button v-if="hasSeries(producto.raw)"
                            @click="$emit('ver-series', producto.raw)"
                            class="relative w-10 h-10 flex items-center justify-center rounded-xl transition-all active:scale-90"
                            :class="faltanSeries(producto.raw) > 0 ? 'bg-amber-500/10 text-amber-500 hover:bg-amber-500/20' : 'bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500/20'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-7 4h8M5 8h14" /></svg>
                        <span v-if="faltanSeries(producto.raw) > 0" class="absolute -top-1 -right-1 bg-amber-500 text-white text-[8px] font-black rounded-full w-4 h-4 flex items-center justify-center shadow-lg">{{ faltanSeries(producto.raw) }}</span>
                    </button>

                    <button @click="$emit('editar', producto.id)" class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-amber-500 rounded-xl transition-all active:scale-90" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </button>

                    <button v-if="producto.estado !== 'activo'" @click="$emit('confirmar-eliminacion', producto.id)" class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-500 rounded-xl transition-all active:scale-90" title="Eliminar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-20 bg-white/50 dark:bg-slate-900/50 backdrop-blur-xl">
        <div class="w-20 h-20 rounded-3xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-300 mb-6">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
        </div>
        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Registros no localizados</h3>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-2">No hay productos que coincidan con los criterios</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  items: { type: Array, required: true },
  formatearFecha: { type: Function, required: true },
  formatNumber: { type: Function, required: true },
  hasSeries: { type: Function, required: true },
  faltanSeries: { type: Function, required: true },
})

defineEmits(['ver', 'editar', 'confirmar-eliminacion', 'toggle-destacado', 'open-sat', 'ver-stock', 'ver-series'])
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.2); }
</style>
