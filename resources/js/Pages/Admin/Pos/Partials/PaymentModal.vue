<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';

const props = defineProps({
    show: Boolean,
    total: Number,
    processing: Boolean,
});

const emit = defineEmits(['update:show', 'confirm', 'close']);

const methods = ['efectivo', 'transferencia', 'tarjeta', 'credito'];

// Estado de pagos múltiples
const payments = ref([
    { method: 'efectivo', amount: props.total }
]);

const formatCurrency = (val) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(val || 0);
};

const totalPaid = computed(() => {
    return round2(payments.value.reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0));
});

const remaining = computed(() => {
    return Math.max(0, round2(props.total - totalPaid.value));
});

const change = computed(() => {
    return Math.max(0, round2(totalPaid.value - props.total));
});

const round2 = (num) => Math.round((num + Number.EPSILON) * 100) / 100;

const canConfirm = computed(() => {
    return round2(totalPaid.value) >= round2(props.total) && !props.processing;
});

const addPayment = () => {
    if (payments.value.length < 4 && remaining.value > 0) {
        payments.value.push({ method: 'tarjeta', amount: round2(remaining.value) });
    }
};

const removePayment = (index) => {
    if (payments.value.length > 1) {
        payments.value.splice(index, 1);
    }
};

const close = () => emit('update:show', false);

const handleConfirm = (withTicket = true) => {
    if (!canConfirm.value) return;
    
    // Preparar data para enviar (asegurar que amount sea número)
    const processedPayments = payments.value.map(p => ({
        ...p,
        amount: round2(parseFloat(p.amount) || 0)
    }));

    const mainPayment = processedPayments.reduce((prev, current) => 
        (prev.amount > current.amount) ? prev : current
    );

    emit('confirm', {
        payments: processedPayments,
        mainMethod: mainPayment.method,
        withTicket
    });
};

const handleKeyDown = (e) => {
    if (!props.show) return;
    
    // Si estamos en un select o input, solo permitimos F5/F6/Enter para procesar
    if (e.key === 'F5') {
        e.preventDefault();
        handleConfirm(true);
    } else if (e.key === 'F6') {
        e.preventDefault();
        handleConfirm(false);
    } else if (e.key === 'Enter') {
        // Solo si no estamos escribiendo en el input o si el monto ya es suficiente
        if (canConfirm.value) {
            e.preventDefault();
            handleConfirm(true);
        }
    } else if (e.key === 'Escape') {
        close();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        payments.value = [{ method: 'efectivo', amount: round2(props.total) }];
        nextTick(() => {
            const input = document.querySelector('.amount-input-0');
            if (input) {
                input.focus();
                input.select();
            }
        });
    }
});

const sanitizeAmount = (val) => {
    let value = String(val).replace(/[^\d.]/g, '');
    const parts = value.split('.');
    if (parts.length > 2) value = parts[0] + '.' + parts.slice(1).join('');
    if (parts[1] && parts[1].length > 2) value = parts[0] + '.' + parts[1].substring(0, 2);
    return value;
};

const updateAmount = (index, value) => {
    payments.value[index].amount = sanitizeAmount(value);
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[999999] flex items-center justify-center p-4 overflow-y-auto custom-scrollbar">
            <div class="fixed inset-0 bg-slate-950/60 dark:bg-slate-950/90 backdrop-blur-xl transition-all duration-500" @click="close"></div>
            
            <div class="relative w-full max-w-4xl my-auto bg-white dark:bg-slate-900 rounded-[3.5rem] border border-slate-200 dark:border-white/10 shadow-[0_50px_100px_-20px_rgba(0,0,0,0.3)] dark:shadow-[0_50px_100px_-20px_rgba(0,0,0,0.9)] animate-in zoom-in-95 fade-in duration-300 z-10 overflow-hidden transition-colors duration-500">
                <div class="p-10 lg:p-16">
                    <!-- Header -->
                    <div class="text-center mb-12">
                        <h2 class="text-4xl lg:text-5xl font-black dark:text-white text-slate-900 uppercase tracking-wide leading-tight">
                            Finalizar <span class="text-purple-600 dark:text-purple-400">Cobro</span>
                        </h2>
                        <div class="flex items-center justify-center gap-4 mt-3">
                            <span class="h-[1px] w-12 bg-slate-200 dark:bg-white/10"></span>
                            <p class="text-slate-400 dark:text-slate-500 font-black uppercase tracking-[0.4em] text-[10px]">Gestión de Operación</p>
                            <span class="h-[1px] w-12 bg-slate-200 dark:bg-white/10"></span>
                        </div>
                    </div>
                    
                    <div class="grid lg:grid-cols-2 gap-12">
                        <!-- Left: Payments List -->
                        <div class="space-y-8">
                            <div class="flex items-center justify-between px-2">
                                <label class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Desglose de Pagos</label>
                                <button v-if="payments.length < 4 && remaining > 0" 
                                        @click="addPayment"
                                        class="text-[10px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-wide hover:text-purple-500 transition-all flex items-center gap-2">
                                    <span class="w-4 h-4 rounded-full bg-purple-600/10 flex items-center justify-center">+</span>
                                    Dividir Pago
                                </button>
                            </div>

                            <div v-for="(p, index) in payments" :key="index" 
                                 class="group relative bg-slate-50 dark:bg-slate-950/50 rounded-3xl p-6 border border-slate-200 dark:border-white/5 flex items-center gap-6 transition-all hover:border-purple-600/30 shadow-inner">
                                
                                <div class="w-1/3">
                                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-2 ml-1">Método</p>
                                    <select v-model="p.method" 
                                            class="w-full bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 text-[13px] font-black dark:text-white text-slate-900 uppercase tracking-wide rounded-2xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all py-3 px-4 shadow-sm">
                                        <option v-for="m in methods" :key="m" :value="m">{{ m }}</option>
                                    </select>
                                </div>

                                <div class="flex-1">
                                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-wide mb-2 ml-1">Monto Recibido</p>
                                    <div class="flex items-center gap-3 bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 rounded-2xl px-5 py-2.5 shadow-sm focus-within:border-purple-600 transition-colors">
                                        <span class="text-xl font-black text-slate-300 dark:text-slate-700">$</span>
                                        <input 
                                            type="text"
                                            inputmode="decimal"
                                            v-model="p.amount"
                                            @input="updateAmount(index, $event.target.value)"
                                            :class="`amount-input-${index}`"
                                            class="bg-transparent border-none text-2xl font-black dark:text-white text-slate-900 focus:ring-0 p-0 w-full tracking-tighter"
                                            placeholder="0.00"
                                        />
                                    </div>
                                </div>

                                <button v-if="payments.length > 1" 
                                        @click="removePayment(index)"
                                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-rose-500/5 hover:bg-rose-500 text-rose-500 hover:text-white transition-all duration-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Remaining alert if sums don't match -->
                            <Transition name="fade">
                                <div v-if="remaining > 0" class="p-6 bg-brand-50 dark:bg-brand-500/5 border border-brand-200 dark:border-brand-500/20 rounded-[2rem] flex items-center justify-between shadow-inner">
                                    <div class="flex items-center gap-3">
                                        <div class="w-3 h-3 rounded-full bg-brand-500 animate-pulse shadow-[0_0_10px_rgba(245,158,11,0.5)]"></div>
                                        <span class="text-[11px] font-black text-brand-600 dark:text-brand-500 uppercase tracking-wide">Saldo Pendiente de Cubrir</span>
                                    </div>
                                    <span class="text-xl font-black text-brand-600 dark:text-brand-500">{{ formatCurrency(remaining) }}</span>
                                </div>
                            </Transition>
                        </div>

                        <!-- Right: Totals & Summary -->
                        <div class="flex flex-col justify-center">
                            <div class="bg-slate-50 dark:bg-slate-950/30 rounded-[3rem] p-10 border border-slate-200 dark:border-white/5 space-y-8 shadow-inner transition-colors">
                                <div class="flex justify-between items-center group/total">
                                    <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.25em] group-hover/total:text-purple-600 transition-colors">Subtotal Venta</span>
                                    <span class="text-3xl font-black dark:text-white text-slate-900 tracking-tighter">{{ formatCurrency(total) }}</span>
                                </div>
                                <div class="flex justify-between items-center group/total">
                                    <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.25em] group-hover/total:text-purple-600 transition-colors">Total Ingresado</span>
                                    <span class="text-3xl font-black text-purple-600 dark:text-purple-400 tracking-tighter">{{ formatCurrency(totalPaid) }}</span>
                                </div>
                                
                                <Transition name="change-pop">
                                    <div v-if="change > 0" class="pt-8 border-t border-slate-200 dark:border-white/10 flex justify-between items-center">
                                        <div class="flex flex-col">
                                            <span class="text-[11px] font-black text-emerald-600 dark:text-emerald-500 uppercase tracking-[0.3em] mb-1">Cambio a Entregar</span>
                                            <div class="w-8 h-1 bg-emerald-500 rounded-full"></div>
                                        </div>
                                        <span class="text-5xl font-black text-emerald-600 dark:text-emerald-400 tracking-tighter drop-shadow-sm">{{ formatCurrency(change) }}</span>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-16 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <button 
                            @click="handleConfirm(true)"
                            :disabled="!canConfirm || processing"
                            class="group relative h-28 bg-purple-600 hover:bg-purple-500 disabled:opacity-50 disabled:grayscale text-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(147,51,234,0.3)] transition-all flex items-center justify-center gap-5 overflow-hidden active:scale-95 transform hover:-translate-y-1"
                        >
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:animate-shimmer duration-700"></div>
                            
                            <svg v-if="processing" class="animate-spin h-10 w-10" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <div v-else class="flex items-center gap-5">
                                <div class="w-14 h-14 rounded-2xl bg-white/20 flex items-center justify-center backdrop-blur-md">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </div>
                                <div class="text-left">
                                    <p class="text-xs font-black uppercase tracking-[0.3em] opacity-80 mb-1">Operación Completa</p>
                                    <p class="text-2xl font-black tracking-tight">COBRAR Y TICKET <span class="text-sm font-black opacity-50 ml-2">[F5]</span></p>
                                </div>
                            </div>
                        </button>

                        <button 
                            @click="handleConfirm(false)"
                            :disabled="!canConfirm || processing"
                            class="h-28 bg-slate-100 dark:bg-white/5 hover:bg-slate-200 dark:hover:bg-white/10 dark:text-white text-slate-900 rounded-[2.5rem] shadow-sm border border-slate-200 dark:border-white/5 transition-all active:scale-95 flex flex-col items-center justify-center gap-1 group/btn"
                        >
                            <span class="text-[10px] font-black uppercase tracking-[0.4em] text-slate-400 dark:text-slate-500 group-hover/btn:text-purple-600 transition-colors">Venta Rápida</span>
                            <span class="text-xl font-black tracking-tight">SOLO COBRAR <span class="text-sm font-black opacity-40 ml-2">[F6]</span></span>
                        </button>
                    </div>

                    <div class="mt-10 text-center">
                        <button @click="close" class="px-8 py-3 rounded-2xl hover:bg-slate-100 dark:hover:bg-white/5 text-slate-400 dark:text-slate-600 text-[10px] font-black uppercase tracking-[0.5em] transition-all hover:text-rose-600 dark:hover:text-rose-500 group">
                            <span class="opacity-40 group-hover:opacity-100 transition-opacity">ESC • </span> Cancelar Transacción
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
@keyframes shimmer {
    100% { transform: translateX(100%); }
}
.animate-shimmer {
    animation: shimmer 1.5s infinite;
}
</style>
