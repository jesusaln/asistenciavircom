<template>
  <transition name="fade">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-md p-4 animate-in fade-in" @click.self="$emit('cancel')">
      <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden border border-slate-200 dark:border-slate-800 transform transition-all">
        <!-- Header -->
        <div class="px-8 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 relative overflow-hidden">
          <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2240%22%20height%3D%2240%22%20viewBox%3D%220%200%2040%2040%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.05%22%3E%3Cpath%20d%3D%22M0%2020L20%200L40%2020L20%2040Z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-50"></div>
          <div class="relative">
            <h3 class="text-xl font-black text-white flex items-center tracking-tight">
              <span class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center mr-4 backdrop-blur-sm">💳</span>
              Confirmar Pago
            </h3>
            <p class="text-blue-100 text-xs mt-1 ml-14">Revisa los detalles y selecciona el método de pago</p>
          </div>
        </div>

        <div class="p-8 space-y-6 max-h-[70vh] overflow-y-auto custom-scrollbar">
          <!-- Total Prominente -->
          <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-100 dark:border-blue-800 p-6 rounded-2xl text-center">
            <p class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] mb-2">Total a Cobrar</p>
            <p class="text-4xl font-black text-blue-700 dark:text-blue-300 tracking-tighter">${{ totalFormatted }}</p>
          </div>

          <!-- Método de Pago con iconos -->
          <div>
            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.15em] mb-3">Método de Pago</label>
            <div class="grid grid-cols-2 gap-3">
              <button
                v-for="metodo in metodosPago"
                :key="metodo.value"
                type="button"
                @click="selectMetodo(metodo.value)"
                :class="[
                  'relative flex flex-col items-center gap-2 p-4 rounded-2xl border-2 transition-all duration-300 group',
                  metodoPagoInmediato === metodo.value
                    ? 'border-indigo-500 bg-indigo-50 dark:bg-sky-900/20 shadow-lg shadow-indigo-500/10 scale-[1.02]'
                    : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 hover:border-slate-300 dark:hover:border-slate-700 hover:shadow-md'
                ]"
              >
                <span class="text-2xl transition-transform group-hover:scale-110">{{ metodo.icon }}</span>
                <span :class="[
                  'text-[10px] font-black uppercase tracking-wide transition-colors',
                  metodoPagoInmediato === metodo.value ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400'
                ]">{{ metodo.label }}</span>
                <div v-if="metodoPagoInmediato === metodo.value" class="absolute -top-1 -right-1 w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center shadow-lg">
                  <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                </div>
              </button>
            </div>
          </div>

          <!-- Efectivo: Monto Recibido y Cambio -->
          <transition name="slide-fade">
            <div v-if="metodoPagoInmediato === 'efectivo'" class="space-y-4">
              <div>
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.15em] mb-2">Monto Recibido</label>
                <div class="relative">
                  <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-black text-lg">$</span>
                  <input
                    :ref="inputRef"
                    type="text"
                    :value="importeRecibido"
                    @input="handleImporteInput"
                    @keypress="validarSoloNumeros"
                    class="w-full bg-slate-50 dark:bg-slate-950 border-2 border-slate-200 dark:border-slate-800 rounded-xl pl-10 pr-4 py-4 text-2xl font-black text-slate-800 dark:text-white focus:border-brand-500 focus:ring-0 text-right transition-all"
                    placeholder="0.00"
                  />
                </div>
              </div>
              <!-- Botones rápidos de montos -->
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="monto in montosRapidos"
                  :key="monto"
                  type="button"
                  @click="setMontoRapido(monto)"
                  class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-black rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-800"
                >
                  ${{ monto.toLocaleString() }}
                </button>
                <button
                  type="button"
                  @click="setMontoExacto"
                  class="px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 text-xs font-black rounded-xl hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-all border border-emerald-200 dark:border-emerald-800"
                >
                  Exacto
                </button>
              </div>
              <div :class="[
                'flex justify-between items-center p-4 rounded-2xl border transition-all duration-300',
                cambioNumerico >= 0
                  ? 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-100 dark:border-emerald-800/30'
                  : 'bg-rose-50 dark:bg-rose-900/10 border-rose-100 dark:border-rose-800/30'
              ]">
                <span :class="[
                  'text-[10px] font-black uppercase tracking-wide',
                  cambioNumerico >= 0 ? 'text-emerald-600' : 'text-rose-600'
                ]">{{ cambioNumerico >= 0 ? 'Cambio' : 'Faltante' }}</span>
                <span :class="[
                  'text-2xl font-black',
                  cambioNumerico >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'
                ]">${{ cambioFormatted }}</span>
              </div>
            </div>
          </transition>

          <!-- Cuenta Bancaria (para transferencia y tarjeta) -->
          <transition name="slide-fade">
            <div v-if="metodoPagoInmediato === 'transferencia' || metodoPagoInmediato === 'tarjeta'">
              <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.15em] mb-2">Cuenta Bancaria</label>
              <select
                :value="cuentaBancariaId"
                @change="$emit('update:cuentaBancariaId', $event.target.value)"
                class="w-full bg-slate-50 dark:bg-slate-950 border-2 border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-800 dark:text-white focus:border-brand-500 focus:ring-0 transition-all"
              >
                <option value="">Selecciona una cuenta...</option>
                <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                  {{ cuenta.banco }} - {{ cuenta.numero_cuenta }} {{ cuenta.alias ? `(${cuenta.alias})` : '' }}
                </option>
              </select>
            </div>
          </transition>

          <!-- Notas del pago -->
          <div>
            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.15em] mb-2">Notas del Pago <span class="text-slate-300 dark:text-slate-600">(opcional)</span></label>
            <textarea
              :value="notasPago"
              @input="$emit('update:notasPago', $event.target.value)"
              rows="2"
              class="w-full bg-slate-50 dark:bg-slate-950 border-2 border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-medium text-slate-800 dark:text-white focus:border-brand-500 focus:ring-0 resize-none transition-all"
              placeholder="Referencia, observaciones..."
            ></textarea>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-8 py-5 bg-slate-50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
          <button @click="$emit('cancel')" class="px-6 py-3 text-[10px] font-black text-slate-500 uppercase tracking-wide hover:text-slate-700 dark:hover:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-all">
            Cancelar
          </button>
          <button
            @click="$emit('confirm')"
            :disabled="processing || !metodoPagoInmediato || (metodoPagoInmediato === 'efectivo' && cambioNumerico < 0)"
            :class="[
              'px-8 py-3 text-[10px] font-black uppercase tracking-wide rounded-xl shadow-lg transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 active:scale-95 flex items-center gap-2',
              !metodoPagoInmediato || processing || (metodoPagoInmediato === 'efectivo' && cambioNumerico < 0)
                ? 'bg-slate-300 dark:bg-slate-700 text-slate-500 dark:text-slate-400 cursor-not-allowed shadow-none'
                : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-500/30 hover:shadow-indigo-500/50'
            ]"
          >
            <svg v-if="processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
            {{ processing ? 'Procesando...' : 'Confirmar Venta' }}
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({
  show: { type: Boolean, default: false },
  total: { type: [Number, String], default: 0 },
  metodoPagoInmediato: { type: String, default: '' },
  importeRecibido: { type: [Number, String], default: '' },
  cambio: { type: [Number, String], default: 0 },
  processing: { type: Boolean, default: false },
  inputRef: { type: Object, default: null },
  formatNumber: { type: Function, required: true },
  cuentasBancarias: { type: Array, default: () => [] },
  cuentaBancariaId: { type: [String, Number], default: '' },
  notasPago: { type: String, default: '' },
});

const emit = defineEmits([
  'cancel', 'confirm',
  'update:metodoPagoInmediato', 'update:importeRecibido',
  'update:cuentaBancariaId', 'update:notasPago',
  'metodo-change', 'importe-change'
]);

const metodosPago = [
  { value: 'efectivo', label: 'Efectivo', icon: '💵' },
  { value: 'tarjeta', label: 'Tarjeta', icon: '💳' },
  { value: 'transferencia', label: 'Transferencia', icon: '🏦' },
  { value: 'credito', label: 'Crédito', icon: '📋' },
];

const totalFormatted = computed(() => props.formatNumber(props.total));
const cambioNumerico = computed(() => parseFloat(props.cambio) || 0);
const cambioFormatted = computed(() => props.formatNumber(Math.abs(cambioNumerico.value)));

const montosRapidos = computed(() => {
  const total = parseFloat(props.total) || 0;
  const montos = [50, 100, 200, 500, 1000, 2000];
  // Solo mostrar montos mayores o iguales al total
  return montos.filter(m => m >= total).slice(0, 4);
});

const selectMetodo = (value) => {
  emit('update:metodoPagoInmediato', value);
  emit('metodo-change');
};

const handleImporteInput = (event) => {
  emit('update:importeRecibido', event.target.value);
  emit('importe-change');
};

const setMontoRapido = (monto) => {
  emit('update:importeRecibido', monto.toString());
  emit('importe-change');
};

const setMontoExacto = () => {
  const total = parseFloat(props.total) || 0;
  emit('update:importeRecibido', total.toFixed(2));
  emit('importe-change');
};

const validarSoloNumeros = (event) => {
  const char = event.key;
  if (!/[0-9.]/.test(char) && event.key !== 'Backspace' && event.key !== 'Delete') {
    event.preventDefault();
  }
  const currentValue = (props.importeRecibido || '').toString();
  if (char === '.' && currentValue.includes('.')) {
    event.preventDefault();
  }
};
</script>

<style scoped>
.slide-fade-enter-active { transition: all 0.3s ease-out; }
.slide-fade-leave-active { transition: all 0.2s ease-in; }
.slide-fade-enter-from { opacity: 0; transform: translateY(-10px); }
.slide-fade-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
