<template>
  <div class="relative group bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] border border-slate-200/50 dark:border-slate-800/50 overflow-hidden transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5">
    
    <!-- Table Content -->
    <div class="overflow-x-auto custom-scrollbar">
      <table class="w-full border-separate border-spacing-0">
        <thead>
          <tr class="bg-slate-50/50 dark:bg-slate-950/50">
            <th @click="$emit('sort', 'fecha')" class="px-8 py-6 text-left cursor-pointer group/th">
              <div class="flex items-center gap-3">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover/th:text-blue-500 transition-colors">Fecha</span>
                <div v-if="sortBy.startsWith('fecha')" class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
              </div>
            </th>
            <th @click="$emit('sort', 'proveedor')" class="px-8 py-6 text-left cursor-pointer group/th">
              <div class="flex items-center gap-3">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover/th:text-blue-500 transition-colors">Proveedor</span>
                <div v-if="sortBy.startsWith('proveedor')" class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
              </div>
            </th>
            <th @click="$emit('sort', 'numero_orden')" class="px-8 py-6 text-left cursor-pointer group/th">
              <div class="flex items-center gap-3">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover/th:text-blue-500 transition-colors">N° Orden</span>
                <div v-if="sortBy.startsWith('numero_orden')" class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
              </div>
            </th>
            <th @click="$emit('sort', 'total')" class="px-8 py-6 text-left cursor-pointer group/th">
              <div class="flex items-center gap-3">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover/th:text-blue-500 transition-colors">Total</span>
                <div v-if="sortBy.startsWith('total')" class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
              </div>
            </th>
            <th class="px-8 py-6 text-left">
              <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Items</span>
            </th>
            <th @click="$emit('sort', 'estado')" class="px-8 py-6 text-left cursor-pointer group/th">
              <div class="flex items-center gap-3">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 group-hover/th:text-blue-500 transition-colors">Estado</span>
                <div v-if="sortBy.startsWith('estado')" class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
              </div>
            </th>
            <th class="px-8 py-6 text-right">
              <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Acciones</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40">
          <tr v-for="doc in items" :key="doc.id" 
              class="group/row hover:bg-blue-500/[0.02] transition-all duration-300"
              :class="{ 'opacity-50 grayscale': doc.estado === 'cancelada' }">
            
            <!-- Fecha -->
            <td class="px-8 py-6">
              <div class="flex flex-col">
                <span class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                  {{ formatearFecha(doc.created_at || doc.fecha) }}
                </span>
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-0.5">
                  {{ formatearHora(doc.created_at || doc.fecha) }}
                </span>
              </div>
            </td>

            <!-- Proveedor -->
            <td class="px-8 py-6">
              <div class="flex flex-col max-w-[200px]">
                <span class="text-sm font-black text-slate-900 dark:text-white uppercase truncate tracking-tight">
                  {{ doc.proveedor?.nombre_razon_social || 'SIN PROVEEDOR' }}
                </span>
                <span class="text-[10px] font-bold text-slate-400 truncate tracking-tight lowercase">
                  {{ doc.proveedor?.email || 'N/A' }}
                </span>
              </div>
            </td>

            <!-- N° Orden -->
            <td class="px-8 py-6">
              <div class="inline-flex items-center px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-[10px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest">
                {{ doc.numero_orden || 'N/A' }}
              </div>
            </td>

            <!-- Total -->
            <td class="px-8 py-6">
              <span class="text-sm font-black text-slate-900 dark:text-white tracking-widest">
                ${{ formatearMoneda(doc.total) }}
              </span>
            </td>

            <!-- Items (Productos) -->
            <td class="px-8 py-6">
                <div 
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-500/5 dark:bg-blue-500/10 rounded-xl cursor-help hover:bg-blue-500/10 dark:hover:bg-blue-500/20 transition-all border border-blue-500/10 dark:border-blue-500/20"
                    @mouseenter="$emit('show-tooltip', doc, $event)"
                    @mouseleave="$emit('hide-tooltip')"
                    @mousemove="$emit('update-tooltip', $event)"
                >
                    <div class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
                    <span class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">
                        {{ doc.productos?.length || doc.items?.length || 0 }} Items
                    </span>
                </div>
            </td>

            <!-- Estado -->
            <td class="px-8 py-6">
              <span 
                :class="obtenerClasesEstado(doc.estado)"
                class="inline-flex items-center px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-[0.2em] transition-all"
              >
                <div class="w-1.5 h-1.5 rounded-full mr-2.5 shadow-sm" :class="obtenerColorPuntoEstado(doc.estado)"></div>
                {{ obtenerLabelEstado(doc.estado) }}
              </span>
            </td>

            <!-- Acciones -->
            <td class="px-8 py-6 text-right">
              <div class="flex items-center justify-end gap-2 opacity-0 group-hover/row:opacity-100 transition-all duration-300 translate-x-4 group-hover/row:translate-x-0">
                <!-- Ver -->
                <button 
                  @click="$emit('ver', doc)"
                  class="w-10 h-10 flex items-center justify-center bg-white dark:bg-slate-800 text-slate-400 hover:text-blue-500 rounded-xl transition-all shadow-sm border border-slate-200 dark:border-slate-700 hover:border-blue-500/30"
                  title="Detalles"
                >
                  <font-awesome-icon icon="eye" class="w-3.5 h-3.5" />
                </button>
                
                <!-- Editar -->
                <button 
                  v-if="['borrador', 'pendiente', 'aprobada', 'enviado_a_proveedor'].includes(doc.estado)"
                  @click="$emit('editar', doc.id)"
                  class="w-10 h-10 flex items-center justify-center bg-white dark:bg-slate-800 text-slate-400 hover:text-amber-500 rounded-xl transition-all shadow-sm border border-slate-200 dark:border-slate-700 hover:border-amber-500/30"
                  title="Editar"
                >
                  <font-awesome-icon icon="edit" class="w-3.5 h-3.5" />
                </button>

                <!-- Email -->
                <button 
                  v-if="doc.proveedor?.email"
                  @click="$emit('enviar-email', doc)"
                  class="w-10 h-10 flex items-center justify-center bg-white dark:bg-slate-800 text-slate-400 hover:text-emerald-500 rounded-xl transition-all shadow-sm border border-slate-200 dark:border-slate-700 hover:border-emerald-500/30"
                  :title="doc.email_enviado ? 'Reenviar Email' : 'Enviar Email'"
                >
                  <font-awesome-icon :icon="doc.email_enviado ? 'envelope-open' : 'envelope'" class="w-3.5 h-3.5" />
                </button>

                <!-- Convertir Directo -->
                <button 
                  v-if="doc.estado === 'pendiente'"
                  @click="$emit('convertir', doc)"
                  class="w-10 h-10 flex items-center justify-center bg-white dark:bg-slate-800 text-slate-400 hover:text-purple-500 rounded-xl transition-all shadow-sm border border-slate-200 dark:border-slate-700 hover:border-purple-500/30"
                  title="Convertir a Compra"
                >
                  <font-awesome-icon icon="exchange-alt" class="w-3.5 h-3.5" />
                </button>

                <!-- Cancelar -->
                <button 
                  v-if="['pendiente', 'aprobada', 'enviado_a_proveedor'].includes(doc.estado)"
                  @click="$emit('cancelar', doc.id)"
                  class="w-10 h-10 flex items-center justify-center bg-white dark:bg-slate-800 text-slate-400 hover:text-rose-500 rounded-xl transition-all shadow-sm border border-slate-200 dark:border-slate-700 hover:border-rose-500/30"
                  title="Cancelar"
                >
                  <font-awesome-icon icon="times-circle" class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div v-if="items.length === 0" class="flex flex-col items-center justify-center py-20 px-6">
      <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800/50 rounded-[2.5rem] flex items-center justify-center mb-6 border border-slate-200 dark:border-slate-700">
        <svg class="w-10 h-10 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-[0.2em] mb-2">Canal Silencioso</h3>
      <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest text-center max-w-[280px]">No se encontraron órdenes de compra que coincidan con los criterios aplicados.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
  items: { type: Array, required: true },
  sortBy: { type: String, default: 'fecha-desc' },
  obtenerClasesEstado: { type: Function, required: true },
  obtenerColorPuntoEstado: { type: Function, required: true },
  obtenerLabelEstado: { type: Function, required: true },
  formatearFecha: { type: Function, required: true },
  formatearHora: { type: Function, required: true },
  formatearMoneda: { type: Function, required: true },
})

defineEmits(['sort', 'ver', 'editar', 'enviar-email', 'convertir', 'cancelar', 'show-tooltip', 'hide-tooltip', 'update-tooltip'])
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.2); }
</style>
