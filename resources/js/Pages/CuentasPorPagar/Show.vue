<template>
    <AppLayout title="Detalles CXP">
        <div class="min-h-screen bg-slate-950 text-slate-200 font-sans selection:bg-indigo-500/30">
            <!-- Header Section -->
            <div class="sticky top-0 z-30 bg-slate-950/80 backdrop-blur-md border-b border-white/5 px-6 py-4">
                <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold tracking-tight text-white">Detalles de Cuenta por Pagar</h1>
                            <p class="text-sm text-slate-400">ID: #{{ cuenta.id }} • {{ new Date(cuenta.created_at).toLocaleDateString() }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <Link v-if="!['cancelada', 'pagado'].includes(estadoCuenta)"
                              :href="route('cuentas-por-pagar.edit', cuenta.id)" 
                              class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-white font-medium transition-all hover:scale-[1.02]">
                            Editar
                        </Link>
                        <Link :href="route('cuentas-por-pagar.index')" 
                              class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium transition-all">
                            Volver
                        </Link>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-6 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left Column: Main Info -->
                    <div class="lg:col-span-2 space-y-8">
                        
                        <!-- Financial Highlights Card -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-slate-900/50 border border-white/5 rounded-3xl p-6 backdrop-blur-sm transition-all hover:border-white/10">
                                <p class="text-xs uppercase tracking-wider text-slate-500 font-bold mb-1">Monto Total</p>
                                <p class="text-2xl font-black text-white">{{ formatCurrency(cuenta.monto_total) }}</p>
                            </div>
                            <div class="bg-slate-900/50 border border-white/5 rounded-3xl p-6 backdrop-blur-sm transition-all hover:border-emerald-500/20">
                                <p class="text-xs uppercase tracking-wider text-emerald-500/70 font-bold mb-1">Pagado</p>
                                <p class="text-2xl font-black text-emerald-400">{{ formatCurrency(cuenta.monto_pagado) }}</p>
                            </div>
                            <div class="bg-slate-900/50 border border-indigo-500/20 rounded-3xl p-6 backdrop-blur-sm shadow-lg shadow-indigo-500/5 transition-all hover:border-indigo-400/40">
                                <p class="text-xs uppercase tracking-wider text-rose-500/70 font-bold mb-1">Pendiente</p>
                                <p class="text-2xl font-black text-rose-400">{{ formatCurrency(cuenta.monto_pendiente) }}</p>
                            </div>
                        </div>

                        <!-- Main Content Tabs Area -->
                        <div class="bg-slate-900/30 border border-white/5 rounded-3xl overflow-hidden">
                            <div class="border-b border-white/5 bg-white/5 px-8 py-4">
                                <h3 class="font-bold text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Información de la Operación
                                </h3>
                            </div>
                            <div class="p-8 space-y-8">
                                <!-- Status info -->
                                <div class="flex flex-wrap gap-6">
                                    <div class="space-y-1">
                                        <p class="text-xs text-slate-500 font-medium">Estado actual</p>
                                        <div :class="estadoClases" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider inline-block">
                                            {{ estadoCuenta }}
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-xs text-slate-500 font-medium">Vencimiento</p>
                                        <p class="text-sm font-bold" :class="isVencido ? 'text-rose-400' : 'text-slate-200'">
                                            {{ cuenta.fecha_vencimiento ? new Date(cuenta.fecha_vencimiento).toLocaleDateString() : 'Sin fecha' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Compra Details -->
                                <div v-if="cuenta.compra" class="bg-slate-800/20 rounded-2xl p-6 border border-white/5">
                                    <h4 class="text-sm font-bold text-slate-400 mb-4 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                        Detalles de la Compra Relacionada
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                                        <div>
                                            <p class="text-slate-500 mb-1">Folio</p>
                                            <Link :href="route('compras.show', cuenta.compra.id)" class="text-indigo-400 hover:text-indigo-300 font-bold underline decoration-indigo-500/30 transition-all">
                                                {{ cuenta.compra.numero_compra }}
                                            </Link>
                                        </div>
                                        <div>
                                            <p class="text-slate-500 mb-1">Proveedor</p>
                                            <p class="font-bold text-slate-200">{{ cuenta.compra.proveedor?.nombre_razon_social || 'Sin proveedor' }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Items / Partidas -->
                                <div v-if="cuenta.compra?.compra_items?.length > 0" class="bg-slate-800/20 rounded-2xl p-6 border border-white/5">
                                    <h4 class="text-sm font-bold text-slate-400 mb-4 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                        Partidas / Productos de la Operación
                                    </h4>
                                    <div class="overflow-x-auto rounded-xl border border-white/5 bg-black/20">
                                        <table class="w-full text-left text-xs">
                                            <thead class="bg-white/5 text-slate-500 uppercase tracking-widest font-bold">
                                                <tr>
                                                    <th class="px-4 py-3">Descripción</th>
                                                    <th class="px-4 py-3 text-center">Cant.</th>
                                                    <th class="px-4 py-3 text-right">Precio</th>
                                                    <th class="px-4 py-3 text-right">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-white/5">
                                                <tr v-for="item in cuenta.compra.compra_items" :key="item.id" class="hover:bg-white/5 transition-colors">
                                                    <td class="px-4 py-3">
                                                        <div class="font-bold text-slate-200">{{ item.descripcion || item.comprable?.nombre }}</div>
                                                        <div v-if="item.comprable?.sku" class="text-[10px] text-slate-500 font-mono">{{ item.comprable.sku }}</div>
                                                    </td>
                                                    <td class="px-4 py-3 text-center text-slate-300">
                                                        {{ item.cantidad }} <span class="text-[10px] text-slate-500">{{ item.unidad_medida }}</span>
                                                    </td>
                                                    <td class="px-4 py-3 text-right text-slate-300">{{ formatCurrency(item.precio) }}</td>
                                                    <td class="px-4 py-3 text-right font-bold text-white">{{ formatCurrency(item.subtotal) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Totales de la Compra -->
                                    <div class="mt-4 flex flex-col items-end space-y-2 px-4">
                                        <div class="flex justify-between w-full max-w-[240px] text-[11px]">
                                            <span class="text-slate-500 font-bold uppercase tracking-widest">Subtotal</span>
                                            <span class="text-slate-300 font-mono">{{ formatCurrency(cuenta.compra.subtotal) }}</span>
                                        </div>
                                        <div v-if="toNumber(cuenta.compra.iva) > 0" class="flex justify-between w-full max-w-[240px] text-[11px]">
                                            <span class="text-slate-500 font-bold uppercase tracking-widest">IVA</span>
                                            <span class="text-slate-300 font-mono">{{ formatCurrency(cuenta.compra.iva) }}</span>
                                        </div>
                                        <div v-if="toNumber(cuenta.compra.retencion_iva) > 0" class="flex justify-between w-full max-w-[240px] text-[11px]">
                                            <span class="text-rose-500/70 font-bold uppercase tracking-widest">Retención IVA</span>
                                            <span class="text-rose-400/80 font-mono">-{{ formatCurrency(cuenta.compra.retencion_iva) }}</span>
                                        </div>
                                        <div v-if="toNumber(cuenta.compra.retencion_isr) > 0" class="flex justify-between w-full max-w-[240px] text-[11px]">
                                            <span class="text-rose-500/70 font-bold uppercase tracking-widest">Retención ISR</span>
                                            <span class="text-rose-400/80 font-mono">-{{ formatCurrency(cuenta.compra.retencion_isr) }}</span>
                                        </div>
                                        <div class="flex justify-between w-full max-w-[240px] pt-3 border-t border-white/10 mt-1">
                                            <span class="text-white font-black uppercase tracking-widest text-[12px]">Total Neto</span>
                                            <span class="text-indigo-400 font-black text-lg font-mono">{{ formatCurrency(cuenta.compra.total) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- CFDI Info -->
                                <div v-if="cuenta.cfdi || cuenta.compra?.cfdi_uuid" class="bg-indigo-500/5 rounded-2xl p-6 border border-indigo-500/10">
                                    <h4 class="text-sm font-bold text-indigo-300 mb-4 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A10.003 10.003 0 0014 20.2c.33 0 .657-.016.98-.048m-2.43-4.661a3 3 0 01-2.43-4.661m-2.43 4.661a3 3 0 114.86 0z" /></svg>
                                        Información Fiscal (CFDI)
                                    </h4>
                                    <div class="space-y-4 font-mono text-[13px]">
                                        <div class="bg-black/20 p-3 rounded-lg flex items-start justify-between gap-4">
                                            <span class="text-indigo-400/60 flex-shrink-0">UUID:</span>
                                            <span class="text-indigo-300 break-all text-right select-all">{{ cuenta.cfdi?.uuid || cuenta.compra?.cfdi_uuid }}</span>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="bg-black/20 p-3 rounded-lg">
                                                <span class="text-indigo-400/60 block mb-1">Folio XML:</span>
                                                <span class="text-slate-200">{{ (cuenta.cfdi?.serie || '') + (cuenta.cfdi?.folio || 'S/F') }}</span>
                                            </div>
                                            <div class="bg-black/20 p-3 rounded-lg">
                                                <span class="text-indigo-400/60 block mb-1">Total XML:</span>
                                                <span class="text-slate-200 font-bold">{{ formatCurrency(cuenta.cfdi?.total || cuenta.compra?.cfdi_total || 0) }}</span>
                                            </div>
                                        </div>
                                        <div v-if="cuenta.cfdi?.xml_url" class="pt-2">
                                            <a :href="'/storage/' + cuenta.cfdi.xml_url" target="_blank" class="flex items-center gap-2 text-indigo-400 hover:text-indigo-300 transition-colors group">
                                                <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                                <span class="underline decoration-indigo-500/30">Descargar XML comprobante</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment History Summary -->
                                <div v-if="cuenta.notas" class="border-t border-white/5 pt-6 space-y-3">
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Historial de Notas / Pagos</p>
                                    <div class="bg-amber-500/5 text-amber-200/80 p-4 rounded-2xl border border-amber-500/10 whitespace-pre-line text-sm leading-relaxed italic">
                                        {{ cuenta.notas }}
                                    </div>
                                </div>

                                <div v-if="cuenta.estado === 'cancelada'" class="border-t border-white/5 pt-6 space-y-3">
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Cancelación</p>
                                    <div class="bg-rose-500/10 text-rose-200 p-4 rounded-2xl border border-rose-500/20 text-sm leading-relaxed">
                                        <p><span class="font-bold">Fecha:</span> {{ cuenta.fecha_cancelacion ? new Date(cuenta.fecha_cancelacion).toLocaleString('es-MX') : 'No registrada' }}</p>
                                        <p class="mt-2"><span class="font-bold">Motivo:</span> {{ cuenta.motivo_cancelacion || 'No registrado' }}</p>
                                        <p v-if="toNumber(cuenta.saldo_favor_generado) > 0" class="mt-2">
                                            <span class="font-bold">Saldo a favor generado:</span> {{ formatCurrency(cuenta.saldo_favor_generado) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Actions & Payment Summary -->
                    <div class="space-y-8">
                        
                        <!-- Progress Card -->
                            <div class="bg-slate-900/50 border border-white/5 rounded-3xl p-8 backdrop-blur-sm">
                            <h3 class="text-lg font-bold text-white mb-6">Progreso de Pago</h3>
                            
                            <div class="relative w-32 h-32 mx-auto mb-8">
                                <svg class="w-full h-full transform -rotate-90">
                                    <circle class="text-slate-800" stroke-width="8" stroke="currentColor" fill="transparent" r="58" cx="64" cy="64" />
                                    <circle class="text-indigo-500 transition-all duration-1000 ease-out" stroke-width="8" stroke-dasharray="364.4" :stroke-dashoffset="364.4 - (364.4 * (pagoProgress / 100))" stroke-linecap="round" stroke="currentColor" fill="transparent" r="58" cx="64" cy="64" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-black text-white leading-none">{{ pagoProgress }}%</span>
                                    <span class="text-[10px] text-slate-500 uppercase tracking-tighter mt-1 font-bold">Liquidado</span>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div v-if="toNumber(saldoFavorDisponibleProveedor) > 0 && cuenta.estado !== 'cancelada'" class="p-3 rounded-2xl bg-teal-500/10 border border-teal-500/20">
                                    <p class="text-[11px] uppercase tracking-wider text-teal-300 font-bold">Saldo a favor proveedor disponible</p>
                                    <p class="text-lg font-black text-teal-200">{{ formatCurrency(saldoFavorDisponibleProveedor) }}</p>
                                </div>

                                <button v-if="cuenta.monto_pendiente > 0" 
                                        @click="triggerPagoParcial"
                                        class="w-full py-4 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-xl shadow-indigo-600/20 transition-all hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                    Registrar Pago Parcial
                                </button>
                                
                                <button v-if="!cuenta.pagado && cuenta.monto_pendiente > 0" 
                                        @click="triggerPagoTotal"
                                        class="w-full py-4 rounded-2xl bg-emerald-600/90 hover:bg-emerald-500 text-white font-bold transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Marcar Pagado Total
                                </button>

                                <div v-if="cuenta.pagado" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-center text-sm font-bold italic">
                                    Completamente Liquidada
                                </div>

                                <button v-if="cuenta.estado !== 'cancelada'"
                                        @click="cancelarCuenta"
                                        class="w-full py-4 rounded-2xl bg-rose-600/80 hover:bg-rose-500 text-white font-bold transition-all flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    Cancelar Cuenta
                                </button>
                            </div>
                        </div>

                        <!-- Paid Metadata Card -->
                        <div v-if="cuenta.pagado" class="bg-indigo-600/10 border border-indigo-500/20 rounded-3xl p-6 backdrop-blur-sm shadow-xl shadow-indigo-500/5 animate-in fade-in zoom-in duration-500">
                             <h3 class="text-indigo-300 font-bold mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                Datos de Liquidación
                             </h3>
                             <dl class="space-y-4 text-sm">
                                <div>
                                    <dt class="text-indigo-400/60 mb-1">Método de Pago</dt>
                                    <dd class="text-indigo-100 font-bold uppercase tracking-wide bg-indigo-500/20 px-2 py-1 rounded-lg inline-block">{{ getMetodoPagoLabel(cuenta.metodo_pago) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-indigo-400/60 mb-1">Fecha de Operación</dt>
                                    <dd class="text-indigo-100 font-bold">{{ new Date(cuenta.fecha_pago).toLocaleDateString() }}</dd>
                                </div>
                                <div>
                                    <dt class="text-indigo-400/60 mb-1">Registrado por</dt>
                                    <dd class="text-indigo-100">{{ cuenta.pagado_por_usuario?.name || 'Sistema' }}</dd>
                                </div>
                             </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Modal: Pago Total -->
            <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showPagoModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showPagoModal = false"></div>
                    <div class="bg-slate-900 border border-white/10 rounded-[2.5rem] shadow-2xl w-full max-w-lg relative overflow-hidden animate-in zoom-in-95 duration-300">
                        <div class="px-8 py-8 md:px-10">
                            <h3 class="text-2xl font-black text-white mb-2">Marcar como Liquidada</h3>
                            <p class="text-slate-400 mb-8">Confirma la liquidación total de esta cuenta por {{ formatCurrency(cuenta.monto_total) }}.</p>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Método de Pago</label>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        <button v-for="m in metodosDisponibles" :key="m.val" 
                                                @click="metodoPago = m.val"
                                                :class="metodoPago === m.val ? 'bg-indigo-600 border-indigo-400 text-white' : 'bg-slate-800 border-white/5 text-slate-400 hover:bg-slate-700'"
                                                class="px-4 py-3 rounded-2xl border text-sm font-bold transition-all capitalize">
                                            {{ m.label }}
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Banco Origen</label>
                                    <select v-model="cuentaBancariaId" class="w-full bg-slate-800 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all outline-none">
                                        <option value="">Seleccionar cuenta bancaria...</option>
                                        <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">{{ cb.nombre }} - {{ cb.banco }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Notas Adicionales</label>
                                    <textarea v-model="notasPago" rows="2" class="w-full bg-slate-800 border-white/10 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-indigo-500 transition-all outline-none" placeholder="Opcional..."></textarea>
                                </div>
                            </div>

                            <div class="mt-10 flex gap-4">
                                <button @click="showPagoModal = false" class="flex-1 py-4 px-6 rounded-2xl bg-white/5 hover:bg-white/10 text-slate-400 font-bold transition-all">Cancelar</button>
                                <button @click="confirmarPago" :disabled="!metodoPago || isSubmitting" class="flex-[1.5] py-4 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold transition-all shadow-lg shadow-indigo-600/30">
                                    {{ isSubmitting ? 'Procesando...' : 'Confirmar Pago' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Modern Modal: Pago Parcial -->
            <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showPagoParcialModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showPagoParcialModal = false"></div>
                    <div class="bg-slate-900 border border-white/10 rounded-[2.5rem] shadow-2xl w-full max-w-lg relative overflow-hidden animate-in zoom-in-95 duration-300">
                        <div class="px-8 py-8 md:px-10">
                            <h3 class="text-2xl font-black text-white mb-2 text-center">Pago Parcial</h3>
                            <div class="bg-white/5 p-4 rounded-3xl mb-8 text-center border border-white/5">
                                <p class="text-xs text-slate-500 uppercase tracking-widest font-bold mb-1">Monto Pendiente</p>
                                <p class="text-xl font-black text-white">{{ formatCurrency(cuenta.monto_pendiente) }}</p>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Importe a Pagar</label>
                                    <div class="relative">
                                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-lg">$</span>
                                        <input v-model="montoPagoParcial" type="number" step="0.01" class="w-full bg-slate-800 border-white/10 rounded-2xl pl-10 pr-5 py-5 text-2xl font-black text-indigo-400 focus:ring-2 focus:ring-indigo-500 outline-none transition-all placeholder:text-slate-700 search-none" placeholder="0.00" />
                                    </div>
                                </div>

                                <div v-if="toNumber(saldoFavorDisponibleProveedor) > 0" class="bg-teal-500/5 border border-teal-500/20 rounded-2xl p-4">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input v-model="usarSaldoFavorParcial" type="checkbox" class="rounded border-teal-500/40 bg-slate-800 text-teal-500 focus:ring-teal-500/40">
                                        <span class="text-sm font-bold text-teal-200">Aplicar saldo a favor del proveedor en este abono</span>
                                    </label>
                                    <p class="text-xs text-teal-300/80 mt-2">
                                        Disponible: {{ formatCurrency(saldoFavorDisponibleProveedor) }}.
                                    </p>
                                    <button type="button"
                                            @click="usarSoloSaldoFavor"
                                            class="mt-3 px-3 py-2 rounded-xl bg-teal-500/20 hover:bg-teal-500/30 text-teal-200 text-xs font-bold uppercase tracking-wider transition-all">
                                        Usar solo saldo a favor
                                    </button>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Banco Origen</label>
                                    <select v-model="cuentaBancariaIdParcial" class="w-full bg-slate-800 border-white/10 rounded-2xl px-5 py-4 text-white outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                                        <option value="">Sin especificar</option>
                                        <option v-for="cb in cuentasBancarias" :key="cb.id" :value="cb.id">{{ cb.nombre }} - {{ cb.banco }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Notas del Abono</label>
                                    <textarea v-model="notasPagoParcial" rows="2" class="w-full bg-slate-800 border-white/10 rounded-2xl px-5 py-4 text-white outline-none focus:ring-2 focus:ring-indigo-500 transition-all" placeholder="Referencia, número de cheque, etc."></textarea>
                                </div>
                            </div>

                            <div class="mt-10 flex gap-4">
                                <button @click="showPagoParcialModal = false" class="flex-1 py-4 px-6 rounded-2xl bg-white/5 hover:bg-white/10 text-slate-400 font-bold transition-all">Cancelar</button>
                                <button @click="confirmarPagoParcial" :disabled="isSubmitting || (!montoPagoParcial && !usarSaldoFavorParcial)" class="flex-[2] py-4 px-6 rounded-2xl bg-indigo-600 hover:bg-emerald-500 text-white font-bold transition-all shadow-lg shadow-indigo-600/30">
                                     Registrar Abono
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Premium Dark Modal: Cancelación -->
            <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showCancelModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-950/90 backdrop-blur-md" @click="showCancelModal = false"></div>
                    <div class="bg-slate-900 border border-rose-500/20 rounded-[2.5rem] shadow-2xl shadow-rose-500/10 w-full max-w-lg relative overflow-hidden animate-in zoom-in-95 duration-300">
                        <!-- Decorative glow -->
                        <div class="absolute -top-24 -right-24 w-48 h-48 bg-rose-500/10 rounded-full blur-3xl"></div>
                        
                        <div class="px-8 py-10 md:px-12 relative">
                            <div class="w-16 h-16 bg-rose-500/10 border border-rose-500/20 rounded-2xl flex items-center justify-center mb-6 mx-auto">
                                <svg class="w-8 h-8 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>

                            <h3 class="text-2xl font-black text-white mb-2 text-center">Cancelar Cuenta</h3>
                            <p class="text-slate-400 mb-8 text-center text-sm">Esta acción marcará la cuenta como <span class="text-rose-400 font-bold uppercase">Anulada</span>. Si hay pagos registrados, se generará un saldo a favor para el proveedor.</p>
                            
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Motivo de Cancelación</label>
                                    <select v-model="motivoCancelacion" 
                                            class="w-full bg-slate-800/50 border border-white/5 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-rose-500/50 focus:border-rose-500/50 transition-all outline-none appearance-none">
                                        <option value="" disabled>Selecciona un motivo...</option>
                                        <option v-for="m in motivosCancelacion" :key="m" :value="m">{{ m }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-3">Notas / Detalles adicionales</label>
                                    <textarea v-model="notasCancelacion" 
                                              rows="3" 
                                              class="w-full bg-slate-800/50 border border-white/5 rounded-2xl px-5 py-4 text-white focus:ring-2 focus:ring-rose-500/50 focus:border-rose-500/50 transition-all outline-none resize-none" 
                                              placeholder="Explica brevemente la razón de la cancelación..."></textarea>
                                </div>
                            </div>

                            <div class="mt-10 flex gap-4">
                                <button @click="showCancelModal = false" 
                                        class="flex-1 py-4 px-6 rounded-2xl bg-white/5 hover:bg-white/10 text-slate-400 font-bold transition-all">
                                    Cerrar
                                </button>
                                <button @click="confirmarCancelacion" 
                                        :disabled="isSubmitting || !motivoCancelacion" 
                                        class="flex-[2] py-4 px-6 rounded-2xl bg-rose-600 hover:bg-rose-500 disabled:opacity-30 disabled:scale-100 text-white font-bold transition-all shadow-lg shadow-rose-600/20 active:scale-95">
                                    {{ isSubmitting ? 'Cancelando...' : 'Confirmar Cancelación' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

const props = defineProps({
    cuenta: { type: Object, required: true },
    cuentasBancarias: { type: Array, default: () => [] },
    saldoFavorDisponibleProveedor: { type: Number, default: 0 },
});

const notyf = new Notyf({
    duration: 4000,
    position: { x: 'right', y: 'top' },
    types: [
        { type: 'success', background: '#10b981', icon: false },
        { type: 'error', background: '#ef4444', icon: false }
    ]
});

const isSubmitting = ref(false);
const showPagoModal = ref(false);
const showPagoParcialModal = ref(false);
const metodoPago = ref('');
const cuentaBancariaId = ref('');
const notasPago = ref('');
const montoPagoParcial = ref('');
const cuentaBancariaIdParcial = ref('');
const notasPagoParcial = ref('');
const usarSaldoFavorParcial = ref(false);
const showCancelModal = ref(false);
const motivoCancelacion = ref('');
const notasCancelacion = ref('');

const motivosCancelacion = [
    'Error en captura / Datos incorrectos',
    'Factura duplicada',
    'Devolución de mercancía',
    'Cancelada por el proveedor',
    'Ajuste de saldos',
    'Solicitud administrativa',
    'Otros (especificar en notas)'
];

const metodosDisponibles = [
    { val: 'efectivo', label: 'Efectivo' },
    { val: 'transferencia', label: 'Transferencia' },
    { val: 'cheque', label: 'Cheque' },
    { val: 'tarjeta', label: 'Tarjeta' },
    { val: 'otros', label: 'Otros' }
];

const toNumber = (v) => { let n = Number(v); return isFinite(n) ? n : 0; };
const formatCurrency = (v) => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(toNumber(v));

const pagoProgress = computed(() => {
    const total = toNumber(props.cuenta?.monto_total);
    if (total <= 0) return 0;
    return Math.min(100, Math.round((toNumber(props.cuenta?.monto_pagado) / total) * 100));
});

const estadoCuenta = computed(() => {
    if (props.cuenta?.estado === 'cancelada') return 'cancelada';
    if (props.cuenta?.pagado || toNumber(props.cuenta?.monto_pendiente) <= 0) return 'pagado';
    return props.cuenta?.estado || 'pendiente';
});

const isVencido = computed(() => props.cuenta?.estado === 'vencido');

const estadoClases = computed(() => {
    const status = estadoCuenta.value;
    if (status === 'pagado') return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
    if (status === 'parcial') return 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
    if (status === 'vencido') return 'bg-rose-500/20 text-rose-400 border border-rose-500/30';
    if (status === 'cancelada') return 'bg-slate-500/20 text-slate-300 border border-slate-500/30';
    return 'bg-slate-500/20 text-slate-400 border border-slate-500/30';
});

const getMetodoPagoLabel = (m) => ({
    'efectivo': 'Efectivo', 'transferencia': 'Transferencia', 'cheque': 'Cheque', 'tarjeta': 'Tarjeta', 'otros': 'Otros'
}[m] || m || 'No especificado');

const triggerPagoParcial = () => {
    montoPagoParcial.value = props.cuenta.monto_pendiente;
    usarSaldoFavorParcial.value = false;
    showPagoParcialModal.value = true;
};

const usarSoloSaldoFavor = () => {
    usarSaldoFavorParcial.value = true;
    montoPagoParcial.value = '';
};

const triggerPagoTotal = () => {
    showPagoModal.value = true;
};

const cancelarCuenta = () => {
    motivoCancelacion.value = '';
    notasCancelacion.value = '';
    showCancelModal.value = true;
};

const confirmarCancelacion = () => {
    if (!motivoCancelacion.value) {
        notyf.error('Selecciona un motivo de cancelación');
        return;
    }

    let motivoFinal = motivoCancelacion.value;
    if (notasCancelacion.value) {
        motivoFinal += " - " + notasCancelacion.value;
    }

    if (motivoFinal.length < 5) {
        notyf.error('El motivo debe tener al menos 5 caracteres.');
        return;
    }

    isSubmitting.value = true;
    router.post(route('cuentas-por-pagar.cancelar', props.cuenta.id), {
        motivo_cancelacion: motivoFinal,
    }, {
        onSuccess: () => {
            notyf.success('Cuenta cancelada correctamente');
            showCancelModal.value = false;
            router.reload();
        },
        onError: () => {
            notyf.error('No se pudo cancelar la cuenta');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

const confirmarPago = async () => {
    if (!metodoPago.value) return notyf.error('Selecciona un método de pago');
    isSubmitting.value = true;
    
    try {
        const res = await fetch(route('cuentas-por-pagar.marcar-pagado', props.cuenta.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
            body: JSON.stringify({
                metodo_pago: metodoPago.value,
                cuenta_bancaria_id: cuentaBancariaId.value || null,
                notas_pago: notasPago.value || null
            })
        });

        const result = await res.json();
        if (result.success) {
            notyf.success('Cuenta liquidada exitosamente');
            showPagoModal.value = false;
            router.visit(route('cuentas-por-pagar.index'));
        } else {
            notyf.error(result.error || 'Error al procesar pago');
        }
    } catch (e) {
        notyf.error('Error crítico de conexión');
    } finally {
        isSubmitting.value = false;
    }
};

const confirmarPagoParcial = async () => {
    const monto = montoPagoParcial.value === '' ? 0 : parseFloat(montoPagoParcial.value);
    const usarSaldoFavor = !!usarSaldoFavorParcial.value;

    if (!usarSaldoFavor && (isNaN(monto) || monto <= 0)) return notyf.error('Monto inválido');
    if (!isNaN(monto) && monto > props.cuenta.monto_pendiente) return notyf.error('Monto excede el pendiente');
    
    isSubmitting.value = true;
    try {
        const res = await fetch(route('cuentas-por-pagar.registrar-pago', props.cuenta.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
            body: JSON.stringify({
                monto: monto > 0 ? monto : null,
                notas: notasPagoParcial.value || null,
                cuenta_bancaria_id: cuentaBancariaIdParcial.value || null,
                usar_saldo_favor: usarSaldoFavor
            })
        });

        if (res.ok) {
            notyf.success('Abono registrado');
            showPagoParcialModal.value = false;
            router.reload();
        } else {
            const err = await res.json();
            notyf.error(err.message || 'Error registrando abono');
        }
    } catch (e) {
        notyf.error('Error de red');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<style scoped>
.search-none::-webkit-outer-spin-button,
.search-none::-webkit-inner-spin-button {
    -webkit-appearance: none;
    appearance: none;
    margin: 0;
}
.search-none {
    -moz-appearance: textfield;
    appearance: textfield;
}
/* Animations */
@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-in {
    animation-fill-mode: both;
}
.zoom-in-95 {
    animation-name: zoomIn;
}
</style>
