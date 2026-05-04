<template>
    <AppLayout title="Editar Cuenta por Cobrar">
        <template #header>
            <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:items-center">
                <div>
                    <h2 class="font-semibold text-xl text-zinc-100 leading-tight">
                        Editar cuenta por cobrar
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">#{{ cuenta.id }} · Actualizar vencimiento, notas y registrar pagos</p>
                </div>
                <Link
                    :href="route('cuentas-por-cobrar.index')"
                    class="mt-2 sm:mt-0 inline-flex items-center justify-center rounded-xl border border-zinc-600/80 bg-zinc-800/80 px-4 py-2 text-sm font-medium text-zinc-200 transition hover:border-zinc-500 hover:bg-zinc-700/90"
                >
                    Listado
                </Link>
            </div>
        </template>

        <div class="min-h-[calc(100vh-8rem)] bg-gradient-to-b from-zinc-950 via-zinc-900 to-black py-10 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl space-y-8">
                <div class="rounded-2xl border border-amber-500/15 bg-gradient-to-br from-amber-500/10 via-zinc-900/40 to-zinc-950 p-6 shadow-xl shadow-black/40">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-amber-200/80">Venta origen</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-100">{{ cuenta.venta?.numero_venta || '—' }}</p>
                            <p class="text-sm text-zinc-400">{{ cuenta.venta?.cliente?.nombre_razon_social || 'N/A' }}</p>
                        </div>
                        <span
                            :class="badgeEstado(cuenta.estado)"
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold capitalize"
                        >
                            {{ cuenta.estado }}
                        </span>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-4 border-t border-zinc-700/40 pt-5 md:grid-cols-4">
                        <div>
                            <span class="text-[10px] uppercase tracking-wider text-zinc-500">Total venta</span>
                            <p class="mt-0.5 font-semibold tabular-nums text-zinc-200">{{ cuenta.venta ? formatCurrency(cuenta.venta.total) : '—' }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-700/50 bg-zinc-900/50 p-6 shadow-xl backdrop-blur-md md:p-8">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Saldos</h3>
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-zinc-700/60 bg-zinc-950/60 p-4">
                            <span class="text-[10px] uppercase tracking-wider text-zinc-500">Monto total</span>
                            <p class="mt-1 text-xl font-bold tabular-nums text-amber-200">{{ formatCurrency(cuenta.monto_total) }}</p>
                        </div>
                        <div class="rounded-xl border border-emerald-500/20 bg-emerald-950/30 p-4">
                            <span class="text-[10px] uppercase tracking-wider text-emerald-400/80">Pagado</span>
                            <p class="mt-1 text-xl font-bold tabular-nums text-emerald-300">{{ formatCurrency(cuenta.monto_pagado) }}</p>
                        </div>
                        <div class="rounded-xl border border-rose-500/20 bg-rose-950/20 p-4">
                            <span class="text-[10px] uppercase tracking-wider text-rose-300/80">Pendiente</span>
                            <p class="mt-1 text-xl font-bold tabular-nums text-rose-200">{{ formatCurrency(cuenta.monto_pendiente) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-700/50 bg-zinc-900/60 shadow-2xl backdrop-blur-md">
                    <div class="border-b border-zinc-700/50 px-6 py-4 md:px-8">
                        <h3 class="text-sm font-semibold text-zinc-200">Registrar pago</h3>
                        <p class="text-xs text-zinc-500">Abono parcial hacia el saldo pendiente</p>
                    </div>
                    <div class="p-6 md:p-8">
                        <div class="rounded-xl border border-emerald-500/15 bg-emerald-950/20 p-5">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label for="monto_pago" class="text-xs font-medium uppercase tracking-wider text-zinc-400">Monto del pago</label>
                                    <input
                                        v-model="pagoForm.monto"
                                        type="number"
                                        step="0.01"
                                        id="monto_pago"
                                        :max="montoPendiente"
                                        class="input-cxc mt-2"
                                        :class="{ 'ring-2 ring-red-500/40': pagoForm.errors.monto }"
                                        placeholder="0.00"
                                    />
                                    <p v-if="pagoForm.errors.monto" class="mt-2 text-sm text-red-400">{{ pagoForm.errors.monto }}</p>
                                </div>
                                <div>
                                    <label for="notas_pago" class="text-xs font-medium uppercase tracking-wider text-zinc-400">Notas / referencia</label>
                                    <input
                                        v-model="pagoForm.notas"
                                        type="text"
                                        id="notas_pago"
                                        class="input-cxc mt-2"
                                        :class="{ 'ring-2 ring-red-500/40': pagoForm.errors.notas }"
                                        placeholder="Folio, banco…"
                                    />
                                    <p v-if="pagoForm.errors.notas" class="mt-2 text-sm text-red-400">{{ pagoForm.errors.notas }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="registrarPago"
                                :disabled="pagoForm.processing || !pagoForm.monto"
                                class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-900/30 transition hover:from-emerald-500 hover:to-emerald-400 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                            >
                                <span v-if="pagoForm.processing">Registrando…</span>
                                <span v-else>Registrar pago</span>
                            </button>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="rounded-2xl border border-zinc-700/50 bg-zinc-900/60 shadow-2xl backdrop-blur-md">
                    <div class="border-b border-zinc-700/50 px-6 py-4 md:px-8">
                        <h3 class="text-sm font-semibold text-zinc-200">Editar información</h3>
                    </div>
                    <div class="space-y-6 p-6 md:p-8">
                        <div>
                            <label for="fecha_vencimiento" class="text-xs font-medium uppercase tracking-wider text-zinc-400">Fecha de vencimiento</label>
                            <input
                                v-model="form.fecha_vencimiento"
                                type="date"
                                id="fecha_vencimiento"
                                class="input-cxc mt-2"
                                :class="{ 'ring-2 ring-red-500/40': form.errors.fecha_vencimiento }"
                            />
                            <p v-if="form.errors.fecha_vencimiento" class="mt-2 text-sm text-red-400">{{ form.errors.fecha_vencimiento }}</p>
                        </div>
                        <div>
                            <label for="notas" class="text-xs font-medium uppercase tracking-wider text-zinc-400">Notas</label>
                            <textarea
                                v-model="form.notas"
                                id="notas"
                                rows="4"
                                class="input-cxc mt-2 min-h-[100px]"
                                :class="{ 'ring-2 ring-red-500/40': form.errors.notas }"
                                placeholder="Notas internas o acuerdos con el cliente…"
                            />
                            <p v-if="form.errors.notas" class="mt-2 text-sm text-red-400">{{ form.errors.notas }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col-reverse gap-3 border-t border-zinc-700/50 px-6 py-5 md:flex-row md:justify-end md:px-8">
                        <Link
                            :href="route('cuentas-por-cobrar.show', cuenta.id)"
                            class="inline-flex items-center justify-center rounded-xl border border-zinc-600 px-5 py-2.5 text-sm font-medium text-zinc-300 transition hover:bg-zinc-800"
                        >
                            Ver detalle
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-2.5 text-sm font-semibold text-zinc-950 shadow-lg shadow-amber-900/25 transition hover:from-amber-400 hover:to-amber-500 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Guardando…</span>
                            <span v-else>Guardar cambios</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    cuenta: {
        type: Object,
        required: true,
    },
});

const badgeEstado = (estado) => {
    const map = {
        vencido: 'bg-rose-500/15 text-rose-200 ring-1 ring-rose-500/30',
        parcial: 'bg-amber-500/15 text-amber-100 ring-1 ring-amber-400/25',
        pagado: 'bg-emerald-500/15 text-emerald-100 ring-1 ring-emerald-400/25',
        pendiente: 'bg-zinc-500/20 text-zinc-200 ring-1 ring-zinc-500/30',
    };
    return map[estado] || map.pendiente;
};

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const toNumber = (value) => {
    if (value === null || value === undefined) {
        return 0;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const formatCurrency = (value) => currencyFormatter.format(toNumber(value));

const montoPendiente = computed(() => toNumber(props.cuenta?.monto_pendiente));

const form = useForm({
    fecha_vencimiento: props.cuenta.fecha_vencimiento ? new Date(props.cuenta.fecha_vencimiento).toISOString().split('T')[0] : '',
    notas: props.cuenta.notas || '',
});

const pagoForm = useForm({
    monto: '',
    notas: '',
});

const submit = () => {
    form.put(route('cuentas-por-cobrar.update', props.cuenta.id), {
        onError: (errors) => {
            console.error('Errores:', errors);
        },
    });
};

const registrarPago = () => {
    pagoForm.post(route('cuentas-por-cobrar.registrar-pago', props.cuenta.id), {
        onSuccess: () => {
            pagoForm.reset();
            window.location.reload();
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        },
    });
};
</script>

<style scoped>
.input-cxc {
    @apply block w-full rounded-xl border border-zinc-600/80 bg-zinc-950/80 px-4 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 shadow-inner shadow-black/20 transition;
    @apply focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/25;
}
</style>
