<!-- Modal de Pago para Compras -->
<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="cerrarModal"></div>
        
        <!-- Modal Panel -->
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg transform transition-all">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-5 rounded-t-2xl">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-white">Confirmar Pago de Compra</h2>
                    <p class="text-emerald-100 text-sm">Selecciona el método de pago y cuenta bancaria</p>
                  </div>
                </div>
                <button @click="cerrarModal" class="text-white/80 hover:text-white transition-colors p-1">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Resumen del Total -->
            <div class="bg-emerald-50 px-6 py-4 border-b border-emerald-100">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-emerald-700">Total a Pagar</span>
                <span class="text-2xl font-bold text-emerald-800">{{ formatearMoneda(totalCompra) }}</span>
              </div>
            </div>

            <!-- Contenido del Modal -->
            <div class="px-6 py-5 space-y-5">
              
              <!-- Método de Pago -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                  Método de Pago <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-3">
                  <button
                    v-for="metodo in metodosPago"
                    :key="metodo.value"
                    type="button"
                    @click="metodoPago = metodo.value"
                    :class="[
                      'flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all duration-200',
                      metodoPago === metodo.value 
                        ? 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-md' 
                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 hover:bg-gray-50'
                    ]"
                  >
                    <span class="text-xl">{{ metodo.icon }}</span>
                    <span class="font-medium text-sm">{{ metodo.label }}</span>
                  </button>
                </div>
              </div>

              <!-- Cuenta Bancaria -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Cuenta Bancaria de Origen
                  <span class="text-gray-400 font-normal">(opcional)</span>
                </label>
                <p class="text-xs text-gray-500 mb-3">
                  Selecciona de qué cuenta sale el dinero para esta compra
                </p>
                <select
                  v-model="cuentaBancariaId"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                  @change="onCuentaChange"
                >
                  <option value="">Sin especificar (no descuenta de banco)</option>
                  <option 
                    v-for="cuenta in cuentasBancarias" 
                    :key="cuenta.id" 
                    :value="cuenta.id"
                    :disabled="cuenta.saldo_actual < totalCompra"
                  >
                    🏦 {{ cuenta.banco }} - {{ cuenta.numero_cuenta }} 
                    | Saldo: {{ formatearMoneda(cuenta.saldo_actual) }}
                    <template v-if="cuenta.saldo_actual < totalCompra"> (Saldo insuficiente)</template>
                  </option>
                </select>

                <!-- Mensaje de saldo insuficiente -->
                <div 
                  v-if="cuentaSeleccionada && saldoInsuficiente" 
                  class="mt-3 p-3 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2"
                >
                  <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-red-700">Saldo Insuficiente</p>
                    <p class="text-xs text-red-600">
                      La cuenta tiene <strong>{{ formatearMoneda(cuentaSeleccionada.saldo_actual) }}</strong> 
                      pero necesitas <strong>{{ formatearMoneda(totalCompra) }}</strong>.
                      Faltan <strong>{{ formatearMoneda(totalCompra - cuentaSeleccionada.saldo_actual) }}</strong>.
                    </p>
                  </div>
                </div>

                <!-- Mensaje de confirmación de descuento -->
                <div 
                  v-if="cuentaSeleccionada && !saldoInsuficiente" 
                  class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-2"
                >
                  <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-blue-700">Se descontará de esta cuenta</p>
                    <p class="text-xs text-blue-600">
                      Saldo actual: <strong>{{ formatearMoneda(cuentaSeleccionada.saldo_actual) }}</strong>
                      → Nuevo saldo: <strong>{{ formatearMoneda(cuentaSeleccionada.saldo_actual - totalCompra) }}</strong>
                    </p>
                  </div>
                </div>
              </div>

              <!-- Notas del Pago -->
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  Notas del Pago
                  <span class="text-gray-400 font-normal">(opcional)</span>
                </label>
                <textarea
                  v-model="notasPago"
                  rows="2"
                  class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors resize-none"
                  placeholder="Agregar detalles o referencias del pago..."
                ></textarea>
              </div>

            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 rounded-b-2xl border-t border-gray-200 flex items-center justify-end gap-3">
              <button
                @click="cerrarModal"
                type="button"
                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
              >
                Cancelar
              </button>
              <button
                @click="confirmarPago"
                type="button"
                :disabled="!puedeConfirmar"
                :class="[
                  'px-6 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200 flex items-center gap-2',
                  puedeConfirmar
                    ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 text-white hover:from-emerald-600 hover:to-emerald-700 shadow-lg shadow-emerald-500/30'
                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                ]"
              >
                <template v-if="isProcessing">
                  <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Procesando...
                </template>
                <template v-else>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  Confirmar Compra
                </template>
              </button>
            </div>

          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  totalCompra: {
    type: Number,
    required: true
  },
  isProcessing: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'confirm']);

// Estado
const metodoPago = ref('');
const cuentaBancariaId = ref('');
const notasPago = ref('');
const cuentasBancarias = ref([]);

// Métodos de pago disponibles
const metodosPago = [
  { value: 'efectivo', label: 'Efectivo', icon: '💵' },
  { value: 'transferencia', label: 'Transferencia', icon: '🏦' },
  { value: 'tarjeta', label: 'Tarjeta', icon: '💳' },
  { value: 'cheque', label: 'Cheque', icon: '📝' },
  { value: 'credito', label: 'Crédito', icon: '📅' },
  { value: 'otros', label: 'Otros', icon: '📋' }
];

// Cuenta seleccionada (objeto completo)
const cuentaSeleccionada = computed(() => {
  if (!cuentaBancariaId.value) return null;
  return cuentasBancarias.value.find(c => c.id === parseInt(cuentaBancariaId.value));
});

// Verificar si hay saldo insuficiente
const saldoInsuficiente = computed(() => {
  if (!cuentaSeleccionada.value) return false;
  return parseFloat(cuentaSeleccionada.value.saldo_actual) < props.totalCompra;
});

// Verificar si puede confirmar
const puedeConfirmar = computed(() => {
  // Debe tener método de pago
  if (!metodoPago.value) return false;
  // No debe estar procesando
  if (props.isProcessing) return false;
  // Si seleccionó cuenta, debe tener saldo suficiente
  if (cuentaSeleccionada.value && saldoInsuficiente.value) return false;
  return true;
});

// Formatear moneda
const formatearMoneda = (valor) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(valor || 0);
};

// Cuando cambia la cuenta seleccionada
const onCuentaChange = () => {
  // Si la cuenta tiene saldo insuficiente, limpiar la selección
  if (saldoInsuficiente.value) {
    // Opcional: mostrar alerta
    console.warn('Saldo insuficiente en la cuenta seleccionada');
  }
};

// Obtener cuentas bancarias activas (con saldo)
const fetchCuentasBancarias = async () => {
  try {
    const response = await axios.get('/api/cuentas-bancarias/activas');
    cuentasBancarias.value = response.data;
  } catch (error) {
    console.error('Error al obtener cuentas bancarias:', error);
    // Fallback: intentar con la ruta web
    try {
      const webResponse = await axios.get('/cuentas-bancarias/activas');
      cuentasBancarias.value = webResponse.data;
    } catch (e) {
      console.error('Error al obtener cuentas bancarias (web):', e);
    }
  }
};

// Cerrar modal
const cerrarModal = () => {
  emit('close');
};

// Confirmar pago
const confirmarPago = () => {
  if (!puedeConfirmar.value) return;
  
  emit('confirm', {
    metodo_pago: metodoPago.value,
    cuenta_bancaria_id: cuentaBancariaId.value || null,
    notas_pago: notasPago.value,
    descontar_de_banco: cuentaBancariaId.value ? true : false  // Indicar si debe descontar
  });
};

// Resetear campos cuando se abre el modal
watch(() => props.show, (newVal) => {
  if (newVal) {
    // Cargar cuentas bancarias siempre al abrir (para tener saldo actualizado)
    fetchCuentasBancarias();
    // Resetear campos
    metodoPago.value = '';
    cuentaBancariaId.value = '';
    notasPago.value = '';
  }
});

// Cargar cuentas bancarias al montar
onMounted(() => {
  fetchCuentasBancarias();
});
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .relative,
.modal-fade-leave-active .relative {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.modal-fade-enter-from .relative,
.modal-fade-leave-to .relative {
  transform: scale(0.95);
  opacity: 0;
}
</style>


