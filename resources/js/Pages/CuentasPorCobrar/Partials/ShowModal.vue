<template>
    <DialogModal :show="show" @close="close" maxWidth="5xl">
        <template #content>
            <div v-if="cuenta" class="relative bg-white pb-6">
                <!-- Header Minimalista (Full White) -->
                <div class="-mx-6 -mt-6 mb-6 px-6 py-6 bg-white border-b border-gray-100/80">
                    <div class="flex justify-between items-start">
                        <div class="flex items-center space-x-4">
                            <div class="p-2.5 bg-white rounded-xl border border-gray-100">
                                <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-gray-900 uppercase tracking-tight tabular-nums">Cuenta por Cobrar #{{ cuenta.id }}</h2>
                                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest mt-1">
                                    {{ (cuenta.cobrable_type?.toLowerCase().includes('venta')) ? 'Venta de Productos' : 'Renta de Equipos' }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-gray-400 uppercase font-black tracking-widest mb-1">Fecha de Registro</p>
                            <p class="font-extrabold text-gray-900 text-lg">{{ formatDate(cuenta.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Columna Izquierda: Información Principal -->
                    <div class="lg:col-span-8 space-y-8">
                        <!-- Card de Cliente (Limpio) -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-50 flex items-center gap-3">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Información del Cliente</h3>
                            </div>
                            <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
                                <div>
                                    <h4 class="text-2xl font-black text-gray-900 leading-tight tracking-tight">
                                        {{ cuenta.cobrable?.cliente?.nombre_razon_social || cuenta.cobrable_data?.cliente?.nombre_razon_social || 'Desconocido' }}
                                    </h4>
                                    <div class="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                                        <div v-if="cuenta.cobrable?.cliente?.rfc" class="flex items-center gap-2">
                                            <span class="text-gray-400 font-bold uppercase text-[10px]">RFC</span>
                                            <span class="font-mono font-bold text-gray-700">{{ cuenta.cobrable.cliente.rfc }}</span>
                                        </div>
                                        <div v-if="cuenta.cobrable?.cliente?.email" class="flex items-center gap-2">
                                            <span class="text-gray-400 font-bold uppercase text-[10px]">Email</span>
                                            <span class="font-bold text-gray-700">{{ cuenta.cobrable.cliente.email }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <template v-if="cuenta.cobrable_type?.toLowerCase().includes('venta')">
                                        <Link :href="route('ventas.show', cuenta.cobrable_id)" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-gray-900 rounded-xl hover:bg-gray-100 font-black transition-all border border-gray-200 text-xs uppercase tracking-widest">
                                            <span>Ver Venta #{{ cuenta.cobrable?.numero_venta || cuenta.cobrable_data?.numero_venta }}</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        </Link>
                                    </template>
                                    <template v-else-if="cuenta.cobrable_type?.toLowerCase().includes('renta')">
                                        <Link :href="route('rentas.show', cuenta.cobrable_id)" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-gray-900 rounded-xl hover:bg-gray-100 font-black transition-all border border-gray-200 text-xs uppercase tracking-widest">
                                            <span>Ver Renta #{{ cuenta.cobrable?.numero_contrato }}</span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                        </Link>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Detalles (Mejorada con IVA y Precios) -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">
                                    {{ (cuenta.cobrable_type?.toLowerCase().includes('venta')) ? 'Conceptos de Venta' : 'Equipos en Renta' }}
                                </h3>
                                <span class="bg-white border border-gray-100 text-[10px] font-black px-3 py-1 rounded-full text-gray-400 uppercase tracking-widest">
                                    {{ (cuenta.cobrable?.items?.length || cuenta.cobrable?.equipos?.length || 0) }} Items
                                </span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left border-collapse">
                                    <thead class="bg-white border-y border-gray-200">
                                        <tr>
                                            <th class="px-6 py-4 font-black text-gray-900 uppercase text-[10px] tracking-widest">Descripción</th>
                                            <th class="px-4 py-4 font-black text-gray-900 uppercase text-[10px] tracking-widest text-center">{{ (cuenta.cobrable_type?.toLowerCase().includes('venta')) ? 'Cant' : 'Serie' }}</th>
                                            <th class="px-4 py-4 font-black text-gray-900 uppercase text-[10px] tracking-widest text-right">P. Unitario</th>
                                            <th v-if="cuenta.cobrable_type?.toLowerCase().includes('venta')" class="px-4 py-4 font-black text-gray-900 uppercase text-[10px] tracking-widest text-right">IVA (16%)</th>
                                            <th class="px-6 py-4 font-black text-gray-900 uppercase text-[10px] tracking-widest text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        <template v-if="cuenta.cobrable_type?.toLowerCase().includes('venta')">
                                            <tr v-for="item in cuenta.cobrable.items" :key="item.id" class="hover:bg-white/50 transition-colors">
                                                <td class="px-6 py-5">
                                                    <div class="font-black text-gray-950 text-base leading-tight">{{ item.ventable?.nombre }}</div>
                                                    <div class="text-[10px] text-gray-500 font-bold uppercase mt-1 tracking-wider">{{ item.ventable_type?.split('\\').pop() }}</div>
                                                </td>
                                                <td class="px-4 py-5 text-center tabular-nums font-black text-gray-900 text-base">{{ item.cantidad }}</td>
                                                <td class="px-4 py-5 text-right tabular-nums font-bold text-gray-900 text-base">{{ formatCurrency(item.precio) }}</td>
                                                <td class="px-4 py-5 text-right tabular-nums font-bold text-gray-600 text-sm">
                                                    {{ formatCurrency((item.subtotal || 0) * 0.16) }}
                                                </td>
                                                <td class="px-6 py-5 text-right tabular-nums font-black text-gray-950 text-base">
                                                    {{ formatCurrency((item.subtotal || 0) * 1.16) }}
                                                </td>
                                            </tr>
                                        </template>
                                        <template v-else-if="cuenta.cobrable_type?.toLowerCase().includes('renta')">
                                            <tr v-for="equipo in cuenta.cobrable.equipos" :key="equipo.id" class="hover:bg-white/50 transition-colors">
                                                <td class="px-6 py-5">
                                                    <div class="font-black text-gray-950 text-base">{{ equipo.nombre }}</div>
                                                    <div class="text-[10px] text-gray-500 font-bold uppercase mt-1">{{ equipo.modelo }}</div>
                                                </td>
                                                <td class="px-4 py-5 text-center">
                                                    <span class="font-mono text-xs bg-gray-100 border border-gray-200 px-2 py-1 rounded-md text-gray-900 font-bold">{{ equipo.serie || '---' }}</span>
                                                </td>
                                                <td class="px-4 py-5 text-right tabular-nums font-black text-gray-950 text-base">{{ formatCurrency(equipo.pivot?.precio_mensual || 0) }}</td>
                                                <td class="px-6 py-5 text-right tabular-nums font-black text-gray-950 text-base">{{ formatCurrency(equipo.pivot?.precio_mensual || 0) }}</td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <!-- Footer de Tabla (Opcional pero recomendado para claridad) -->
                                    <tfoot v-if="cuenta.cobrable_type?.toLowerCase().includes('venta')" class="bg-white/50">
                                        <tr>
                                            <td colspan="3" class="px-6 py-4 text-right text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Totales de Venta</td>
                                            <td class="px-4 py-4 text-right tabular-nums font-bold text-gray-600 text-sm border-t border-gray-200">{{ formatCurrency(cuenta.cobrable?.iva || 0) }}</td>
                                            <td class="px-6 py-4 text-right tabular-nums font-black text-gray-950 text-lg border-t border-gray-200">{{ formatCurrency(cuenta.cobrable?.total || 0) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Historial de Pagos (Detallado) -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                                <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Historial de Pagos Recibidos</h3>
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div v-if="cuenta.historial_pagos?.length" class="divide-y divide-gray-50">
                                <div v-for="pago in cuenta.historial_pagos" :key="pago.id" class="px-6 py-4 flex items-center justify-between hover:bg-white/30 transition-colors group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center border"
                                             :class="pago.estado === 'recibido' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-amber-50 text-amber-600 border-amber-100'">
                                            <svg v-if="pago.metodo_pago === 'efectivo'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900 capitalize">{{ (pago.metodo_pago || 'N/A').replace('_', ' ') }}</p>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-0.5 tracking-wider">
                                                {{ formatDateShort(pago.fecha_entrega) }} 
                                                <span v-if="pago.cuenta_bancaria">• {{ pago.cuenta_bancaria.banco }}</span>
                                                <span v-if="pago.estado === 'pendiente'" class="text-amber-500">• (Pendiente Entregar)</span>
                                            </p>
                                            <p v-if="pago.notas" class="text-[9px] text-gray-400 italic mt-0.5">{{ pago.notas }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-base font-black text-emerald-600 tabular-nums">{{ formatCurrency(pago.total) }}</span>
                                        
                                        <!-- Botón Anular (Solo Admin) -->
                                        <!-- Asumiendo que $page.props.auth.user existe. Se oculta si no es admin (o similar, ajustaré logica backend) -->
                                        <button v-if="$page.props.auth.user?.roles?.some(r => r.name === 'super-admin') || $page.props.auth.user?.id === 1" 
                                                @click="anularPago(pago.id)"
                                                class="opacity-0 group-hover:opacity-100 p-2 text-red-300 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" 
                                                title="Anular/Revertir Pago (Solo Admin)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="p-12 text-center">
                                <p class="text-[10px] font-black text-gray-300 uppercase tracking-widest">Sin pagos registrados</p>
                            </div>
                        </div>

                        <!-- Historial de Documentos Digitales (Local ADD) -->
                        <div v-if="cuenta.cobrable?.cfdis?.length > 0" class="space-y-4">
                            <div class="flex items-center justify-between px-2">
                                <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Historial de Documentos Digitales</h5>
                                <Link :href="route('cfdi.index', { search: cuenta.cobrable?.numero_venta || cuenta.factura_uuid })" 
                                      class="text-[9px] font-black text-blue-600 uppercase tracking-widest hover:underline transition-all flex items-center gap-1">
                                    Ver ADD Global
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                </Link>
                            </div>
                            
                            <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-white/50 border-b border-gray-100">
                                            <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-widest">Folio / UUID</th>
                                            <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-widest text-right">Monto</th>
                                            <th class="px-5 py-4 text-[9px] font-black text-gray-400 uppercase tracking-widest text-center">Gestión</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        <tr v-for="cfdi in cuenta.cobrable.cfdis" :key="cfdi.id" class="hover:bg-white transition-colors group">
                                            <td class="px-5 py-4">
                                                <div class="flex flex-col gap-0.5">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-[10px] font-black text-gray-900 uppercase">{{ cfdi.serie }}{{ cfdi.folio }}</span>
                                                        <span :class="['px-1.5 py-0.5 rounded text-[8px] font-black uppercase tracking-widest border', 
                                                            cfdi.tipo_comprobante === 'I' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-purple-50 text-purple-600 border-purple-100']">
                                                            {{ getTipoLabel(cfdi.tipo_comprobante) }}
                                                        </span>
                                                    </div>
                                                    <span class="text-[9px] font-mono text-gray-400 uppercase truncate max-w-[150px]" :title="cfdi.uuid">{{ cfdi.uuid }}</span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-right">
                                                <span class="text-[10px] font-black text-gray-900 tabular-nums italic">{{ formatCurrency(cfdi.total) }}</span>
                                            </td>
                                            <td class="px-5 py-4">
                                                <div class="flex justify-center items-center gap-2">
                                                    <button @click="verFacturaSat(cfdi.uuid)" title="Ver PDF" class="p-2 text-gray-300 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                                    </button>
                                                    <button @click="enviarCorreoFactura(cfdi.uuid)" title="Email" class="p-2 text-gray-300 hover:text-purple-600 hover:bg-purple-50 rounded-xl transition-all">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Finanzas y Acciones (Blanco) -->
                    <div class="lg:col-span-4 space-y-8">
                        <!-- Card Financiera (Blanco Limpio) -->
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-xl shadow-gray-100/50 overflow-hidden">
                            <div class="p-8 space-y-8">
                                <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Resumen Financiero</h4>
                                
                                <div class="space-y-6">
                                    <div>
                                        <span class="text-gray-400 text-[10px] font-black uppercase tracking-widest block mb-2">Monto Neto a Cobrar</span>
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-3xl font-black text-gray-900 tabular-nums">{{ formatCurrency(cuenta.monto_total) }}</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 pt-6 border-t border-gray-50">
                                        <div>
                                            <span class="text-gray-400 text-[9px] font-black uppercase tracking-widest block mb-1">Pagado</span>
                                            <span class="text-lg font-black text-emerald-600 tabular-nums">{{ formatCurrency(cuenta.monto_pagado) }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-gray-400 text-[9px] font-black uppercase tracking-widest block mb-1">Pendiente</span>
                                            <span class="text-lg font-black text-red-600 tabular-nums">{{ formatCurrency(cuenta.monto_pendiente) }}</span>
                                        </div>
                                    </div>

                                    <div class="pt-6 space-y-2">
                                        <div class="flex justify-between items-end">
                                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Progreso de Pago</span>
                                            <span class="text-[10px] font-black text-gray-900 uppercase">{{ pagoProgress }}%</span>
                                        </div>
                                        <div class="w-full bg-white border border-gray-100 rounded-full h-3 overflow-hidden">
                                            <div class="h-full bg-gray-900 transition-all duration-1000 ease-out" :style="{ width: `${pagoProgress}%` }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botones de Acción -->
                            <div class="p-6 bg-white/50 border-t border-gray-100 flex flex-col gap-3">
                                <button
                                    v-if="cuenta.estado !== 'pagado' && cuenta.monto_pendiente > 0"
                                    @click="openPaymentModal('total')"
                                    class="w-full py-4 px-6 bg-gray-900 hover:bg-black text-white rounded-2xl font-black shadow-lg shadow-gray-200 transform active:scale-[0.98] transition-all flex items-center justify-center gap-3 uppercase text-xs tracking-[0.15em]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Liquidar Cuenta
                                </button>
                                <button
                                    v-if="cuenta.monto_pendiente > 0"
                                    @click="openPaymentModal('parcial')"
                                    class="w-full py-4 px-6 bg-white border-2 border-gray-900 text-gray-900 hover:bg-white rounded-2xl font-black transform active:scale-[0.98] transition-all flex items-center justify-center gap-3 uppercase text-xs tracking-[0.15em]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    Abono Parcial
                                </button>

                                <button
                                    v-if="canEmitRep"
                                    @click="openRepModal"
                                    class="w-full py-4 px-6 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black shadow-lg shadow-blue-200/60 transform active:scale-[0.98] transition-all flex items-center justify-center gap-3 uppercase text-xs tracking-[0.15em]"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 7h4m1-3h2a2 2 0 012 2v14a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2h2" /></svg>
                                    Recibo Electrónico de Pago
                                </button>
                                
                                <div v-if="cuenta.estado === 'pagado'" class="w-full py-5 text-center bg-white border-2 border-emerald-500 rounded-2xl">
                                    <div class="inline-flex items-center gap-2 text-emerald-600 font-black uppercase text-xs tracking-widest">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Cuenta Liquidada
                                    </div>
                                </div>

                                <!-- Facturación SAT Section -->
                                <div v-if="cuenta.cobrable_type?.toLowerCase().includes('venta')" class="pt-6 border-t border-gray-100 space-y-4">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2 px-2">Facturación SAT</p>
                                    
                                    <div v-if="cuenta.esta_facturada" class="space-y-3">
                                        <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-[10px] text-blue-700 font-mono break-all line-clamp-2">
                                            UUID: {{ cuenta.factura_uuid || 'Generado' }}
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button @click="verFacturaSat"
                                                    class="inline-flex justify-center items-center px-3 py-2 border border-blue-200 rounded-xl text-[10px] font-black text-blue-700 bg-blue-50 hover:bg-blue-100 transition-all uppercase tracking-widest">
                                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                PDF
                                            </button>
                                            <button @click="verXmlSat"
                                                    class="inline-flex justify-center items-center px-3 py-2 border border-gray-200 rounded-xl text-[10px] font-black text-gray-700 bg-white hover:bg-white transition-all uppercase tracking-widest">
                                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                                XML
                                            </button>
                                            <button @click="descargarXmlSat"
                                                    class="col-span-2 inline-flex justify-center items-center px-3 py-2 border border-gray-200 rounded-xl text-[10px] font-black text-gray-700 bg-white hover:bg-white transition-all uppercase tracking-widest">
                                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                Descargar XML
                                            </button>
                                            <button @click="enviarCorreoFactura"
                                                    :disabled="isSendingEmail"
                                                    class="col-span-2 inline-flex justify-center items-center px-3 py-2 border border-purple-200 rounded-xl text-[10px] font-black text-purple-700 bg-purple-50 hover:bg-purple-100 transition-all uppercase tracking-widest disabled:opacity-50">
                                                <svg v-if="!isSendingEmail" class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                                <svg v-else class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-purple-700" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                {{ isSendingEmail ? 'Enviando...' : 'Enviar Correo' }}
                                            </button>
                                        </div>

                                        <div v-if="!showCancelFacturaOptions" class="mt-2">
                                            <button @click="showCancelFacturaOptions = true"
                                                    class="w-full inline-flex justify-center items-center px-3 py-2 border-2 border-red-50 rounded-xl text-[10px] font-black text-red-600 bg-white hover:bg-red-50 transition-all uppercase tracking-widest">
                                                <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                Cancelar CFDI
                                            </button>
                                        </div>
                                        <div v-else class="mt-2 p-4 bg-red-50 rounded-2xl border border-red-100 space-y-3">
                                            <div>
                                                <label class="block text-[9px] font-black text-red-700 uppercase tracking-widest mb-1">Motivo SAT</label>
                                                <select v-model="cancelFacturaParams.motivo" class="w-full text-xs py-2 px-3 border border-red-200 rounded-xl bg-white focus:ring-0">
                                                    <option value="01">01 - Errores con relación</option>
                                                    <option value="02">02 - Errores sin relación</option>
                                                    <option value="03">03 - Operación no realizada</option>
                                                    <option value="04">04 - Global nominativa</option>
                                                </select>
                                            </div>
                                            <div class="flex gap-2">
                                                <button @click="cancelarFacturaSat" :disabled="isCancellingFactura"
                                                        class="flex-1 py-2 bg-red-600 text-white text-[10px] font-black rounded-xl hover:bg-red-700 uppercase tracking-widest disabled:opacity-50">
                                                    {{ isCancellingFactura ? '...' : 'Confirmar' }}
                                                </button>
                                                <button @click="showCancelFacturaOptions = false"
                                                        class="flex-1 py-2 bg-white border border-red-200 text-red-600 text-[10px] font-black rounded-xl hover:bg-red-50 uppercase tracking-widest">
                                                    Atrás
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-else-if="cuenta.cobrable?.estado !== 'cancelada'" class="space-y-4 px-2">
                                        <div class="p-4 bg-indigo-50 rounded-2xl border border-indigo-100/50">
                                            <label class="block text-[10px] font-black text-indigo-700 uppercase tracking-widest mb-2">Monto a Facturar</label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-indigo-300 font-black">$</span>
                                                <input v-model="formatoFactura.monto" type="number" step="0.01" 
                                                       class="w-full pl-7 pr-3 py-2 bg-white border border-indigo-100 rounded-xl text-lg font-black text-indigo-900 focus:ring-2 focus:ring-indigo-200 transition-all" />
                                            </div>
                                            <p class="mt-2 text-[9px] text-indigo-500 font-bold italic line-clamp-1">Max: {{ formatCurrency(cuenta.monto_pendiente) }}</p>
                                        </div>
                                        
                                        <button @click="facturarVenta"
                                                :disabled="isProcessingFactura || !formatoFactura.monto || formatoFactura.monto <= 0"
                                                class="w-full py-4 px-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-black shadow-lg shadow-indigo-200 transform active:scale-[0.98] transition-all flex items-center justify-center gap-3 uppercase text-xs tracking-[0.15em] disabled:opacity-50">
                                            <svg v-if="!isProcessingFactura" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944V22m0-19.056c1.11 0 2.22.12 3.291.352 3.174.694 5.254 3.012 5.254 6.225 0 2.969-1.928 5.48-4.686 6.305" /></svg>
                                            <svg v-else class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            {{ isProcessingFactura ? 'Generando...' : 'Facturar SAT' }}
                                        </button>
                                    </div>

                                    <div v-if="cuenta.cfdi_cancelado" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-2xl">
                                        <div class="flex items-center mb-2">
                                            <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                            <span class="text-[10px] font-black text-red-700 uppercase tracking-widest">CFDI Cancelado</span>
                                        </div>
                                        <div class="text-[9px] text-red-600 font-mono break-all bg-white p-2 rounded-lg border border-red-100">
                                            UUID: {{ cuenta.cfdi_cancelado_uuid || 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                                <!-- End Facturación SAT -->

                                <button
                                    @click="close"
                                    class="w-full py-4 text-center text-gray-400 hover:text-gray-900 font-black uppercase text-[10px] tracking-widest transition-colors"
                                >
                                    Cerrar Ventana
                                </button>
                            </div>
                        </div>

                        <!-- Auditoría Section -->
                        <div v-if="cuenta.metadata" class="bg-white/50 rounded-2xl border border-gray-100 p-6">
                            <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Registro de Auditoría</h5>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-1">
                                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Creado por</p>
                                    <p class="text-xs font-black text-gray-900 capitalize">{{ cuenta.metadata.creado_por }}</p>
                                    <p class="text-[10px] text-gray-500 font-bold tabular-nums">{{ formatDateShort(cuenta.metadata.creado_en) }}</p>
                                </div>
                                <div class="space-y-1 text-right">
                                    <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Último Cambio</p>
                                    <p class="text-xs font-black text-gray-900 capitalize">{{ cuenta.metadata.actualizado_por }}</p>
                                    <p class="text-[10px] text-gray-500 font-bold tabular-nums">{{ formatDateShort(cuenta.metadata.actualizado_en) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Fechas Clave (Estilo Limpio) -->
                        <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-6">
                            <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Vencimiento y Plazos</h5>
                            <div class="space-y-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest mb-0.5">Fecha Límite</p>
                                        <p class="text-sm font-black" :class="cuenta.estado === 'vencido' ? 'text-red-600' : 'text-gray-900'">
                                            {{ formatDateShort(cuenta.fecha_vencimiento) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="cuenta.fecha_pago" class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center border border-emerald-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-emerald-600 font-black uppercase tracking-widest mb-0.5">Último Pago</p>
                                        <p class="text-sm font-black text-gray-900">{{ formatDateShort(cuenta.fecha_pago) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loader -->
            <div v-else class="flex flex-col items-center justify-center py-24 text-gray-400">
                <div class="w-12 h-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="mt-4 font-bold uppercase tracking-widest text-xs">Sincronizando datos...</p>
            </div>

            <!-- Unified Payment Modal (White Theme) -->
            <div v-if="showPaymentModal" class="fixed inset-0 bg-white0/50 backdrop-blur-sm flex items-center justify-center z-[70] p-4" @click.self="showPaymentModal = false">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 transform transition-all animate-fadeIn">
                    <div class="px-8 py-6 bg-white border-b border-gray-50 flex justify-between items-center">
                        <h3 class="font-black uppercase tracking-[0.15em] text-sm text-gray-900">Registrar Cobro</h3>
                        <button @click="showPaymentModal = false" class="text-gray-300 hover:text-gray-900 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="p-6 bg-white rounded-2xl border border-gray-100">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Monto del Abono</label>
                            <div class="relative">
                                <span class="absolute left-0 top-1/2 -translate-y-1/2 text-2xl font-black text-gray-300">$</span>
                                <input v-model="paymentForm.monto" type="number" step="0.01" class="w-full pl-6 py-2 bg-transparent border-0 rounded-none text-3xl font-black text-gray-900 focus:ring-0 placeholder:text-gray-100" placeholder="0.00" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Método de Pago</label>
                            <select v-model="paymentForm.metodo_pago" class="w-full py-4 px-5 bg-white border-2 border-gray-100 rounded-2xl font-bold text-gray-900 focus:border-gray-900 focus:ring-0 transition-all">
                                <option value="">Seleccionar...</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="cheque">Cheque</option>
                                <option value="tarjeta_credito">Tarjeta de Crédito</option>
                                <option value="tarjeta_debito">Tarjeta de Débito</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>

                        <div v-if="requiresBankAccount">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Cuenta Bancaria Destino</label>
                            <select v-model="paymentForm.cuenta_bancaria_id" class="w-full py-4 px-5 bg-white border-2 border-gray-100 rounded-2xl font-bold text-gray-900 focus:border-gray-900 focus:ring-0 transition-all">
                                <option value="">Seleccionar Banco...</option>
                                <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">{{ cb.banco }} - {{ cb.nombre }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Notas / Referencia</label>
                            <textarea v-model="paymentForm.notas" rows="2" class="w-full px-5 py-4 bg-white border-2 border-gray-100 rounded-2xl font-bold text-gray-900 focus:border-gray-900 focus:ring-0 transition-all" placeholder="Ej: Pago de factura..."></textarea>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-white/50 border-t border-gray-100 flex flex-col gap-3">
                        <button @click="confirmarPago" :disabled="!canConfirmPayment || processing" class="w-full py-4 bg-gray-900 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] shadow-xl shadow-gray-200 disabled:opacity-50 flex items-center justify-center gap-3 active:scale-[0.98] transition-all">
                            <span v-if="processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            {{ processing ? 'Procesando...' : 'Confirmar Cobro' }}
                        </button>
                        <button @click="showPaymentModal = false" class="w-full py-3 font-black text-gray-400 hover:text-gray-900 uppercase text-[10px] tracking-widest transition-colors tracking-widest">Cancelar</button>
                    </div>
                </div>
            </div>

            <!-- REP Modal (White Theme) -->
            <div v-if="showRepModal" class="fixed inset-0 bg-white0/50 backdrop-blur-sm flex items-center justify-center z-[70] p-4" @click.self="showRepModal = false">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100 transform transition-all animate-fadeIn">
                    <div class="px-8 py-6 bg-white border-b border-gray-50 flex justify-between items-center">
                        <h3 class="font-black uppercase tracking-[0.15em] text-sm text-gray-900">Recibo Electrónico de Pago</h3>
                        <button @click="showRepModal = false" class="text-gray-300 hover:text-gray-900 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="p-6 bg-white rounded-2xl border border-gray-100">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Monto del Pago</label>
                            <div class="relative">
                                <span class="absolute left-0 top-1/2 -translate-y-1/2 text-2xl font-black text-gray-300">$</span>
                                <input v-model="repForm.monto" type="number" step="0.01" class="w-full pl-6 py-2 bg-transparent border-0 rounded-none text-3xl font-black text-gray-900 focus:ring-0 placeholder:text-gray-100" placeholder="0.00" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Fecha de Pago</label>
                            <input v-model="repForm.fecha_pago" type="date" class="w-full py-4 px-5 bg-white border-2 border-gray-100 rounded-2xl font-bold text-gray-900 focus:border-gray-900 focus:ring-0 transition-all" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Método de Pago</label>
                            <select v-model="repForm.metodo_pago" class="w-full py-4 px-5 bg-white border-2 border-gray-100 rounded-2xl font-bold text-gray-900 focus:border-gray-900 focus:ring-0 transition-all">
                                <option value="">Seleccionar...</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="cheque">Cheque</option>
                                <option value="tarjeta_credito">Tarjeta de Crédito</option>
                                <option value="tarjeta_debito">Tarjeta de Débito</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>

                        <div v-if="requiresBankAccountRep">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Cuenta Bancaria Destino</label>
                            <select v-model="repForm.cuenta_bancaria_id" class="w-full py-4 px-5 bg-white border-2 border-gray-100 rounded-2xl font-bold text-gray-900 focus:border-gray-900 focus:ring-0 transition-all">
                                <option value="">Seleccionar Banco...</option>
                                <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">{{ cb.banco }} - {{ cb.nombre }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Notas / Referencia</label>
                            <textarea v-model="repForm.notas" rows="2" class="w-full px-5 py-4 bg-white border-2 border-gray-100 rounded-2xl font-bold text-gray-900 focus:border-gray-900 focus:ring-0 transition-all" placeholder="Ej: Pago de factura..."></textarea>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-white/50 border-t border-gray-100 flex flex-col gap-3">
                        <button @click="confirmarRep" :disabled="!canConfirmRep || repProcessing" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs tracking-[0.2em] shadow-xl shadow-blue-200/60 disabled:opacity-50 flex items-center justify-center gap-3 active:scale-[0.98] transition-all">
                            <span v-if="repProcessing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            {{ repProcessing ? 'Procesando...' : 'Timbrar REP' }}
                        </button>
                        <button @click="showRepModal = false" class="w-full py-3 font-black text-gray-400 hover:text-gray-900 uppercase text-[10px] tracking-widest transition-colors tracking-widest">Cancelar</button>
                    </div>
                </div>
            </div>
        </template>
        <template #footer></template>
    </DialogModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import { Link, router } from '@inertiajs/vue3';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const emit = defineEmits(['close']);

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    cuenta: {
        type: Object,
        default: null,
    },
    cuentasBancarias: {
        type: Array,
        default: () => [],
    }
});

const close = () => {
    emit('close');
};

// Configuración de notificaciones
const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false },
        { type: 'warning', background: '#f59e0b', icon: false }
    ]
});

// Estado de carga y modales
const processing = ref(false);
const showPaymentModal = ref(false);
const showRepModal = ref(false);
const repProcessing = ref(false);

// CFDI Ported State
const isProcessingFactura = ref(false);
const isCancellingFactura = ref(false);
const isSendingEmail = ref(false);
const showCancelFacturaOptions = ref(false);
const cancelFacturaParams = ref({
    motivo: '02',
    folio_sustitucion: ''
});
const formatoFactura = ref({
    monto: 0
});

// Formulario de pago
const paymentForm = ref({
    monto: 0,
    metodo_pago: '',
    cuenta_bancaria_id: '',
    notas: '',
    tipo_pago: 'total' // 'total' o 'parcial'
});

const repForm = ref({
    monto: 0,
    fecha_pago: '',
    metodo_pago: '',
    cuenta_bancaria_id: '',
    notas: ''
});

// Watch para cuando se abre el modal de detalles, resetear forms
watch(() => props.show, (newVal) => {
    if (newVal && props.cuenta) {
        paymentForm.value.monto = props.cuenta.monto_pendiente;
        paymentForm.value.tipo_pago = 'total';
        formatoFactura.value.monto = props.cuenta.monto_pendiente;
        showCancelFacturaOptions.value = false;
    }
});

const openPaymentModal = (tipo = 'total') => {
    paymentForm.value.tipo_pago = tipo;
    paymentForm.value.monto = tipo === 'total' ? props.cuenta.monto_pendiente : '';
    paymentForm.value.metodo_pago = '';
    paymentForm.value.cuenta_bancaria_id = '';
    paymentForm.value.notas = '';
    showPaymentModal.value = true;
};

const requiresBankAccount = computed(() => {
    return ['transferencia', 'cheque', 'tarjeta_credito', 'tarjeta_debito'].includes(paymentForm.value.metodo_pago);
});

const requiresBankAccountRep = computed(() => {
    return ['transferencia', 'cheque', 'tarjeta_credito', 'tarjeta_debito'].includes(repForm.value.metodo_pago);
});

const canConfirmPayment = computed(() => {
    if (!paymentForm.value.metodo_pago) return false;
    if (requiresBankAccount.value && !paymentForm.value.cuenta_bancaria_id) return false;
    if (!paymentForm.value.monto || paymentForm.value.monto <= 0) return false;
    return true;
});

const canConfirmRep = computed(() => {
    if (!repForm.value.metodo_pago) return false;
    if (!repForm.value.fecha_pago) return false;
    if (requiresBankAccountRep.value && !repForm.value.cuenta_bancaria_id) return false;
    if (!repForm.value.monto || repForm.value.monto <= 0) return false;
    return true;
});

const currencyFormatter = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

const toNumber = (value) => {
    if (value === null || value === undefined) return 0;
    const number = Number(value);
    return Number.isFinite(number) ? number : 0;
};

const formatCurrency = (value) => currencyFormatter.format(toNumber(value));

const formatDate = (date) => {
    if (!date) return '---';
    return new Date(date).toLocaleDateString('es-MX', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
};

const formatDateShort = (date) => {
    if (!date) return '---';
    return new Date(date).toLocaleDateString('es-MX');
};

const pagoProgress = computed(() => {
    if (!props.cuenta) return 0;
    const total = toNumber(props.cuenta.monto_total);
    if (total <= 0) return 0;
    const pagado = toNumber(props.cuenta.monto_pagado);
    const porcentaje = (pagado / total) * 100;
    return Math.min(100, Math.max(0, Math.round(porcentaje)));
});

const canEmitRep = computed(() => {
    if (!props.cuenta) return false;
    if (!props.cuenta.cobrable_type?.toLowerCase().includes('venta')) return false;
    const venta = props.cuenta.cobrable;
    if (!venta) return false;
    const metodoCredito = (venta.metodo_pago || '').toLowerCase() === 'credito' ||
        (venta.metodo_pago_sat || '').toUpperCase() === 'PPD';
    return metodoCredito && !!venta.cliente?.requiere_factura && !!props.cuenta.ppd_cfdi_exists;
});

const getMetodoPagoLabel = (metodo) => {
    const metodos = {
        'efectivo': 'Efectivo',
        'transferencia': 'Transferencia',
        'cheque': 'Cheque',
        'tarjeta_credito': 'Tarjeta de Crédito',
        'tarjeta_debito': 'Tarjeta de Débito',
        'tarjeta': 'Tarjeta',
        'otros': 'Otros'
    };
    return metodos[metodo] || metodo || 'No especificado';
};

const getTipoLabel = (tipo) => {
    const tipos = {
        'I': 'Factura',
        'P': 'REP',
        'E': 'N. Crédito',
        'T': 'Traslado'
    };
    return tipos[tipo] || tipo;
};

const openRepModal = () => {
    repForm.value.monto = props.cuenta?.monto_pendiente || 0;
    repForm.value.fecha_pago = new Date().toISOString().slice(0, 10);
    repForm.value.metodo_pago = '';
    repForm.value.cuenta_bancaria_id = '';
    repForm.value.notas = '';
    showRepModal.value = true;
};

const confirmarPago = () => {
    if (!canConfirmPayment.value) return;

    processing.value = true;
    router.post(route('cuentas-por-cobrar.registrar-pago', props.cuenta.id), {
        monto: paymentForm.value.monto,
        metodo_pago: paymentForm.value.metodo_pago,
        cuenta_bancaria_id: paymentForm.value.cuenta_bancaria_id,
        notas: paymentForm.value.notas
    }, {
        onSuccess: () => {
            notyf.success('Pago registrado correctamente');
            showPaymentModal.value = false;
            processing.value = false;
            close(); // Cerrar modal principal para refrescar
        },
        onError: (errors) => {
            processing.value = false;
            const message = Object.values(errors)[0] || 'Error al registrar el pago';
            notyf.error(message);
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

const confirmarRep = () => {
    if (!canConfirmRep.value) return;

    repProcessing.value = true;
    router.post(route('cuentas-por-cobrar.timbrar-rep', props.cuenta.id), {
        monto: repForm.value.monto,
        fecha_pago: repForm.value.fecha_pago,
        metodo_pago: repForm.value.metodo_pago,
        cuenta_bancaria_id: repForm.value.cuenta_bancaria_id,
        notas: repForm.value.notas
    }, {
        onSuccess: () => {
            notyf.success('Recibo electrónico de pago generado');
            showRepModal.value = false;
            repProcessing.value = false;
            close();
        },
        onError: (errors) => {
            repProcessing.value = false;
            const message = Object.values(errors)[0] || 'Error al generar el REP';
            notyf.error(message);
        },
        onFinish: () => {
            repProcessing.value = false;
        }
    });
};

/* Función para anular pago (entrega de dinero) */
const anularPago = (id) => {
    if(!confirm('⚠️ ¿Estás seguro de REVERTIR este pago?\n\nSe realizarán las siguientes acciones:\n1. Se anulará el ingreso en banco/caja.\n2. La cuenta por cobrar volverá a tener saldo pendiente.\n\nEsta acción no se puede deshacer.')) return;
    
    router.post(route('cuentas-por-cobrar.anular-pago', id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            notyf.success('Pago revertido correctamente');
            emit('close');
        },
        onError: (errors) => {
            console.error(errors);
            notyf.error(errors.error || 'Error al revertir el pago');
        }
    });
};

// --- CFDI METHODS PORTED FROM MODALVENTA.VUE ---

const facturarVenta = () => {
    if (!props.cuenta?.cobrable_id) return;
    if (!confirm('¿Deseas generar la factura electrónica SAT para esta venta?')) return;
    
    isProcessingFactura.value = true;
    router.post(route('ventas.facturar', props.cuenta.cobrable_id), {
        monto_a_facturar: formatoFactura.value.monto
    }, {
        onSuccess: () => {
            isProcessingFactura.value = false;
            notyf.success('Factura generada exitosamente');
        },
        onError: (errors) => {
            isProcessingFactura.value = false;
            const msg = errors.error || 'Error al generar factura';
            notyf.error(msg);
        },
        onFinish: () => {
            isProcessingFactura.value = false;
        }
    });
};

const verFacturaSat = (uuid = null) => {
    const targetUuid = uuid || props.cuenta?.factura_uuid;
    if (targetUuid) {
        const url = route('cfdi.ver-pdf-view', targetUuid);
        window.open(url, '_blank');
    } else {
        notyf.error('No se encontró el UUID de la factura');
    }
};

const verXmlSat = (uuid = null) => {
    const targetUuid = uuid || props.cuenta?.factura_uuid;
    if (targetUuid) {
        const url = route('cfdi.xml', { uuid: targetUuid, inline: 1 });
        window.open(url, '_blank');
    } else {
        notyf.error('No se encontró el UUID de la factura');
    }
};

const descargarXmlSat = (uuid = null) => {
    const targetUuid = uuid || props.cuenta?.factura_uuid;
    if (targetUuid) {
        const url = route('cfdi.xml', { uuid: targetUuid });
        window.location.href = url;
    } else {
        notyf.error('No se encontró el UUID de la factura');
    }
};

const enviarCorreoFactura = async (uuid = null) => {
    const targetUuid = uuid || props.cuenta?.factura_uuid;
    if (!targetUuid) {
        notyf.error('No hay factura disponible para enviar');
        return;
    }

    if (!confirm('¿Deseas enviar los archivos (XML y PDF) por correo al cliente?')) return;

    isSendingEmail.value = true;
    try {
        const response = await axios.post(route('cfdi.enviar-correo', targetUuid));
        if (response.data?.success) {
            notyf.success('Correo enviado correctamente');
        } else {
            notyf.error(response.data?.message || 'Error al enviar correo');
        }
    } catch (error) {
        notyf.error('Error al conectar con el servidor para enviar correo');
    } finally {
        isSendingEmail.value = false;
    }
};

const cancelarFacturaSat = () => {
    if (!props.cuenta?.factura_uuid) return;
    if (!confirm('¿Deseas cancelar definitivamente esta factura ante el SAT?')) return;

    isCancellingFactura.value = true;
    router.post(route('cfdi.cancelar', props.cuenta.factura_uuid), {
        motivo: cancelFacturaParams.value.motivo,
        folio_sustitucion: cancelFacturaParams.value.folio_sustitucion
    }, {
        onSuccess: () => {
            isCancellingFactura.value = false;
            showCancelFacturaOptions.value = false;
            notyf.success('CFDI cancelado exitosamente');
        },
        onError: (errors) => {
            isCancellingFactura.value = false;
            const msg = errors.error || 'Error al cancelar CFDI';
            notyf.error(msg);
        },
        onFinish: () => {
            isCancellingFactura.value = false;
        }
    });
};
</script>
