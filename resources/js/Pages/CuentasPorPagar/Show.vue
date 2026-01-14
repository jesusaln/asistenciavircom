<template>
    <AppLayout title="Detalles de Cuenta por Pagar">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detalles de Cuenta por Pagar
                </h2>
                <div class="flex space-x-2">
                    <Link
                        :href="route('cuentas-por-pagar.edit', cuenta.id)"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Editar
                    </Link>
                    <Link
                        :href="route('cuentas-por-pagar.index')"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
                    >
                        Volver
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                                            'bg-red-100 text-red-800': estadoCuenta === 'vencido',
                                            'bg-yellow-100 text-yellow-800': estadoCuenta === 'parcial',
                                            'bg-green-100 text-green-800': estadoCuenta === 'pagado',
                                            'bg-gray-100 text-gray-800': estadoCuenta === 'pendiente'
                                        }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                            {{ estadoCuenta }}
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

                        <!-- Información de la Compra -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Información de la Compra</h3>
                            <div v-if="cuenta.compra" class="bg-gray-50 p-4 rounded-lg">
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Número de Compra</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <Link :href="route('compras.show', cuenta.compra.id)" class="text-blue-600 hover:text-blue-800">
                                                {{ cuenta.compra.numero_compra }}
                                            </Link>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Proveedor</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.compra.proveedor?.nombre_razon_social || 'Sin proveedor' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Total de la Compra</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.compra ? formatCurrency(cuenta.compra.total) : 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Estado de la Compra</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.compra.estado || 'N/A' }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div v-else class="bg-gray-50 p-4 rounded-lg text-gray-600">
                                Sin compra asociada
                            </div>
                        </div>

                        <!-- Información del CFDI (XML) -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Datos del CFDI (Factura XML)</h3>
                            <div v-if="cuenta.cfdi || cuenta.compra?.cfdi_uuid" class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                                    <div class="md:col-span-2">
                                        <dt class="text-sm font-medium text-indigo-600">UUID (Folio Fiscal)</dt>
                                        <dd class="mt-1 text-sm font-mono text-gray-900 break-all select-all">
                                            {{ cuenta.cfdi?.uuid || cuenta.compra?.cfdi_uuid }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-indigo-600">Serie y Folio</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ (cuenta.cfdi?.serie || cuenta.compra?.cfdi_serie || '') + (cuenta.cfdi?.folio || cuenta.compra?.cfdi_folio || 'S/N') }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-indigo-600">Fecha de Emisión</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            {{ cuenta.cfdi?.fecha_emision ? new Date(cuenta.cfdi.fecha_emision).toLocaleString() : (cuenta.compra?.cfdi_fecha ? new Date(cuenta.compra.cfdi_fecha).toLocaleString() : 'N/A') }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-indigo-600">RFC Emisor</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.cfdi?.rfc_emisor || cuenta.compra?.cfdi_emisor_rfc || 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-indigo-600">Nombre Emisor</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.cfdi?.nombre_emisor || cuenta.compra?.cfdi_emisor_nombre || 'N/A' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-indigo-600">Total XML</dt>
                                        <dd class="mt-1 text-sm font-bold text-gray-900">
                                            {{ formatCurrency(cuenta.cfdi?.total || cuenta.compra?.cfdi_total || 0) }}
                                        </dd>
                                    </div>
                                    <div v-if="cuenta.cfdi?.xml_url">
                                        <dt class="text-sm font-medium text-indigo-600">Archivo XML</dt>
                                        <dd class="mt-1 text-sm">
                                            <a :href="'/storage/' + cuenta.cfdi.xml_url" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                Ver XML
                                            </a>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                            <div v-else class="bg-gray-50 p-4 rounded-lg text-gray-500 italic">
                                No hay información de CFDI vinculada directamente.
                            </div>
                        </div>

                        <!-- Estado de Pagos -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Estado de Pagos</h3>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(cuenta.monto_total) }}</div>
                                        <div class="text-sm text-blue-600">Monto Total</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-green-600">{{ formatCurrency(cuenta.monto_pagado) }}</div>
                                        <div class="text-sm text-green-600">Monto Pagado</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-red-600">{{ formatCurrency(cuenta.monto_pendiente) }}</div>
                                        <div class="text-sm text-red-600">Monto Pendiente</div>
                                    </div>
                                </div>

                                <!-- Barra de progreso -->
                                <div class="mt-4">
                                    <div class="flex justify-between text-sm text-gray-600 mb-1">
                                        <span>Progreso de Pago</span>
                                        <span>{{ pagoProgress }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div
                                            class="bg-blue-600 h-2 rounded-full"
                                            :style="{ width: pagoProgress + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Pago (si está pagada) -->
                        <div v-if="cuenta.pagado" class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Información de Pago</h3>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Estado de Pago</dt>
                                        <dd class="mt-1">
                                            <span class="bg-green-100 text-green-800 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                Pagado
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Método de Pago</dt>
                                        <dd class="mt-1">
                                            <span :class="getMetodoPagoClass(cuenta.metodo_pago)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                {{ getMetodoPagoLabel(cuenta.metodo_pago) }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Fecha de Pago</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ new Date(cuenta.fecha_pago).toLocaleDateString() }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Pagado Por</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ cuenta.pagado_por_usuario?.name || 'Usuario no encontrado' }}</dd>
                                    </div>
                                </div>
                                <div v-if="cuenta.notas_pago" class="mt-4">
                                    <dt class="text-sm font-medium text-gray-500">Notas de Pago</dt>
                                    <dd class="mt-1 text-sm text-gray-700 bg-white p-3 rounded border">{{ cuenta.notas_pago }}</dd>
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

                        <!-- Historial de Pagos -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Gestión de Pagos</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <!-- Información de pagos -->
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Total de la cuenta:</span>
                                            <span class="font-semibold">{{ formatCurrency(cuenta.monto_total) }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600">Pagado hasta ahora:</span>
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
                                            @click="showPagoParcialModal = true"
                                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm"
                                        >
                                            + Registrar Pago Parcial
                                        </button>
                                        <button
                                            v-if="!cuenta.pagado && cuenta.monto_pendiente > 0"
                                            @click="showPagoModal = true"
                                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"
                                        >
                                            ✓ Marcar como Pagado
                                        </button>
                                        <Link
                                            :href="route('cuentas-por-pagar.index')"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium text-center"
                                        >
                                            Volver al listado
                                        </Link>
                                        <div v-if="cuenta.pagado" class="text-center">
                                            <span class="bg-green-100 text-green-800 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                Cuenta Completamente Pagada
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información adicional -->
                                <div class="border-t border-gray-200 pt-4">
                                    <p class="text-xs text-gray-500 mb-2">
                                        <strong>Nota:</strong> Los pagos parciales se registran en el historial de notas.
                                        Para ver el historial detallado, edita la cuenta.
                                    </p>
                                    <Link
                                        :href="route('cuentas-por-pagar.edit', cuenta.id)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                    >
                                        Ver historial completo de pagos →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Marcar como Pagado -->
        <div v-if="showPagoModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showPagoModal = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <!-- Header del modal -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Marcar Cuenta como Pagada</h3>
                    <button @click="showPagoModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="space-y-4">
                        <!-- Información de la cuenta -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Cuenta:</span>
                                <span class="text-sm text-gray-900">#{{ cuenta.id }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Proveedor:</span>
                                <span class="text-sm text-gray-900">{{ cuenta.compra?.proveedor?.nombre_razon_social || 'Sin compra asociada' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Monto Total:</span>
                                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(cuenta.monto_total) }}</span>
                            </div>
                        </div>

                        <!-- Método de Pago -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago *</label>
                            <select
                                v-model="metodoPago"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                                <option value="">Seleccionar método de pago</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="cheque">Cheque</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>

                        <!-- Cuenta Bancaria (origen del pago) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cuenta Bancaria (origen)</label>
                            <select
                                v-model="cuentaBancariaId"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Sin especificar</option>
                                <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                                    {{ cuenta.nombre }} - {{ cuenta.banco }}
                                </option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">¿De qué cuenta salió el pago?</p>
                        </div>

                        <!-- Notas de Pago -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notas de Pago (opcional)</label>
                            <textarea
                                v-model="notasPago"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Agregar notas sobre el pago..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Footer del modal -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button @click="showPagoModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button
                        @click="confirmarPago"
                        :disabled="!metodoPago"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Marcar como Pagado
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para Pago Parcial -->
        <div v-if="showPagoParcialModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" @click.self="showPagoParcialModal = false">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <!-- Header del modal -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Registrar Pago Parcial</h3>
                    <button @click="showPagoParcialModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div class="space-y-4">
                        <!-- Información de la cuenta -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Cuenta:</span>
                                <span class="text-sm text-gray-900">#{{ cuenta.id }}</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Proveedor:</span>
                                <span class="text-sm text-gray-900">{{ cuenta.compra?.proveedor?.nombre_razon_social || 'Sin compra asociada' }}</span>
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

                        <!-- Monto del pago parcial -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Monto del Pago *</label>
                            <input
                                v-model="montoPagoParcial"
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

                        <!-- Cuenta Bancaria para Pago Parcial -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cuenta Bancaria (origen)</label>
                            <select
                                v-model="cuentaBancariaIdParcial"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Sin especificar</option>
                                <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">
                                    {{ cb.nombre }} - {{ cb.banco }}
                                </option>
                            </select>
                        </div>

                        <!-- Notas del pago parcial -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notas del Pago (opcional)</label>
                            <textarea
                                v-model="notasPagoParcial"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Agregar notas sobre este pago parcial..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Footer del modal -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <button @click="showPagoParcialModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button
                        @click="confirmarPagoParcial"
                        :disabled="!montoPagoParcial || isNaN(parseFloat(montoPagoParcial)) || parseFloat(montoPagoParcial) <= 0"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        Registrar Pago
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

const showPagoModal = ref(false);
const showPagoParcialModal = ref(false);
const metodoPago = ref('');
const cuentaBancariaId = ref('');
const notasPago = ref('');
const montoPagoParcial = ref('');
const cuentaBancariaIdParcial = ref('');
const notasPagoParcial = ref('');

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

const estadoCuenta = computed(() => {
    if (props.cuenta?.pagado || toNumber(props.cuenta?.monto_pendiente) <= 0) {
        return 'pagado';
    }
    return props.cuenta?.estado || 'pendiente';
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

const getMetodoPagoClass = (metodo) => {
    const clases = {
        'efectivo': 'bg-green-100 text-green-800',
        'transferencia': 'bg-blue-100 text-blue-800',
        'cheque': 'bg-purple-100 text-purple-800',
        'tarjeta': 'bg-orange-100 text-orange-800',
        'otros': 'bg-gray-100 text-gray-800'
    };
    return clases[metodo] || 'bg-gray-100 text-gray-800';
};

const confirmarPago = async () => {
    if (!metodoPago.value) {
        notyf.error('Debe seleccionar un método de pago');
        return;
    }

    try {
        const response = await fetch(route('cuentas-por-pagar.marcar-pagado', props.cuenta.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                metodo_pago: metodoPago.value,
                cuenta_bancaria_id: cuentaBancariaId.value || null,
                notas_pago: notasPago.value || null
            })
        });

        const result = await response.json();

        if (result.success) {
            notyf.success('Cuenta marcada como pagada exitosamente');
            showPagoModal.value = false;
            metodoPago.value = '';
            cuentaBancariaId.value = '';
            notasPago.value = '';
            // Volver al listado tras pagar
            router.visit(route('cuentas-por-pagar.index'));
        } else {
            notyf.error(result.error || 'Error al marcar como pagada');
        }
    } catch (error) {
        notyf.error('Error de conexión');
    }
};

const confirmarPagoParcial = async () => {
    const monto = parseFloat(montoPagoParcial.value);

    if (!montoPagoParcial.value || isNaN(monto) || monto <= 0) {
        notyf.error('Debe ingresar un monto válido mayor a cero');
        return;
    }

    if (monto > props.cuenta.monto_pendiente) {
        notyf.error(`El monto no puede ser mayor al pendiente de ${formatCurrency(props.cuenta.monto_pendiente)}`);
        return;
    }

    try {
        const response = await fetch(route('cuentas-por-pagar.registrar-pago', props.cuenta.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                monto: monto,
                notas: notasPagoParcial.value || null,
                cuenta_bancaria_id: cuentaBancariaIdParcial.value || null
            })
        });

        if (response.ok) {
            notyf.success('Pago parcial registrado exitosamente');
            showPagoParcialModal.value = false;
            montoPagoParcial.value = '';
            cuentaBancariaIdParcial.value = '';
            notasPagoParcial.value = '';
            // Recargar la página para mostrar los nuevos datos
            router.reload();
        } else {
            const error = await response.json();
            notyf.error(error.message || 'Error al registrar el pago parcial');
        }
    } catch (error) {
        notyf.error('Error de conexión');
    }
};
</script>

