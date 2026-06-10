<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar animate-fadeIn">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="close"></div>

      <!-- Modal Container -->
      <div class="flex min-h-full items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="relative w-full max-w-6xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden transform transition-all max-h-[90vh]">
          
          <!-- Header Premium -->
          <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center border border-amber-500/20 text-2xl shadow-inner">
                📊
              </div>
              <div>
                <h3 class="text-xl font-black tracking-tight text-white flex items-center gap-2.5">
                  <span>Administrador de XML y Pagos REP</span>
                  <span class="px-2.5 py-1 bg-amber-500 text-slate-950 font-black text-[10px] uppercase rounded-lg tracking-widest shadow-sm">SAT En Vivo</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Explora flujos reales de dinero (REPs) y facturas de clientes/proveedores para generar pólizas bancarias</p>
              </div>
            </div>
            
            <div class="flex items-center gap-3 self-end md:self-center">
              <div class="flex items-center gap-2 bg-slate-800/80 p-1 rounded-2xl border border-white/10 text-xs">
                <select v-model="filtroMes" @change="fetchData" class="bg-transparent border-none text-slate-200 text-xs font-bold py-1 px-3 focus:ring-0 cursor-pointer">
                  <option value="todos" class="bg-slate-900 text-slate-200">📅 Todos los meses</option>
                  <option value="01" class="bg-slate-900 text-slate-200">Enero</option>
                  <option value="02" class="bg-slate-900 text-slate-200">Febrero</option>
                  <option value="03" class="bg-slate-900 text-slate-200">Marzo</option>
                  <option value="04" class="bg-slate-900 text-slate-200">Abril</option>
                  <option value="05" class="bg-slate-900 text-slate-200">Mayo</option>
                  <option value="06" class="bg-slate-900 text-slate-200">Junio</option>
                  <option value="07" class="bg-slate-900 text-slate-200">Julio</option>
                  <option value="08" class="bg-slate-900 text-slate-200">Agosto</option>
                  <option value="09" class="bg-slate-900 text-slate-200">Septiembre</option>
                  <option value="10" class="bg-slate-900 text-slate-200">Octubre</option>
                  <option value="11" class="bg-slate-900 text-slate-200">Noviembre</option>
                  <option value="12" class="bg-slate-900 text-slate-200">Diciembre</option>
                </select>
                <span class="text-slate-600">|</span>
                <select v-model="filtroAnio" @change="fetchData" class="bg-transparent border-none text-slate-200 text-xs font-bold py-1 px-3 focus:ring-0 cursor-pointer">
                  <option value="todos" class="bg-slate-900 text-slate-200">🗓️ Todos los años</option>
                  <option value="2026" class="bg-slate-900 text-slate-200">2026</option>
                  <option value="2025" class="bg-slate-900 text-slate-200">2025</option>
                  <option value="2024" class="bg-slate-900 text-slate-200">2024</option>
                </select>
              </div>
              <button @click="close" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 transition-all border border-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>
          </div>

          <!-- Tabs Navigation -->
          <div class="px-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center gap-2 overflow-x-auto pt-4">
            <button @click="activeTab = 'reps'" class="pb-3 px-4 text-xs font-black uppercase tracking-wider flex items-center gap-2 border-b-2 transition-all whitespace-nowrap" :class="activeTab === 'reps' ? 'border-amber-500 text-amber-500 dark:text-amber-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
              <span class="p-1 rounded bg-amber-500/10 text-amber-500">💸</span>
              Pagos REP / Flujos de Dinero ({{ saldosData?.reps?.length || 0 }})
            </button>
            <button @click="activeTab = 'clientes'" class="pb-3 px-4 text-xs font-black uppercase tracking-wider flex items-center gap-2 border-b-2 transition-all whitespace-nowrap" :class="activeTab === 'clientes' ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
              <span class="p-1 rounded bg-emerald-500/10 text-emerald-500">💰</span>
              Facturas Clientes / CxC ({{ saldosData?.por_cobrar?.facturas?.length || 0 }})
            </button>
            <button @click="activeTab = 'proveedores'" class="pb-3 px-4 text-xs font-black uppercase tracking-wider flex items-center gap-2 border-b-2 transition-all whitespace-nowrap" :class="activeTab === 'proveedores' ? 'border-rose-500 text-rose-600 dark:text-rose-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'">
              <span class="p-1 rounded bg-rose-500/10 text-rose-500">🏢</span>
              Facturas Proveedores / CxP ({{ saldosData?.por_pagar?.facturas?.length || 0 }})
            </button>
          </div>

          <!-- Buscador Interno -->
          <div class="p-4 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="relative flex-1 w-full max-w-lg">
              <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              <input v-model="searchQuery" placeholder="Buscar por nombre de cliente/proveedor, RFC, serie o folio..." class="w-full pl-10 pr-4 py-2 text-xs font-medium border border-slate-200 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" />
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
              <label class="flex items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300 cursor-pointer select-none bg-slate-100 dark:bg-slate-800 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm transition-all hover:bg-slate-200 dark:hover:bg-slate-700">
                <input type="checkbox" v-model="ocultarRegistrados" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                <span>Ocultar ya conciliados / con REP</span>
              </label>
              <div class="text-xs text-slate-400 font-medium whitespace-nowrap">
                Mostrando <span class="text-slate-800 dark:text-slate-200 font-bold">{{ filteredList.length }}</span> resultados
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="flex-1 flex flex-col items-center justify-center py-20 px-4 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-4 border-indigo-500/20 border-t-indigo-600 mb-4"></div>
            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Consultando Base de Datos y XML En Vivo...</p>
          </div>

          <!-- Content List -->
          <div v-else class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/50 dark:bg-slate-900/50">
            
            <!-- TAB 1: REPs -->
            <template v-if="activeTab === 'reps'">
              <div v-if="filteredList.length === 0" class="py-16 text-center text-slate-400 text-xs">
                No se encontraron Recibos Electrónicos de Pago (REP) con el filtro actual.
              </div>
              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="rep in filteredList" :key="rep.uuid" class="bg-white dark:bg-slate-800 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                  <div>
                    <div class="flex items-start justify-between gap-3 mb-3">
                      <div>
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                          <span :class="rep.tipo_flujo === 'ingreso' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'" class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider border">
                            {{ rep.tipo_flujo === 'ingreso' ? '💰 Ingreso (Cliente)' : '💸 Egreso (Proveedor)' }}
                          </span>
                          <span v-if="rep.en_bancos" class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 flex items-center gap-1">
                            <span>🏦 En Bancos</span>
                          </span>
                          <span class="text-xs font-black text-slate-900 dark:text-white">{{ rep.serie }}{{ rep.folio }}</span>
                        </div>
                        <p class="text-[10px] font-mono text-slate-400">{{ formatDate(rep.fecha_emision) }}</p>
                      </div>
                      <div class="text-right">
                        <span class="text-base font-black tabular-nums" :class="rep.tipo_flujo === 'ingreso' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                          ${{ n(rep.monto_total) }}
                        </span>
                      </div>
                    </div>

                    <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-xl mb-4 border border-slate-100 dark:border-slate-800/80">
                      <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider block mb-0.5">Contraparte</span>
                      <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate" :title="rep.contraparte_nombre">{{ rep.contraparte_nombre }}</p>
                      <p class="text-[10px] font-mono text-slate-500">{{ rep.contraparte_rfc }}</p>
                    </div>

                    <!-- Facturas Relacionadas -->
                    <div v-if="rep.pagos && rep.pagos.length > 0" class="mb-4">
                      <span class="text-[10px] font-bold text-slate-500 block mb-1.5">Facturas Pagadas en este REP:</span>
                      <div class="space-y-1">
                        <div v-for="(dr, idx) in (rep.pagos[0]?.doctos || [])" :key="idx" class="text-[11px] flex items-center justify-between bg-slate-100/80 dark:bg-slate-800/80 px-2.5 py-1 rounded-lg font-mono text-slate-700 dark:text-slate-300">
                          <span>Factura: {{ dr.serie }}{{ dr.folio || dr.uuid.slice(0,8) }}</span>
                          <span class="font-bold text-indigo-600 dark:text-indigo-400">${{ n(dr.imp_pagado) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="rep.en_bancos" class="w-full py-2.5 px-4 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-2 border border-emerald-500/20 shadow-sm select-none">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    <span>Vinculado en Bancos</span>
                  </div>
                  <button v-else @click="seleccionarRep(rep)" class="w-full py-2.5 px-4 bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white rounded-xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-2 shadow-md transition-all active:scale-[0.99]">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    <span>🏦 Generar Movimiento y Póliza</span>
                  </button>
                </div>
              </div>
            </template>

            <!-- TAB 2 y 3: FACTURAS CXC y CXP -->
            <template v-else>
              <div v-if="filteredList.length === 0" class="py-16 text-center text-slate-400 text-xs">
                No se encontraron facturas con el filtro actual.
              </div>
              <div v-else class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                  <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-700 text-[10px] font-black uppercase tracking-wider text-slate-400">
                      <tr>
                        <th class="p-4">Folio / Fecha</th>
                        <th class="p-4">{{ activeTab === 'clientes' ? 'Cliente / Razón Social' : 'Proveedor / Razón Social' }}</th>
                        <th class="p-4 text-right">Monto Total</th>
                        <th class="p-4 text-right">Saldo Pendiente</th>
                        <th class="p-4 text-center">Estado REP / Pago</th>
                        <th class="p-4 text-center">Acción</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-xs">
                      <tr v-for="item in filteredList" :key="item.uuid" class="hover:bg-slate-50/80 dark:hover:bg-slate-700/50 transition-colors">
                        <td class="p-4 whitespace-nowrap">
                          <div class="font-black text-slate-900 dark:text-white">{{ item.serie }}{{ item.folio }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ formatDate(item.fecha) }}</div>
                        </td>
                        <td class="p-4 max-w-xs">
                          <div class="font-bold text-slate-800 dark:text-slate-200 truncate" :title="item.razon_social">{{ item.razon_social }}</div>
                          <div class="text-[10px] text-slate-400 font-mono">{{ item.rfc }}</div>
                        </td>
                        <td class="p-4 text-right whitespace-nowrap font-bold text-slate-900 dark:text-white tabular-nums">
                          ${{ n(item.total) }}
                        </td>
                        <td class="p-4 text-right whitespace-nowrap tabular-nums font-black" :class="item.saldo > 0 ? (activeTab === 'clientes' ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400') : 'text-emerald-600 dark:text-emerald-400'">
                          ${{ n(item.saldo) }}
                        </td>
                        <td class="p-4 text-center whitespace-nowrap">
                          <div v-if="item.tiene_rep" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl text-[10px] font-black uppercase tracking-wider">
                            <span>✅ REP:</span>
                            <span class="tabular-nums">${{ n(item.rep_pagado) }}</span>
                          </div>
                          <div v-else-if="item.metodo_pago === 'PUE'" class="inline-flex items-center gap-1 px-2 py-0.5 bg-sky-500/10 text-sky-600 dark:text-sky-400 rounded-lg text-[10px] font-bold uppercase border border-sky-500/20">
                            PUE (Contado)
                          </div>
                          <div v-else-if="item.estado_pago === 'pagado'" class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-lg text-[10px] font-bold uppercase">
                            Pagado
                          </div>
                          <div v-else class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-lg text-[10px] font-bold uppercase">
                            Pendiente PPD
                          </div>
                        </td>
                        <td class="p-4 text-center whitespace-nowrap">
                          <button v-if="item.tiene_poliza" @click="verPoliza(item)" title="Ver Póliza Contable" class="px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 text-indigo-600 dark:text-indigo-400 rounded-xl text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-1.5 transition-all active:scale-95 mx-auto cursor-pointer shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>{{ item.poliza_numero ? `✅ Póliza #${item.poliza_numero}` : '✅ Con Póliza' }}</span>
                          </button>
                          <span v-else-if="item.en_bancos" class="px-3 py-1.5 bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 rounded-xl text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-1 select-none mx-auto">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>🏦 Ya en Bancos</span>
                          </span>
                          <button v-else @click="seleccionarFactura(item)" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-md shadow-indigo-600/20 flex items-center gap-1.5 transition-all active:scale-95 mx-auto">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            <span>Generar Póliza</span>
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </template>
          </div>

          <!-- Footer -->
          <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between">
            <span class="text-xs text-slate-400">Selecciona un XML/REP para vincularlo y llenar automáticamente el formulario de Bancos.</span>
            <button @click="close" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-sm transition-all active:scale-95">
              Cerrar Administrador
            </button>
          </div>

        </div>
      </div>

      <!-- Modal de Detalle de Póliza Contable Interno -->
      <div v-if="showPolizaModal" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md" @click="showPolizaModal = false"></div>
        <div class="relative bg-slate-900 border border-white/10 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col animate__animated animate__zoomIn animate__faster">
          <!-- Header -->
          <div class="px-6 py-5 border-b border-white/10 bg-slate-800/50 flex justify-between items-center">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center border border-indigo-500/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
              </div>
              <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                  <span>Detalle de Póliza Contable</span>
                  <span v-if="selectedPoliza?.numero" class="px-2.5 py-0.5 rounded-lg bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-mono">#{{ selectedPoliza.numero }}</span>
                </h3>
                <p class="text-xs text-slate-400 font-medium mt-0.5">{{ selectedPoliza?.concepto || 'Cargando concepto...' }}</p>
              </div>
            </div>
            <button @click="showPolizaModal = false" class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 transition-all border border-white/5">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <!-- Body -->
          <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <!-- Loading State -->
            <div v-if="loadingPoliza" class="flex flex-col items-center justify-center py-20 text-center">
              <div class="w-12 h-12 border-4 border-indigo-500/20 border-t-indigo-500 rounded-full animate-spin mb-4 mx-auto"></div>
              <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Cargando Asientos y Documentos...</p>
            </div>

            <template v-else-if="selectedPoliza">
              <!-- Cards Resumen -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                  <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Fecha</span>
                  <span class="text-base font-bold text-slate-200">{{ formatDate(selectedPoliza.fecha) }}</span>
                </div>
                <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                  <span class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-1">Tipo de Póliza</span>
                  <div class="flex items-center gap-2">
                    <span class="text-base font-bold text-slate-200 uppercase">{{ selectedPoliza.tipo || '-' }}</span>
                    <span v-if="selectedPoliza.concepto?.includes('[PPD]')" class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30">PPD</span>
                    <span v-else-if="selectedPoliza.concepto?.includes('[PUE]')" class="px-2 py-0.5 rounded-lg text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">PUE</span>
                  </div>
                </div>
                <div class="p-4 rounded-2xl bg-indigo-500/[0.05] border border-indigo-500/20">
                  <span class="block text-[10px] font-black uppercase tracking-widest text-indigo-400/80 mb-1">Total Póliza</span>
                  <span class="text-xl font-black text-indigo-400 tabular-nums">${{ n(selectedPoliza.total) }}</span>
                </div>
              </div>

              <!-- Póliza Descuadrada -->
              <div v-if="selectedPoliza.descuadrada" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-400 text-lg font-black shrink-0">⚠️</div>
                <div>
                  <p class="text-xs font-black uppercase tracking-wider text-rose-400">Póliza Descuadrada</p>
                  <p class="text-xs text-rose-300/80 mt-0.5">La suma del Debe no coincide con la del Haber. Diferencia: <strong class="font-mono text-rose-400">${{ n(selectedPoliza.diferencia) }}</strong></p>
                </div>
              </div>

              <!-- Asientos Contables -->
              <div>
                <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-3 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                  <span>Asientos Contables</span>
                </h4>
                <div class="rounded-2xl border border-white/5 bg-slate-950/50 overflow-hidden shadow-xl">
                  <table class="w-full text-xs text-left border-collapse">
                    <thead class="bg-white/[0.03] text-slate-400 border-b border-white/5 uppercase font-black text-[10px] tracking-wider">
                      <tr>
                        <th class="p-3.5">Cuenta / Código</th>
                        <th class="p-3.5 text-right">Debe</th>
                        <th class="p-3.5 text-right">Haber</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                      <tr v-for="a in selectedPoliza.asientos" :key="a.id" class="hover:bg-white/[0.02] transition-colors">
                        <td class="p-3.5">
                          <div class="font-bold text-white">{{ a.cuenta?.nombre || 'Cuenta sin nombre' }}</div>
                          <div class="text-[10px] font-mono text-indigo-400/80 mt-0.5 font-semibold">{{ a.cuenta?.codigo || '-' }}</div>
                        </td>
                        <td class="p-3.5 text-right font-mono font-black tabular-nums" :class="a.debe > 0 ? 'text-emerald-400' : 'text-slate-600'">${{ n(a.debe) }}</td>
                        <td class="p-3.5 text-right font-mono font-black tabular-nums" :class="a.haber > 0 ? 'text-rose-400' : 'text-slate-600'">${{ n(a.haber) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Documentos Vinculados -->
              <div v-if="selectedDocumentos.length > 0 || selectedPoliza.cfdi_uuid">
                <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-3 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                  <span>CFDIs Vinculados</span>
                </h4>
                <div class="space-y-2">
                  <template v-if="selectedDocumentos.length > 0">
                    <div v-for="doc in selectedDocumentos" :key="doc.uuid" class="p-3.5 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-between group">
                      <div>
                        <div class="flex items-center gap-2 mb-1">
                          <span class="text-[10px] font-black px-2 py-0.5 rounded-lg bg-blue-500/20 text-blue-400 uppercase tracking-widest">{{ doc.relacion || 'CFDI' }}</span>
                          <span class="text-xs font-bold text-white">{{ doc.emisor || doc.receptor || '-' }}</span>
                        </div>
                        <p class="text-[11px] font-mono text-blue-300/80">{{ doc.uuid }}</p>
                      </div>
                      <div class="text-right shrink-0 ml-4">
                        <p class="text-xs font-black text-white font-mono tabular-nums">${{ n(doc.total) }}</p>
                        <button @click="abrirXml(doc.uuid)" class="text-[10px] font-bold text-blue-400 hover:underline mt-0.5 inline-block">Ver XML</button>
                      </div>
                    </div>
                  </template>
                  <template v-else-if="selectedPoliza.cfdi_uuid">
                    <div class="p-3.5 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-between">
                      <span class="text-xs font-mono text-blue-300 font-medium">{{ selectedPoliza.cfdi_uuid }}</span>
                      <button @click="abrirXml(selectedPoliza.cfdi_uuid)" class="text-[10px] font-black text-blue-400 hover:underline ml-3 inline-block">Ver XML</button>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Soportes Adjuntos -->
              <div v-if="selectedPoliza?.soportes?.length > 0">
                <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-3 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                  <span>Soportes Adjuntos</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div v-for="(sop, idx) in selectedPoliza.soportes" :key="idx" class="p-3 rounded-2xl bg-white/[0.02] border border-white/5 flex items-center justify-between group">
                    <div class="flex items-center gap-3 overflow-hidden">
                      <div class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center shrink-0">📄</div>
                      <div class="overflow-hidden">
                        <p class="text-xs font-bold text-white truncate">{{ sop.name }}</p>
                        <p class="text-[9px] text-slate-500 font-mono">{{ sop.date }}</p>
                      </div>
                    </div>
                    <a :href="sop.url" target="_blank" class="p-2 bg-slate-800 hover:bg-indigo-500 hover:text-white text-slate-400 rounded-xl transition-all inline-block">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </a>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Footer Modal -->
          <div class="p-5 border-t border-white/10 bg-slate-800/30 flex justify-end">
            <button @click="showPolizaModal = false" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-black uppercase tracking-widest shadow-sm transition-all active:scale-95">
              Cerrar Póliza
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { Notyf } from 'notyf'

const props = defineProps({
  show: { type: Boolean, default: false }
})

const emit = defineEmits(['close', 'select'])

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })
const loading = ref(false)
const saldosData = ref(null)
const activeTab = ref('reps') // reps, clientes, proveedores
const searchQuery = ref('')
const filtroMes = ref('todos')
const filtroAnio = ref('todos')
const ocultarRegistrados = ref(true)

// Estado para el modal de póliza
const showPolizaModal = ref(false)
const selectedPoliza = ref(null)
const selectedDocumentos = ref([])
const loadingPoliza = ref(false)

const fetchData = async () => {
  loading.value = true
  try {
    const res = await axios.get(route('contabilidad.api.saldos-xml', { mes: filtroMes.value, anio: filtroAnio.value }))
    if (res.data.success) {
      saldosData.value = res.data.data
    }
  } catch (e) {
    notyf.error('Error al cargar datos del Administrador de XML.')
  } finally {
    loading.value = false
  }
}

watch(() => props.show, (newVal) => {
  if (newVal && !saldosData.value) {
    fetchData()
  }
})

onMounted(() => {
  if (props.show) {
    fetchData()
  }
})

const n = (val) => {
  const num = parseFloat(val) || 0
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (val) => {
  if (!val) return ''
  return val.split('T')[0]
}

const filteredList = computed(() => {
  if (!saldosData.value) return []
  
  let list = []
  if (activeTab.value === 'reps') {
    list = saldosData.value.reps || []
  } else if (activeTab.value === 'clientes') {
    list = saldosData.value.por_cobrar?.facturas || []
  } else if (activeTab.value === 'proveedores') {
    list = saldosData.value.por_pagar?.facturas || []
  }

  if (ocultarRegistrados.value) {
    if (activeTab.value === 'reps') {
      list = list.filter(item => !item.en_bancos)
    } else {
      list = list.filter(item => {
        if (item.en_bancos) return false
        const metodo = (item.metodo_pago || 'PUE').toUpperCase()
        if (metodo === 'PPD') {
          return !item.tiene_rep
        } else {
          return !item.tiene_poliza
        }
      })
    }
  }

  if (!searchQuery.value) return list

  const q = searchQuery.value.toLowerCase()
  return list.filter(item => {
    const folio = (item.folio || '').toLowerCase()
    const serie = (item.serie || '').toLowerCase()
    const rfc = (item.rfc || item.contraparte_rfc || '').toLowerCase()
    const name = (item.razon_social || item.contraparte_nombre || '').toLowerCase()
    return folio.includes(q) || serie.includes(q) || rfc.includes(q) || name.includes(q)
  })
})

const seleccionarRep = (rep) => {
  emit('select', {
    is_rep: true,
    id: rep.id,
    uuid: rep.uuid,
    serie: rep.serie,
    folio: rep.folio,
    monto: rep.monto_total,
    tipo: rep.tipo_flujo, // ingreso vs egreso
    contraparte_nombre: rep.contraparte_nombre,
    contraparte_rfc: rep.contraparte_rfc,
    concepto: `Pago REP ${rep.serie}${rep.folio} - ${rep.contraparte_nombre}`,
    referencia: `${rep.serie}${rep.folio}`
  })
  emit('close')
}

const seleccionarFactura = (item) => {
  const isCliente = activeTab.value === 'clientes'
  emit('select', {
    is_rep: false,
    uuid: item.uuid,
    serie: item.serie,
    folio: item.folio,
    monto: item.saldo > 0 ? item.saldo : item.total,
    tipo: isCliente ? 'ingreso' : 'egreso',
    contraparte_nombre: item.razon_social,
    contraparte_rfc: item.rfc,
    concepto: `Pago Factura ${item.serie}${item.folio} - ${item.razon_social}`,
    referencia: `${item.serie}${item.folio}`
  })
  emit('close')
}

const verPoliza = async (item) => {
  if (!item.poliza_id) {
    if (item.poliza_numero) {
      window.open(route('contabilidad.index', { search: item.poliza_numero }), '_blank')
    } else {
      window.open(route('contabilidad.index'), '_blank')
    }
    return
  }
  
  showPolizaModal.value = true
  loadingPoliza.value = true
  selectedPoliza.value = null
  selectedDocumentos.value = []

  try {
    const res = await axios.get(route('contabilidad.show', item.poliza_id), {
      headers: { Accept: 'application/json' }
    })
    if (res.data?.poliza) {
      selectedPoliza.value = res.data.poliza
    }
    if (res.data?.documentos) {
      selectedDocumentos.value = res.data.documentos
    }
  } catch (e) {
    notyf.error('Error al cargar los detalles de la póliza.')
    showPolizaModal.value = false
  } finally {
    loadingPoliza.value = false
  }
}

const abrirXml = (uuid) => {
  window.open(route('cfdi.descargar-xml', { uuid, inline: 1 }), '_blank')
}

const close = () => {
  emit('close')
}
</script>
