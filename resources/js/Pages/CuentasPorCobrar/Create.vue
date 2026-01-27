<template>
    <AppLayout title="Crear Cuenta por Cobrar">
        <Head title="Crear Cuenta" />

        <div class="min-h-screen bg-[#0F172A] text-slate-300 pb-12 relative overflow-hidden">
            <!-- Background Gradients -->
            <div class="absolute top-0 right-0 -translate-y-1/2 translate-x-1/2 w-[500px] h-[500px] bg-indigo-600/10 blur-[120px] rounded-full pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 translate-y-1/2 -translate-x-1/2 w-[500px] h-[500px] bg-emerald-600/10 blur-[120px] rounded-full pointer-events-none"></div>

            <!-- Header Section -->
            <div class="relative z-10 pt-8 pb-8 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <Link :href="route('cuentas-por-cobrar.index')" class="flex items-center text-slate-500 hover:text-indigo-400 text-xs font-black uppercase tracking-widest mb-2 transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                            Volver a Cuentas
                        </Link>
                        <h1 class="text-3xl font-black text-white tracking-tight">Nueva Cuenta por Cobrar</h1>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="bg-slate-800/40 backdrop-blur-xl border border-slate-700/50 rounded-3xl overflow-hidden shadow-2xl relative">
                    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500 via-emerald-500 to-indigo-500"></div>
                    
                    <form @submit.prevent="submit" class="p-8 space-y-8">
                        
                        <!-- Sección Venta Pre-seleccionada -->
                        <div v-if="venta" class="bg-slate-900/50 rounded-2xl border border-slate-800 p-6 space-y-4">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <h3 class="text-lg font-bold text-white">Información de Origen</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Referencia</label>
                                    <p class="text-sm font-bold text-slate-200">Venta #{{ venta.numero_venta }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Cliente</label>
                                    <p class="text-sm font-bold text-slate-200">{{ venta.cliente?.nombre_razon_social || 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Monto Original</label>
                                    <p class="text-sm font-bold text-emerald-400">{{ formatCurrency(venta.total) }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Fecha Emisión</label>
                                    <p class="text-sm font-bold text-slate-200">{{ new Date(venta.created_at).toLocaleDateString() }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Selección Manual de Venta -->
                        <div v-if="!venta" class="space-y-2">
                            <label for="venta_id" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                Seleccionar Venta Origen
                            </label>
                            <select
                                v-model="form.venta_id"
                                id="venta_id"
                                class="w-full bg-slate-900 border border-slate-700 text-slate-200 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 transition-colors"
                                required
                            >
                                <option value="">-- Seleccione una Venta --</option>
                                <option
                                    v-for="ventaOption in ventas"
                                    :key="ventaOption.id"
                                    :value="ventaOption.id"
                                >
                                    {{ ventaOption.numero_venta }} - {{ ventaOption.cliente?.nombre_razon_social }} ({{ formatCurrency(ventaOption.total) }})
                                </option>
                            </select>
                            <p v-if="form.errors.venta_id" class="text-xs text-red-400 font-bold mt-1">{{ form.errors.venta_id }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Monto Total -->
                            <div class="space-y-2">
                                <label for="monto_total" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Monto a Cobrar ($)
                                </label>
                                <input
                                    v-model="form.monto_total"
                                    type="number"
                                    step="0.01"
                                    id="monto_total"
                                    class="w-full bg-slate-900 border border-slate-700 text-white font-mono text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 transition-all"
                                    :class="{ 'border-red-500 focus:border-red-500': form.errors.monto_total }"
                                    required
                                />
                                <p v-if="form.errors.monto_total" class="text-xs text-red-400 font-bold mt-1">{{ form.errors.monto_total }}</p>
                            </div>

                            <!-- Fecha Vencimiento -->
                            <div class="space-y-2">
                                <label for="fecha_vencimiento" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                    Fecha Vencimiento
                                </label>
                                <input
                                    v-model="form.fecha_vencimiento"
                                    type="date"
                                    id="fecha_vencimiento"
                                    class="w-full bg-slate-900 border border-slate-700 text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 transition-all placeholder-slate-600"
                                    :class="{ 'border-red-500 focus:border-red-500': form.errors.fecha_vencimiento }"
                                />
                                <p class="text-[10px] text-slate-500 font-medium italic">30 días por defecto si se deja vacío</p>
                                <p v-if="form.errors.fecha_vencimiento" class="text-xs text-red-400 font-bold mt-1">{{ form.errors.fecha_vencimiento }}</p>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div class="space-y-2">
                            <label for="notas" class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                Notas Internas
                            </label>
                            <textarea
                                v-model="form.notas"
                                id="notas"
                                rows="4"
                                class="w-full bg-slate-900 border border-slate-700 text-slate-300 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3 transition-all resize-none"
                                placeholder="Agregar detalles o recordatorios sobre este cobro..."
                            ></textarea>
                            <p v-if="form.errors.notas" class="text-xs text-red-400 font-bold mt-1">{{ form.errors.notas }}</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-700/50">
                            <Link
                                :href="route('cuentas-por-cobrar.index')"
                                class="px-6 py-3 rounded-xl border border-slate-600 text-slate-400 font-bold uppercase text-xs tracking-widest hover:bg-slate-800 hover:text-white transition-all"
                            >
                                Cancelar
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white font-black uppercase text-xs tracking-[0.15em] rounded-xl shadow-lg shadow-indigo-900/40 transform active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-3 w-3 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Procesando...
                                </span>
                                <span v-else>Registrar Cuenta</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    venta: Object,
    ventas: Array,
});

const form = useForm({
    venta_id: props.venta ? props.venta.id : '',
    monto_total: props.venta ? props.venta.total : '',
    fecha_vencimiento: '',
    notas: '',
});

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const toNumber = (value) => {
    if (value === null || value === undefined) {
        return 0;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const formatCurrency = (value) => currencyFormatter.format(toNumber(value));

const submit = () => {
    form.post(route('cuentas-por-cobrar.store'), {
        onSuccess: () => {
            // Redirigir al índice
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        },
    });
};

onMounted(() => {
    const fechaVencimiento = new Date();
    fechaVencimiento.setDate(fechaVencimiento.getDate() + 30);
    form.fecha_vencimiento = fechaVencimiento.toISOString().split('T')[0];

    if (props.venta) {
        form.venta_id = props.venta.id;
        form.monto_total = props.venta.total;
    }
});
</script>


