<template>
    <AppLayout title="Detalles de Cuenta por Cobrar">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalles de Cuenta por Cobrar
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('cuentas-por-cobrar.edit', cuenta.id)"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Editar
                    </Link>
                    <Link
                        :href="route('cuentas-por-cobrar.index')"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="w-full sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- Información General -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Información General</h3>
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">ID de Cuenta</dt>
                                    <dd class="mt-1 text-sm text-gray-900">#{{ cuenta.id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                                    <dd class="mt-1">
                                        <span :class="{
                                            'bg-red-100 text-red-800': cuenta.estado === 'vencido',
                                            'bg-yellow-100 text-yellow-800': cuenta.estado === 'parcial',
                                            'bg-green-100 text-green-800': cuenta.estado === 'pagado',
                                            'bg-gray-100 text-gray-800': cuenta.estado === 'pendiente'
                                        }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            {{ cuenta.estado }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha de Creación</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ new Date(cuenta.created_at).toLocaleDateString() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fecha de Vencimiento</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ cuenta.fecha_vencimiento ? new Date(cuenta.fecha_vencimiento).toLocaleDateString() : 'No especificada' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Información de Origen -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Información de Origen</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Referencia</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <template v-if="cuenta.cobrable_type && cuenta.cobrable_type.includes('Venta')">
                                                <Link :href="route('ventas.show', cuenta.cobrable?.id)" class="text-blue-600 hover:text-blue-800">
                                                    Venta #{{ cuenta.cobrable?.numero_venta || '??' }}
                                                </Link>
                                            </template>
                                            <template v-else-if="cuenta.cobrable_type && cuenta.cobrable_type.includes('Renta')">
                                                <Link :href="route('rentas.show', cuenta.cobrable?.id)" class="text-blue-600 hover:text-blue-800">
                                                    Renta #{{ cuenta.cobrable?.numero_contrato || '??' }}
                                                </Link>
                                                <div class="text-xs text-gray-500 mt-1">{{ cuenta.notas }}</div>
                                            </template>
                                            <template v-else>
                                                {{ cuenta.venta?.numero_venta || 'N/A' }}
                                            </template>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Cliente</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.cobrable?.cliente?.nombre_razon_social || cuenta.venta?.cliente?.nombre_razon_social || 'Cliente no disponible' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Total Original</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.cobrable_type && cuenta.cobrable_type.includes('Venta') ? formatCurrency(cuenta.cobrable.total) : formatCurrency(cuenta.monto_total) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Estado de Origen</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.cobrable?.estado || cuenta.venta?.estado }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Detalles de Artículos (Productos o Equipos) -->
                        <div class="mb-6" v-if="(cuenta.cobrable_type && cuenta.cobrable_type.includes('Venta') && cuenta.cobrable?.items?.length) || (cuenta.cobrable_type && cuenta.cobrable_type.includes('Renta') && cuenta.cobrable?.equipos?.length)">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                {{ cuenta.cobrable_type.includes('Venta') ? 'Productos Vendidos' : 'Equipos en Renta' }}
                            </h3>
                            <div class="bg-white border rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Descripción
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ cuenta.cobrable_type.includes('Venta') ? 'Cantidad' : 'Serie' }}
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ cuenta.cobrable_type.includes('Venta') ? 'Precio Unit.' : 'Precio Mensual' }}
                                            </th>
                                            <th scope="col" v-if="cuenta.cobrable_type.includes('Venta')" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Subtotal
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <!-- Iteración para Ventas -->
                                        <template v-if="cuenta.cobrable_type.includes('Venta')">
                                            <tr v-for="item in cuenta.cobrable.items" :key="item.id">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ item.ventable?.nombre || 'Producto desconocido' }}
                                                    <div v-if="item.notas" class="text-xs text-gray-500">{{ item.notas }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                    {{ item.cantidad }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                    {{ formatCurrency(item.precio_unitario) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right font-medium">
                                                    {{ formatCurrency(item.subtotal) }}
                                                </td>
                                            </tr>
                                        </template>
                                        <!-- Iteración para Rentas -->
                                        <template v-else-if="cuenta.cobrable_type.includes('Renta')">
                                            <tr v-for="equipo in cuenta.cobrable.equipos" :key="equipo.id">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ equipo.nombre }}
                                                    <div class="text-xs text-gray-500">{{ equipo.modelo }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                    {{ equipo.serie || 'S/N' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                    {{ formatCurrency(equipo.pivot?.precio_mensual || 0) }}
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Estado de Cobros -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Estado de Cobros</h3>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(cuenta.monto_total) }}</div>
                                        <div class="text-sm text-blue-600">Monto Total</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600">{{ formatCurrency(cuenta.monto_pagado) }}</div>
                                        <div class="text-sm text-green-600">Monto Cobrado</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-red-600">{{ formatCurrency(cuenta.monto_pendiente) }}</div>
                                        <div class="text-sm text-red-600">Monto Pendiente</div>
                                    </div>
                                </div>

                                <!-- Barra de progreso -->
                                <div class="mt-4">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Progreso de Cobro</span>
                                        <span>{{ pagoProgress }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            class="bg-green-600 h-2 rounded-full"
                                            :style="{ width: pagoProgress + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Cobro (si está pagada) -->
                        <div v-if="cuenta.estado === 'pagado'" class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Información de Cobro</h3>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Estado de Cobro</dt>
                                        <dd class="mt-1">
                                            <span class="bg-green-100 text-green-800 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                Cobrado
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Método de Cobro</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ getMetodoPagoLabel(cuenta.venta?.metodo_pago) }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Fecha de Cobro</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.venta?.fecha_pago ? new Date(cuenta.venta.fecha_pago).toLocaleDateString() : 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Cobrado Por</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.venta?.pagado_por_usuario?.name || 'Usuario no encontrado' }}</dd>
                                    </div>
                                </div>
                                <div v-if="cuenta.venta?.notas_pago" class="mt-4">
                                    <dt class="text-sm font-medium text-gray-500">Notas de Cobro</dt>
                                    <dd class="mt-1 text-sm text-gray-700 bg-white p-3 rounded border">{{ cuenta.venta.notas_pago }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div v-if="cuenta.notas" class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Notas</h3>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-700 whitespace-pre-line">{{ cuenta.notas }}</p>
                            </div>
                        </div>

                        <!-- Gestión de Cobros -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Gestión de Cobros</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <!-- Información de cobros -->
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Total de la cuenta:</span>
                                            <span class="font-semibold">{{ formatCurrency(cuenta.monto_total) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Cobrado hasta ahora:</span>
                                            <span class="font-semibold text-green-600">{{ formatCurrency(cuenta.monto_pagado) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Pendiente:</span>
                                            <span class="font-semibold text-red-600">{{ formatCurrency(cuenta.monto_pendiente) }}</span>
                                        </div>
                                    </div>

                                    <!-- Acciones disponibles -->
                                    <div class="flex flex-col justify-center space-y-2">
                                        <button
                                            v-if="cuenta.monto_pendiente > 0"
                                            @click="showCobroParcialModal = true"
                                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm"
                                        >
                                            + Registrar Cobro Parcial
                                        </button>
                                        <button
                                            v-if="cuenta.estado !== 'pagado' && cuenta.monto_pendiente > 0"
                                            @click="showCobroModal = true"
                                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"
                                        >
                                            ✓ Marcar como Cobrado
                                        </button>
                                        <div v-if="cuenta.estado === 'pagado'" class="text-center">
                                            <span class="bg-green-100 text-green-800 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                Cuenta Completamente Cobrada
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información adicional -->
                                <div class="border-t border-gray-200 pt-4">
                                    <p class="text-xs text-gray-500 mb-2">
                                        <strong>Nota:</strong> Los cobros parciales se registran en el historial de notas.
                                        Para ver el historial detallado, edita la cuenta.
                                    </p>
                                    <Link
                                        :href="route('cuentas-por-cobrar.edit', cuenta.id)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
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
        <div v-if="showCobroParcialModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showCobroParcialModal = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Registrar Cobro Parcial</h3>
                    <button @click="showCobroParcialModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Cuenta:</span>
                                <span class="text-sm text-gray-900">#{{ cuenta.id }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Cliente:</span>
                                <span class="text-sm text-gray-900">{{ cuenta.venta?.cliente?.nombre_razon_social || cuenta.cobrable?.cliente?.nombre_razon_social || 'Cliente no disponible' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Monto Total:</span>
                                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(cuenta.monto_total) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Pendiente:</span>
                                <span class="text-lg font-bold text-red-600">{{ formatCurrency(cuenta.monto_pendiente) }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Método de Cobro *</label>
                            <select
                                v-model="metodoCobroParcial"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                                <option value="">Seleccionar método de cobro</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="cheque">Cheque</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>

                        <!-- Banco para Parcial (Solo tarjeta/transferencia/cheque) -->
                        <div v-if="requiresBankAccountParcial">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Cuenta Bancaria (destino) <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="cuentaBancariaParcialId"
                                :class="{'border-red-500 ring-red-500': !cuentaBancariaParcialId}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Seleccionar cuenta bancaria *</option>
                                <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">
                                    {{ cb.banco }} - {{ cb.nombre }}
                                </option>
                            </select>
                            <p class="text-xs text-blue-600 mt-1">💳 El cobro parcial entrará directo al banco seleccionado</p>
                        </div>
                        <p v-else-if="metodoCobroParcial === 'efectivo'" class="text-xs text-yellow-600 mt-1 mb-2">
                            💵 El efectivo se registra en "Entregas de Dinero" como pendiente
                        </p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Monto del Cobro *</label>
                            <input
                                v-model="montoCobroParcial"
                                type="number"
                                step="0.01"
                                min="0.01"
                                :max="cuenta.monto_pendiente"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="0.00"
                            />
                            <p class="text-xs text-gray-500 mt-1">
                                Máximo: {{ formatCurrency(cuenta.monto_pendiente) }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notas del Cobro (opcional)</label>
                            <textarea
                                v-model="notasCobroParcial"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Agregar notas sobre este cobro parcial..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button @click="showCobroParcialModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button
                        @click="confirmarCobroParcial"
                        :disabled="!canConfirmCobroParcial"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Registrar Cobro
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para Marcar como Cobrado -->
        <div v-if="showCobroModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showCobroModal = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Marcar Cuenta como Cobrada</h3>
                    <button @click="showCobroModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Cuenta:</span>
                                <span class="text-sm text-gray-900">#{{ cuenta.id }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Cliente:</span>
                                <span class="text-sm text-gray-900">{{ cuenta.venta?.cliente?.nombre_razon_social || cuenta.cobrable?.cliente?.nombre_razon_social || 'Cliente no disponible' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Monto Total:</span>
                                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(cuenta.monto_total) }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Método de Cobro *</label>
                            <select
                                v-model="metodoCobro"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                                <option value="">Seleccionar método de cobro</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="cheque">Cheque</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>

                        <!-- Banco SOLO para tarjeta/transferencia (van directo al banco) -->
                        <div v-if="requiresBankAccount">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Cuenta Bancaria (destino) <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="cuentaBancariaId"
                                :class="{'border-red-500 ring-red-500': !cuentaBancariaId}"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Seleccionar cuenta bancaria *</option>
                                <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">
                                    {{ cb.banco }} - {{ cb.nombre }}
                                </option>
                            </select>
                            <p class="text-xs text-blue-600 mt-1">💳 Tarjeta/Transferencia va directo al banco seleccionado</p>
                        </div>
                        <p v-else-if="metodoCobro === 'efectivo'" class="text-xs text-yellow-600 mt-1">
                            💵 El efectivo se registra en "Entregas de Dinero" cuando el vendedor lo entregue
                        </p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notas de Cobro (opcional)</label>
                            <textarea
                                v-model="notasCobro"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Agregar notas sobre el cobro..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button @click="showCobroModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button
                        @click="confirmarCobro"
                        :disabled="!canConfirmCobro"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Marcar como Cobrado
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
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

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const toNumber = (value) => {
    if (value === null || value === undefined) {
        return 0;
    }

    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const formatCurrency = (value) => currencyFormatter.format(toNumber(value));

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

