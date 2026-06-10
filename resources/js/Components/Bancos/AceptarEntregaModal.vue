<template>
  <div v-if="show" class="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4 overflow-y-auto" @click.self="closeModal">
    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-4xl overflow-hidden border border-slate-100 dark:border-slate-700 my-8 flex flex-col max-h-[90vh] transition-all animate-modal-pop">
      <!-- Header Premium -->
      <div class="p-8 bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-900 text-white flex justify-between items-start shrink-0">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-black uppercase tracking-widest text-indigo-100 mb-3 shadow-inner">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
            Recepción en Tesorería
          </div>
          <h3 class="text-3xl font-black uppercase tracking-tight">Confirmar Depósito de Entrega #{{ entrega?.id }}</h3>
          <p class="text-indigo-200 text-sm mt-1 font-medium">Asigna la chequera destino, ajusta la fecha y hora exacta, y revisa el rastreo de ventas.</p>
        </div>
        <button @click="closeModal" class="p-3 hover:bg-white/10 rounded-full transition-colors text-indigo-200 hover:text-white">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>

      <!-- Body -->
      <div v-if="entrega" class="p-8 overflow-y-auto space-y-8 custom-scrollbar grow">
        <!-- Tarjeta de Totales -->
        <div class="bg-slate-50 dark:bg-slate-900/60 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-sm">
          <div>
            <span class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Monto Total a Acreditar</span>
            <div class="text-4xl font-black text-indigo-600 dark:text-indigo-400 mt-1">${{ formatNumber(entrega.total) }}</div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-bold">Responsable de entrega: {{ entrega.usuario }}</p>
          </div>

          <!-- Desglose por método -->
          <div class="flex flex-wrap gap-3">
            <div v-if="entrega.monto_efectivo > 0" class="px-4 py-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 rounded-2xl border border-emerald-200 dark:border-emerald-800/60 text-xs font-bold">
              💵 Efectivo: ${{ formatNumber(entrega.monto_efectivo) }}
            </div>
            <div v-if="entrega.monto_transferencia > 0" class="px-4 py-2 bg-sky-50 dark:bg-sky-950/40 text-sky-700 dark:text-sky-300 rounded-2xl border border-sky-200 dark:border-sky-800/60 text-xs font-bold">
              💸 Transferencia: ${{ formatNumber(entrega.monto_transferencia) }}
            </div>
            <div v-if="entrega.monto_cheques > 0" class="px-4 py-2 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 rounded-2xl border border-amber-200 dark:border-amber-800/60 text-xs font-bold">
              📑 Cheques: ${{ formatNumber(entrega.monto_cheques) }}
            </div>
            <div v-if="entrega.monto_tarjetas > 0" class="px-4 py-2 bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 rounded-2xl border border-purple-200 dark:border-purple-800/60 text-xs font-bold">
              💳 Tarjetas: ${{ formatNumber(entrega.monto_tarjetas) }}
            </div>
          </div>
        </div>

        <!-- Opciones de Depósito (Cuenta Destino y Fecha) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Cuenta Destino -->
          <div class="space-y-2">
            <label class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest block flex items-center gap-2">
              <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
              Cuenta Bancaria Destino <span class="text-rose-500">*</span>
            </label>
            <select v-model="form.cuenta_id" class="w-full bg-slate-50 dark:bg-slate-900/80 border-2 border-slate-200 dark:border-slate-700 rounded-2xl p-4 text-sm font-black focus:border-indigo-500 outline-none transition-all dark:text-white cursor-pointer shadow-sm">
              <option :value="null" disabled>— Selecciona cuenta destino —</option>
              <option v-for="cuenta in cuentas" :key="cuenta.id" :value="cuenta.id">
                🏦 {{ cuenta.alias || cuenta.nombre_banco }} ({{ cuenta.numero_cuenta || 'S/N' }}) — Saldo: ${{ formatNumber(cuenta.saldo_actual ?? cuenta.saldo_inicial) }}
              </option>
            </select>
          </div>

          <!-- Fecha y Hora Exacta -->
          <div class="space-y-2">
            <label class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest block flex items-center gap-2">
              <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              Fecha y Hora Real de Depósito <span class="text-rose-500">*</span>
            </label>
            <input type="datetime-local" v-model="form.fecha_hora" class="w-full bg-slate-50 dark:bg-slate-900/80 border-2 border-slate-200 dark:border-slate-700 rounded-2xl p-4 text-sm font-black focus:border-indigo-500 outline-none transition-all dark:text-white shadow-sm" />
          </div>
        </div>

        <!-- Notas / Concepto -->
        <div class="space-y-2">
          <label class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-widest block">Referencia / Notas del Depósito</label>
          <textarea v-model="form.notas" rows="2" class="w-full bg-slate-50 dark:bg-slate-900/80 border-2 border-slate-200 dark:border-slate-700 rounded-2xl p-4 text-sm font-medium focus:border-indigo-500 outline-none transition-all dark:text-white shadow-sm" placeholder="Especifica folio de ficha de depósito o detalles adicionales..."></textarea>
        </div>

        <!-- Rastreo de Origen (Hijos del Lote) -->
        <div class="space-y-4 pt-6 border-t border-slate-200 dark:border-slate-700">
          <div class="flex items-center justify-between">
            <h4 class="text-sm font-black uppercase text-slate-900 dark:text-white tracking-widest flex items-center gap-2">
              <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
              Rastreo de Movimientos en el Lote ({{ entrega.hijos?.length || 0 }})
            </h4>
            <span class="text-xs font-bold text-slate-400">Auditoría en Vivo</span>
          </div>

          <div v-if="entrega.hijos && entrega.hijos.length > 0" class="overflow-x-auto rounded-3xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-100 dark:bg-slate-900 text-slate-500 dark:text-slate-400 text-[10px] uppercase font-black tracking-widest border-b border-slate-200 dark:border-slate-700">
                  <th class="py-3 px-4">Tipo / Folio</th>
                  <th class="py-3 px-4">Cliente</th>
                  <th class="py-3 px-4">Vendedor / Cobró</th>
                  <th class="py-3 px-4">Método</th>
                  <th class="py-3 px-4 text-right">Monto</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-800/50 text-xs">
                <tr v-for="hijo in entrega.hijos" :key="hijo.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2 font-black text-slate-900 dark:text-white">
                      <span :class="hijo.tipo === 'VENTA' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'" class="px-2 py-0.5 rounded-lg text-[9px]">
                        {{ hijo.tipo }}
                      </span>
                      {{ hijo.folio }}
                    </div>
                  </td>
                  <td class="py-3.5 px-4 font-bold text-slate-600 dark:text-slate-300">{{ hijo.cliente }}</td>
                  <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 font-medium">{{ hijo.vendedor }}</td>
                  <td class="py-3.5 px-4">
                    <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 font-mono text-[10px] text-slate-600 dark:text-slate-300 uppercase font-black">
                      {{ hijo.metodo_pago }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-right font-black text-slate-900 dark:text-white font-mono">${{ formatNumber(hijo.monto) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-3xl text-center text-xs text-slate-400 font-medium italic border border-slate-200 dark:border-slate-800">
            No se desglosaron sub-movimientos para este registro o es un ingreso directo.
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-8 bg-slate-50 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <button @click="closeModal" :disabled="saving" class="px-6 py-3.5 rounded-2xl bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 font-black text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300 active:scale-95 transition-all">
          Cancelar
        </button>
        <button 
          @click="confirmarDeposito" 
          :disabled="!form.cuenta_id || saving"
          class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-black text-xs uppercase tracking-widest shadow-xl shadow-indigo-600/30 active:scale-95 disabled:opacity-50 transition-all flex items-center gap-2 cursor-pointer"
        >
          <div v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
          {{ saving ? 'Acreditando en Banco...' : `Confirmar y Acreditar ($${formatNumber(entrega?.total)})` }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import axios from 'axios'
import { Notyf } from 'notyf'

const props = defineProps({
  show: Boolean,
  entrega: Object,
  cuentas: { type: Array, default: () => [] },
  cuentaSeleccionada: Object
})

const emit = defineEmits(['update:show', 'success'])

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const saving = ref(false)
const form = reactive({
  cuenta_id: null,
  fecha_hora: '',
  notas: ''
})

watch(() => props.show, (val) => {
  if (val && props.entrega) {
    if (props.cuentaSeleccionada) {
      form.cuenta_id = props.cuentaSeleccionada.id
    } else {
      const homeCuenta = props.cuentas.find(c => (c.alias || c.nombre_banco || '').toLowerCase().includes('home'))
      form.cuenta_id = homeCuenta ? homeCuenta.id : (props.cuentas.length > 0 ? props.cuentas[0].id : null)
    }

    // Formatear hora local actual para el input datetime-local
    const now = new Date()
    const tzOffset = now.getTimezoneOffset() * 60000
    const localISO = new Date(now.getTime() - tzOffset).toISOString().slice(0, 16)
    form.fecha_hora = localISO

    form.notas = `Depositado en Banco (Entrega #${props.entrega.id} por ${props.entrega.usuario})`
  }
})

// Cuando cambia la cuenta seleccionada en el form, actualizar el texto sugerido en notas
watch(() => form.cuenta_id, (val) => {
  if (val && props.entrega) {
    const selected = props.cuentas.find(c => c.id === val)
    if (selected) {
      form.notas = `Depositado en ${selected.alias || selected.nombre_banco} (Entrega #${props.entrega.id})`
    }
  }
})

const formatNumber = (num) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0)

const closeModal = () => {
  if (!saving.value) {
    emit('update:show', false)
  }
}

const confirmarDeposito = async () => {
  if (!form.cuenta_id || !props.entrega) return
  saving.value = true
  try {
    const payload = {
      banco_cuenta_id: form.cuenta_id,
      fecha_hora: form.fecha_hora.replace('T', ' ') + ':00', // Formato SQL YYYY-MM-DD HH:MM:SS
      notas: form.notas
    }

    const res = await axios.post(`/bancos/api/entregas/${props.entrega.id}/aceptar`, payload)
    if (res.data.success) {
      notyf.success(`¡Entrega #${props.entrega.id} acreditada exitosamente en el banco!`)
      emit('success', res.data.cuenta)
      closeModal()
    }
  } catch (error) {
    notyf.error(error.response?.data?.message || 'Error al procesar el depósito en el banco')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.3); border-radius: 10px; }
.animate-modal-pop { animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalPop { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
</style>
