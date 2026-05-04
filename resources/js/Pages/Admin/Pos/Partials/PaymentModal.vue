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
        <div v-if="show" class="fixed inset-0 z-[999999] flex items-center justify-center p-4 overflow-y-auto">
            <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-xl" @click="close"></div>
            
            <div class="relative w-full max-w-4xl my-auto bg-slate-900 rounded-[3rem] border border-white/10 shadow-[0_50px_100px_-20px_rgba(0,0,0,0.9)] animate-in zoom-in-95 fade-in duration-300 z-10 overflow-hidden">
                <div class="p-8 lg:p-12">
                    <!-- Header -->
                    <div class="text-center mb-10">
                        <h2 class="text-3xl lg:text-4xl font-black text-white uppercase tracking-tighter">
                            Finalizar <span class="text-purple-400">Cobro</span>
                        </h2>
                        <p class="text-slate-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-2">Pagos Divididos y Opciones de Ticket</p>
                    </div>
                    
                    <div class="grid lg:grid-cols-2 gap-10">
                        <!-- Left: Payments List -->
                        <div class="space-y-4">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Desglose de Pagos</label>
                                <button v-if="payments.length < 4 && remaining > 0" 
                                        @click="addPayment"
                                        class="text-[10px] font-black text-purple-400 uppercase tracking-widest hover:text-purple-300 transition-colors">
                                    + Agregar Otro
                                </button>
                            </div>

                            <div v-for="(p, index) in payments" :key="index" 
                                 class="group relative bg-slate-950/50 rounded-2xl p-4 border border-white/5 flex items-center gap-4 transition-all hover:border-purple-500/30">
                                
                                <select v-model="p.method" 
                                        class="bg-slate-900 border-none text-xs font-black text-white uppercase tracking-widest rounded-xl focus:ring-2 focus:ring-purple-500 py-2 pl-4 pr-10">
                                    <option v-for="m in methods" :key="m" :value="m">{{ m }}</option>
                                </select>

                                <div class="flex-1 flex items-center gap-2">
                                    <span class="text-lg font-black text-slate-700">$</span>
                                    <input 
                                        type="text"
                                        inputmode="decimal"
                                        v-model="p.amount"
                                        @input="updateAmount(index, $event.target.value)"
                                        :class="`amount-input-${index}`"
                                        class="bg-transparent border-none text-2xl font-black text-white focus:ring-0 p-0 w-full"
                                        placeholder="0.00"
                                    />
                                </div>

                                <button v-if="payments.length > 1" 
                                        @click="removePayment(index)"
                                        class="text-slate-600 hover:text-rose-500 transition-colors p-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Remaining alert if sums don't match -->
                            <div v-if="remaining > 0" class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
                                <span class="text-[10px] font-black text-amber-500 uppercase tracking-widest">Faltan {{ formatCurrency(remaining) }} por cubrir</span>
                            </div>
                        </div>

                        <!-- Right: Totals & Summary -->
                        <div class="flex flex-col justify-center space-y-4">
                            <div class="bg-slate-950/30 rounded-3xl p-8 border border-white/5 space-y-6">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-bold text-slate-500 uppercase tracking-widest">Total a Pagar</span>
                                    <span class="text-3xl font-black text-white">{{ formatCurrency(total) }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-bold text-slate-500 uppercase tracking-widest">Total Recibido</span>
                                    <span class="text-3xl font-black text-purple-400">{{ formatCurrency(totalPaid) }}</span>
                                </div>
                                <div v-if="change > 0" class="pt-6 border-t border-white/5 flex justify-between items-center">
                                    <span class="text-sm font-black text-emerald-500 uppercase tracking-widest">Su Cambio</span>
                                    <span class="text-4xl font-black text-emerald-400">{{ formatCurrency(change) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-12 space-y-3">
                        <button 
                            @click="handleConfirm(true)"
                            :disabled="!canConfirm || processing"
                            class="group relative w-full h-24 bg-purple-600 hover:bg-purple-500 disabled:opacity-50 disabled:grayscale text-white rounded-[2rem] shadow-2xl shadow-purple-900/40 transition-all flex items-center justify-center gap-4 overflow-hidden"
                        >
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:animate-shimmer"></div>
                            <svg v-if="processing" class="animate-spin h-8 w-8" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <div v-else class="text-center">
                                <div class="text-2xl font-black uppercase tracking-widest flex items-center justify-center gap-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    COBRAR Y TICKET [F5]
                                </div>
                            </div>
                        </button>

                        <button 
                            @click="handleConfirm(false)"
                            :disabled="!canConfirm || processing"
                            class="w-full h-16 bg-slate-800/50 hover:bg-slate-800 text-slate-400 hover:text-white rounded-2xl text-xs font-black uppercase tracking-[0.3em] transition-all border border-white/5"
                        >
                            SOLO COBRAR (Sin Ticket) [F6]
                        </button>

                        <button @click="close" class="w-full py-2 text-slate-600 text-[10px] font-black uppercase tracking-[0.4em] hover:text-white transition-colors">
                            ESC para cancelar
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
