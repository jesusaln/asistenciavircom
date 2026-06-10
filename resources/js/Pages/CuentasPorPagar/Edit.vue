<template>
    <AppLayout title="Editar CXP">
        <div class="min-h-screen bg-[var(--ui-surface)] px-4 sm:px-6 lg:px-8 py-10 font-sans selection:bg-indigo-500/30">
            <div class="max-w-4xl mx-auto">
                <!-- Header Premium -->
                <div class="flex items-center justify-between mb-10 animate-in fade-in slide-in-from-top-4 duration-700">
                    <div class="flex items-center space-x-5">
                        <div class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center shadow-xl">
                            <svg class="w-7 h-7 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-white tracking-tight">Editar Cuenta #{{ cuenta.id }}</h1>
                            <p class="text-slate-400 text-sm">Actualización de términos y condiciones de pago</p>
                        </div>
                    </div>
                    <Link :href="route('cuentas-por-pagar.show', cuenta.id)" class="text-slate-500 hover:text-white transition-colors text-sm font-bold uppercase tracking-wide px-4 py-2 bg-white/5 rounded-xl border border-white/5">
                        Volver
                    </Link>
                </div>

                <!-- Financial Status Summary Banner -->
                <div class="bg-indigo-600/10 border border-indigo-500/20 rounded-[2rem] p-8 mb-10 backdrop-blur-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div>
                             <p class="text-[10px] font-black text-indigo-400 uppercase tracking-wide mb-1">Monto Total</p>
                             <p class="text-2xl font-black text-white">{{ formatCurrency(cuenta.monto_total) }}</p>
                        </div>
                        <div>
                             <p class="text-[10px] font-black text-emerald-400 uppercase tracking-wide mb-1">Pagado</p>
                             <p class="text-2xl font-black text-emerald-400">{{ formatCurrency(cuenta.monto_pagado) }}</p>
                        </div>
                        <div>
                             <p class="text-[10px] font-black text-rose-400 uppercase tracking-wide mb-1">Pendiente</p>
                             <p class="text-2xl font-black text-rose-400">{{ formatCurrency(cuenta.monto_pendiente) }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Section: Edit General Info -->
                    <div class="bg-black/50 border border-white/5 rounded-[2.5rem] p-10 backdrop-blur-sm shadow-2xl">
                        <form @submit.prevent="submit" class="space-y-6">
                            <h3 class="text-xs font-black text-indigo-400 uppercase tracking-[0.2em] mb-8 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
                                Datos Modificables
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">Fecha de Vencimiento</label>
                                    <input v-model="form.fecha_vencimiento" type="date"
                                           class="w-full bg-slate-800/80 border border-white/5 rounded-2xl px-6 py-5 text-white font-bold focus:ring-2 focus:ring-brand-500 outline-none transition-all shadow-inner" />
                                    <p v-if="form.errors.fecha_vencimiento" class="text-rose-500 text-xs mt-2 font-bold">{{ form.errors.fecha_vencimiento }}</p>
                                </div>
                                
                                <div class="flex flex-col justify-end">
                                     <div class="p-5 rounded-2xl bg-white/5 border border-white/5">
                                        <p class="text-xs text-slate-500 mb-1">Estado actual del flujo</p>
                                        <div :class="estadoClases" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wide inline-block">
                                            {{ cuenta.estado }}
                                        </div>
                                     </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em] mb-3 ml-1">Notas Internas</label>
                                <textarea v-model="form.notas" rows="4"
                                          class="w-full bg-slate-800/50 border border-white/5 rounded-2xl px-6 py-5 text-slate-200 focus:ring-2 focus:ring-brand-500 outline-none transition-all resize-none shadow-inner"
                                          placeholder="Anotaciones extra..."></textarea>
                            </div>

                            <div class="flex justify-end gap-4 border-t border-white/5 pt-8">
                                <Link :href="route('cuentas-por-pagar.show', cuenta.id)" 
                                      class="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 text-slate-400 font-bold transition-all">
                                    Descartar
                                </Link>
                                <button type="submit" :disabled="form.processing"
                                        class="px-10 py-4 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white font-black transition-all shadow-xl shadow-indigo-600/20 active:scale-95 disabled:opacity-50">
                                    {{ form.processing ? 'Guardando...' : 'Aplicar Cambios' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Quick Payment Section (Optional inline) -->
                    <div v-if="toNumber(cuenta.monto_pendiente) > 0" class="bg-brand-500/5 border border-emerald-500/10 rounded-[2.5rem] p-10 backdrop-blur-sm relative overflow-hidden group">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-brand-500/5 rounded-full blur-3xl group-hover:bg-slate-500/10 transition-colors"></div>
                        
                        <h3 class="text-xs font-black text-emerald-400 uppercase tracking-[0.2em] mb-8 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" stroke-width="2" /></svg>
                            Registrar Abono Rápido
                        </h3>

                        <form @submit.prevent="registrarPago" class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                            <div class="md:col-span-1">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2 ml-1">Monto Abono</label>
                                <input v-model="pagoForm.monto" type="number" step="0.01" :max="cuenta.monto_pendiente"
                                       class="w-full bg-slate-900/80 border border-white/5 rounded-xl px-4 py-3 text-emerald-400 font-bold focus:ring-1 focus:ring-brand-500 outline-none" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2 ml-1">Concepto / Referencia</label>
                                <input v-model="pagoForm.notas" type="text"
                                       class="w-full bg-slate-900/80 border border-white/5 rounded-xl px-4 py-3 text-white focus:ring-1 focus:ring-brand-500 outline-none" />
                            </div>
                            <button type="submit" :disabled="pagoForm.processing || !pagoForm.monto"
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 rounded-xl transition-all shadow-xl shadow-emerald-600/20 disabled:opacity-30">
                                Registrar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps({
    cuenta: { type: Object, required: true },
});

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' }
});

const toNumber = (v) => { let n = Number(v); return isFinite(n) ? n : 0; };
const formatCurrency = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(toNumber(v));

const form = useForm({
    fecha_vencimiento: props.cuenta.fecha_vencimiento ? new Date(props.cuenta.fecha_vencimiento).toISOString().split('T')[0] : '',
    notas: props.cuenta.notas || '',
});

const pagoForm = useForm({
    monto: '',
    notas: '',
});

const estadoClases = computed(() => {
    const s = props.cuenta.estado;
    if (s === 'pagado') return 'bg-brand-500/20 text-emerald-400 border border-emerald-500/30';
    if (s === 'parcial') return 'bg-brand-500/20 text-brand-400 border border-brand-500/30';
    if (s === 'vencido') return 'bg-brand-500/20 text-rose-400 border border-rose-500/30';
    return 'bg-slate-500/20 text-slate-400 border border-slate-500/30';
});

const submit = () => {
    form.put(route('cuentas-por-pagar.update', props.cuenta.id), {
        onSuccess: () => notyf.success('Información actualizada'),
        onError: (err) => {
            const msg = Object.values(err)[0];
            if (msg) notyf.error(msg);
        }
    });
};

const registrarPago = () => {
    pagoForm.post(route('cuentas-por-pagar.registrar-pago', props.cuenta.id), {
        onSuccess: () => {
            notyf.success('Pago registrado correctamente');
            pagoForm.reset();
        },
        onError: (err) => {
             const msg = Object.values(err)[0];
             if (msg) notyf.error(msg);
        }
    });
};
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
