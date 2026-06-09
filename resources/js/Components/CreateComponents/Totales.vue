<template>
  <!-- Calculadora de Márgenes -->
  <div v-if="showMarginCalculator" class="bg-slate-900 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden mb-8 transition-all duration-500">
    <div class="bg-gradient-to-r from-amber-500/20 to-amber-600/20 border-b border-amber-500/20 px-6 py-5">
      <div class="flex justify-between items-center">
        <h2 class="text-lg font-bold text-amber-500 flex items-center tracking-tight">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
          Calculadora de Márgenes Premium
        </h2>
      <button @click="toggleMarginCalculator" type="button" class="text-amber-500/50 hover:text-amber-500 bg-amber-500/10 p-2 rounded-xl transition-all">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>
    <div class="p-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-slate-950/50 p-6 rounded-2xl border border-slate-800">
          <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Costo Total</div>
          <div class="text-2xl font-black text-white font-mono">
            ${{ marginData.costoTotal.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
          </div>
        </div>
        <div class="bg-slate-950/50 p-6 rounded-2xl border border-slate-800">
          <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Precio de Venta</div>
          <div class="text-2xl font-black text-emerald-400 font-mono">
            ${{ marginData.precioVenta.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
          </div>
        </div>
        <div class="bg-slate-950/50 p-6 rounded-2xl border border-slate-800">
          <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Margen Bruto</div>
          <div class="text-2xl font-black text-indigo-400 font-mono">
            {{ marginData.margenPorcentaje.toFixed(1) }}%
          </div>
        </div>
        <div class="bg-slate-950/50 p-6 rounded-2xl border border-slate-800">
          <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Ganancia</div>
          <div class="text-2xl font-black text-amber-500 font-mono">
            ${{ marginData.ganancia.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Resumen Total -->
  <div class="bg-slate-900 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden mt-8">
    <div class="bg-gradient-to-r from-indigo-600/20 to-indigo-700/20 border-b border-indigo-500/20 px-8 py-5">
      <h2 class="text-lg font-bold text-white flex items-center tracking-tight">
        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
        </svg>
        Resumen Financiero
      </h2>
    </div>
    <div class="p-6">
      <!-- Estadísticas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="text-center p-6 bg-slate-950/50 rounded-2xl border border-slate-800/50 group hover:border-indigo-500/30 transition-all">
          <div class="text-3xl font-black text-indigo-500 mb-1 font-mono tracking-tighter">{{ itemCount }}</div>
          <div class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Items Seleccionados</div>
        </div>
        <div class="text-center p-6 bg-slate-950/50 rounded-2xl border border-slate-800/50 group hover:border-emerald-500/30 transition-all">
          <div class="text-3xl font-black text-emerald-500 mb-1 font-mono tracking-tighter">{{ totalQuantity }}</div>
          <div class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Unidades Totales</div>
        </div>
        <div class="text-center p-6 bg-indigo-600/10 rounded-2xl border border-indigo-500/20 group hover:bg-indigo-600/20 transition-all">
          <div class="text-3xl font-black text-white mb-1 font-mono tracking-tighter">${{ totals.total.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</div>
          <div class="text-[10px] text-indigo-400 font-black uppercase tracking-widest">Importe Total</div>
        </div>
      </div>

      <!-- Desglose de totales simplificado -->
      <div class="bg-slate-950/50 rounded-2xl p-8 border border-slate-800">
        <h3 class="text-sm font-black text-slate-500 uppercase tracking-widest mb-6 border-b border-slate-800 pb-4">Desglose de Operación</h3>
        <div class="space-y-4">
          <div class="flex justify-between items-center text-slate-400">
            <span class="text-xs font-bold uppercase tracking-wider">Subtotal:</span>
            <span class="font-black text-white font-mono">${{ totals.subtotal.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div v-if="totals.descuentoItems > 0" class="flex justify-between items-center text-amber-500/80">
            <span class="text-xs font-bold uppercase tracking-wider">Descuentos por Items:</span>
            <span class="font-black font-mono">-${{ totals.descuentoItems.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div v-if="totals.descuentoGeneral > 0" class="flex justify-between items-center text-rose-500/80">
            <span class="text-xs font-bold uppercase tracking-wider">Descuento General:</span>
            <span class="font-black font-mono">-${{ totals.descuentoGeneral.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div v-if="(totals.descuentoItems > 0 || totals.descuentoGeneral > 0)" class="flex justify-between items-center text-slate-400 border-t border-slate-800 pt-4">
            <span class="text-xs font-bold uppercase tracking-wider">Subtotal c/ Dctos:</span>
            <span class="font-black text-white font-mono">${{ totals.subtotalConDescuentos.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div class="flex justify-between items-center text-indigo-400">
            <span class="text-xs font-bold uppercase tracking-wider">IVA ({{ ivaPorcentaje }}%):</span>
            <span class="font-black font-mono">${{ totals.iva.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div v-if="totals.isr > 0" class="flex justify-between items-center text-amber-500/80">
            <span class="text-xs font-bold uppercase tracking-wider">ISR (PM):</span>
            <span class="font-black font-mono">-${{ totals.isr.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
 
          <!-- REtención IVA -->
          <div v-if="enableRetencionIva" class="flex justify-between items-center text-amber-500/80">
            <label class="flex items-center gap-2 cursor-pointer group">
              <input 
                type="checkbox" 
                :checked="aplicarRetencionIva" 
                @change="$emit('update:aplicarRetencionIva', $event.target.checked)"
                class="rounded-lg bg-slate-900 border-slate-700 text-indigo-500 shadow-sm focus:ring-indigo-500/50"
              >
              <span class="text-xs font-bold uppercase tracking-wider group-hover:text-amber-500 transition-colors">Retención IVA ({{ retencionIvaDefault }}%):</span>
            </label>
            <span v-if="aplicarRetencionIva && totals.retencion_iva > 0" class="font-black font-mono">-${{ totals.retencion_iva.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
            <span v-else class="text-slate-600 font-mono italic">$0.00</span>
          </div>
 
          <!-- Retención ISR -->
          <div v-if="enableRetencionIsr" class="flex justify-between items-center text-amber-500/80">
             <label class="flex items-center gap-2 cursor-pointer group">
              <input 
                type="checkbox" 
                :checked="aplicarRetencionIsr" 
                @change="$emit('update:aplicarRetencionIsr', $event.target.checked)"
                class="rounded-lg bg-slate-900 border-slate-700 text-indigo-500 shadow-sm focus:ring-indigo-500/50"
              >
              <span class="text-xs font-bold uppercase tracking-wider group-hover:text-amber-500 transition-colors">Retención ISR ({{ retencionIsrDefault }}%):</span>
            </label>
            <span v-if="aplicarRetencionIsr && totals.retencion_isr > 0" class="font-black font-mono">-${{ totals.retencion_isr.toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
             <span v-else class="text-slate-600 font-mono italic">$0.00</span>
          </div>
          <div v-if="depositoGarantia && depositoGarantia > 0" class="flex justify-between items-center text-emerald-400">
            <span class="text-xs font-bold uppercase tracking-wider">Depósito de Garantía:</span>
            <span class="font-black font-mono">${{ Number(depositoGarantia).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}</span>
          </div>
          <div class="border-t-2 border-slate-800 pt-6 mt-6">
            <div class="flex justify-between items-end">
              <div>
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] block mb-1">Cifra Final</span>
                <span class="text-sm font-bold text-slate-400">Total a Pagar</span>
              </div>
              <div class="text-right">
                <span class="text-4xl font-black text-white font-mono tracking-tighter block mb-1">
                  ${{ (totals.total + Number(depositoGarantia || 0)).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </span>
                <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-500/20">Moneda Nacional (MXN)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  showMarginCalculator: Boolean,
  marginData: {
    type: Object,
    required: true,
    default: () => ({
      costoTotal: 0,
      precioVenta: 0,
      ganancia: 0,
      margenPorcentaje: 0
    })
  },
  totals: {
    type: Object,
    required: true,
    default: () => ({
      subtotal: 0,
      descuentoItems: 0,
      subtotalConDescuentos: 0,
      iva: 0,
      isr: 0,
      total: 0
    })
  },
  itemCount: {
    type: Number,
    default: 0
  },
  totalQuantity: {
    type: Number,
    default: 0
  },
  depositoGarantia: {
    type: [Number, String],
    default: 0
  },
  ivaPorcentaje: {
    type: Number,
    default: 16
  },
  isrPorcentaje: {
    type: Number,
    default: 1.25
  },
  enableRetencionIva: Boolean,
  enableRetencionIsr: Boolean,
  retencionIvaDefault: {
    type: Number,
    default: 0
  },
  retencionIsrDefault: {
    type: Number,
    default: 0
  },
  aplicarRetencionIva: Boolean,
  aplicarRetencionIsr: Boolean
});

const emit = defineEmits(['toggle-margin-calculator', 'update:aplicarRetencionIva', 'update:aplicarRetencionIsr']);

const toggleMarginCalculator = () => {
  emit('toggle-margin-calculator');
};
</script>

