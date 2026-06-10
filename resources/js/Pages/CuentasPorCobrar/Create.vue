<template>
    <AppLayout title="Crear Cuenta por Cobrar">
        <template #header>
            <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:items-center">
                <div>
                    <h2 class="font-semibold text-xl text-zinc-100 leading-tight tracking-tight">
                        Nueva cuenta por cobrar
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">Registra un saldo pendiente vinculado a una venta</p>
                </div>
                <Link
                    :href="route('cuentas-por-cobrar.index')"
                    class="mt-2 sm:mt-0 inline-flex items-center justify-center rounded-xl border border-zinc-600/80 bg-zinc-800/80 px-4 py-2 text-sm font-medium text-zinc-200 shadow-sm transition hover:border-brand-500 hover:bg-zinc-700/90"
                >
                    Cancelar
                </Link>
            </div>
        </template>

        <div class="min-h-[calc(100vh-8rem)] bg-gradient-to-b from-zinc-950 via-zinc-900 to-black py-10 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl">
                <!-- Hero strip -->
                <div class="mb-8 rounded-2xl border border-brand-500/15 bg-gradient-to-br from-brand-500/10 via-zinc-900/60 to-zinc-950 p-6 shadow-xl shadow-black/40 backdrop-blur-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-500/20 ring-1 ring-brand-400/30">
                            <svg class="h-6 w-6 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-brand-200/80">Cartera</p>
                            <p class="text-sm text-zinc-400">Los importes se pueden ajustar según acuerdos con el cliente.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-700/50 bg-zinc-900/60 shadow-2xl shadow-black/50 backdrop-blur-md">
                    <div class="border-b border-zinc-700/50 px-6 py-5 md:px-8">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-zinc-300">Datos de la cuenta</h3>
                    </div>
                    <div class="p-6 md:p-8">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div v-if="venta" class="rounded-xl border border-zinc-700/60 bg-zinc-950/50 p-5">
                                <h4 class="mb-4 text-xs font-semibold uppercase tracking-wider text-brand-200/90">Venta seleccionada</h4>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <span class="text-xs text-zinc-500">Número de venta</span>
                                        <p class="mt-1 font-mono text-sm text-zinc-100">{{ venta.numero_venta }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-zinc-500">Cliente</span>
                                        <p class="mt-1 text-sm text-zinc-100">{{ venta.cliente?.nombre_razon_social || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-zinc-500">Total venta</span>
                                        <p class="mt-1 text-lg font-semibold tabular-nums text-amber-200">{{ formatCurrency(venta.total) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-zinc-500">Fecha</span>
                                        <p class="mt-1 text-sm text-zinc-300">{{ new Date(venta.created_at).toLocaleDateString('es-MX') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="!venta">
                                <label for="venta_id" class="block text-xs font-medium uppercase tracking-wider text-zinc-400">
                                    Seleccionar venta
                                </label>
                                <select
                                    v-model="form.venta_id"
                                    id="venta_id"
                                    class="input-cxc mt-2"
                                    required
                                >
                                    <option value="">Seleccione una venta</option>
                                    <option
                                        v-for="ventaOption in ventas"
                                        :key="ventaOption.id"
                                        :value="ventaOption.id"
                                    >
                                        {{ ventaOption.numero_venta }} — {{ ventaOption.cliente?.nombre_razon_social || 'N/A' }} ({{ formatCurrency(ventaOption.total) }})
                                    </option>
                                </select>
                                <p v-if="form.errors.venta_id" class="mt-2 text-sm text-rose-400">
                                    {{ form.errors.venta_id }}
                                </p>
                            </div>

                            <div>
                                <label for="monto_total" class="block text-xs font-medium uppercase tracking-wider text-zinc-400">
                                    Monto total (CXC)
                                </label>
                                <input
                                    v-model="form.monto_total"
                                    type="number"
                                    step="0.01"
                                    id="monto_total"
                                    class="input-cxc mt-2"
                                    :class="{ 'ring-2 ring-rose-500/50': form.errors.monto_total }"
                                    required
                                />
                                <p v-if="form.errors.monto_total" class="mt-2 text-sm text-rose-400">
                                    {{ form.errors.monto_total }}
                                </p>
                            </div>

                            <div>
                                <label for="fecha_vencimiento" class="block text-xs font-medium uppercase tracking-wider text-zinc-400">
                                    Fecha de vencimiento
                                </label>
                                <input
                                    v-model="form.fecha_vencimiento"
                                    type="date"
                                    id="fecha_vencimiento"
                                    class="input-cxc mt-2"
                                    :class="{ 'ring-2 ring-rose-500/50': form.errors.fecha_vencimiento }"
                                />
                                <p class="mt-2 text-xs text-zinc-500">
                                    Opcional. Si no indicas fecha, se usarán 30 días por defecto.
                                </p>
                                <p v-if="form.errors.fecha_vencimiento" class="mt-2 text-sm text-rose-400">
                                    {{ form.errors.fecha_vencimiento }}
                                </p>
                            </div>

                            <div>
                                <label for="notas" class="block text-xs font-medium uppercase tracking-wider text-zinc-400">
                                    Notas
                                </label>
                                <textarea
                                    v-model="form.notas"
                                    id="notas"
                                    rows="4"
                                    class="input-cxc mt-2 min-h-[100px]"
                                    :class="{ 'ring-2 ring-rose-500/50': form.errors.notas }"
                                    placeholder="Condiciones de pago, referencias…"
                                />
                                <p v-if="form.errors.notas" class="mt-2 text-sm text-rose-400">
                                    {{ form.errors.notas }}
                                </p>
                            </div>

                            <div class="flex flex-col-reverse gap-3 border-t border-zinc-700/50 pt-6 sm:flex-row sm:justify-end">
                                <Link
                                    :href="route('cuentas-por-cobrar.index')"
                                    class="inline-flex items-center justify-center rounded-xl border border-zinc-600 bg-transparent px-5 py-2.5 text-sm font-medium text-zinc-300 transition hover:bg-zinc-800"
                                >
                                    Volver al listado
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-zinc-950 shadow-xl shadow-brand-900/30 transition hover:from-brand-400 hover:to-brand-500 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <span v-if="form.processing">Guardando…</span>
                                    <span v-else>Crear cuenta por cobrar</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { onMounted } from 'vue';
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

const { formatCurrency } = useFormatters();

const toNumber = (value) => {
    if (value === null || value === undefined) {
        return 0;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const submit = () => {
    form.post(route('cuentas-por-cobrar.store'), {
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

