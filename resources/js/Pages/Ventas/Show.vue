<template>
  <Head title="Detalles de Venta" />

  <div class="min-h-screen bg-[var(--ui-surface)] py-8 transition-colors duration-200">
    <div class="w-full px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <Link :href="route('ventas.index')" class="inline-flex items-center text-[10px] font-black uppercase tracking-wide text-slate-500 dark:text-slate-500 hover:text-slate-900 dark:hover:text-white mb-6 transition-colors group">
          <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-800 flex items-center justify-center mr-3 shadow-sm group-hover:scale-105 transition-transform">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </div>
          Volver al Panel de Ventas
        </Link>

        <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-800 p-8">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center space-x-6">
              <div class="w-16 h-16 rounded-2xl flex items-center justify-center shadow-xl transform transition-transform" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
                <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <h1 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Detalles de Venta</h1>
                <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em] mt-1">Ref: {{ venta.numero_venta || venta.id }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span :class="getEstadoClass(venta.estado)" class="px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase tracking-wide border border-current">
                {{ getEstadoLabel(venta.estado) }}
              </span>
              <span v-if="venta.pagado" class="px-5 py-2.5 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 text-emerald-600 dark:text-slate-400 rounded-2xl text-[10px] font-black uppercase tracking-wide border border-emerald-100 dark:border-emerald-800/30">
                Líquidado
              </span>
              <span v-else class="px-5 py-2.5 bg-brand-50 dark:bg-brand-900/20 text-brand-600 dark:text-brand-400 rounded-2xl text-[10px] font-black uppercase tracking-wide border border-brand-100 dark:border-brand-800/30">
                Pendiente de Pago
              </span>
              <span v-if="venta.esta_facturada" class="px-5 py-2.5 bg-blue-50 dark:bg-sky-900/20 text-blue-600 dark:text-blue-400 rounded-2xl text-[10px] font-black uppercase tracking-wide border border-blue-100 dark:border-blue-800/30">
                Facturada (CFDI)
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Sale Information -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between" :style="{ background: `linear-gradient(135deg, ${colors.principal}05 0%, ${colors.secundario}03 100%)` }">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-800">
                  <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Información General</h2>
              </div>
            </div>
            <div class="p-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Client -->
                <div class="bg-slate-50 dark:bg-slate-950/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                  <h3 class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Cliente / Receptor</h3>
                  <div class="space-y-3">
                    <p class="text-2xl font-black text-slate-900 dark:text-white uppercase leading-tight">{{ venta.cliente?.nombre_razon_social || 'Desconocido' }}</p>
                    <div class="flex items-center text-xs font-bold text-slate-500 dark:text-slate-400">
                       <svg class="w-4 h-4 mr-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                       {{ venta.cliente?.email || 'Sin correo' }}
                    </div>
                    <div class="flex items-center text-xs font-bold text-slate-500 dark:text-slate-400">
                       <svg class="w-4 h-4 mr-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                       {{ venta.cliente?.telefono || 'Sin teléfono' }}
                    </div>
                    <div v-if="venta.cliente?.rfc" class="inline-flex items-center px-2 py-1 bg-slate-100 dark:bg-slate-800 rounded-xl text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-wide mt-2">
                       RFC: {{ venta.cliente?.rfc }}
                    </div>
                  </div>
                </div>

                <!-- Sale Details -->
                <div class="bg-slate-50 dark:bg-slate-950/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800/50">
                  <h3 class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Detalles de Operación</h3>
                  <div class="space-y-6">
                    <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800/50 pb-2">
                      <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Fecha:</span>
                      <span class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ formatearFecha(venta.fecha) }}</span>
                    </div>
                    <div v-if="venta.almacen" class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800/50 pb-2">
                      <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Almacén:</span>
                      <span class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ venta.almacen.nombre }}</span>
                    </div>
                    <div v-if="venta.vendedor" class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800/50 pb-2">
                      <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Vendedor:</span>
                      <span class="text-xs font-black text-slate-900 dark:text-white uppercase">{{ venta.vendedor.nombre || venta.vendedor.name }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800/50 pb-2">
                      <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Forma de pago</span>
                      <span class="text-xs font-black text-slate-900 dark:text-white text-right max-w-[60%]">{{ resumenFormaPago }}</span>
                    </div>
                    <div v-if="venta.forma_pago_sat || venta.metodo_pago_sat" class="flex justify-between items-center border-b border-slate-100 dark:border-slate-800/50 pb-2">
                      <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">SAT</span>
                      <span class="text-xs font-bold text-slate-700 dark:text-slate-200 text-right">{{ etiquetaSat }}</span>
                    </div>
                    <div v-if="venta.cuenta_bancaria" class="flex justify-between items-center">
                      <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Cuenta</span>
                      <span class="text-xs font-bold text-slate-800 dark:text-slate-200 text-right">{{ venta.cuenta_bancaria.banco }} — {{ venta.cuenta_bancaria.nombre }}</span>
                    </div>
                    <div v-if="venta.cliente?.forma_pago_default && !venta.metodo_pago" class="flex justify-between items-start gap-2 pt-2 border-t border-dashed border-slate-200 dark:border-slate-800/50">
                      <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide shrink-0">Cat. cliente</span>
                      <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 text-right">{{ textoFormaPagoClienteCatalogo(venta.cliente.forma_pago_default) }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="venta.notas" class="mt-8">
                <h3 class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-3">Observaciones / Notas</h3>
                <div class="p-5 bg-[var(--ui-surface)] dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/50 rounded-2xl italic text-sm text-slate-500 dark:text-slate-400">
                  {{ venta.notas }}
                </div>
              </div>
            </div>
          </div>

          <!-- Products and Services -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-slate-100 dark:bg-slate-800">
                  <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                </div>
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Productos y Servicios</h2>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ venta.productos.length }} conceptos registrados</p>
                </div>
              </div>
            </div>

            <div v-if="venta.productos.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-800/50">
                  <tr>
                    <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Concepto</th>
                    <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Cant.</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Precio</th>
                    <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Desc.</th>
                    <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                  <template v-for="producto in venta.productos" :key="producto.id">
                    <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-700/20 transition-colors">
                      <td class="px-8 py-5">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-11 w-11 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-100 dark:border-slate-700/50 shadow-sm">
                            <svg v-if="producto.tipo === 'producto'" class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <svg v-else class="w-10 h-10 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                          </div>
                          <div class="ml-5">
                            <div class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">{{ producto.nombre }}</div>
                            <div class="flex items-center mt-1 space-x-2">
                              <span :class="producto.tipo === 'producto' ? 'bg-indigo-50 dark:bg-sky-900/20 text-indigo-600 dark:text-indigo-400 border border-indigo-100/50 dark:border-indigo-800/30' : 'bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 text-emerald-600 dark:text-slate-400 border border-emerald-100/50 dark:border-emerald-800/30'"
                                    class="inline-flex items-center px-2 py-0.5 rounded-xl text-[8px] font-black uppercase tracking-wide">
                                {{ producto.tipo }}
                              </span>
                              <span v-if="producto.requiere_serie && producto.series && producto.series.length > 0"
                                    class="inline-flex items-center px-2 py-0.5 rounded-xl text-[8px] font-black bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 text-blue-600 dark:text-blue-400 border border-blue-100/50 dark:border-blue-800/30 tracking-wide">
                                #SERIES: {{ producto.series.length }}
                              </span>
                            </div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-5 text-center">
                        <span class="inline-flex items-center justify-center px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs font-black text-slate-900 dark:text-white">
                          {{ producto.pivot.cantidad }}
                        </span>
                      </td>
                      <td class="px-6 py-5 text-right font-bold text-xs text-slate-900 dark:text-white">${{ formatCurrency(producto.pivot.precio) }}</td>
                      <td class="px-6 py-5 text-right">
                        <span v-if="producto.pivot.descuento > 0" class="text-[10px] font-black text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 px-2 py-1 rounded-xl border border-rose-100 dark:border-rose-800/30">
                          -{{ producto.pivot.descuento }}%
                        </span>
                        <span v-else class="text-[10px] font-bold text-slate-400 dark:text-slate-500">-</span>
                      </td>
                      <td class="px-8 py-5 text-right">
                        <span class="text-xs font-black text-slate-900 dark:text-white">${{ formatCurrency(producto.pivot.subtotal) }}</span>
                      </td>
                    </tr>
                    <!-- Series Row Detail -->
                    <tr v-if="producto.requiere_serie && producto.series && producto.series.length > 0" class="bg-sky-50 dark:bg-sky-900/20/10 dark:bg-blue-900/05">
                      <td colspan="5" class="px-8 py-4">
                        <div class="flex flex-wrap gap-2">
                          <div v-for="(serie, idx) in producto.series" :key="idx"
                               class="inline-flex flex-col bg-white dark:bg-slate-800/50 border border-blue-100 dark:border-blue-900/30 rounded-xl px-4 py-2 shadow-sm">
                             <span class="text-[8px] font-black text-blue-500 dark:text-blue-400 uppercase tracking-wide mb-1">Serie No.</span>
                             <span class="text-xs font-mono font-bold text-slate-900 dark:text-white tracking-wide">{{ serie.numero_serie }}</span>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>

            <!-- Empty State -->
            <div v-else class="px-8 py-20 text-center">
              <div class="w-16 h-16 bg-[var(--ui-surface)] dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-slate-300 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
              </div>
              <h3 class="text-sm font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Sin conceptos registrados</h3>
            </div>
          </div>
        </div>

        <!-- Sidebar Summary & Actions -->
        <div class="space-y-6">
          <!-- Financial Summary Card -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-100 dark:border-slate-800 overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 dark:border-slate-800/50 bg-[var(--ui-surface)] dark:bg-slate-950/20">
              <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Resumen de Venta</h2>
            </div>
            <div class="p-8 space-y-5">
              <div class="flex justify-between items-center text-xs font-bold">
                <span class="text-slate-400 dark:text-slate-500 uppercase tracking-wide">Subtotal Global</span>
                <span class="text-slate-900 dark:text-white">${{ formatCurrency(venta.subtotal) }}</span>
              </div>
              <div v-if="venta.descuento_general > 0" class="flex justify-between items-center text-xs font-bold">
                <span class="text-rose-600 dark:text-rose-400 uppercase tracking-wide">Descuento Global</span>
                <span class="text-rose-600 dark:text-rose-400">-${{ formatCurrency(venta.descuento_general) }}</span>
              </div>
              <div class="flex justify-between items-center text-xs font-bold">
                <span class="text-slate-400 dark:text-slate-500 uppercase tracking-wide">Impuestos (IVA 16%)</span>
                <span class="text-slate-900 dark:text-white">${{ formatCurrency(venta.iva) }}</span>
              </div>
              <div class="pt-5 border-t-2 border-slate-100 dark:border-slate-800/50">
                <div class="flex flex-col gap-2">
                  <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em]">Total a Liquidar</span>
                  <p class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter">
                    <span class="text-xl font-bold opacity-50 mr-1">$</span>{{ formatCurrency(venta.total).split('.')[0] }}<span class="text-xl opacity-70">.{{ formatCurrency(venta.total).split('.')[1] }}</span>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Context Card -->
          <div :class="venta.pagado ? 'bg-emerald-50 dark:bg-emerald-900/20/50 dark:bg-emerald-950/20 border-emerald-100 dark:border-emerald-800/30' : 'bg-brand-50 dark:bg-brand-900/20/50 dark:bg-amber-950/20 border-brand-100 dark:border-brand-800/30'"
               class="rounded-3xl border p-8 transition-colors duration-200">
            <div class="flex items-center space-x-4 mb-6">
              <div :class="venta.pagado ? 'bg-brand-500 text-white' : 'bg-brand-500 text-white'" class="w-10 h-10 rounded-2xl flex items-center justify-center shadow-xl transform rotate-3">
                 <svg v-if="venta.pagado" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                 <svg v-else class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <div>
                <h3 :class="venta.pagado ? 'text-emerald-900 dark:text-emerald-300' : 'text-brand-900 dark:text-amber-300'" class="text-xs font-black uppercase tracking-wide">
                  {{ venta.pagado ? 'Recaudación Exitosa' : 'Pendiente de Cobro' }}
                </h3>
                <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wide">Estado Financiero</p>
              </div>
            </div>
            
            <div v-if="venta.pagado" class="space-y-6">
              <div class="bg-white/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-emerald-100/50 dark:border-emerald-800/20">
                <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide block mb-1">Forma de pago (liquidación)</span>
                <span class="text-sm font-black text-emerald-600 dark:text-slate-400 tracking-tight">{{ resumenFormaPago }}</span>
                <p v-if="etiquetaSat" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-2">{{ etiquetaSat }}</p>
              </div>
              <div v-if="venta.fecha_pago" class="bg-white/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-emerald-100/50 dark:border-emerald-800/20">
                <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide block mb-1">Fecha de liquidación</span>
                <span class="text-sm font-black text-emerald-600 dark:text-slate-400 uppercase tracking-wide">{{ formatearFecha(venta.fecha_pago) }}</span>
              </div>
            </div>
            <div v-else class="space-y-3">
              <div class="bg-white/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-brand-200 dark:border-brand-800/30/60 dark:border-brand-800/30">
                <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide block mb-1">Forma de pago acordada</span>
                <span class="text-sm font-black text-brand-800 dark:text-brand-200 dark:text-brand-300 tracking-tight">{{ resumenFormaPago }}</span>
                <p v-if="etiquetaSat" class="text-[10px] font-bold text-slate-500 dark:text-slate-400 mt-2">{{ etiquetaSat }}</p>
              </div>
              <p class="text-xs font-bold text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-500 uppercase leading-relaxed text-center p-4 bg-brand-100/30 dark:bg-brand-900/10 rounded-2xl border border-brand-200 dark:border-brand-800/30/50 dark:border-brand-800/30">
                Pendiente de cobro en sistema.
              </p>
            </div>
          </div>

          <!-- Actions Grid -->
          <div class="grid grid-cols-1 gap-3">
            <Link :href="route('ventas.pdf', venta.id)" target="_blank"
                  class="flex items-center justify-center gap-3 px-6 py-4 bg-white dark:bg-slate-800 hover:bg-slate-700 dark:hover:bg-slate-700 text-[10px] font-black uppercase tracking-[0.2em] text-slate-700 dark:text-slate-400 rounded-2xl border-2 border-slate-100 dark:border-slate-800 transition-all transform hover:shadow-xl hover:shadow-xl active:translate-y-0">
               <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
               Visualizar PDF
            </Link>
            
            <Link v-if="canEdit" :href="route('ventas.edit', venta.id)"
                  class="flex items-center justify-center gap-3 px-6 py-4 bg-sky-600 hover:bg-sky-700 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl hover:shadow-sky-500/20 transition-all transform hover:shadow-xl hover:shadow-xl active:translate-y-0">
               <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
               Modificar Registro
            </Link>

            <button v-if="!venta.pagado && !esCancelada(venta)" @click="showPagoModal = true"
                    class="flex items-center justify-center gap-3 px-6 py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl hover:shadow-emerald-500/20 transition-all transform hover:shadow-xl hover:shadow-xl active:translate-y-0">
               <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
               Liquidar Ahora
            </button>


            
            <div v-if="venta.esta_facturada" class="flex flex-col gap-2">
               <a :href="route('cfdi.ver-pdf', venta.factura_uuid)" target="_blank"
                     class="flex items-center justify-center gap-3 px-6 py-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                  Ver PDF Factura
               </a>
               <a :href="route('cfdi.descargar-xml', venta.factura_uuid)" target="_blank"
                     class="flex items-center justify-center gap-3 px-6 py-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl transition-all">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                  Descargar XML
               </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Modals Section -->
      <!-- Pago Modal -->
      <DialogModal :show="showPagoModal" @close="showPagoModal = false" max-width="lg">
        <template #title>
          <div class="flex items-center space-x-3 text-emerald-600">
             <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
             <span class="text-xl font-black uppercase tracking-wide">Registrar Pago</span>
          </div>
        </template>
        <template #content>
          <div class="space-y-6 pt-4">
             <div class="bg-slate-50 dark:bg-slate-950 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                <div class="flex justify-between items-center">
                   <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">Monto Total a Liquidar</span>
                   <span class="text-2xl font-black text-slate-900 dark:text-white">${{ formatCurrency(venta.total) }}</span>
                </div>
             </div>

             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
               <div>
                  <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Método de Pago</label>
                  <select v-model="metodoPago" class="w-full bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-900 dark:text-white focus:border-brand-500 transition-all outline-none">
                    <option value="">Seleccionar método</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="cheque">Cheque</option>
                    <option value="tarjeta">Tarjeta</option>
                  </select>
               </div>
               <div v-if="requiresBankAccount">
                  <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Cuenta Destino</label>
                  <select v-model="cuentaBancariaId" class="w-full bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-900 dark:text-white focus:border-brand-500 transition-all outline-none">
                     <option value="">Seleccionar cuenta</option>
                     <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">{{ cuenta.nombre }} - {{ cuenta.banco }}</option>
                  </select>
               </div>
             </div>

             <div v-if="usuariosCobroLista.length">
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">¿Quién recibió el dinero?</label>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-2">Afecta corte de caja y reportes.</p>
                <select v-model="pagadoPorUserId" class="w-full bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-900 dark:text-white focus:border-brand-500 transition-all outline-none">
                  <option v-for="u in usuariosCobroLista" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                </select>
             </div>

             <div>
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2 ml-1">Notas del Pago</label>
                <textarea v-model="notasPago" rows="3" class="w-full bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl px-4 py-3 text-sm font-bold text-slate-900 dark:text-white focus:border-brand-500 transition-all outline-none resize-none" placeholder="Opcional..."></textarea>
             </div>
          </div>
        </template>
        <template #footer>
          <div class="flex gap-3">
             <button @click="showPagoModal = false" class="px-6 py-3 text-[10px] font-black uppercase tracking-wide text-slate-500 hover:text-slate-900 dark:text-slate-500 dark:hover:text-white transition-colors">Cerrar</button>
             <button @click="confirmarPago" :disabled="!canConfirmPayment" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl shadow-xl disabled:opacity-50 transition-all">Confirmar Liquidación</button>
          </div>
        </template>
      </DialogModal>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import DialogModal from '@/Components/DialogModal.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';
import {
  labelMetodoPagoVenta,
  labelFormaPagoSat,
  labelMetodoPagoSat,
  textoFormaPagoClienteCatalogo,
} from '@/Utils/ventaPagoLabels';
import Swal from '@/Utils/Swal';

const { colors } = useCompanyColors();

const props = defineProps({
  venta: Object,
  canEdit: Boolean,
  canDelete: Boolean,
  canCancel: Boolean,
  isAdmin: Boolean,
  cuentasBancarias: Array,
  usuariosCobro: { type: Array, default: () => [] }
});

const page = usePage();

const usuariosCobroLista = computed(() => {
  const fromApi = props.usuariosCobro;
  if (Array.isArray(fromApi) && fromApi.length > 0) {
    return fromApi;
  }
  const u = page.props.auth?.user;
  if (u?.id) {
    return [{ id: u.id, name: u.name || u.email || 'Usuario actual' }];
  }
  return [];
});

const resumenFormaPago = computed(() => {
  const v = props.venta;
  const m = labelMetodoPagoVenta(v?.metodo_pago);
  if (m) {
    return m;
  }
  if (v?.forma_pago_sat) {
    return labelFormaPagoSat(v.forma_pago_sat);
  }
  if (v?.cliente?.forma_pago_default) {
    return `Catálogo cliente: ${textoFormaPagoClienteCatalogo(v.cliente.forma_pago_default)}`;
  }
  return 'No registrada';
});

const etiquetaSat = computed(() => {
  const v = props.venta;
  const parts = [];
  if (v?.forma_pago_sat) {
    parts.push(`Forma ${String(v.forma_pago_sat).padStart(2, '0')} — ${labelFormaPagoSat(v.forma_pago_sat)}`);
  }
  if (v?.metodo_pago_sat) {
    parts.push(labelMetodoPagoSat(v.metodo_pago_sat));
  }
  return parts.join(' · ');
});

const notyf = new Notyf({ duration: 3000, position: { x: 'right', y: 'top' } });
const showPagoModal = ref(false);
const metodoPago = ref('');
const cuentaBancariaId = ref('');
const notasPago = ref('');
const pagadoPorUserId = ref('');

const requiresBankAccount = computed(() => ['tarjeta', 'transferencia'].includes(metodoPago.value));


const canConfirmPayment = computed(() => {
  const cobroOk = !usuariosCobroLista.value.length || !!pagadoPorUserId.value;
  return metodoPago.value && (!requiresBankAccount.value || cuentaBancariaId.value) && cobroOk;
});

watch(showPagoModal, (open) => {
  if (open) {
    pagadoPorUserId.value = String(page.props.auth?.user?.id ?? '');
  }
});

const formatCurrency = (value) => value.toLocaleString('es-MX', { minimumFractionDigits: 2 });
const formatearFecha = (fecha) => fecha ? new Date(fecha).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
const esCancelada = (v) => v.estado === 'cancelada';

const getEstadoClass = (estado) => {
  const map = {
    'borrador': 'text-slate-400 border-slate-100 dark:border-slate-800',
    'pendiente': 'text-brand-500 border-brand-100 dark:border-brand-900/30',
    'aprobada': 'text-emerald-500 border-emerald-100 dark:border-emerald-900/30',
    'cancelada': 'text-rose-500 border-rose-100 dark:border-rose-900/30'
  };
  return map[estado] || 'text-slate-400';
};

const getEstadoLabel = (estado) => estado.toUpperCase();

const confirmarPago = () => {
  router.post(route('ventas.marcar-pagado', props.venta.id), {
    metodo_pago: metodoPago.value,
    cuenta_bancaria_id: cuentaBancariaId.value || null,
    notas_pago: notasPago.value,
    pagado_por_user_id: pagadoPorUserId.value ? Number(pagadoPorUserId.value) : undefined
  }, {
    onSuccess: () => {
      notyf.success('Venta liquidada correctamente');
      showPagoModal.value = false;
    },
    onError: () => notyf.error('Error al procesar el pago')
  });
};
</script>

<style scoped>
.min-h-screen {
  font-family: 'Figtree', sans-serif;
}
</style>
