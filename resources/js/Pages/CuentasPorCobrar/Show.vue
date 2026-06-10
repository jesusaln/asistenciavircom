<template>
    <AppLayout title="Detalles de Cuenta por Cobrar">
        <template #header>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-zinc-100 leading-tight tracking-tight">
                        Cuenta por cobrar
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">#{{ cuenta.id }} · Detalle y cobros</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('cuentas-por-cobrar.edit', cuenta.id)"
                        class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2 text-sm font-semibold text-zinc-950 shadow-xl shadow-brand-900/25 transition hover:from-brand-400 hover:to-brand-500"
                    >
                        Editar
                    </Link>
                    <Link
                        :href="route('cuentas-por-cobrar.index')"
                        class="inline-flex items-center justify-center rounded-xl border border-zinc-600/80 bg-zinc-800/80 px-4 py-2 text-sm font-medium text-zinc-200 transition hover:border-brand-500 hover:bg-zinc-700/90"
                    >
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="min-h-[calc(100vh-8rem)] bg-gradient-to-b from-zinc-950 via-zinc-900 to-black py-10 px-4 sm:px-6 lg:px-8">
            <div class="mx-auto w-full max-w-6xl">
                <div class="overflow-hidden rounded-2xl border border-zinc-700/50 bg-zinc-900/50 shadow-2xl shadow-black/50 backdrop-blur-md">
                    <div class="border-b border-zinc-700/50 bg-zinc-950/40 p-6 md:p-8">
                        <!-- Información General -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-brand-200/90">Información general</h3>
                            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 md:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">ID de cuenta</dt>
                                    <dd class="mt-1 font-mono text-sm text-zinc-100">#{{ cuenta.id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Estado</dt>
                                    <dd class="mt-1">
                                        <span :class="{
                                            'bg-brand-500/15 text-rose-200 ring-1 ring-rose-500/30': cuenta.estado === 'vencido',
                                            'bg-brand-500/15 text-brand-100 ring-1 ring-brand-400/25': cuenta.estado === 'parcial',
                                            'bg-brand-500/15 text-emerald-100 ring-1 ring-emerald-400/25': cuenta.estado === 'pagado',
                                            'bg-zinc-500/20 text-zinc-200 ring-1 ring-zinc-500/30': cuenta.estado === 'pendiente'
                                        }" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize">
                                            {{ cuenta.estado }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Fecha de creación</dt>
                                    <dd class="mt-1 text-sm text-zinc-200">{{ new Date(cuenta.created_at).toLocaleDateString('es-MX') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Fecha de vencimiento</dt>
                                    <dd class="mt-1 text-sm text-zinc-200">
                                        {{ cuenta.fecha_vencimiento ? new Date(cuenta.fecha_vencimiento).toLocaleDateString('es-MX') : 'No especificada' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Información de Origen -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-brand-200/90">Origen</h3>
                            <div class="rounded-xl border border-zinc-700/60 bg-zinc-950/50 p-5">
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 md:grid-cols-2">
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Referencia</dt>
                                        <dd class="mt-1 text-sm text-zinc-100">
                                            <template v-if="cuenta.cobrable_type && cuenta.cobrable_type.includes('Venta')">
                                                <Link :href="route('ventas.show', cuenta.cobrable?.id)" class="font-medium text-brand-300 hover:underline decoration-brand-500/40 underline-offset-2 hover:text-amber-200">
                                                    Venta #{{ cuenta.cobrable?.numero_venta || '??' }}
                                                </Link>
                                            </template>
                                            <template v-else-if="cuenta.cobrable_type && cuenta.cobrable_type.includes('Renta')">
                                                <Link :href="route('rentas.show', cuenta.cobrable?.id)" class="font-medium text-brand-300 hover:underline decoration-brand-500/40 underline-offset-2 hover:text-amber-200">
                                                    Renta #{{ cuenta.cobrable?.numero_contrato || '??' }}
                                                </Link>
                                                <div class="mt-1 text-xs text-zinc-500">{{ cuenta.notas }}</div>
                                            </template>
                                            <template v-else>
                                                {{ cuenta.venta?.numero_venta || 'N/A' }}
                                            </template>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Cliente</dt>
                                        <dd class="mt-1 text-sm text-zinc-100">{{ cuenta.cobrable?.cliente?.nombre_razon_social || cuenta.venta?.cliente?.nombre_razon_social || 'Cliente no disponible' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Total original</dt>
                                        <dd class="mt-1 text-sm tabular-nums text-zinc-200">{{ cuenta.cobrable_type && cuenta.cobrable_type.includes('Venta') ? formatCurrency(cuenta.cobrable.total) : formatCurrency(cuenta.monto_total) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Estado origen</dt>
                                        <dd class="mt-1 text-sm text-zinc-300">{{ cuenta.cobrable?.estado || cuenta.venta?.estado }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Detalles de Artículos (Productos o Equipos) -->
                        <div class="mb-8" v-if="(cuenta.cobrable_type && cuenta.cobrable_type.includes('Venta') && cuenta.cobrable?.items?.length) || (cuenta.cobrable_type && cuenta.cobrable_type.includes('Renta') && cuenta.cobrable?.equipos?.length)">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-brand-200/90">
                                {{ cuenta.cobrable_type.includes('Venta') ? 'Productos vendidos' : 'Equipos en renta' }}
                            </h3>
                            <div class="overflow-hidden rounded-xl border border-zinc-700/60">
                                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                                    <thead class="bg-slate-50 dark:bg-slate-800/50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                                                Descripción
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                                                {{ cuenta.cobrable_type.includes('Venta') ? 'Cantidad' : 'Serie' }}
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                                                {{ cuenta.cobrable_type.includes('Venta') ? 'Precio unit.' : 'Precio mensual' }}
                                            </th>
                                            <th scope="col" v-if="cuenta.cobrable_type.includes('Venta')" class="px-6 py-3 text-right text-[10px] font-semibold uppercase tracking-wider text-zinc-400">
                                                Subtotal
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                                        <!-- Iteración para Ventas -->
                                        <template v-if="cuenta.cobrable_type.includes('Venta')">
                                            <tr v-for="item in cuenta.cobrable.items" :key="item.id" class="hover:bg-zinc-800/30">
                                                <td class="px-6 py-4 text-sm text-zinc-100">
                                                    {{ item.ventable?.nombre || 'Producto desconocido' }}
                                                    <div v-if="item.notas" class="text-xs text-zinc-500">{{ item.notas }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm tabular-nums text-zinc-200">
                                                    {{ item.cantidad }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm tabular-nums text-zinc-200">
                                                    {{ formatCurrency(item.precio_unitario) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium tabular-nums text-amber-100">
                                                    {{ formatCurrency(item.subtotal) }}
                                                </td>
                                            </tr>
                                        </template>
                                        <!-- Iteración para Rentas -->
                                        <template v-else-if="cuenta.cobrable_type.includes('Renta')">
                                            <tr v-for="equipo in cuenta.cobrable.equipos" :key="equipo.id" class="hover:bg-zinc-800/30">
                                                <td class="px-6 py-4 text-sm text-zinc-100">
                                                    {{ equipo.nombre }}
                                                    <div class="text-xs text-zinc-500">{{ equipo.modelo }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-zinc-200">
                                                    {{ equipo.serie || 'S/N' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm tabular-nums text-amber-100">
                                                    {{ formatCurrency(equipo.pivot?.precio_mensual || 0) }}
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Estado de Cobros -->
                        <div class="mb-8">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-brand-200/90">Estado de cobros</h3>
                            <div class="rounded-xl border border-emerald-500/20 bg-gradient-to-br from-emerald-950/40 to-zinc-950/60 p-6 ring-1 ring-emerald-500/10">
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold tabular-nums text-amber-200">{{ formatCurrency(cuenta.monto_total) }}</div>
                                        <div class="text-xs font-medium uppercase tracking-wider text-zinc-500">Monto total</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold tabular-nums text-emerald-300">{{ formatCurrency(cuenta.monto_pagado) }}</div>
                                        <div class="text-xs font-medium uppercase tracking-wider text-emerald-400/70">Cobrado</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold tabular-nums text-rose-300">{{ formatCurrency(cuenta.monto_pendiente) }}</div>
                                        <div class="text-xs font-medium uppercase tracking-wider text-rose-300/70">Pendiente</div>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <div class="mb-1 flex justify-between text-xs text-zinc-400">
                                        <span>Progreso de cobro</span>
                                        <span class="tabular-nums text-zinc-200">{{ pagoProgress }}%</span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-800">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-500"
                                            :style="{ width: pagoProgress + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Cobro (si está pagada) -->
                        <div v-if="cuenta.estado === 'pagado'" class="mb-8">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-brand-200/90">Información de cobro</h3>
                            <div class="rounded-xl border border-emerald-500/25 bg-gradient-to-br from-emerald-950/35 to-zinc-950/50 p-6 ring-1 ring-emerald-500/10">
                                <div class="grid grid-cols-1 gap-x-4 gap-y-6 md:grid-cols-2">
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Estado de cobro</dt>
                                        <dd class="mt-1">
                                            <span class="inline-flex items-center rounded-full bg-brand-500/15 px-2.5 py-0.5 text-xs font-medium text-emerald-200 ring-1 ring-emerald-400/25">
                                                Cobrado
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Método de cobro</dt>
                                        <dd class="mt-1 text-sm text-zinc-100">
                                            {{ getMetodoPagoLabel(cuenta.venta?.metodo_pago) }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Fecha de cobro</dt>
                                        <dd class="mt-1 text-sm text-zinc-200">{{ cuenta.venta?.fecha_pago ? new Date(cuenta.venta.fecha_pago).toLocaleDateString() : 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Cobrado por</dt>
                                        <dd class="mt-1 text-sm text-zinc-200">{{ cuenta.venta?.pagado_por_usuario?.name || 'Usuario no encontrado' }}</dd>
                                    </div>
                                </div>
                                <div v-if="cuenta.venta?.notas_pago" class="mt-6">
                                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500">Notas de cobro</dt>
                                    <dd class="mt-2 rounded-xl border border-zinc-700/60 bg-zinc-950/60 p-4 text-sm text-zinc-200">{{ cuenta.venta.notas_pago }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div v-if="cuenta.notas" class="mb-8">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-brand-200/90">Notas</h3>
                            <div class="rounded-xl border border-brand-500/20 bg-amber-950/25 p-5 ring-1 ring-brand-500/10">
                                <p class="whitespace-pre-line text-sm leading-relaxed text-zinc-200">{{ cuenta.notas }}</p>
                            </div>
                        </div>

                        <!-- Gestión de Cobros -->
                        <div class="mb-2">
                            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-brand-200/90">Gestión de cobros</h3>
                            <div class="rounded-xl border border-zinc-700/60 bg-zinc-950/40 p-6">
                                <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div class="space-y-3 rounded-xl border border-zinc-700/50 bg-zinc-900/50 p-4">
                                        <div class="flex justify-between gap-4">
                                            <span class="text-sm text-zinc-400">Total de la cuenta</span>
                                            <span class="font-semibold tabular-nums text-zinc-100">{{ formatCurrency(cuenta.monto_total) }}</span>
                                        </div>
                                        <div class="flex justify-between gap-4">
                                            <span class="text-sm text-zinc-400">Cobrado hasta ahora</span>
                                            <span class="font-semibold tabular-nums text-emerald-300">{{ formatCurrency(cuenta.monto_pagado) }}</span>
                                        </div>
                                        <div class="flex justify-between gap-4">
                                            <span class="text-sm text-zinc-400">Pendiente</span>
                                            <span class="font-semibold tabular-nums text-rose-300">{{ formatCurrency(cuenta.monto_pendiente) }}</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-col justify-center space-y-3">
                                        <button
                                            v-if="cuenta.monto_pendiente > 0"
                                            type="button"
                                            @click="showCobroParcialModal = true"
                                            class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-xl shadow-emerald-900/30 transition hover:from-emerald-500 hover:to-emerald-400"
                                        >
                                            Registrar cobro parcial
                                        </button>
                                        <button
                                            v-if="cuenta.estado !== 'pagado' && cuenta.monto_pendiente > 0"
                                            type="button"
                                            @click="showCobroModal = true"
                                            class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2.5 text-sm font-semibold text-zinc-950 shadow-xl shadow-brand-900/25 transition hover:from-brand-400 hover:to-brand-500"
                                        >
                                            Marcar como cobrado
                                        </button>
                                        <div v-if="cuenta.estado === 'pagado'" class="text-center">
                                            <span class="inline-flex items-center rounded-full bg-brand-500/15 px-2.5 py-0.5 text-xs font-medium text-emerald-200 ring-1 ring-emerald-400/25">
                                                Cuenta completamente cobrada
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-zinc-700/60 pt-4">
                                    <p class="mb-3 text-xs leading-relaxed text-zinc-500">
                                        Los cobros parciales se registran en el historial de notas. Para ver el historial detallado, edita la cuenta.
                                    </p>
                                    <Link
                                        :href="route('cuentas-por-cobrar.edit', cuenta.id)"
                                        class="text-sm font-medium text-brand-400 hover:underline decoration-brand-500/40 underline-offset-2 transition hover:text-amber-300"
                                    >
                                        Ver historial completo de cobros →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Cobro Parcial -->
        <div v-if="showCobroParcialModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm" @click.self="showCobroParcialModal = false">
            <div class="max-h-[90vh] w-full max-w-md overflow-y-auto custom-scrollbar rounded-2xl border border-zinc-600/80 bg-zinc-900 shadow-2xl shadow-black/60 ring-1 ring-zinc-500/20">
                <div class="flex items-center justify-between border-b border-zinc-700/60 px-6 py-5">
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-100">Registrar cobro parcial</h3>
                    <button type="button" @click="showCobroParcialModal = false" class="rounded-xl p-1 text-zinc-500 transition hover:bg-zinc-800 hover:text-zinc-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="space-y-5">
                        <div class="rounded-xl border border-zinc-700/60 bg-zinc-950/60 p-4">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <span class="text-xs font-medium uppercase tracking-wider text-zinc-500">Cuenta</span>
                                <span class="font-mono text-sm text-zinc-100">#{{ cuenta.id }}</span>
                            </div>
                            <div class="mb-3 border-t border-zinc-800 pt-3">
                                <span class="text-xs font-medium uppercase tracking-wider text-zinc-500">Cliente</span>
                                <p class="mt-1 text-sm text-zinc-200">{{ cuenta.venta?.cliente?.nombre_razon_social || cuenta.cobrable?.cliente?.nombre_razon_social || 'Cliente no disponible' }}</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-zinc-400">Monto total</span>
                                <span class="text-lg font-bold tabular-nums text-amber-200">{{ formatCurrency(cuenta.monto_total) }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-sm text-zinc-400">Pendiente</span>
                                <span class="text-lg font-bold tabular-nums text-rose-300">{{ formatCurrency(cuenta.monto_pendiente) }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wider text-zinc-400">Método de cobro *</label>
                            <select
                                v-model="metodoCobroParcial"
                                class="w-full rounded-xl border border-zinc-600 bg-zinc-950/80 px-3 py-2.5 text-sm text-zinc-100 focus:border-brand-500/50 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                required
                            >
                                <option value="" class="bg-zinc-900">Seleccionar método de cobro</option>
                                <option value="efectivo" class="bg-zinc-900">Efectivo</option>
                                <option value="transferencia" class="bg-zinc-900">Transferencia</option>
                                <option value="cheque" class="bg-zinc-900">Cheque</option>
                                <option value="tarjeta" class="bg-zinc-900">Tarjeta</option>
                                <option value="otros" class="bg-zinc-900">Otros</option>
                            </select>
                        </div>

                        <div v-if="requiresBankAccountParcial">
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wider text-zinc-400">
                                Cuenta bancaria (destino) <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="cuentaBancariaParcialId"
                                :class="{'border-rose-500/60 ring-1 ring-rose-500/30': !cuentaBancariaParcialId}"
                                class="w-full rounded-xl border border-zinc-600 bg-zinc-950/80 px-3 py-2.5 text-sm text-zinc-100 focus:border-brand-500/50 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                            >
                                <option value="" class="bg-zinc-900">Seleccionar cuenta bancaria *</option>
                                <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id" class="bg-zinc-900">
                                    {{ cb.banco }} - {{ cb.nombre }}
                                </option>
                            </select>
                            <p class="mt-2 text-xs text-emerald-400/90">El cobro parcial se registra en la cuenta bancaria seleccionada.</p>
                        </div>
                        <p v-else-if="metodoCobroParcial === 'efectivo'" class="text-xs text-brand-300/90">
                            El efectivo queda registrado en entregas de dinero como pendiente.
                        </p>

                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wider text-zinc-400">Monto del cobro *</label>
                            <input
                                v-model="montoCobroParcial"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="cuenta.monto_pendiente"
                                class="w-full rounded-xl border border-zinc-600 bg-zinc-950/80 px-3 py-2.5 text-sm tabular-nums text-zinc-100 placeholder-zinc-600 focus:border-brand-500/50 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                placeholder="0.00"
                            />
                            <p class="mt-1 text-xs text-zinc-500">
                                Máximo: {{ formatCurrency(cuenta.monto_pendiente) }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wider text-zinc-400">Notas del cobro (opcional)</label>
                            <textarea
                                v-model="notasCobroParcial"
                                rows="3"
                                class="w-full resize-y rounded-xl border border-zinc-600 bg-zinc-950/80 px-3 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-brand-500/50 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                placeholder="Agregar notas sobre este cobro parcial..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-zinc-700/60 bg-zinc-950/40 px-6 py-4">
                    <button type="button" @click="showCobroParcialModal = false" class="rounded-xl border border-zinc-600 bg-zinc-800/80 px-4 py-2 text-sm font-medium text-zinc-200 transition hover:border-brand-500 hover:bg-zinc-700">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="confirmarCobroParcial"
                        :disabled="!canConfirmCobroParcial"
                        class="rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow-xl shadow-emerald-900/30 transition hover:from-emerald-500 hover:to-emerald-400 disabled:cursor-not-allowed disabled:opacity-45"
                    >
                        Registrar cobro
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para Marcar como Cobrado -->
        <div v-if="showCobroModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm" @click.self="showCobroModal = false">
            <div class="max-h-[90vh] w-full max-w-md overflow-y-auto custom-scrollbar rounded-2xl border border-zinc-600/80 bg-zinc-900 shadow-2xl shadow-black/60 ring-1 ring-zinc-500/20">
                <div class="flex items-center justify-between border-b border-zinc-700/60 px-6 py-5">
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-100">Marcar cuenta como cobrada</h3>
                    <button type="button" @click="showCobroModal = false" class="rounded-xl p-1 text-zinc-500 transition hover:bg-zinc-800 hover:text-zinc-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="space-y-5">
                        <div class="rounded-xl border border-zinc-700/60 bg-zinc-950/60 p-4">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <span class="text-xs font-medium uppercase tracking-wider text-zinc-500">Cuenta</span>
                                <span class="font-mono text-sm text-zinc-100">#{{ cuenta.id }}</span>
                            </div>
                            <div class="mb-3 border-t border-zinc-800 pt-3">
                                <span class="text-xs font-medium uppercase tracking-wider text-zinc-500">Cliente</span>
                                <p class="mt-1 text-sm text-zinc-200">{{ cuenta.venta?.cliente?.nombre_razon_social || cuenta.cobrable?.cliente?.nombre_razon_social || 'Cliente no disponible' }}</p>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-zinc-400">Monto total</span>
                                <span class="text-lg font-bold tabular-nums text-amber-200">{{ formatCurrency(cuenta.monto_total) }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wider text-zinc-400">Método de cobro *</label>
                            <select
                                v-model="metodoCobro"
                                class="w-full rounded-xl border border-zinc-600 bg-zinc-950/80 px-3 py-2.5 text-sm text-zinc-100 focus:border-brand-500/50 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                required
                            >
                                <option value="" class="bg-zinc-900">Seleccionar método de cobro</option>
                                <option value="efectivo" class="bg-zinc-900">Efectivo</option>
                                <option value="transferencia" class="bg-zinc-900">Transferencia</option>
                                <option value="cheque" class="bg-zinc-900">Cheque</option>
                                <option value="tarjeta" class="bg-zinc-900">Tarjeta</option>
                                <option value="otros" class="bg-zinc-900">Otros</option>
                            </select>
                        </div>

                        <div v-if="requiresBankAccount">
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wider text-zinc-400">
                                Cuenta bancaria (destino) <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="cuentaBancariaId"
                                :class="{'border-rose-500/60 ring-1 ring-rose-500/30': !cuentaBancariaId}"
                                class="w-full rounded-xl border border-zinc-600 bg-zinc-950/80 px-3 py-2.5 text-sm text-zinc-100 focus:border-brand-500/50 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                            >
                                <option value="" class="bg-zinc-900">Seleccionar cuenta bancaria *</option>
                                <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id" class="bg-zinc-900">
                                    {{ cb.banco }} - {{ cb.nombre }}
                                </option>
                            </select>
                            <p class="mt-2 text-xs text-emerald-400/90">Tarjeta, transferencia o cheque se registran en la cuenta bancaria seleccionada.</p>
                        </div>
                        <p v-else-if="metodoCobro === 'efectivo'" class="text-xs text-brand-300/90">
                            El efectivo se registra en entregas de dinero cuando el vendedor lo entregue.
                        </p>

                        <div>
                            <label class="mb-2 block text-xs font-medium uppercase tracking-wider text-zinc-400">Notas de cobro (opcional)</label>
                            <textarea
                                v-model="notasCobro"
                                rows="3"
                                class="w-full resize-y rounded-xl border border-zinc-600 bg-zinc-950/80 px-3 py-2.5 text-sm text-zinc-100 placeholder-zinc-600 focus:border-brand-500/50 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
                                placeholder="Agregar notas sobre el cobro..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-zinc-700/60 bg-zinc-950/40 px-6 py-4">
                    <button type="button" @click="showCobroModal = false" class="rounded-xl border border-zinc-600 bg-zinc-800/80 px-4 py-2 text-sm font-medium text-zinc-200 transition hover:border-brand-500 hover:bg-zinc-700">
                        Cancelar
                    </button>
                    <button
                        type="button"
                        @click="confirmarCobro"
                        :disabled="!canConfirmCobro"
                        class="rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-4 py-2 text-sm font-semibold text-zinc-950 shadow-xl shadow-brand-900/25 transition hover:from-brand-400 hover:to-brand-500 disabled:cursor-not-allowed disabled:opacity-45"
                    >
                        Marcar como cobrado
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false },
        { type: 'warning', background: '#f59e0b', icon: false }
    ]
});

const props = defineProps({
    cuenta: {
        type: Object,
        required: true,
    },
    cuentasBancarias: {
        type: Array,
        default: () => [],
    },
});

const showCobroModal = ref(false);
const showCobroParcialModal = ref(false);
const metodoCobro = ref('');
const cuentaBancariaId = ref('');
const notasCobro = ref('');
const montoCobroParcial = ref('');
const notasCobroParcial = ref('');
const metodoCobroParcial = ref('');
const cuentaBancariaParcialId = ref('');

// Computed: banco obligatorio SOLO para tarjeta/transferencia (van directo al banco)
const requiresBankAccount = computed(() => {
    return ['tarjeta', 'transferencia', 'cheque'].includes(metodoCobro.value);
});

const requiresBankAccountParcial = computed(() => {
    return ['tarjeta', 'transferencia', 'cheque'].includes(metodoCobroParcial.value);
});

// Computed: puede confirmar cobro
const canConfirmCobro = computed(() => {
    if (!metodoCobro.value) return false;
    // Si es tarjeta/transferencia, requiere banco
    if (requiresBankAccount.value && !cuentaBancariaId.value) return false;
    return true;
});

const canConfirmCobroParcial = computed(() => {
    const monto = parseFloat(montoCobroParcial.value);
    if (!monto || isNaN(monto) || monto <= 0) return false;
    if (!metodoCobroParcial.value) return false;
    if (requiresBankAccountParcial.value && !cuentaBancariaParcialId.value) return false;
    return true;
});

const { formatCurrency } = useFormatters();

const toNumber = (value) => {
    if (value === null || value === undefined) {
        return 0;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};


const pagoProgress = computed(() => {
    const total = toNumber(props.cuenta?.monto_total);

    if (total <= 0) {
        return 0;
    }

    const pagado = toNumber(props.cuenta?.monto_pagado);
    const porcentaje = (pagado / total) * 100;

    return Math.min(100, Math.max(0, Math.round(porcentaje)));
});

const getMetodoPagoLabel = (metodo) => {
    const metodos = {
        'efectivo': 'Efectivo',
        'transferencia': 'Transferencia',
        'cheque': 'Cheque',
        'tarjeta': 'Tarjeta',
        'otros': 'Otros'
    };
    return metodos[metodo] || metodo || 'No especificado';
};

const confirmarCobro = async () => {
    if (!metodoCobro.value) {
        notyf.error('Debe seleccionar un método de cobro');
        return;
    }

    try {
        // Marcar la venta como pagada (lo que actualiza la cuenta por cobrar)
        const response = await fetch(route('ventas.marcar-pagado', props.cuenta.venta.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                metodo_pago: metodoCobro.value,
                cuenta_bancaria_id: cuentaBancariaId.value || null,
                notas_pago: notasCobro.value || null
            })
        });

        const result = await response.json();

        if (result.success) {
            notyf.success('Cuenta marcada como cobrada exitosamente');
            showCobroModal.value = false;
            metodoCobro.value = '';
            cuentaBancariaId.value = '';
            notasCobro.value = '';
            // Recargar la página para mostrar los nuevos datos
            router.reload();
        } else {
            notyf.error(result.error || 'Error al marcar como cobrada');
        }
    } catch (error) {
        notyf.error('Error de conexión');
    }
};

const confirmarCobroParcial = async () => {
    const monto = parseFloat(montoCobroParcial.value);

    if (!canConfirmCobroParcial.value) {
        notyf.error('Por favor complete todos los campos requeridos');
        return;
    }

    if (monto > props.cuenta.monto_pendiente) {
        notyf.error(`El monto no puede ser mayor al pendiente de ${formatCurrency(props.cuenta.monto_pendiente)}`);
        return;
    }

    try {
        const response = await fetch(route('cuentas-por-cobrar.registrar-pago', props.cuenta.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                monto: monto,
                notas: notasCobroParcial.value || null,
                metodo_pago: metodoCobroParcial.value,
                cuenta_bancaria_id: cuentaBancariaParcialId.value || null
            })
        });

        if (response.ok) {
            notyf.success('Cobro parcial registrado exitosamente');
            showCobroParcialModal.value = false;
            montoCobroParcial.value = '';
            notasCobroParcial.value = '';
            metodoCobroParcial.value = '';
            cuentaBancariaParcialId.value = '';
            // Recargar la página para mostrar los nuevos datos
            router.reload();
        } else {
            const error = await response.json();
            notyf.error(error.message || 'Error al registrar el cobro parcial');
        }
    } catch (error) {
        notyf.error('Error de conexión');
    }
};
</script>

