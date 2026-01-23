<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="onClose"
    >
      <div
        v-if="selected"
        class="bg-slate-900 rounded-3xl shadow-2xl w-full max-w-5xl max-h-[95vh] overflow-hidden flex flex-col border border-slate-800 outline-none transform transition-all"
        role="dialog"
        aria-modal="true"
        aria-label="Modal de Detalles de Venta"
        tabindex="-1"
        ref="modalRef"
        @keydown.esc.prevent="onClose"
      >
        <!-- Header Principal -->
        <div class="px-8 py-6 bg-gradient-to-r from-indigo-600 to-indigo-800 text-white relative overflow-hidden">
          <!-- Efecto de brillo de fondo -->
          <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white dark:bg-slate-900/10 rounded-full blur-3xl"></div>
          
          <div class="flex justify-between items-start relative z-10">
            <div class="flex items-center gap-4">
              <div class="bg-white dark:bg-slate-900/10 p-3 rounded-2xl backdrop-blur-md border border-white/20">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div>
                <h2 class="text-2xl font-black uppercase tracking-tight text-white flex items-center gap-2">
                  Detalles de Venta
                </h2>
                <div class="flex items-center gap-3 mt-1">
                  <span class="text-indigo-200 text-sm font-mono font-bold px-2 py-0.5 bg-black/20 rounded-lg">{{ selected.numero_venta }}</span>
                  <span :class="getEstadoStyle(selected.estado)" class="px-3 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border">
                    {{ getEstadoLabel(selected.estado) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="text-right">
              <p class="text-[10px] text-indigo-200 font-black uppercase tracking-[0.2em] mb-1">Fecha de Emisión</p>
              <p class="font-bold text-lg text-white font-mono">{{ formatearFecha(selected.created_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Cuerpo del Modal -->
        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar bg-slate-900">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Columna Principal (2/3) -->
            <div class="lg:col-span-2 space-y-8">
              
              <!-- Tarjeta de Cliente y Datos Generales -->
              <div class="bg-slate-950/40 rounded-3xl border border-slate-800 overflow-hidden shadow-inner">
                <div class="px-8 py-6">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Cliente -->
                    <div class="space-y-4">
                      <div class="flex items-center gap-2 mb-1">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 shadow-[0_0_8px_rgba(99,102,241,0.6)]"></div>
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Información del Cliente</h3>
                      </div>
                      <div>
                        <p class="text-xl font-black text-white leading-tight mb-2">{{ selected.cliente?.nombre_razon_social || 'Público General' }}</p>
                        <div class="grid grid-cols-1 gap-2 text-sm text-slate-400 font-medium">
                          <div v-if="selected.cliente?.rfc && selected.cliente?.rfc !== 'N/A'" class="flex items-center gap-2">
                             <span class="bg-slate-800/50 text-slate-500 text-[9px] font-black px-1.5 py-0.5 rounded border border-slate-700/50 uppercase">RFC</span>
                             <span class="text-slate-300">{{ selected.cliente.rfc }}</span>
                          </div>
                          <div v-if="selected.cliente?.email && selected.cliente?.email !== 'N/A'" class="flex items-center gap-2">
                             <span class="bg-slate-800/50 text-slate-500 text-[9px] font-black px-1.5 py-0.5 rounded border border-slate-700/50 uppercase">Email</span>
                             <span class="text-slate-300 truncate">{{ selected.cliente.email }}</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Datos Logísticos -->
                    <div class="space-y-4">
                      <div class="flex items-center gap-2 mb-1">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)]"></div>
                        <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Logística de Venta</h3>
                      </div>
                      <div class="space-y-3">
                         <div v-if="selected.almacen" class="flex items-start gap-3 p-3 bg-slate-900/50 rounded-2xl border border-slate-800/50">
                            <div class="p-2 bg-slate-800 rounded-lg text-slate-400">
                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <div>
                               <p class="text-xs font-black text-white uppercase tracking-tight">{{ selected.almacen.nombre }}</p>
                               <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Almacén de Salida</p>
                            </div>
                         </div>
                         <div v-if="selected.vendedor" class="flex items-start gap-3 p-3 bg-slate-900/50 rounded-2xl border border-slate-800/50">
                            <div class="p-2 bg-slate-800 rounded-lg text-slate-400">
                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div>
                               <p class="text-xs font-black text-white uppercase tracking-tight">{{ selected.vendedor.nombre }}</p>
                               <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Vendedor Responsable</p>
                            </div>
                         </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Notas -->
                <div v-if="selected.notas" class="px-8 py-4 bg-amber-500/5 border-t border-slate-800/50 flex items-start gap-4">
                  <div class="mt-1">
                    <svg class="w-5 h-5 text-amber-500 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                  </div>
                  <div>
                    <h4 class="text-[9px] font-black text-amber-400 uppercase tracking-widest mb-1">Notas del despacho</h4>
                    <p class="text-sm text-slate-300 font-medium italic">{{ selected.notas }}</p>
                  </div>
                </div>
              </div>

              <!-- Tabla de Productos -->
              <div class="bg-slate-950/40 rounded-3xl border border-slate-800 overflow-hidden shadow-inner">
                <div class="px-8 py-5 border-b border-slate-800 bg-slate-900/30 flex justify-between items-center">
                  <h3 class="text-sm font-black text-white uppercase tracking-[0.25em] flex items-center gap-3">
                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                    Desglose de Partidas
                  </h3>
                  <div class="flex items-center gap-4">
                     <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                       {{ itemsCalculados.length + (productoPrincipal ? 1 : 0) }} Posiciones
                     </span>
                  </div>
                </div>

                <div v-if="itemsCalculados.length > 0 || productoPrincipal" class="overflow-x-auto overflow-y-visible">
                  <table class="min-w-full">
                    <thead>
                      <tr class="bg-slate-900/40 border-b border-slate-800/30">
                        <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Concepto / Partida</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Unidad</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Cant</th>
                        <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">P. Unitario</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Subtotal</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/30">
                      <template v-for="(item, idx) in (productoPrincipal ? [productoPrincipal] : itemsCalculados)" :key="item.id || idx">
                        <tr class="group hover:bg-indigo-500/5 transition-all duration-300 text-sm">
                          <td class="px-8 py-5">
                            <div class="flex items-start gap-4">
                              <div :class="item.tipo === 'producto' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'" 
                                   class="w-10 h-10 rounded-xl border flex items-center justify-center font-mono text-xs font-black shadow-lg shadow-black/20 flex-shrink-0">
                                {{ item.tipo === 'producto' ? 'PR' : 'SR' }}
                              </div>
                              <div class="min-w-0">
                                <p class="text-white font-black text-sm leading-snug group-hover:text-indigo-300 transition-colors">{{ item.nombre }}</p>
                                <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                  <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">{{ item.codigo || 'S/C' }}</span>
                                  <span v-if="item.pivot?.descuento > 0" class="text-[9px] font-black bg-rose-500/10 text-rose-400 px-1.5 py-0.5 rounded border border-rose-500/20 uppercase tracking-widest">
                                    DTO {{ item.pivot.descuento }}%
                                  </span>
                                </div>
                              </div>
                            </div>
                          </td>
                          <td class="px-6 py-5 text-center">
                             <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-slate-800/30 px-2 py-1 rounded border border-slate-700/50">{{ item.unidad || 'PZ' }}</span>
                          </td>
                          <td class="px-6 py-5 text-center">
                            <span class="text-sm font-black text-white font-mono">{{ item.pivot?.cantidad || 1 }}</span>
                          </td>
                          <td class="px-6 py-5 text-right font-mono font-bold text-slate-300">
                             {{ formatCurrency(item.pivot?.precio || 0) }}
                          </td>
                          <td class="px-8 py-5 text-right font-mono font-black text-indigo-400">
                             {{ formatCurrency(item.pivot?.subtotal || 0) }}
                          </td>
                        </tr>
                        <!-- Fila de Series -->
                        <tr v-if="item.requiere_serie && item.series?.length > 0" class="bg-indigo-500/5">
                           <td colspan="5" class="px-8 py-4 border-t border-indigo-500/20">
                             <div class="flex flex-col gap-3">
                                <div class="flex items-center gap-2">
                                   <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
                                   <span class="text-[9px] font-black text-indigo-300 uppercase tracking-[0.2em]">Identificadores de Serie (IMEI/SN)</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                   <span v-for="(serie, sIdx) in item.series" :key="sIdx" 
                                         class="bg-slate-900/80 border border-indigo-500/30 text-indigo-200 px-3 py-1.5 rounded-xl font-mono text-xs font-black shadow-sm group-hover:border-indigo-500/50 transition-all flex items-center gap-2">
                                      <span class="w-1.5 h-1.5 rounded-full bg-indigo-500/50"></span>
                                      {{ serie.numero_serie }}
                                      <span v-if="serie.almacen" class="text-[9px] text-slate-500 font-bold ml-1 uppercase">[{{ serie.almacen }}]</span>
                                   </span>
                                </div>
                             </div>
                           </td>
                        </tr>
                      </template>
                    </tbody>
                  </table>
                </div>
                
                <div v-else class="px-8 py-20 text-center space-y-4">
                  <div class="w-20 h-20 bg-slate-900 rounded-full border-2 border-dashed border-slate-800 flex items-center justify-center mx-auto text-slate-700">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                  </div>
                  <div>
                    <h4 class="text-white font-black uppercase tracking-widest text-sm">Sin partidas registradas</h4>
                    <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Esta venta no contiene productos o servicios aún.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Columna Lateral (1/3) -->
            <div class="space-y-8">
              
              <!-- Card: Financiero (Total) -->
              <div class="bg-indigo-600 rounded-3xl p-8 flex flex-col justify-between shadow-[0_20px_50px_rgba(79,70,229,0.3)] border border-indigo-400/30 relative overflow-hidden group">
                <!-- Efecto lumínico -->
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-transparent"></div>
                <div class="absolute -top-10 -right-10 w-40 h-40 bg-white dark:bg-slate-900/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-1000"></div>
                
                <div class="relative z-10">
                  <div class="flex items-center gap-2 mb-2">
                    <span class="w-2 h-2 rounded-full bg-white dark:bg-slate-900 animate-pulse"></span>
                    <h3 class="text-[10px] font-black text-indigo-100 uppercase tracking-[0.3em]">Cifra Total</h3>
                  </div>
                  <div class="flex items-baseline gap-1">
                    <span class="text-xl font-black text-indigo-200">$</span>
                    <span class="text-5xl font-black text-white font-mono tracking-tighter">{{ formatCurrency(selected.total || 0) }}</span>
                    <span class="text-[10px] font-black text-indigo-200 uppercase tracking-widest ml-1">MXN</span>
                  </div>
                </div>

                <div class="mt-8 pt-6 border-t border-indigo-500 relative z-10 grid grid-cols-2 gap-4">
                   <div class="space-y-1">
                      <p class="text-[9px] font-black text-indigo-200 uppercase tracking-widest">Subtotal</p>
                      <p class="text-sm font-black text-white font-mono tracking-tighter">{{ formatCurrency(selected.subtotal || 0) }}</p>
                   </div>
                   <div class="space-y-1 text-right">
                      <p class="text-[9px] font-black text-indigo-200 uppercase tracking-widest">Impuestos (16%)</p>
                      <p class="text-sm font-black text-white font-mono tracking-tighter">{{ formatCurrency(selected.iva || 0) }}</p>
                   </div>
                </div>
              </div>

              <!-- Card: Estado de Cobro -->
              <div :class="selected.pagado ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-amber-500/10 border-amber-500/20'" 
                   class="rounded-3xl border p-6 shadow-sm overflow-hidden relative">
                <div class="flex items-center gap-3 mb-4">
                  <div :class="selected.pagado ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white'" 
                       class="w-10 h-10 rounded-2xl flex items-center justify-center shadow-lg">
                    <svg v-if="selected.pagado" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  </div>
                  <div>
                    <h3 :class="selected.pagado ? 'text-emerald-400' : 'text-amber-400'" class="text-xs font-black uppercase tracking-widest">
                      {{ selected.pagado ? 'Cobro Completado' : 'Cobro Pendiente' }}
                    </h3>
                    <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Estado de la cuenta</p>
                  </div>
                </div>

                <div v-if="selected.pagado" class="space-y-4">
                  <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-slate-900/40 rounded-2xl border border-slate-800/50">
                       <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-1">Medio</p>
                       <p class="text-[10px] font-black text-white uppercase truncate">{{ selected.metodo_pago || 'Electrónico' }}</p>
                    </div>
                    <div class="p-3 bg-slate-900/40 rounded-2xl border border-slate-800/50 text-right">
                       <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-1">Fecha</p>
                       <p class="text-[10px] font-black text-white uppercase font-mono">{{ selected.fecha_pago ? new Date(selected.fecha_pago).toLocaleDateString() : 'N/A' }}</p>
                    </div>
                  </div>
                  <div v-if="selected.entrega_dinero?.cuenta_bancaria || selected.cuenta_bancaria" 
                       class="p-3 bg-indigo-500/5 border border-indigo-500/10 rounded-2xl flex items-center gap-3">
                    <div class="text-indigo-400 text-lg">🏛️</div>
                    <p class="text-[10px] font-bold text-slate-300">
                      {{ (selected.entrega_dinero?.cuenta_bancaria || selected.cuenta_bancaria).banco }} - {{ (selected.entrega_dinero?.cuenta_bancaria || selected.cuenta_bancaria).nombre }}
                    </p>
                  </div>
                </div>
                <div v-else class="flex items-center gap-4">
                  <p class="text-xs font-bold text-amber-500/70 leading-relaxed italic">
                    Esta venta se encuentra registrada como cuenta por cobrar.
                  </p>
                </div>
              </div>

              <!-- Card: Auditoria -->
              <div v-if="auditoriaSafe" class="bg-slate-950/40 rounded-3xl border border-slate-800 overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-slate-800 bg-slate-900/30 flex items-center gap-2">
                  <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" /></svg>
                  <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Trazabilidad</h3>
                </div>
                <div class="p-6 space-y-4">
                   <div class="flex items-start gap-4">
                      <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center text-xs font-black text-indigo-400 border border-slate-800">C</div>
                      <div>
                         <p class="text-[10px] font-black text-white uppercase truncate">{{ auditoriaSafe.creado_por || 'Sistema' }}</p>
                         <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">{{ formatearFecha(auditoriaSafe.creado_en || selected.created_at) }}</p>
                      </div>
                   </div>
                   <div class="hidden"></div> <!-- Espaciador -->
                   <div class="flex items-start gap-4">
                      <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center text-xs font-black text-emerald-400 border border-slate-800">M</div>
                      <div>
                         <p class="text-[10px] font-black text-white uppercase truncate">{{ auditoriaSafe.actualizado_por || 'N/A' }}</p>
                         <p class="text-[9px] text-slate-500 font-bold uppercase tracking-widest">{{ formatearFecha(auditoriaSafe.actualizado_en || selected.updated_at) }}</p>
                      </div>
                   </div>
                </div>
              </div>

              <!-- Facturación Sat Header -->
              <div v-if="selected.estado !== 'cancelada'" class="space-y-3">
                 <div class="flex items-center gap-2 px-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    <h3 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Facturación Electrónica</h3>
                 </div>
                 
                 <div v-if="selected.esta_facturada" class="space-y-3">
                    <div class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-2xl">
                       <p class="text-[8px] font-black text-blue-400 uppercase tracking-widest mb-1">UUID Timbrado SAT</p>
                       <p class="text-[10px] text-blue-200 font-mono break-all font-black leading-tight">{{ selected.factura_uuid || 'En proceso...' }}</p>
                    </div>
                    <!-- Botones CFDI -->
                    <div class="grid grid-cols-2 gap-3">
                       <button @click="verFacturaSat(selected.id)" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-[10px] font-black uppercase transition-all flex items-center justify-center gap-2 border border-slate-700">
                          📄 PDF
                       </button>
                       <button @click="verXmlSat(selected.id)" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-[10px] font-black uppercase transition-all flex items-center justify-center gap-2 border border-slate-700">
                          🧩 XML
                       </button>
                    </div>
                 </div>

                 <button v-else @click="facturarVenta(selected.id)" 
                         :disabled="isProcessingFactura || (!selected.pagado && selected.metodo_pago !== 'credito')"
                         class="w-full py-4 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-30 disabled:grayscale text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-xl transition-all flex items-center justify-center gap-3">
                    <span v-if="isProcessingFactura" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944V22m0-19.056c1.11 0 2.22.12 3.291.352 3.174.694 5.254 3.012 5.254 6.225 0 2.969-1.928 5.48-4.686 6.305" /></svg>
                    Generar CFDI 4.0
                 </button>
              </div>

              <!-- Footer de Botones -->
              <div class="pt-4 space-y-3">
                 <div class="grid grid-cols-2 gap-3">
                    <button @click="verPdfEnNavegador(selected.id)" class="group h-12 flex items-center justify-center gap-3 bg-slate-800/80 hover:bg-slate-700 rounded-2xl border border-slate-700/50 transition-all">
                       <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest group-hover:text-white">PDF Detalle</span>
                    </button>
                    <Link :href="route('ventas.ticket', selected.id)" target="_blank" class="group h-12 flex items-center justify-center gap-3 bg-slate-800/80 hover:bg-slate-700 rounded-2xl border border-slate-700/50 transition-all text-center">
                       <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest group-hover:text-white italic">Ticket</span>
                    </Link>
                 </div>
                 
                 <div class="flex flex-col gap-2">
                    <button v-if="!selected.pagado" @click="$emit('marcar-pagado', selected)" 
                            class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-3">
                       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                       Marcar como Pagado
                    </button>
                    
                    <button @click="onClose" class="w-full py-4 bg-slate-950/50 hover:bg-slate-900 text-slate-400 hover:text-white rounded-2xl text-[10px] font-black uppercase tracking-[0.3em] transition-all border border-slate-800/50">
                       Cerrar Visor
                    </button>
                 </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import axios from 'axios'
import 'notyf/notyf.min.css'
import Swal from 'sweetalert2'

const props = defineProps({
  show: { type: Boolean, default: false },
  selected: { type: Object, default: null },
  auditoria: { type: Object, default: null }
})

const emit = defineEmits(['close', 'edit', 'print', 'marcar-pagado', 'cancelar', 'eliminar'])

const notyf = new Notyf({ 
  duration: 4000, 
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#4F46E5', icon: false },
    { type: 'error', background: '#E11D48', icon: false }
  ]
})

const modalRef = ref(null)
const isProcessingFactura = ref(false)
const isCancellingFactura = ref(false)
const isSendingEmail = ref(false)
const showCancelFacturaOptions = ref(false)
const cancelFacturaParams = ref({
  motivo: '02',
  folio_sustitucion: ''
})

const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => props.show, (v) => { if (v) setTimeout(focusFirst, 0) })

const onClose = () => emit('close')
const onKey = (e) => { if (e.key === 'Escape' && props.show) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

const formatearFecha = (date) => {
  if (!date) return 'S/F'
  try {
    const t = new Date(date).getTime()
    if (Number.isNaN(t)) return 'INV'
    return new Date(t).toLocaleDateString('es-MX', {
      year: 'numeric', month: 'short', day: 'numeric',
      hour: '2-digit', minute: '2-digit'
    })
  } catch { return 'INV' }
}

const formatearFechaHora = formatearFecha

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe)
}

const formatCurrency = formatearMoneda

const getEstadoStyle = (estado) => {
  const map = {
    borrador: 'bg-slate-800 text-slate-400 border-slate-700',
    facturado: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
    pagado: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
    vencido: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
    aprobada: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30',
    cancelada: 'bg-slate-800 text-rose-500 border-rose-500/20'
  }
  return map[estado?.toLowerCase()] || 'bg-slate-800 text-slate-400 border-slate-700'
}

const getEstadoLabel = (estado) => {
  const map = {
    borrador: 'Borrador',
    facturado: 'Facturado',
    pagado: 'Liquidado',
    vencido: 'Vencido',
    aprobada: 'Aprobada',
    cancelada: 'Cancelada'
  }
  return map[estado?.toLowerCase()] || estado
}

// Items desde backend
const itemsCalculados = computed(() => {
  const lista = Array.isArray(props.selected?.items) ? props.selected.items : []
  return lista.map((item) => {
    const cantidad = parseFloat(item.cantidad || 1)
    const precio = parseFloat(item.precio || 0)
    const descuento = parseFloat(item.descuento || 0)
    const subtotalBase = precio * cantidad
    const subtotal = subtotalBase - (subtotalBase * (descuento / 100))
    return {
      ...item,
      pivot: {
        cantidad,
        precio,
        descuento,
        subtotal,
      },
      requiere_serie: item.requiere_serie || false,
      series: item.series || [],
      tipo: item.tipo || 'producto',
      nombre: item.nombre || 'Concepto sin nombre',
    }
  })
})

// Producto principal si no vienen items (legacy)
const productoPrincipal = computed(() => {
  const s = props.selected || {}
  if ((Array.isArray(s.items) && s.items.length > 0)) return null
  const nombre = s.producto_nombre || s.producto || s.concepto || null
  if (!nombre) return null
  const cantidad = parseFloat(s.cantidad || 1)
  const precio = parseFloat(s.precio_unitario || s.precio || s.total || 0)
  const subtotal = precio * cantidad
  return {
    id: `legacy-${s.id || '0'}`,
    nombre,
    tipo: 'producto',
    pivot: {
      cantidad,
      precio,
      descuento: 0,
      subtotal,
    },
    requiere_serie: false,
    series: [],
  }
})

const auditoriaSafe = computed(() => {
  if (props.auditoria) return props.auditoria
  if (props.selected) {
    return {
      creado_por: props.selected.created_by_user_name || 'N/A',
      actualizado_por: props.selected.updated_by_user_name || 'N/A',
      creado_en: props.selected.created_at,
      actualizado_en: props.selected.updated_at,
    }
  }
  return null
})

const verPdfEnNavegador = async (id) => {
  try {
    const response = await fetch(`/ventas/${id}/pdf`)
    if (!response.ok) throw new Error()
    const blob = await response.blob()
    const url = URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }))
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 30000)
  } catch {
    notyf.error('Error al visualizar el PDF')
  }
}

const facturarVenta = (id) => {
  if (!confirm('¿Generar factura electrónica ante el SAT?')) return
  isProcessingFactura.value = true
  router.post(route('ventas.facturar', id), {}, {
    onSuccess: () => notyf.success('CFDI generado exitosamente'),
    onError: (e) => notyf.error(e.error || 'Error en timbrado'),
    onFinish: () => isProcessingFactura.value = false
  })
}

const verFacturaSat = (id) => {
  if (props.selected?.factura_uuid) {
    window.open(route('cfdi.ver-pdf-view', props.selected.factura_uuid), '_blank')
  }
}

const verXmlSat = (id) => {
  if (props.selected?.factura_uuid) {
    window.open(route('cfdi.xml', { uuid: props.selected.factura_uuid, inline: 1 }), '_blank')
  }
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95) translateY(20px); }

.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(79, 70, 229, 0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(79, 70, 229, 0.3); }
</style>
