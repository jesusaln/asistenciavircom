<template>
    <AppLayout title="Crear CXP">
        <div class="min-h-screen bg-slate-950 px-4 sm:px-6 lg:px-8 py-10 font-sans selection:bg-indigo-500/30">
            <div class="max-w-4xl mx-auto">
                <!-- Header Premium -->
                <div class="flex items-center justify-between mb-10 animate-in fade-in slide-in-from-top-4 duration-700">
                    <div class="flex items-center space-x-5">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                            <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-white tracking-tight">Nueva Cuenta por Pagar</h1>
                            <p class="text-slate-400 text-sm">Registro manual de obligaciones financieras</p>
                        </div>
                    </div>
                    <Link :href="route('cuentas-por-pagar.index')" class="text-slate-500 hover:text-white transition-colors text-sm font-bold uppercase tracking-widest">
                        Cancelar
                    </Link>
                </div>

                <form @submit.prevent="submit" class="space-y-8">
                    <!-- Section: Compra / Origen -->
                    <div class="bg-slate-900/50 border border-white/5 rounded-[2rem] p-8 backdrop-blur-sm shadow-xl">
                        <h3 class="text-xs font-black text-indigo-400 uppercase tracking-[0.2em] mb-8 flex items-center gap-2">
                             <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                             Origen de la Obligación
                        </h3>

                        <!-- Compra info if exists -->
                        <div v-if="compra" class="bg-indigo-500/5 border border-indigo-500/10 rounded-2xl p-6 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">
                                <div>
                                    <p class="text-slate-500 mb-1">Folio de Compra</p>
                                    <p class="text-white font-bold text-lg">{{ compra.numero_compra }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 mb-1">Proveedor</p>
                                    <p class="text-white font-bold text-lg">{{ compra.proveedor?.nombre_razon_social || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 mb-1">Importe Original</p>
                                    <p class="text-indigo-400 font-black text-xl">{{ formatCurrency(compra.total) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500 mb-1">Fecha de Registro</p>
                                    <p class="text-white font-medium">{{ new Date(compra.created_at).toLocaleDateString() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Selector switch if no purchase -->
                        <div v-if="!compra" class="space-y-6">
                            <div class="group relative">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 ml-1">Seleccionar Compra Pendiente</label>
                                <select v-model="form.compra_id" 
                                        class="w-full bg-slate-800/50 border border-white/10 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all appearance-none cursor-pointer">
                                    <option value="">-- Elige una compra --</option>
                                    <option v-for="c in compras" :key="c.id" :value="c.id">
                                        {{ c.numero_compra }} | {{ c.proveedor?.nombre_razon_social }} | {{ formatCurrency(c.total) }}
                                    </option>
                                </select>
                                <div class="absolute right-6 top-[38px] pointer-events-none text-slate-500">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" stroke-width="2" /></svg>
                                </div>
                                <p v-if="form.errors.compra_id" class="text-rose-500 text-xs mt-2 font-bold ml-1">{{ form.errors.compra_id }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Financial Data -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-slate-900/50 border border-white/5 rounded-[2rem] p-8 backdrop-blur-sm">
                             <label class="block text-xs font-black text-emerald-400 uppercase tracking-[0.2em] mb-4">Total a Pagar</label>
                             <div class="relative group">
                                <span class="absolute left-6 top-1/2 -translate-y-1/2 text-emerald-500/50 font-black text-2xl">$</span>
                                <input v-model="form.monto_total" type="number" step="0.01" 
                                       class="w-full bg-slate-800/80 border border-white/5 rounded-2xl pl-12 pr-6 py-6 text-3xl font-black text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all placeholder:text-slate-700"
                                       placeholder="0.00" />
                             </div>
                             <p v-if="form.errors.monto_total" class="text-rose-500 text-xs mt-2 font-bold">{{ form.errors.monto_total }}</p>
                        </div>

                        <div class="bg-slate-900/50 border border-white/5 rounded-[2rem] p-8 backdrop-blur-sm">
                             <label class="block text-xs font-black text-indigo-400 uppercase tracking-[0.2em] mb-4">Fecha Vencimiento</label>
                             <input v-model="form.fecha_vencimiento" type="date"
                                    class="w-full bg-slate-800/80 border border-white/5 rounded-2xl px-6 py-6 text-xl font-bold text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all" />
                             <p class="text-[10px] text-slate-500 mt-3 font-medium italic">Sugerencia: 30 días a partir de hoy</p>
                             <p v-if="form.errors.fecha_vencimiento" class="text-rose-500 text-xs mt-2 font-bold">{{ form.errors.fecha_vencimiento }}</p>
                        </div>
                    </div>

                    <!-- Section: Notes -->
                    <div class="bg-slate-900/50 border border-white/5 rounded-[2rem] p-8 backdrop-blur-sm">
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-[0.2em] mb-4">Notas y Observaciones</label>
                        <textarea v-model="form.notas" rows="3"
                                  class="w-full bg-slate-800/50 border border-white/10 rounded-2xl px-6 py-4 text-white focus:ring-2 focus:ring-indigo-500 outline-none transition-all resize-none"
                                  placeholder="Detalles adicionales, condiciones de crédito, etc."></textarea>
                    </div>

                    <!-- Submit Area -->
                    <div class="flex flex-col md:flex-row items-center justify-end gap-6 pt-6">
                        <button type="submit" :disabled="form.processing"
                                class="w-full md:w-auto px-12 py-5 rounded-2xl bg-indigo-600 hover:bg-white hover:text-slate-950 text-white font-black text-lg transition-all shadow-2xl shadow-indigo-600/20 disabled:opacity-50 flex items-center justify-center gap-3 active:scale-95">
                            <span v-if="form.processing">Registrando...</span>
                            <template v-else>
                                Crear Cuenta por Pagar
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M14 5l7 7m0 0l-7 7m7-7H3" stroke-width="2.5" /></svg>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps({
    compra: Object,
    compras: { type: Array, default: () => [] },
});

const notyf = new Notyf({
    duration: 5000,
    position: { x: 'right', y: 'top' },
    types: [{ type: 'error', background: '#ef4444', icon: false }]
});

const formatCurrency = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0);

const form = useForm({
    compra_id: props.compra ? props.compra.id : '',
    monto_total: props.compra ? props.compra.total : '',
    fecha_vencimiento: '',
    notas: '',
});

const submit = () => {
    form.post(route('cuentas-por-pagar.store'), {
        onSuccess: () => {}, // Inertia handles redirect
        onError: (err) => {
            const first = Object.values(err)[0];
            if (first) notyf.error(first);
        },
    });
};

onMounted(() => {
    // Default expiration: 30 days
    const d = new Date();
    d.setDate(d.getDate() + 30);
    form.fecha_vencimiento = d.toISOString().split('T')[0];
    
    if (props.compra) {
        form.compra_id = props.compra.id;
        form.monto_total = props.compra.total;
    }
});
</script>

<style scoped>
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  -moz-appearance: textfield;
}
</style>
