<template>
    <DialogModal :show="show" @close="close" maxWidth="md">
        <template #content>
            <div class="bg-[#0F172A] text-slate-300 rounded-3xl shadow-2xl w-full overflow-hidden border border-slate-700/50 transform transition-all relative">
                <!-- Decorative Gradients -->
                <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500/10 blur-[80px] rounded-full pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 w-64 h-64 bg-indigo-500/10 blur-[80px] rounded-full pointer-events-none"></div>

                <!-- Header -->
                <div class="px-8 py-6 bg-slate-900/80 backdrop-blur-xl border-b border-slate-800 flex justify-between items-center relative z-10">
                    <h3 class="font-black uppercase tracking-[0.15em] text-sm text-white flex items-center gap-2">
                        <span class="p-1.5 bg-emerald-500/10 rounded-lg text-emerald-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        Registrar Cobro
                    </h3>
                    <button @click="close" class="text-slate-500 hover:text-white transition-colors bg-slate-800/50 hover:bg-slate-700 p-2 rounded-xl">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-8 space-y-6 relative z-10">
                    <div v-if="cuenta" class="mb-6 p-4 bg-slate-800/50 rounded-2xl border border-slate-700/50">
                        <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest mb-1">{{ cuenta.cobrable?.cliente?.nombre_razon_social || 'Cliente' }}</p>
                        <div class="flex items-baseline justify-between">
                            <p class="text-xs font-bold text-slate-400">Saldo Pendiente</p>
                            <p class="text-xl font-black text-white">{{ formatCurrency(cuenta.monto_pendiente) }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Monto del Abono</label>
                        <div class="relative group">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-black text-slate-500 group-focus-within:text-emerald-500 transition-colors">$</span>
                            <input 
                                v-model="form.monto" 
                                type="number" 
                                step="0.01" 
                                class="w-full pl-8 py-3 bg-slate-900 border border-slate-700 rounded-xl text-2xl font-black text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder-slate-700" 
                                placeholder="0.00" 
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Método de Pago</label>
                        <select v-model="form.metodo_pago" class="w-full py-3 px-4 bg-slate-900 border border-slate-700 rounded-xl text-sm font-bold text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            <option value="">Seleccionar...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="cheque">Cheque</option>
                            <option value="tarjeta_credito">Tarjeta de Crédito</option>
                            <option value="tarjeta_debito">Tarjeta de Débito</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            Cuenta Bancaria Destino 
                            <span v-if="!requiresBankAccount" class="text-slate-600 font-normal normal-case">(Opcional)</span>
                        </label>
                        
                        <div v-if="cuentasBancarias.length === 0" class="p-3 bg-amber-900/10 rounded-xl border border-amber-500/20 text-amber-500 text-[10px] text-center font-bold">
                            ⚠️ No hay cuentas registradas.
                        </div>
                        
                        <select v-else v-model="form.cuenta_bancaria_id" class="w-full py-3 px-4 bg-slate-900 border border-slate-700 rounded-xl text-sm font-bold text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            <option value="">{{ requiresBankAccount ? 'Seleccionar Banco (Requerido)...' : 'Seleccionar Banco (Opcional)...' }}</option>
                            <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">{{ cb.banco }} - {{ cb.nombre }}</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">Notas / Referencia</label>
                        <textarea 
                            v-model="form.notas" 
                            rows="2" 
                            class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-sm font-medium text-white focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all resize-none" 
                            placeholder="Ej: Pago de factura..."
                        ></textarea>
                    </div>
                </div>

                <div class="px-8 py-6 bg-slate-900/50 backdrop-blur-md border-t border-slate-800 flex flex-col gap-3 relative z-10">
                    <button 
                        @click="confirmarPago" 
                        :disabled="!canConfirmPayment || processing" 
                        class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white rounded-xl font-black uppercase text-xs tracking-[0.2em] shadow-lg shadow-emerald-900/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3 transform active:scale-[0.98] transition-all"
                    >
                        <span v-if="processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        {{ processing ? 'Procesando...' : 'Confirmar Cobro' }}
                    </button>
                    <button 
                        @click="close" 
                        class="w-full py-3 font-black text-slate-500 hover:text-white uppercase text-[10px] tracking-widest transition-colors hover:bg-slate-800/50 rounded-xl"
                    >
                        Cancelar
                    </button>
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
