<template>
  <Teleport to="body">
    <transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-950/60 backdrop-blur-sm" @click.self="close">
        <div class="w-full max-w-5xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-200/50 dark:border-slate-800/50 overflow-hidden flex flex-col max-h-[90vh]">
          
          <!-- Sticky Header -->
          <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl z-20">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-2xl bg-blue-600/10 flex items-center justify-center text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              </div>
              <div>
                <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-900 dark:text-white">Previsualización de Documento</h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ documentTypeLabel }} Operativo</p>
              </div>
            </div>
            <button @click="close" class="w-10 h-10 flex items-center justify-center bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-400 hover:text-rose-500 transition-all">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Document Body -->
          <div class="flex-1 overflow-y-auto p-10 custom-scrollbar space-y-10">
            <!-- Header Section -->
            <div class="text-center space-y-2">
                <span class="text-[10px] font-black uppercase tracking-[0.4em] text-blue-600 dark:text-blue-400">Canal de Documentación Oficial</span>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">{{ documentTypeLabel }}</h1>
                <div class="flex items-center justify-center gap-4">
                    <div class="h-px w-12 bg-slate-200 dark:bg-slate-800"></div>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ currentDate }}</span>
                    <div class="h-px w-12 bg-slate-200 dark:bg-slate-800"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <!-- Entity Info -->
                <div class="bg-slate-50/50 dark:bg-slate-950/30 rounded-[2rem] p-8 border border-slate-200/50 dark:border-slate-800/50">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-4">Información del Receptor</span>
                    <div v-if="cliente" class="space-y-4">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Nombre / Razón Social</p>
                                <p class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ cliente.nombre_razon_social || cliente.nombre }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-6 pt-2">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Contacto Digital</p>
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ cliente.email || 'SIN EMAIL' }}</p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Ubicación / Fiscal</p>
                                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 line-clamp-2 uppercase">{{ cliente.direccion || 'SIN DOMICILIO' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Document Context (Metadata) -->
                <div class="bg-slate-50/50 dark:bg-slate-950/30 rounded-[2rem] p-8 border border-slate-200/50 dark:border-slate-800/50">
                     <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-4">Metadatos de Operación</span>
                     <div class="grid grid-cols-2 gap-8" v-if="ordenData">
                          <div>
                              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">N° Documento</p>
                              <p class="text-sm font-black text-blue-600 tracking-widest">{{ ordenData.numero_orden || 'PENDIENTE' }}</p>
                          </div>
                          <div>
                              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Entrega Est.</p>
                              <p class="text-sm font-black text-slate-900 dark:text-white">{{ ordenData.fecha_entrega_esperada || 'INMEDIATA' }}</p>
                          </div>
                          <div>
                              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Términos Pago</p>
                              <p class="text-[11px] font-black text-slate-900 dark:text-white uppercase">{{ ordenData.terminos_pago?.replace('_', ' ') || 'CONTADO' }}</p>
                          </div>
                          <div>
                              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Prioridad</p>
                              <div class="flex items-center gap-2 mt-1">
                                  <div class="w-2 h-2 rounded-full" :class="ordenData.prioridad === 'alta' || ordenData.prioridad === 'urgente' ? 'bg-rose-500' : 'bg-blue-500'"></div>
                                  <p class="text-[11px] font-black text-slate-900 dark:text-white uppercase">{{ ordenData.prioridad || 'MEDIA' }}</p>
                              </div>
                          </div>
                     </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden bg-white dark:bg-slate-950/20">
              <table class="w-full text-left border-separate border-spacing-0">
                <thead class="bg-slate-50 dark:bg-slate-950">
                  <tr>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Descripción Técnica</th>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Cant</th>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Unitario</th>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Dcto</th>
                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Importe</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/40">
                  <tr v-for="item in items" :key="item.id" class="group/row">
                    <td class="px-8 py-5 text-sm font-bold text-slate-900 dark:text-white uppercase tracking-tight">{{ item.nombre || item.descripcion }}</td>
                    <td class="px-8 py-5 text-sm font-black text-slate-500 dark:text-slate-400 text-center tracking-widest">{{ item.cantidad }}</td>
                    <td class="px-8 py-5 text-sm font-black text-slate-500 dark:text-slate-400 text-right tracking-widest">${{ (item.precio || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</td>
                    <td class="px-8 py-5 text-xs font-black text-amber-600 dark:text-amber-500 text-right tracking-widest">{{ item.descuento }}%</td>
                    <td class="px-8 py-5 text-sm font-black text-slate-900 dark:text-white text-right tracking-widest">
                       ${{ ((item.cantidad * item.precio) - (item.cantidad * item.precio * item.descuento / 100)).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                    </td>
                  </tr>
                </tbody>
              </table>
              
              <!-- Summary Sub-table -->
              <div class="p-10 bg-slate-50 dark:bg-slate-950/60 border-t border-slate-100 dark:border-slate-800/40 flex flex-col md:flex-row justify-between gap-10">
                  <div class="flex-1 max-w-md">
                      <div v-if="notas" class="space-y-2">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Observaciones Especiales</span>
                        <p class="text-[11px] font-bold text-slate-600 dark:text-slate-300 leading-relaxed uppercase">{{ notas }}</p>
                      </div>
                  </div>
                  <div class="space-y-4 min-w-[300px]">
                      <div class="flex justify-between items-center text-[11px] font-black uppercase text-slate-400 tracking-widest">
                          <span>Subtotal Lineal</span>
                          <span class="text-slate-600 dark:text-slate-300">${{ totals.subtotal.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                      </div>
                      <div v-if="totals.descuentoItems > 0 || totals.descuentoGeneral > 0" class="flex justify-between items-center text-[11px] font-black uppercase text-rose-500 tracking-widest animate-pulse">
                          <span>Incentivos / Dctos</span>
                          <span>-${{ (totals.descuentoItems + totals.descuentoGeneral).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                      </div>
                      <div class="flex justify-between items-center text-[11px] font-black uppercase text-slate-400 tracking-widest">
                          <span>Impuestos Trasl. (16%)</span>
                          <span class="text-blue-600">${{ totals.iva.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                      </div>
                      <div v-if="totals.retencion_iva > 0 || totals.retencion_isr > 0" class="flex justify-between items-center text-[11px] font-black uppercase text-indigo-500 tracking-widest">
                          <span>Retenciones Fiscales</span>
                          <span>-${{ (totals.retencion_iva + totals.retencion_isr).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                      </div>
                      <div class="h-px bg-slate-200 dark:bg-slate-800 my-4"></div>
                      <div class="flex justify-between items-center text-lg font-black uppercase text-slate-900 dark:text-white tracking-[0.2em]">
                          <span class="text-xs">Monto Liquidación</span>
                          <span class="text-blue-600">${{ totals.total.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
                      </div>
                  </div>
              </div>
            </div>
          </div>

          <!-- Footer Actions -->
          <div class="p-8 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-4 bg-slate-50/50 dark:bg-slate-950/20">
            <button @click="close" class="px-8 py-4 bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-300 transition-all">Cerrar</button>
            <button @click="imprimir" class="px-8 py-4 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-500/20 hover:shadow-blue-500/40 hover:-translate-y-1 transition-all">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Emitir / Imprimir
                </div>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  type: {
    type: String,
    required: true,
    validator: (v) => ['cotizacion', 'pedido', 'venta', 'compra', 'ordenescompra', 'renta'].includes(v)
  },
  cliente: { type: Object, default: null },
  items: { type: Array, default: () => [] },
  totals: { type: Object, required: true },
  descuentoGeneral: { type: Number, default: 0 },
  notas: { type: String, default: '' },
  ordenData: { type: Object, default: null },
  depositoGarantia: { type: [Number, String], default: 0 }
});

const emit = defineEmits(['close', 'print']);

const documentTypeLabel = computed(() => {
  const labels = {
    cotizacion: 'Cotización',
    pedido: 'Pedido',
    venta: 'Venta',
    compra: 'Compra',
    ordenescompra: 'Orden de Compra',
    renta: 'Contrato de Renta'
  };
  return labels[props.type] || 'Documento';
});

const currentDate = computed(() => {
  return new Date().toLocaleDateString('es-MX', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

const close = () => emit('close');
const imprimir = () => emit('print');
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(59, 130, 246, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(59, 130, 246, 0.2); }
</style>
