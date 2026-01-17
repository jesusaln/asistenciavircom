<template>
    <DialogModal :show="show" @close="close" maxWidth="md">
        <template #content>
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl w-full overflow-hidden border border-gray-100 dark:border-gray-700 transform transition-all">
                <div class="px-8 py-6 bg-white dark:bg-gray-800 border-b border-gray-50 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-black uppercase tracking-[0.15em] text-sm text-gray-900 dark:text-gray-100">Registrar Cobro</h3>
                    <button @click="close" class="text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-8 space-y-6">
                    <div v-if="cuenta" class="mb-4">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase tracking-widest">{{ cuenta.cobrable?.cliente?.nombre_razon_social || 'Cliente' }}</p>
                        <p class="text-lg font-black text-gray-800 dark:text-gray-100">Saldo Pendiente: {{ formatCurrency(cuenta.monto_pendiente) }}</p>
                    </div>

                    <div class="p-6 bg-white dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Monto del Abono</label>
                        <div class="relative">
                            <span class="absolute left-0 top-1/2 -translate-y-1/2 text-2xl font-black text-gray-300 dark:text-gray-600">$</span>
                            <input v-model="form.monto" type="number" step="0.01" class="w-full pl-6 py-2 bg-transparent border-0 rounded-none text-3xl font-black text-gray-900 dark:text-white focus:ring-0 placeholder:text-gray-100 dark:placeholder:text-gray-800" placeholder="0.00" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Método de Pago</label>
                        <select v-model="form.metodo_pago" class="w-full py-4 px-5 bg-white dark:bg-gray-700 border-2 border-gray-100 dark:border-gray-600 rounded-2xl font-bold text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-gray-400 focus:ring-0 transition-all">
                            <option value="">Seleccionar...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="cheque">Cheque</option>
                            <option value="tarjeta_credito">Tarjeta de Crédito</option>
                            <option value="tarjeta_debito">Tarjeta de Débito</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">
                            Cuenta Bancaria Destino 
                            <span v-if="!requiresBankAccount" class="text-gray-300 dark:text-gray-600 font-normal">(Opcional)</span>
                        </label>
                        
                        <div v-if="cuentasBancarias.length === 0" class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-xl border border-amber-100 dark:border-amber-900/50 text-amber-700 dark:text-amber-400 text-xs text-center">
                            <p class="font-bold">⚠️ No hay cuentas bancarias registradas.</p>
                            <p class="mt-1 text-[10px]">Para métodos bancarios (Transferencia, Tarjeta), registra tus cuentas en el módulo de Finanzas.</p>
                        </div>
                        
                        <select v-else v-model="form.cuenta_bancaria_id" class="w-full py-4 px-5 bg-white dark:bg-gray-700 border-2 border-gray-100 dark:border-gray-600 rounded-2xl font-bold text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-gray-400 focus:ring-0 transition-all">
                            <option value="">{{ requiresBankAccount ? 'Seleccionar Banco (Requerido)...' : 'Seleccionar Banco (Opcional)...' }}</option>
                            <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">{{ cb.banco }} - {{ cb.nombre }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Notas / Referencia</label>
                        <textarea v-model="form.notas" rows="2" class="w-full px-5 py-4 bg-white dark:bg-gray-700 border-2 border-gray-100 dark:border-gray-600 rounded-2xl font-bold text-gray-900 dark:text-white focus:border-gray-900 dark:focus:border-gray-400 focus:ring-0 transition-all" placeholder="Ej: Pago de factura..."></textarea>
                    </div>
                </div>

                <div class="px-8 py-6 bg-white/50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700 flex flex-col gap-3">
                    <button @click="confirmarPago" :disabled="!canConfirmPayment || processing" class="w-full py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-2xl font-black uppercase text-xs tracking-[0.2em] shadow-xl shadow-gray-200 dark:shadow-none disabled:opacity-50 flex items-center justify-center gap-3 active:scale-[0.98] transition-all">
                        <span v-if="processing" class="w-4 h-4 border-2 border-white/30 border-t-white dark:border-gray-400/30 dark:border-t-gray-900 rounded-full animate-spin"></span>
                        {{ processing ? 'Procesando...' : 'Confirmar Cobro' }}
                    </button>
                    <button @click="close" class="w-full py-3 font-black text-gray-400 dark:text-gray-500 hover:text-gray-900 dark:hover:text-gray-300 uppercase text-[10px] tracking-widest transition-colors tracking-widest">Cancelar</button>
                </div>
            </div>
        </template>
    </DialogModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import { router } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps({
    show: Boolean,
    cuenta: Object,
    cuentasBancarias: Array,
});

const emit = defineEmits(['close']);

const close = () => emit('close');

const form = ref({
    monto: 0,
    metodo_pago: '',
    cuenta_bancaria_id: '',
    notas: '',
});

const processing = ref(false);

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false }
    ]
});

watch(() => props.cuenta, (newVal) => {
    if (newVal) {
        form.value.monto = newVal.monto_pendiente;
        form.value.metodo_pago = '';
        form.value.cuenta_bancaria_id = '';
        form.value.notas = '';
    }
}, { immediate: true });

const requiresBankAccount = computed(() => {
    return ['transferencia', 'cheque', 'tarjeta_credito', 'tarjeta_debito'].includes(form.value.metodo_pago);
});

const canConfirmPayment = computed(() => {
    if (!form.value.metodo_pago) return false;
    if (requiresBankAccount.value && !form.value.cuenta_bancaria_id) return false;
    if (!form.value.monto || form.value.monto <= 0) return false;
    return true;
});

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
const formatCurrency = (val) => currencyFormatter.format(Number(val) || 0);

const confirmarPago = () => {
    if (!canConfirmPayment.value) return;

    processing.value = true;
    router.post(route('cuentas-por-cobrar.registrar-pago', props.cuenta.id), form.value, {
        onSuccess: () => {
            notyf.success('Pago registrado correctamente');
            processing.value = false;
            close();
        },
        onError: (errors) => {
            processing.value = false;
            const message = Object.values(errors)[0] || 'Error al registrar el pago';
            notyf.error(message);
        }
    });
};
</script>
