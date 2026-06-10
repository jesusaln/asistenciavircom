<template>
  <div v-if="show" class="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4 overflow-y-auto" @click.self="closeModal">
    <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-4xl overflow-hidden border border-slate-100 dark:border-slate-700 my-8 flex flex-col max-h-[90vh] transition-all animate-modal-pop">
      <!-- Header -->
      <div class="p-8 bg-gradient-to-r from-emerald-600 to-teal-700 text-white flex justify-between items-start shrink-0">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-black uppercase tracking-widest text-emerald-100 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" /></svg>
            Operación de Mostrador
          </div>
          <h3 class="text-3xl font-black uppercase tracking-tight">Corte de Caja y Cobranza</h3>
          <p class="text-emerald-100/80 text-sm mt-1 font-medium">Selecciona las ventas o cobros de mostrador para formalizarlos en un lote hacia Tesorería.</p>
        </div>
        <button @click="closeModal" class="p-3 hover:bg-white/10 rounded-full transition-colors text-emerald-100 hover:text-white">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>

      <!-- Body / List -->
      <div class="p-8 overflow-y-auto space-y-6 custom-scrollbar grow">
        <div v-if="loading" class="py-16 text-center space-y-4">
          <div class="inline-block w-12 h-12 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
          <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">Cargando cobranza por formalizar...</p>
        </div>

        <div v-else-if="registros.length === 0" class="py-16 text-center space-y-4">
          <div class="w-20 h-20 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
          </div>
          <h4 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">¡Todo al día en Mostrador!</h4>
          <p class="text-slate-500 dark:text-slate-400 text-sm max-w-md mx-auto">No hay ventas en efectivo ni cobranzas pendientes de formalizar en este momento.</p>
        </div>

        <div v-else class="space-y-6">
          <div class="flex justify-between items-center bg-slate-50 dark:bg-slate-900/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
            <label class="flex items-center gap-3 cursor-pointer select-none">
              <input type="checkbox" :checked="isAllSelected" @change="toggleAll" class="w-5 h-5 rounded-xl border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-700">
              <span class="text-xs font-black uppercase text-slate-700 dark:text-slate-300 tracking-wider">Seleccionar Todos ({{ registros.length }})</span>
            </label>
            <div class="text-right">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Seleccionado</span>
              <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">${{ formatNumber(totalSeleccionado) }}</span>
            </div>
          </div>

          <div class="space-y-3">
            <div v-for="registro in registros" :key="registro.id" @click="toggleSelect(registro)" class="p-4 bg-white dark:bg-slate-800/80 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 border border-slate-200 dark:border-slate-700/60 rounded-2xl transition-all cursor-pointer flex items-center gap-4 group">
              <input type="checkbox" :checked="selected.some(r => r.id === registro.id)" @change.stop="toggleSelect(registro)" class="w-5 h-5 rounded-xl border-slate-300 text-emerald-600 focus:ring-emerald-500 dark:bg-slate-700 cursor-pointer">
              
              <div class="grow flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="space-y-1">
                  <div class="flex items-center gap-2">
                    <span :class="registro.tipo === 'venta' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'" class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase">
                      {{ registro.tipo }}
                    </span>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">{{ registro.fecha_pago }}</span>
                  </div>
                  <h5 class="text-sm font-black text-slate-900 dark:text-white">{{ registro.concepto }}</h5>
                  <p class="text-xs text-slate-500 dark:text-slate-400 italic">{{ registro.cliente }}</p>
                </div>

                <div class="text-right">
                  <div class="text-base font-black text-slate-900 dark:text-white">${{ formatNumber(registro.saldo_pendiente) }}</div>
                  <div class="text-[10px] text-slate-400 font-bold uppercase">{{ registro.vendedor || 'Vendedor' }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Opciones de Destino Directo -->
          <div v-if="selected.length > 0" class="pt-6 border-t border-slate-100 dark:border-slate-700/60 space-y-6 animate-fade-in">
            <div class="bg-slate-50 dark:bg-slate-900/60 p-6 rounded-3xl border border-slate-200 dark:border-slate-800 space-y-4 shadow-sm">
              <div class="flex items-center justify-between flex-wrap gap-4">
                <h6 class="text-xs font-black uppercase tracking-widest text-slate-700 dark:text-slate-300 flex items-center gap-2">
                  <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                  Modalidad de Ingreso a Tesorería
                </h6>
                <div class="flex items-center bg-slate-200/80 dark:bg-slate-800/80 p-1 rounded-xl gap-1 text-xs font-bold">
                  <button @click="modoDirecto = true" :class="modoDirecto ? 'bg-white dark:bg-slate-700 text-emerald-600 dark:text-emerald-400 shadow-sm font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'" class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                    ⚡ Acreditar Directo en Chequera
                  </button>
                  <button @click="modoDirecto = false" :class="!modoDirecto ? 'bg-white dark:bg-slate-700 text-amber-600 dark:text-amber-400 shadow-sm font-black' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700'" class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                    ⏳ Dejar Pendiente por Revisar
                  </button>
                </div>
              </div>

              <!-- Opciones si es Modo Directo -->
              <div v-if="modoDirecto" class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 animate-fade-in">
                <div class="space-y-1.5">
                  <label class="text-[11px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Chequera Destino</label>
                  <select v-model="cuentaDestinoId" class="w-full bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-2xl p-3.5 text-xs font-black focus:border-emerald-500 outline-none transition-all dark:text-white cursor-pointer shadow-sm">
                    <option :value="null" disabled>— Selecciona chequera destino —</option>
                    <option v-for="cuenta in cuentas" :key="cuenta.id" :value="cuenta.id">
                      🏦 {{ cuenta.alias || cuenta.nombre_banco }} ({{ cuenta.numero_cuenta || 'S/N' }}) — ${{ formatNumber(cuenta.saldo_actual ?? cuenta.saldo_inicial) }}
                    </option>
                  </select>
                </div>

                <div class="space-y-1.5">
                  <label class="text-[11px] font-black uppercase tracking-wider text-slate-600 dark:text-slate-400">Fecha y Hora Real</label>
                  <input type="datetime-local" v-model="fechaHora" class="w-full bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-2xl p-3.5 text-xs font-black focus:border-emerald-500 outline-none transition-all dark:text-white shadow-sm" />
                </div>
              </div>
            </div>

            <!-- Notas del Lote -->
            <div class="space-y-2">
              <label class="text-xs font-black text-slate-400 uppercase tracking-widest block">Referencia / Notas del Lote de Entrega</label>
              <textarea v-model="notas" rows="2" class="w-full bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-200 dark:border-slate-700 rounded-2xl p-4 text-sm font-medium focus:border-emerald-500 outline-none transition-all dark:text-white" placeholder="Ej: Corte de caja matutino / Efectivo de mostrador"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-8 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700 flex flex-wrap items-center justify-between gap-4 shrink-0">
        <button @click="closeModal" class="px-6 py-3 rounded-2xl bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-50 font-bold text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300 active:scale-95 transition-all">
          Cancelar
        </button>
        <button 
          @click="confirmarLote" 
          :disabled="selected.length === 0 || saving || (modoDirecto && !cuentaDestinoId)"
          class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-600/20 active:scale-95 disabled:opacity-50 transition-all flex items-center gap-2 cursor-pointer"
        >
          <div v-if="saving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
          {{ saving ? 'Generando Corte...' : (modoDirecto ? `Acreditar en Chequera ($${formatNumber(totalSeleccionado)})` : `Crear Lote Pendiente (${selected.length})`) }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { Notyf } from 'notyf'

const props = defineProps({
  show: Boolean,
  cuentas: { type: Array, default: () => [] },
  cuentaSeleccionada: Object
})

const emit = defineEmits(['update:show', 'success'])

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
})

const loading = ref(false)
const saving = ref(false)
const registros = ref([])
const selected = ref([])
const notas = ref('')
const cuentaDestinoId = ref(null)
const fechaHora = ref('')
const modoDirecto = ref(true)

const totalSeleccionado = computed(() => selected.value.reduce((sum, item) => sum + item.saldo_pendiente, 0))
const isAllSelected = computed(() => registros.value.length > 0 && selected.value.length === registros.value.length)

watch(() => props.show, (val) => {
  if (val) {
    loadRegistros()
    selected.value = []
    notas.value = ''
    modoDirecto.value = true

    if (props.cuentaSeleccionada) {
      cuentaDestinoId.value = props.cuentaSeleccionada.id
    } else {
      const homeCuenta = props.cuentas.find(c => (c.alias || c.nombre_banco || '').toLowerCase().includes('home'))
      cuentaDestinoId.value = homeCuenta ? homeCuenta.id : (props.cuentas.length > 0 ? props.cuentas[0].id : null)
    }

    const now = new Date()
    const tzOffset = now.getTimezoneOffset() * 60000
    fechaHora.value = new Date(now.getTime() - tzOffset).toISOString().slice(0, 16)
  }
})

const formatNumber = (num) => new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0)

const loadRegistros = async () => {
  loading.value = true
  try {
    const { data } = await axios.get('/bancos/api/cobranza-por-formalizar')
    registros.value = data || []
  } catch (error) {
    notyf.error('Error al cargar ventas por formalizar')
  } finally {
    loading.value = false
  }
}

const toggleSelect = (registro) => {
  const idx = selected.value.findIndex(r => r.id === registro.id)
  if (idx === -1) {
    selected.value.push(registro)
  } else {
    selected.value.splice(idx, 1)
  }
}

const toggleAll = (e) => {
  if (e.target.checked) {
    selected.value = [...registros.value]
  } else {
    selected.value = []
  }
}

const closeModal = () => {
  emit('update:show', false)
}

const confirmarLote = async () => {
  if (selected.value.length === 0) return
  saving.value = true
  try {
    const payload = {
      items: selected.value.map(r => ({
        tipo_origen: r.tipo_origen,
        id_origen: r.id_origen,
        total: r.saldo_pendiente,
        metodo_pago: r.metodo_pago || 'efectivo',
      })),
      notas: notas.value || (modoDirecto.value ? 'Corte de caja matutino acreditado directo en chequera.' : 'Corte de caja matutino pendiente de revisión en Tesorería.'),
      banco_cuenta_id: modoDirecto.value ? cuentaDestinoId.value : null,
      fecha_hora: (modoDirecto.value && fechaHora.value) ? (fechaHora.value.replace('T', ' ') + ':00') : null
    }

    const res = await axios.post('/entregas-dinero/lote', payload)
    notyf.success(modoDirecto.value ? '¡Corte de caja generado y acreditado directamente en la chequera!' : 'Lote de entrega generado y enviado a Tesorería con éxito')
    emit('success', res.data)
    closeModal()
  } catch (error) {
    notyf.error(error.response?.data?.message || 'Error al generar el lote de entrega')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 10px; }
.animate-modal-pop { animation: modalPop 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes modalPop { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
.animate-fade-in { animation: fadeIn 0.2s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>
