<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  poliza: Object,
  documento: Object, // Mantener por compatibilidad
  documentos: {
    type: Array,
    default: () => []
  }
})

// Usar documentos si existe, si no el documento único
const documentosAMostrar = computed(() => {
  if (props.documentos && props.documentos.length > 0) return props.documentos
  return props.documento ? [props.documento] : []
})

const totalDebe = computed(() => {
  if (!props.poliza?.asientos) return 0
  return props.poliza.asientos.reduce((acc, a) => acc + parseFloat(a.debe || 0), 0)
})

const totalHaber = computed(() => {
  if (!props.poliza?.asientos) return 0
  return props.poliza.asientos.reduce((acc, a) => acc + parseFloat(a.haber || 0), 0)
})

const diferencia = computed(() => {
  return Math.abs(totalDebe.value - totalHaber.value)
})

const estaCuadrada = computed(() => diferencia.value < 0.01)

const getTipoColor = (tipo) => {
  const colors = { 
    ingreso: 'bg-emerald-500 text-white', 
    egreso: 'bg-rose-500 text-white', 
    diario: 'bg-slate-500 text-white' 
  }
  return colors[tipo] || 'bg-slate-500 text-white'
}
</script>

<template>
  <Head :title="'Póliza ' + poliza.numero" />

  <div class="py-12 px-4 sm:px-6 max-w-7xl mx-auto">
    <!-- Header de la Póliza -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
      <div class="flex items-center gap-4">
        <Link :href="route('contabilidad.index')" class="p-2.5 bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-100 dark:border-slate-700 text-slate-500 hover:text-brand-600 transition-colors">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        </Link>
        <div>
          <div class="flex items-center gap-3 mb-1">
            <span :class="getTipoColor(poliza.tipo)" class="px-2 py-0.5 text-[10px] font-black uppercase rounded shadow-sm">
              {{ poliza.tipo }}
            </span>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white leading-tight">
              Póliza {{ poliza.numero }}
            </h1>
          </div>
          <p class="text-slate-500 font-medium">{{ poliza.concepto }}</p>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div class="px-6 py-3 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
          <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Fecha Contable</p>
          <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
            {{ new Date(poliza.fecha).toLocaleDateString(undefined, { dateStyle: 'long' }) }}
          </p>
        </div>
        <div v-if="!estaCuadrada" class="px-6 py-3 bg-rose-50 dark:bg-rose-900/20 rounded-2xl shadow-sm border border-rose-100 dark:border-rose-800">
          <p class="text-[10px] font-black text-rose-500 uppercase tracking-widest mb-1">Estado</p>
          <p class="text-sm font-bold text-rose-600 dark:text-rose-400 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
            Descuadrada
          </p>
        </div>
        <div v-else class="px-6 py-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl shadow-sm border border-emerald-100 dark:border-emerald-800">
          <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Estado</p>
          <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">Cuadrada</p>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Tabla de Asientos -->
      <div class="lg:col-span-2 space-y-6">
        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-50 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50">
            <h3 class="font-black text-slate-900 dark:text-white uppercase tracking-wider text-sm">Asientos Contables</h3>
          </div>
          <table class="w-full text-left">
            <thead class="bg-slate-50/50 dark:bg-slate-900/30 text-slate-400 text-[10px] font-black uppercase tracking-widest">
              <tr>
                <th class="px-6 py-3">Cuenta / Código</th>
                <th class="px-6 py-3 text-right">Debe</th>
                <th class="px-6 py-3 text-right">Haber</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-700">
              <template v-if="poliza.asientos && poliza.asientos.length > 0">
                <tr v-for="a in poliza.asientos" :key="a.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-900/10 transition-colors">
                  <td class="px-6 py-4">
                    <div class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ a.cuenta?.nombre || 'S/N' }}</div>
                    <div class="text-[10px] font-mono text-slate-400">{{ a.cuenta?.codigo || '-' }}</div>
                    <div v-if="a.referencia" class="mt-1 text-[10px] text-slate-400 italic">{{ a.referencia }}</div>
                  </td>
                  <td class="px-6 py-4 text-right font-mono text-sm" :class="a.debe > 0 ? 'text-slate-900 dark:text-white font-bold' : 'text-slate-300'">
                    ${{ Number(a.debe).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                  </td>
                  <td class="px-6 py-4 text-right font-mono text-sm" :class="a.haber > 0 ? 'text-slate-900 dark:text-white font-bold' : 'text-slate-300'">
                    ${{ Number(a.haber).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                  </td>
                </tr>
              </template>
              <tr v-else>
                <td colspan="3" class="px-6 py-20 text-center">
                  <p class="text-slate-400 text-sm font-bold uppercase tracking-widest">Esta póliza no tiene asientos contables</p>
                  <p class="text-[10px] text-slate-400 mt-2">Puede ser una póliza de cierre o hubo un error en la integración.</p>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-slate-50 dark:bg-slate-900 font-bold border-t-2 border-slate-100 dark:border-slate-700">
              <tr>
                <td class="px-6 py-5 text-right text-[10px] uppercase tracking-widest text-slate-400">Totales Póliza</td>
                <td class="px-6 py-5 text-right font-mono text-lg text-slate-900 dark:text-white">${{ totalDebe.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
                <td class="px-6 py-5 text-right font-mono text-lg text-slate-900 dark:text-white">${{ totalHaber.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</td>
              </tr>
              <tr v-if="!estaCuadrada" class="bg-rose-50 dark:bg-rose-900/20">
                <td class="px-6 py-3 text-right text-[10px] uppercase tracking-widest text-rose-500">Diferencia (Descuadre)</td>
                <td colspan="2" class="px-6 py-3 text-right font-mono text-lg text-rose-600 font-black">
                  ${{ diferencia.toLocaleString(undefined, {minimumFractionDigits: 2}) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="flex items-center gap-4 text-xs text-slate-400 italic px-4">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          Creada por {{ poliza.creador?.name }} el {{ new Date(poliza.created_at).toLocaleString() }}
        </div>
      </div>

      <!-- Columna Derecha: Tesorería y XML Origen -->
      <div class="space-y-6">
        <!-- Trazabilidad de Tesorería (Anexo 24 / Bancos) -->
        <div v-if="poliza.movimiento_bancario || poliza.movimientoBancario || poliza.metodo_pago_sat" class="bg-gradient-to-br from-slate-900 to-indigo-950 border border-indigo-500/20 rounded-3xl p-6 text-white shadow-2xl relative overflow-hidden group">
          <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl group-hover:scale-125 transition-transform duration-700"></div>
          <div class="relative z-10 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
              <h3 class="text-xs font-black uppercase tracking-widest text-indigo-400 flex items-center gap-2">
                <span class="p-1 rounded bg-indigo-500/20">🏦</span>
                Trazabilidad Tesorería (Anexo 24)
              </h3>
              <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-black uppercase">Vinculado</span>
            </div>

            <div class="space-y-3 font-medium text-xs text-slate-300">
              <div v-if="poliza.movimiento_bancario || poliza.movimientoBancario" class="p-3 bg-white/5 rounded-2xl border border-white/5 space-y-1">
                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">Cuenta Bancaria</p>
                <p class="text-sm text-white font-black">{{ (poliza.movimiento_bancario || poliza.movimientoBancario).cuenta_bancaria?.nombre_banco }} - {{ (poliza.movimiento_bancario || poliza.movimientoBancario).cuenta_bancaria?.numero_cuenta }}</p>
                <p class="text-[11px] text-indigo-300 font-mono" v-if="(poliza.movimiento_bancario || poliza.movimientoBancario).cuenta_bancaria?.alias">Alias: {{ (poliza.movimiento_bancario || poliza.movimientoBancario).cuenta_bancaria?.alias }}</p>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">Método Pago SAT</p>
                  <p class="text-xs text-white font-mono font-bold mt-0.5">{{ poliza.metodo_pago_sat || (poliza.movimiento_bancario || poliza.movimientoBancario)?.forma_pago_sat || '03 - Transferencia' }}</p>
                </div>
                <div>
                  <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">Clave Rastreo SPEI</p>
                  <p class="text-xs text-white font-mono font-bold mt-0.5 truncate">{{ poliza.clave_spei_rastreo || (poliza.movimiento_bancario || poliza.movimientoBancario)?.referencia || 'S/N' }}</p>
                </div>
              </div>

              <div v-if="poliza.rfc_tercero || (poliza.movimiento_bancario || poliza.movimientoBancario)?.beneficiario_rfc">
                <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">RFC Tercero / Beneficiario</p>
                <p class="text-xs text-emerald-400 font-mono font-bold mt-0.5">{{ poliza.rfc_tercero || (poliza.movimiento_bancario || poliza.movimientoBancario)?.beneficiario_rfc }}</p>
              </div>
            </div>

            <div class="pt-2">
              <Link :href="route('bancos.index')" class="inline-flex items-center justify-center w-full px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-black text-[10px] uppercase tracking-widest text-white gap-2 transition-all shadow-lg shadow-indigo-600/30">
                <span>Ir a Módulo de Bancos / Tesorería</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
              </Link>
            </div>
          </div>
        </div>

        <template v-if="documentosAMostrar.length > 0">
          <div v-for="(doc, index) in documentosAMostrar" :key="doc.uuid" class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-50 dark:border-slate-700 bg-brand-50/30 dark:bg-brand-900/10">
              <h3 class="font-black text-brand-600 uppercase tracking-wider text-sm flex justify-between items-center">
                <span>Documento Origen {{ documentosAMostrar.length > 1 ? (index + 1) : '' }} ({{ doc.relacion }})</span>
              </h3>
            </div>
            <div class="p-6 space-y-6">
              <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">UUID del SAT</p>
                <p class="text-xs font-mono break-all text-slate-600 dark:text-slate-300 leading-relaxed">{{ doc.uuid }}</p>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Emisor</p>
                  <p class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">{{ doc.emisor }}</p>
                </div>
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Receptor</p>
                  <p class="text-sm font-bold text-slate-700 dark:text-slate-200 truncate">{{ doc.receptor }}</p>
                </div>
              </div>

              <div class="p-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl space-y-3">
                <div class="flex justify-between items-center text-xs">
                  <span class="text-slate-500">Subtotal XML</span>
                  <span class="font-bold text-slate-700 dark:text-slate-200 font-mono">${{ doc.subtotal.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                  <span class="text-slate-500">IVA (16%) XML</span>
                  <span class="font-bold text-slate-700 dark:text-slate-200 font-mono">${{ doc.iva.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                </div>
                <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                  <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Total XML</span>
                  <span class="text-lg font-black text-slate-900 dark:text-white font-mono">${{ doc.total.toLocaleString(undefined, {minimumFractionDigits: 2}) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Resumen de comparación (si hay varios) -->
          <div v-if="documentosAMostrar.length > 1" class="p-4 rounded-3xl border-2" :class="Math.abs(documentosAMostrar.reduce((acc, d) => acc + d.total, 0) - totalDebe) < 0.01 ? 'border-emerald-100 bg-emerald-50/30 dark:border-emerald-900/30 dark:bg-emerald-900/10' : 'border-rose-100 bg-rose-50/30 dark:border-rose-900/30 dark:bg-rose-900/10'">
            <p class="text-[10px] font-black uppercase tracking-widest mb-2" :class="Math.abs(documentosAMostrar.reduce((acc, d) => acc + d.total, 0) - totalDebe) < 0.01 ? 'text-emerald-600' : 'text-rose-600'">
              Comparativa Global ({{ documentosAMostrar.length }} CFDI)
            </p>
            <div class="flex justify-between items-center">
              <span class="text-xs text-slate-500 italic">Diferencia vs Póliza:</span>
              <span class="text-sm font-black font-mono" :class="Math.abs(documentosAMostrar.reduce((acc, d) => acc + d.total, 0) - totalDebe) < 0.01 ? 'text-emerald-600' : 'text-rose-600'">
                ${{ Math.abs(documentosAMostrar.reduce((acc, d) => acc + d.total, 0) - totalDebe).toLocaleString(undefined, {minimumFractionDigits: 2}) }}
              </span>
            </div>
          </div>
        </template>

        <div v-else class="p-8 bg-slate-50 dark:bg-slate-900/50 rounded-3xl border border-dashed border-slate-200 dark:border-slate-700 text-center">
          <svg class="w-12 h-12 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          <p class="text-sm text-slate-500">Esta póliza no está vinculada a un documento XML directo.</p>
        </div>
      </div>
    </div>
  </div>
</template>
