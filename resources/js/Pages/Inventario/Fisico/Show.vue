<template>
  <AppLayout :title="audit.nombre">
    <template #header>
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
        <div class="flex items-center gap-6">
          <Link 
            :href="route('inventarios-fisicos.index')"
            class="w-12 h-12 rounded-2xl bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] flex items-center justify-center text-[var(--ui-text-muted)] hover:text-[var(--ui-accent)] hover:border-[var(--ui-accent)] transition-all duration-300 shadow-sm"
          >
            <font-awesome-icon icon="arrow-left" />
          </Link>
          <div>
            <div class="flex items-center gap-3">
              <h2 class="text-2xl font-black text-[var(--ui-text)] uppercase tracking-tight">{{ audit.nombre }}</h2>
              <span 
                class="px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border"
                :class="{
                  'bg-amber-500/10 border-amber-500/20 text-amber-500': audit.estado === 'borrador',
                  'bg-emerald-500/10 border-emerald-500/20 text-emerald-500': audit.estado === 'procesado',
                  'bg-rose-500/10 border-rose-500/20 text-rose-500': audit.estado === 'cancelado'
                }"
              >
                {{ audit.estado }}
              </span>
            </div>
            <p class="text-sm font-bold text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">{{ audit.almacen?.nombre }} — Iniciado el {{ new Date(audit.fecha_inicio).toLocaleString() }}</p>
          </div>
        </div>
        
        <div class="flex items-center gap-4" v-if="audit.estado === 'borrador'">
          <button 
            @click="procesarAuditoria"
            :disabled="isProcessing"
            class="premium-button flex items-center gap-3 px-8 py-4 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-black uppercase tracking-[0.2em] text-xs shadow-xl shadow-emerald-500/20 hover:scale-105 active:scale-95 disabled:opacity-50 transition-all duration-300"
          >
            <font-awesome-icon v-if="isProcessing" icon="sync-alt" spin />
            <template v-else>
              Finalizar y Ajustar Stock
              <font-awesome-icon icon="check-double" class="ml-2" />
            </template>
          </button>
        </div>
      </div>
    </template>

    <div class="px-6 pb-24">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-panel p-8 rounded-[2rem] border border-[var(--ui-border)] shadow-xl overflow-hidden relative group">
          <div class="absolute -right-8 -top-8 w-24 h-24 bg-blue-500/5 blur-3xl group-hover:bg-blue-500/10 transition-colors"></div>
          <span class="text-[9px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.3em]">Total Productos</span>
          <p class="text-3xl font-black text-[var(--ui-text)] mt-2">{{ audit.items.length }}</p>
        </div>
        
        <div class="glass-panel p-8 rounded-[2rem] border border-[var(--ui-border)] shadow-xl overflow-hidden relative group">
          <div class="absolute -right-8 -top-8 w-24 h-24 bg-emerald-500/5 blur-3xl group-hover:bg-emerald-500/10 transition-colors"></div>
          <span class="text-[9px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.3em]">Sin Diferencias</span>
          <p class="text-3xl font-black text-emerald-500 mt-2">{{ itemsSinDiferencia }}</p>
        </div>

        <div class="glass-panel p-8 rounded-[2rem] border border-[var(--ui-border)] shadow-xl overflow-hidden relative group">
          <div class="absolute -right-8 -top-8 w-24 h-24 bg-amber-500/5 blur-3xl group-hover:bg-amber-500/10 transition-colors"></div>
          <span class="text-[9px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.3em]">Con Faltantes</span>
          <p class="text-3xl font-black text-rose-500 mt-2">{{ itemsConFaltante }}</p>
        </div>

        <div class="glass-panel p-8 rounded-[2rem] border border-[var(--ui-border)] shadow-xl overflow-hidden relative group">
          <div class="absolute -right-8 -top-8 w-24 h-24 bg-purple-500/5 blur-3xl group-hover:bg-purple-500/10 transition-colors"></div>
          <span class="text-[9px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.3em]">Con Sobrantes</span>
          <p class="text-3xl font-black text-blue-500 mt-2">{{ itemsConSobrante }}</p>
        </div>
      </div>

      <!-- Inventory List Table -->
      <div class="glass-panel rounded-[2.5rem] overflow-hidden border border-[var(--ui-border)] shadow-2xl">
        <div class="p-8 border-b border-[var(--ui-border)]/50 bg-[var(--ui-surface-soft)]/30 flex justify-between items-center">
          <div class="relative w-full max-w-md">
             <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <font-awesome-icon icon="search" class="text-[var(--ui-text-muted)] text-xs" />
             </div>
             <input 
                v-model="searchQuery"
                type="text" 
                placeholder="BUSCAR PRODUCTO O SKU..." 
                class="w-full pl-10 pr-4 py-3 bg-[var(--ui-surface)] border border-[var(--ui-border)] rounded-xl text-[10px] font-black uppercase tracking-widest text-[var(--ui-text)] focus:border-[var(--ui-accent)] focus:ring-4 focus:ring-[var(--ui-accent)]/5 transition-all outline-none"
             >
          </div>
          
          <div class="flex items-center gap-4">
             <button @click="onlyDiffs = !onlyDiffs" :class="onlyDiffs ? 'bg-[var(--ui-accent)] text-white' : 'bg-[var(--ui-surface)] text-[var(--ui-text-muted)]'" class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest border border-[var(--ui-border)] transition-all">
                Solo Diferencias
             </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-[var(--ui-surface-soft)]/50 border-b border-[var(--ui-border)]">
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em]">Producto / SKU</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em] text-center">Stock Sistema</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em] text-center">Stock Físico</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em] text-center">Diferencia</th>
                <th class="px-8 py-6 text-[10px] font-black text-[var(--ui-text-muted)] uppercase tracking-[0.2em] text-center">Impacto</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ui-border)]/50">
              <tr v-for="item in filteredItems" :key="item.id" class="group hover:bg-[var(--ui-accent)]/[0.02] transition-colors duration-300">
                <td class="px-8 py-6">
                  <div class="flex flex-col">
                    <span class="text-sm font-black text-[var(--ui-text)] uppercase tracking-tight">{{ item.producto?.nombre }}</span>
                    <span class="text-[10px] font-bold text-[var(--ui-text-soft)] uppercase tracking-widest mt-1">{{ item.producto?.codigo }}</span>
                  </div>
                </td>
                <td class="px-8 py-6 text-center">
                  <span class="text-sm font-black text-[var(--ui-text-soft)]">{{ parseFloat(item.stock_sistema) }}</span>
                </td>
                <td class="px-8 py-6 text-center">
                  <div v-if="audit.estado === 'borrador'" class="flex items-center justify-center gap-2">
                     <input 
                        v-model="item.stock_fisico"
                        type="number"
                        @change="updatePhysicalStock(item)"
                        class="w-20 px-3 py-2 bg-[var(--ui-surface-soft)] border border-[var(--ui-border)] rounded-lg text-sm font-black text-center focus:border-[var(--ui-accent)] outline-none"
                     >
                  </div>
                  <span v-else class="text-sm font-black text-[var(--ui-text)]">{{ parseFloat(item.stock_fisico) }}</span>
                </td>
                <td class="px-8 py-6 text-center">
                   <div class="flex flex-col items-center">
                      <span 
                        class="text-sm font-black"
                        :class="{
                          'text-[var(--ui-text-muted)]': item.diferencia == 0,
                          'text-rose-500': item.diferencia < 0,
                          'text-blue-500': item.diferencia > 0
                        }"
                      >
                        {{ item.diferencia > 0 ? '+' : '' }}{{ parseFloat(item.diferencia) }}
                      </span>
                   </div>
                </td>
                <td class="px-8 py-6 text-center">
                   <div v-if="item.diferencia != 0" class="flex justify-center">
                      <div 
                        class="w-3 h-3 rounded-full animate-pulse"
                        :class="item.diferencia < 0 ? 'bg-rose-500' : 'bg-blue-500'"
                      ></div>
                   </div>
                   <font-awesome-icon v-else icon="check-circle" class="text-emerald-500/30" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { 
  faArrowLeft, faSearch, faCheckCircle, 
  faSyncAlt, faCheckDouble 
} from '@fortawesome/free-solid-svg-icons';
import { library } from '@fortawesome/fontawesome-svg-core';
import axios from 'axios';

library.add(faArrowLeft, faSearch, faCheckCircle, faSyncAlt, faCheckDouble);

const props = defineProps({
  audit: Object
});

const searchQuery = ref('');
const onlyDiffs = ref(false);
const isProcessing = ref(false);

const filteredItems = computed(() => {
  return props.audit.items.filter(item => {
    const matchesSearch = item.producto?.nombre?.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                         item.producto?.codigo?.toLowerCase().includes(searchQuery.value.toLowerCase());
    
    if (onlyDiffs.value) {
      return matchesSearch && Math.abs(item.diferencia) > 0.001;
    }
    return matchesSearch;
  });
});

const itemsSinDiferencia = computed(() => props.audit.items.filter(i => Math.abs(i.diferencia) <= 0.001).length);
const itemsConFaltante = computed(() => props.audit.items.filter(i => i.diferencia < -0.001).length);
const itemsConSobrante = computed(() => props.audit.items.filter(i => i.diferencia > 0.001).length);

const updatePhysicalStock = async (item) => {
  try {
    const response = await axios.post(route('inventarios-fisicos.update-item', { audit: props.audit.id, item: item.id }), {
      stock_fisico: item.stock_fisico
    });
    
    // El backend devuelve el item actualizado, podemos actualizar localmente la diferencia
    item.diferencia = response.data.item.diferencia;
    
    if (window.$toast) {
       window.$toast.info('CANTIDAD ACTUALIZADA');
    }
  } catch (error) {
    console.error('Error updating stock:', error);
    alert('Error al guardar el conteo.');
  }
};

const procesarAuditoria = () => {
  if (confirm('¿Estás seguro de finalizar esta auditoría? Se generarán movimientos de entrada/salida para ajustar el stock del sistema al stock físico contado.')) {
    isProcessing.value = true;
    router.post(route('inventarios-fisicos.procesar', props.audit.id), {}, {
      onFinish: () => isProcessing.value = false
    });
  }
};
</script>
