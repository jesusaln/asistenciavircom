<template>
  <div>
    <Head title="Conciliación Bancaria Inteligente" />

    <div class="w-full px-6 py-8 bg-slate-950 text-slate-100 min-h-screen selection:bg-indigo-500/30">
      
      <!-- Top Glowing Accent -->
      <div class="absolute top-0 left-1/4 right-1/4 h-[300px] bg-indigo-500/10 rounded-full blur-[120px] pointer-events-none"></div>
      
      <!-- Header -->
      <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 mb-8">
        <div>
          <div class="flex items-center gap-3">
            <span class="p-3 bg-indigo-500/10 text-indigo-400 rounded-2xl border border-indigo-500/20 shadow-lg shadow-indigo-500/5">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </span>
            <div>
              <h1 class="text-3xl font-black tracking-tight text-white flex items-center gap-3">
                Conciliación Bancaria <span class="px-2.5 py-0.5 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-[10px] font-black uppercase tracking-wider rounded-lg">Cabina IA</span>
              </h1>
              <p class="text-slate-400 text-xs mt-1">Vincula movimientos bancarios reales con tus pólizas, facturas y cuentas por cobrar/pagar de forma inteligente.</p>
            </div>
          </div>
        </div>
        
        <div class="flex items-center gap-3 self-start lg:self-center">
          <button
            @click="showImportModal = true"
            class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white border border-white/10 rounded-xl transition-all font-black text-xs uppercase tracking-widest flex items-center gap-2 shadow-sm cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Importar CSV
          </button>
          
          <button
            @click="conciliacionAutomatica"
            class="px-4 py-2.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 hover:text-emerald-300 border border-emerald-500/20 hover:border-emerald-500/30 rounded-xl transition-all font-black text-xs uppercase tracking-widest flex items-center gap-2 shadow-sm cursor-pointer"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Match Automático
          </button>
          
          <button
            @click="abrirAsistenteAi"
            class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl transition-all font-black text-xs uppercase tracking-widest flex items-center gap-2 shadow-xl shadow-indigo-500/20 hover:shadow-indigo-500/30 active:scale-95 cursor-pointer relative overflow-hidden group"
          >
            <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <span class="animate-pulse">✨</span> Asistente IA Gemini
          </button>
        </div>
      </div>

      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl flex items-center gap-3 animate-fade-in">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-xs font-bold">{{ $page.props.flash.success }}</span>
      </div>
      
      <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-2xl flex items-center gap-3 animate-fade-in">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-xs font-bold">{{ $page.props.flash.error }}</span>
      </div>

      <!-- Overview Cards (Stats Cockpit) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 relative z-10">
        
        <!-- Pendientes -->
        <div class="bg-slate-900/60 backdrop-blur-xl border border-white/5 rounded-3xl p-6 shadow-2xl flex items-center justify-between group hover:border-indigo-500/20 transition-all duration-300">
          <div class="space-y-1">
            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Pendientes Conciliar</p>
            <p class="text-3xl font-black text-white font-mono">{{ resumen.total }}</p>
            <p class="text-[9px] text-slate-400">Transacciones en cola</p>
          </div>
          <span class="p-4 bg-slate-950/80 rounded-2xl border border-white/5 text-slate-400 group-hover:text-indigo-400 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </span>
        </div>
        
        <!-- Depósitos -->
        <div class="bg-slate-900/60 backdrop-blur-xl border border-white/5 rounded-3xl p-6 shadow-2xl flex items-center justify-between group hover:border-emerald-500/20 transition-all duration-300">
          <div class="space-y-1">
            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Total Depósitos</p>
            <p class="text-3xl font-black text-emerald-400 font-mono">${{ formatMonto(resumen.monto_depositos) }}</p>
            <p class="text-[9px] text-slate-400">{{ resumen.depositos }} depósitos pendientes</p>
          </div>
          <span class="p-4 bg-slate-950/80 rounded-2xl border border-white/5 text-slate-400 group-hover:text-emerald-400 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          </span>
        </div>
        
        <!-- Retiros -->
        <div class="bg-slate-900/60 backdrop-blur-xl border border-white/5 rounded-3xl p-6 shadow-2xl flex items-center justify-between group hover:border-rose-500/20 transition-all duration-300">
          <div class="space-y-1">
            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Total Retiros</p>
            <p class="text-3xl font-black text-rose-400 font-mono">${{ formatMonto(resumen.monto_retiros) }}</p>
            <p class="text-[9px] text-slate-400">{{ resumen.retiros }} retiros pendientes</p>
          </div>
          <span class="p-4 bg-slate-950/80 rounded-2xl border border-white/5 text-slate-400 group-hover:text-rose-400 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"/></svg>
          </span>
        </div>
        
        <!-- Estado IA -->
        <div class="bg-slate-900/60 backdrop-blur-xl border border-white/5 rounded-3xl p-6 shadow-2xl flex items-center justify-between group hover:border-purple-500/20 transition-all duration-300">
          <div class="space-y-1">
            <p class="text-[9px] font-black uppercase tracking-widest text-slate-500">Diferencia Neta</p>
            <p class="text-3xl font-black font-mono" :class="diferencia >= 0 ? 'text-emerald-400' : 'text-rose-400'">
              ${{ formatMonto(diferencia) }}
            </p>
            <p class="text-[9px] text-slate-400">Balance del periodo</p>
          </div>
          <span class="p-4 bg-slate-950/80 rounded-2xl border border-white/5 text-slate-400 group-hover:text-purple-400 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
          </span>
        </div>
      </div>

      <!-- Workbench (Dual Panel Side-by-Side) -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 relative z-10">
        
        <!-- PANEL IZQUIERDO: MOVIMIENTOS BANCARIOS -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-black text-white flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
              1. Selecciona Movimiento Bancario
            </h3>
            <span class="px-2 py-0.5 bg-white/5 rounded text-[10px] font-mono text-slate-400">{{ movimientos.total }} registros</span>
          </div>

          <!-- Filtros de búsqueda rápidos -->
          <div class="p-4 bg-slate-900/60 backdrop-blur-xl border border-white/5 rounded-3xl space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <select v-model="form.estado" @change="aplicarFiltros" class="bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-slate-300 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                <option value="pendiente">Pendientes</option>
                <option value="conciliado">Conciliados</option>
                <option value="ignorado">Ignorados</option>
                <option value="todos">Todos</option>
              </select>
              <select v-model="form.tipo" @change="aplicarFiltros" class="bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-slate-300 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                <option value="">Todos los tipos</option>
                <option value="deposito">📈 Depósitos</option>
                <option value="retiro">📉 Retiros</option>
              </select>
              <select v-model="form.banco" @change="aplicarFiltros" class="bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-slate-300 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none">
                <option value="">Todos los bancos</option>
                <option v-for="banco in bancos" :key="banco" :value="banco">{{ banco }}</option>
              </select>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
              <div class="flex items-center gap-2">
                <span class="text-[9px] font-black uppercase text-slate-500">Desde:</span>
                <input type="date" v-model="form.fecha_desde" @change="aplicarFiltros" class="w-full bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-slate-300 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none" />
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[9px] font-black uppercase text-slate-500">Hasta:</span>
                <input type="date" v-model="form.fecha_hasta" @change="aplicarFiltros" class="w-full bg-slate-950 border border-white/10 rounded-xl text-xs font-bold text-slate-300 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none" />
              </div>
            </div>
          </div>

          <!-- Listado de Movimientos -->
          <div class="p-2 bg-slate-900/60 backdrop-blur-xl border border-white/5 rounded-[2rem] shadow-2xl overflow-hidden max-h-[60vh] overflow-y-auto space-y-2">
            <div
              v-for="mov in movimientos.data"
              :key="mov.id"
              @click="seleccionarMov(mov)"
              :class="[
                'p-4 rounded-2xl border transition-all cursor-pointer flex items-center justify-between group relative overflow-hidden',
                selectedMov?.id === mov.id
                  ? 'bg-indigo-600/10 border-indigo-500/50 shadow-lg shadow-indigo-500/5'
                  : 'bg-slate-950/60 border-white/5 hover:bg-white/[0.02] hover:border-white/10'
              ]"
            >
              <!-- Selected glow indicator -->
              <div v-if="selectedMov?.id === mov.id" class="absolute left-0 top-0 bottom-0 w-1.5 bg-indigo-500"></div>

              <div class="flex-1 space-y-1 pr-4">
                <div class="flex items-center gap-2">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider border',
                      mov.tipo === 'deposito'
                        ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                        : 'bg-rose-500/10 text-rose-400 border-rose-500/20'
                    ]"
                  >
                    {{ mov.tipo === 'deposito' ? 'Depósito' : 'Retiro' }}
                  </span>
                  <span class="text-[10px] font-mono text-slate-500 font-bold">{{ formatFecha(mov.fecha) }}</span>
                  <span class="px-1.5 py-0.5 bg-white/5 rounded text-[8px] font-mono text-slate-400 uppercase">{{ mov.banco }}</span>
                </div>
                <h4 class="text-sm font-bold text-slate-200 break-words group-hover:text-white transition-colors leading-snug">
                  {{ mov.concepto || '-' }}
                </h4>
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-[10px] text-slate-400 mt-1">
                  <span v-if="mov.referencia" class="font-mono">Ref: {{ mov.referencia }}</span>
                  <span v-if="mov.archivo_origen" class="break-all opacity-80 leading-tight">📁 {{ mov.archivo_origen }}</span>
                </div>
              </div>

              <div class="text-right space-y-1.5 flex-shrink-0">
                <h4
                  :class="[
                    'text-base font-black font-mono',
                    mov.tipo === 'deposito' ? 'text-emerald-400' : 'text-rose-400'
                  ]"
                >
                  {{ mov.tipo === 'deposito' ? '+' : '-' }}${{ formatMonto(Math.abs(mov.monto)) }}
                </h4>
                
                <div class="flex items-center justify-end gap-1.5">
                  <span
                    v-if="mov.estado === 'conciliado'"
                    class="px-1.5 py-0.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded text-[8px] font-bold uppercase tracking-wider"
                  >
                    Conciliado
                  </span>
                  <span
                    v-else-if="mov.estado === 'ignorado'"
                    class="px-1.5 py-0.5 bg-slate-800 border border-white/5 text-slate-400 rounded text-[8px] font-bold uppercase tracking-wider"
                  >
                    Ignorado
                  </span>
                  <span
                    v-else
                    class="px-1.5 py-0.5 bg-amber-500/10 border border-amber-500/20 text-amber-400 rounded text-[8px] font-bold uppercase tracking-wider animate-pulse"
                  >
                    Pendiente
                  </span>

                  <!-- Context action button to revert/ignore directly -->
                  <div class="flex items-center gap-1">
                    <button
                      v-if="mov.estado === 'pendiente'"
                      @click.stop="ignorar(mov.id)"
                      class="p-1 bg-white/5 hover:bg-rose-500/20 text-slate-400 hover:text-rose-400 rounded transition-all border border-white/5 cursor-pointer"
                      title="Ignorar Movimiento"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                    </button>
                    <button
                      v-if="mov.estado === 'conciliado'"
                      @click.stop="revertir(mov.id)"
                      class="p-1 bg-white/5 hover:bg-amber-500/20 text-slate-400 hover:text-amber-400 rounded transition-all border border-white/5 cursor-pointer"
                      title="Revertir Conciliación"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18v3"/></svg>
                    </button>
                    <button
                      v-if="mov.estado !== 'conciliado'"
                      @click.stop="eliminar(mov.id)"
                      class="p-1 bg-white/5 hover:bg-rose-600/30 text-slate-400 hover:text-rose-400 rounded transition-all border border-white/5 cursor-pointer"
                      title="Eliminar registro"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="movimientos.data.length === 0" class="py-16 text-center space-y-3 bg-slate-950/60 rounded-[2rem] border border-dashed border-white/10">
              <span class="inline-block p-4 bg-white/[0.01] rounded-3xl border border-white/5 text-slate-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v4m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2"/></svg>
              </span>
              <h3 class="text-sm font-black text-slate-300">No hay movimientos bancarios</h3>
              <p class="text-xs text-slate-500 max-w-[240px] mx-auto">Importa un archivo CSV o Excel de tu estado de cuenta para empezar.</p>
            </div>
          </div>

          <!-- Paginación local -->
          <div v-if="movimientos.last_page > 1" class="p-4 bg-slate-900/60 backdrop-blur-xl border border-white/5 rounded-3xl flex items-center justify-between">
            <span class="text-[10px] font-bold text-slate-500">
              Pág {{ movimientos.current_page }} de {{ movimientos.last_page }} ({{ movimientos.total }} total)
            </span>
            <div class="flex gap-1.5">
              <Link
                v-for="link in movimientos.links.filter(l => l.url)"
                :key="link.label"
                :href="link.url"
                v-html="link.label"
                :class="[
                  'px-2.5 py-1 rounded-xl text-[10px] font-bold transition-all border',
                  link.active
                    ? 'bg-indigo-600 border-indigo-500 text-white shadow-lg shadow-indigo-500/10'
                    : 'bg-slate-950 border-white/5 text-slate-400 hover:text-white hover:border-white/10'
                ]"
              />
            </div>
          </div>
        </div>

        <!-- PANEL DERECHO: EMPAREJAMIENTO SIDE-BY-SIDE -->
        <div class="space-y-4">
          <h3 class="text-lg font-black text-white flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full" :class="selectedMov ? 'bg-emerald-400 animate-pulse' : 'bg-slate-700'"></span>
            2. Conciliar con Facturas / Cuentas
          </h3>

          <!-- Si no hay movimiento seleccionado -->
          <div v-if="!selectedMov" class="py-24 px-6 rounded-[2.5rem] bg-slate-900/40 border border-dashed border-white/10 text-center space-y-4 flex flex-col items-center justify-center min-h-[50vh]">
            <span class="p-6 bg-slate-950/60 rounded-full border border-white/5 text-indigo-400/80 shadow-xl shadow-indigo-500/[0.02]">
              <svg class="w-10 h-10 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            </span>
            <h3 class="text-base font-black text-slate-200">Esperando Selección</h3>
            <p class="text-xs text-slate-400 max-w-[280px] leading-relaxed">
              Selecciona un movimiento bancario de la columna izquierda. El sistema buscará de manera inteligente y ordenará las facturas correspondientes.
            </p>
          </div>

          <!-- Si HAY movimiento seleccionado -->
          <div v-else class="space-y-4 min-h-[50vh]">
            <!-- Detalle del movimiento activo -->
            <div class="p-5 rounded-3xl bg-gradient-to-r from-indigo-900/20 to-purple-900/20 border border-indigo-500/20 shadow-2xl relative overflow-hidden flex items-center justify-between">
              <div class="absolute -right-10 -top-10 w-24 h-24 bg-indigo-500/10 rounded-full blur-xl"></div>
              
              <div class="space-y-1 relative z-10">
                <p class="text-[9px] font-black uppercase tracking-wider text-indigo-400">Transacción Activa</p>
                <h4 class="text-sm font-bold text-white max-w-[320px] truncate" :title="selectedMov.concepto">
                  "{{ selectedMov.concepto }}"
                </h4>
                <p class="text-[10px] text-slate-400 font-mono">
                  Fecha: {{ formatFecha(selectedMov.fecha) }} | Ref: {{ selectedMov.referencia || 'Sin Referencia' }}
                </p>
              </div>
              
              <div class="text-right relative z-10">
                <p class="text-xl font-black font-mono" :class="selectedMov.tipo === 'deposito' ? 'text-emerald-400' : 'text-rose-400'">
                  ${{ formatMonto(Math.abs(selectedMov.monto)) }}
                </p>
                <span class="text-[8px] font-black uppercase text-slate-500 tracking-wider">Monto Exacto a Buscar</span>
              </div>
            </div>

            <!-- Filtros de búsqueda para facturas -->
            <div class="p-4 bg-slate-900/60 border border-white/5 rounded-3xl flex items-center justify-between gap-4">
              <div class="relative flex-1">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </span>
                <input
                  type="text"
                  v-model="invoiceSearch"
                  placeholder="Buscar factura por cliente, proveedor, folio o RFC..."
                  class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-white/10 rounded-2xl text-xs font-bold text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all outline-none"
                />
              </div>
              
              <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                <input type="checkbox" id="exactMatch" v-model="onlyExactMatch" class="rounded bg-slate-950 border-white/10 text-indigo-600 focus:ring-0 cursor-pointer" />
                <label for="exactMatch" class="font-bold cursor-pointer select-none">Monto Exacto</label>
              </div>
            </div>

            <!-- Listado de Invoices Relacionados (CxC para depósito, CxP para retiro) -->
            <div class="max-h-[48vh] overflow-y-auto space-y-3 p-1">
              <div
                v-for="inv in filteredInvoices"
                :key="inv.id"
                :class="[
                  'p-5 rounded-3xl border transition-all relative overflow-hidden group',
                  isExactMatch(inv.monto_pendiente)
                    ? 'bg-emerald-500/[0.02] border-emerald-500/30 shadow-lg shadow-emerald-500/[0.02]'
                    : 'bg-slate-900/60 border-white/5 hover:bg-white/[0.02] hover:border-white/10'
                ]"
              >
                <!-- Glowing card badge for exact match -->
                <div v-if="isExactMatch(inv.monto_pendiente)" class="absolute right-0 top-0 bg-emerald-500/10 text-emerald-400 px-3 py-1 rounded-bl-2xl border-l border-b border-emerald-500/20 text-[9px] font-black uppercase tracking-wider animate-pulse">
                  Coincidencia de Monto
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                  <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                      <span :class="selectedMov.tipo === 'deposito' ? 'bg-sky-500/10 text-sky-400 border-sky-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'" class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider border">
                        {{ selectedMov.tipo === 'deposito' ? 'Ingreso (CxC)' : 'Egreso (CxP)' }}
                      </span>
                      <span v-if="inv.fecha_vencimiento" class="text-[10px] font-mono text-slate-500 font-bold">Vencimiento: {{ formatFecha(inv.fecha_vencimiento) }}</span>
                    </div>
                    
                    <h4 class="text-sm font-bold text-white group-hover:text-indigo-300 transition-colors">
                      {{ selectedMov.tipo === 'deposito' ? inv.cliente?.nombre_razon_social : inv.proveedor?.nombre_razon_social }}
                    </h4>
                    
                    <div class="flex items-center gap-3 text-[10px] text-slate-400 font-mono">
                      <span>Folio/Ref: <strong class="text-slate-300">{{ inv.referencia || 'Sin Folio' }}</strong></span>
                      <span v-if="selectedMov.tipo === 'deposito' && inv.cliente?.rfc">RFC: {{ inv.cliente.rfc }}</span>
                      <span v-else-if="selectedMov.tipo === 'retiro' && inv.proveedor?.rfc">RFC: {{ inv.proveedor.rfc }}</span>
                    </div>
                  </div>

                  <!-- Amount & Match Action -->
                  <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-2 border-t sm:border-t-0 border-white/5 pt-3 sm:pt-0">
                    <div class="text-left sm:text-right">
                      <p class="text-lg font-black font-mono text-white">
                        ${{ formatMonto(inv.monto_pendiente) }}
                      </p>
                      <p class="text-[8px] font-black uppercase text-slate-500 tracking-wider">Saldo Pendiente</p>
                    </div>

                    <button
                      @click="aplicarReconciliacionManual(inv)"
                      class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-black text-[10px] uppercase tracking-widest rounded-xl transition-all shadow-md active:scale-95 flex items-center gap-1.5 cursor-pointer"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                      Conciliar
                    </button>
                  </div>
                </div>
              </div>

              <!-- Sin facturas encontradas -->
              <div v-if="filteredInvoices.length === 0" class="py-16 text-center space-y-3 bg-slate-900/30 rounded-[2rem] border border-dashed border-white/10">
                <span class="inline-block p-4 bg-white/[0.01] rounded-3xl border border-white/5 text-slate-500">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <h3 class="text-sm font-black text-slate-300">No se encontraron facturas o cuentas</h3>
                <p class="text-xs text-slate-500 max-w-[260px] mx-auto leading-relaxed">
                  No hay registros pendientes que coincidan con la búsqueda o con el tipo de transacción bancaria.
                </p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Modal Importar CSV -->
    <ImportarCsvModal
      v-if="showImportModal"
      :bancos-soportados="bancos_soportados"
      @close="showImportModal = false"
    />

    <!-- Modal Asistente Conciliación IA (Gemini) -->
    <div v-if="showAiModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none">
      <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" @click="showAiModal = false"></div>
      
      <div class="relative w-full max-w-6xl mx-auto my-6 z-10 px-4">
        <div class="border-0 rounded-[2.5rem] shadow-2xl relative flex flex-col w-full bg-slate-900 border border-white/10 outline-none focus:outline-none text-slate-100 selection:bg-indigo-500/30 overflow-hidden">
          
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-white/5 bg-slate-950/40">
            <h3 class="text-xl font-black text-white flex items-center gap-2">
              <span class="text-indigo-400 animate-pulse">✨</span> Conciliación Inteligente con Asistente IA (Gemini)
            </h3>
            <button @click="showAiModal = false" class="text-slate-400 hover:text-white transition-colors cursor-pointer">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          
          <!-- Body -->
          <div class="relative p-6 flex-auto max-h-[70vh] overflow-y-auto space-y-6">
            <div v-if="loadingAi" class="py-24 text-center space-y-4">
              <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-indigo-500/10 border-t-indigo-500"></div>
              <p class="text-sm text-slate-400 font-bold animate-pulse">La Inteligencia Artificial de Gemini está leyendo los movimientos del banco y proponiendo los emparejamientos del otro lado...</p>
            </div>
            
            <div v-else-if="sugerenciasAi.length === 0" class="py-20 text-center space-y-3 bg-slate-950/40 rounded-[2rem] border border-white/5 p-8">
              <div class="w-16 h-16 bg-rose-500/10 text-rose-400 rounded-3xl flex items-center justify-center mx-auto border border-rose-500/20">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
              </div>
              <h3 class="text-sm font-black text-slate-300">Sin sugerencias de la IA</h3>
              <p class="text-xs text-slate-500 max-w-[340px] mx-auto leading-relaxed">
                Gemini no pudo emparejar con suficiente certeza tus movimientos bancarios pendientes con las facturas por cobrar/pagar.
              </p>
            </div>
            
            <div v-else class="space-y-4">
              <p class="text-xs text-indigo-300 font-bold">✨ Gemini ha emparejado exitosamente tus movimientos reales con su cuenta contable correspondiente:</p>
              
              <div class="grid grid-cols-1 gap-6">
                <div v-for="(sug, index) in sugerenciasAi" :key="index" class="p-6 rounded-[2rem] bg-slate-950/60 border border-white/5 hover:border-indigo-500/20 transition-all flex flex-col lg:flex-row items-stretch gap-6 shadow-xl relative overflow-hidden group">
                  <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-700"></div>
                  
                  <!-- Left: Bank Movement -->
                  <div class="flex-1 space-y-2">
                    <div class="flex items-center gap-2">
                      <span :class="sug.movimiento.tipo === 'deposito' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20'" class="px-2.5 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider border">
                        🏦 {{ sug.movimiento.tipo === 'deposito' ? 'Depósito' : 'Retiro' }}
                      </span>
                      <span class="text-xs font-mono text-slate-500">{{ formatFecha(sug.movimiento.fecha) }}</span>
                    </div>
                    <h4 class="text-lg font-black text-white">${{ formatMonto(Math.abs(sug.movimiento.monto)) }}</h4>
                    <p class="text-xs text-slate-300 font-medium line-clamp-2">"{{ sug.movimiento.concepto }}"</p>
                    <p v-if="sug.movimiento.referencia" class="text-[10px] text-slate-500 font-mono">Ref: {{ sug.movimiento.referencia }}</p>
                  </div>
                  
                  <!-- Middle: AI Score & Match Reason -->
                  <div class="lg:w-72 flex flex-col justify-center items-center p-4 bg-indigo-500/[0.03] border border-indigo-500/10 rounded-2xl text-center space-y-2 relative z-10 self-center">
                    <div class="flex items-center gap-1.5">
                      <span class="text-indigo-400 text-xs">✨</span>
                      <span class="px-2.5 py-0.5 bg-indigo-500/10 text-indigo-300 rounded-full text-[10px] font-black tracking-wide border border-indigo-500/20">
                        {{ sug.score }}% Confianza
                      </span>
                    </div>
                    <p class="text-[11px] text-indigo-200/90 font-medium leading-relaxed">
                      {{ sug.razonamiento }}
                    </p>
                  </div>
                  
                  <!-- Right: Suggested Invoice (CxC/CxP) -->
                  <div class="flex-1 space-y-2 border-t lg:border-t-0 lg:border-l border-white/5 pt-4 lg:pt-0 lg:pl-6 flex flex-col justify-between">
                    <div class="space-y-2">
                      <div class="flex items-center gap-2">
                        <span :class="sug.tipo_cuenta === 'CXC' ? 'bg-sky-500/10 text-sky-400 border-sky-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'" class="px-2.5 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider border">
                          {{ sug.tipo_cuenta === 'CXC' ? 'Cuenta por Cobrar (Venta)' : 'Cuenta por Pagar (Gasto)' }}
                        </span>
                        <span v-if="sug.factura.fecha_vencimiento" class="text-xs font-mono text-slate-500">Vence: {{ formatFecha(sug.factura.fecha_vencimiento) }}</span>
                      </div>
                      <h4 class="text-lg font-black text-white">${{ formatMonto(sug.factura.monto_pendiente) }}</h4>
                      <p class="text-xs text-slate-300 font-bold">{{ sug.factura.nombre_auxiliar }}</p>
                      <p class="text-[10px] text-slate-500 font-mono">Folio/Ref: {{ sug.factura.referencia || 'S/F' }} | RFC: {{ sug.factura.rfc || 'S/R' }}</p>
                    </div>
                    
                    <!-- Button to Reconcile -->
                    <div class="pt-4 flex justify-end">
                      <button
                        @click="conciliarSugerencia(sug)"
                        :disabled="sug.conciliando"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 disabled:bg-emerald-800 disabled:opacity-50 text-white font-black text-xs uppercase tracking-widest rounded-xl transition-all shadow-md active:scale-95 flex items-center gap-1.5 cursor-pointer"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        {{ sug.conciliando ? 'Conciliando...' : 'Aplicar Conciliación' }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Swal from '@/Utils/Swal'
import ImportarCsvModal from '@/Components/ConciliacionBancaria/ImportarCsvModal.vue'
import axios from 'axios'

defineOptions({ layout: AppLayout })

const props = defineProps({
  movimientos: { type: Object, required: true },
  resumen: { type: Object, default: () => ({ total: 0, depositos: 0, retiros: 0, monto_depositos: 0, monto_retiros: 0 }) },
  filtros: { type: Object, default: () => ({}) },
  bancos: { type: Array, default: () => [] },
  bancos_soportados: { type: Array, default: () => ['BBVA', 'BANAMEX', 'BANORTE', 'SANTANDER'] },
  cuentasCobrar: { type: Array, default: () => [] },
  cuentasPagar: { type: Array, default: () => [] },
})

const showImportModal = ref(false)
const selectedMov = ref(null)
const invoiceSearch = ref('')
const onlyExactMatch = ref(false)

// AI Assistant State
const showAiModal = ref(false)
const loadingAi = ref(false)
const sugerenciasAi = ref([])

const form = useForm({
  estado: props.filtros.estado || 'pendiente',
  tipo: props.filtros.tipo || '',
  banco: props.filtros.banco || '',
  fecha_desde: props.filtros.fecha_desde || '',
  fecha_hasta: props.filtros.fecha_hasta || '',
})

const diferencia = computed(() => props.resumen.monto_depositos - props.resumen.monto_retiros)

// Seleccionar movimiento y buscar coincidencias automáticas
const seleccionarMov = (mov) => {
  selectedMov.value = mov
}

// Saber si hay coincidencia exacta de montos
const isExactMatch = (montoFactura) => {
  if (!selectedMov.value) return false
  return Math.abs(Math.abs(selectedMov.value.monto) - parseFloat(montoFactura)) < 0.05
}

// Filtrado inteligente de facturas en el frontend
const filteredInvoices = computed(() => {
  if (!selectedMov.value) return []
  
  // 1. Filtrar por tipo (depósito -> CxC, retiro -> CxP)
  let rawList = selectedMov.value.tipo === 'deposito' ? props.cuentasCobrar : props.cuentasPagar
  
  // 2. Filtrar por coincidencia exacta de monto si aplica
  if (onlyExactMatch.value) {
    rawList = rawList.filter(inv => isExactMatch(inv.monto_pendiente))
  }
  
  // 3. Filtrar por término de búsqueda (cliente, proveedor, folio, rfc)
  if (invoiceSearch.value.trim() !== '') {
    const q = invoiceSearch.value.toLowerCase().trim()
    rawList = rawList.filter(inv => {
      const nombre = selectedMov.value.tipo === 'deposito' 
        ? (inv.cliente?.nombre_razon_social || '').toLowerCase()
        : (inv.proveedor?.nombre_razon_social || '').toLowerCase()
      const rfc = selectedMov.value.tipo === 'deposito'
        ? (inv.cliente?.rfc || '').toLowerCase()
        : (inv.proveedor?.rfc || '').toLowerCase()
      const ref = (inv.referencia || '').toLowerCase()
      const monto = String(inv.monto_pendiente)
      
      return nombre.includes(q) || rfc.includes(q) || ref.includes(q) || monto.includes(q)
    })
  }

  // 4. Ordenar para que los matches exactos aparezcan primero
  return [...rawList].sort((a, b) => {
    const aMatch = isExactMatch(a.monto_pendiente) ? 1 : 0
    const bMatch = isExactMatch(b.monto_pendiente) ? 1 : 0
    return bMatch - aMatch
  })
})

const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFecha = (fecha) => {
  if (!fecha) return '-'
  return new Date(fecha).toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const aplicarFiltros = () => {
  router.get(route('conciliacion.index'), {
    estado: form.estado,
    tipo: form.tipo || undefined,
    banco: form.banco || undefined,
    fecha_desde: form.fecha_desde || undefined,
    fecha_hasta: form.fecha_hasta || undefined,
  }, { preserveState: true })
}

const ignorar = async (id) => {
  const { isConfirmed } = await Swal.fire({
    title: '¿Ignorar movimiento?',
    text: 'Este movimiento bancario ya no se mostrará para conciliar.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, ignorar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#3b82f6',
    cancelButtonColor: '#1e293b'
  })
  if (isConfirmed) {
    router.post(route('conciliacion.ignorar', id), {}, {
      onSuccess: () => {
        if (selectedMov.value?.id === id) selectedMov.value = null
        Swal.fire('¡Ignorado!', 'El movimiento ha sido marcado como ignorado.', 'success')
      }
    })
  }
}

const revertir = async (id) => {
  const { isConfirmed } = await Swal.fire({
    title: '¿Revertir conciliación?',
    text: 'Esta acción romperá el vínculo de conciliación y la póliza asociada.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, revertir',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#f97316',
    cancelButtonColor: '#1e293b'
  })
  if (isConfirmed) {
    router.post(route('conciliacion.revertir', id), {}, {
      onSuccess: () => {
        if (selectedMov.value?.id === id) selectedMov.value = null
        Swal.fire('¡Revertida!', 'La conciliación ha sido revertida.', 'success')
      }
    })
  }
}

const eliminar = async (id) => {
  const { isConfirmed } = await Swal.fire({
    title: '¿Eliminar transacción?',
    text: 'Eliminará permanentemente este registro del estado de cuenta bancario.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#1e293b'
  })
  if (isConfirmed) {
    router.delete(route('conciliacion.destroy', id), {
      onSuccess: () => {
        if (selectedMov.value?.id === id) selectedMov.value = null
        Swal.fire('¡Eliminado!', 'El movimiento bancario fue eliminado.', 'success')
      }
    })
  }
}

// Conciliación de montos exactos de forma masiva
const conciliacionAutomatica = async () => {
  const { isConfirmed } = await Swal.fire({
    title: '¿Ejecutar Match Automático?',
    text: 'El sistema conciliará instantáneamente todos los movimientos que tengan un MATCH ÚNICO y EXACTO de monto con sus facturas.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ejecutar Match',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#1e293b'
  })
  if (isConfirmed) {
    router.post(route('conciliacion.automatica'), {}, {
      onSuccess: () => {
        selectedMov.value = null
      }
    })
  }
}

// Conciliación Manual Side-by-Side
const aplicarReconciliacionManual = (inv) => {
  if (!selectedMov.value) return

  router.post(route('conciliacion.conciliar'), {
    movimiento_id: selectedMov.value.id,
    tipo_cuenta: selectedMov.value.tipo === 'deposito' ? 'CXC' : 'CXP',
    cuenta_id: inv.id
  }, {
    preserveScroll: true,
    onSuccess: () => {
      selectedMov.value = null
      Swal.fire({
        title: '¡Conciliado!',
        text: 'El movimiento y la cuenta se han vinculado exitosamente.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
    },
    onError: (errors) => {
      Swal.fire('Error', errors.error || 'Ocurrió un error al aplicar la conciliación.', 'error')
    }
  })
}

// AI Assistant
const abrirAsistenteAi = async () => {
  showAiModal.value = true
  loadingAi.value = true
  sugerenciasAi.value = []
  
  try {
    const res = await axios.get(route('conciliacion.ai-analisis'))
    if (res.data && res.data.success) {
      sugerenciasAi.value = res.data.matches.map(s => ({
        ...s,
        conciliando: false
      }))
    } else {
      Swal.fire('Error', res.data.message || 'No se pudieron obtener sugerencias.', 'error')
    }
  } catch (error) {
    console.error(error)
    Swal.fire('Error', error.response?.data?.message || 'Error de red o de servidor al obtener sugerencias con IA.', 'error')
  } finally {
    loadingAi.value = false
  }
}

const conciliarSugerencia = (sug) => {
  sug.conciliando = true
  router.post(route('conciliacion.conciliar'), {
    movimiento_id: sug.movimiento.id,
    tipo_cuenta: sug.tipo_cuenta,
    cuenta_id: sug.cuenta_id
  }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      sug.conciliando = false
      sugerenciasAi.value = sugerenciasAi.value.filter(item => item.movimiento.id !== sug.movimiento.id)
      if (selectedMov.value?.id === sug.movimiento.id) selectedMov.value = null
      Swal.fire({
        title: '¡Conciliado por IA!',
        text: 'La conciliación inteligente fue aplicada exitosamente.',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false
      })
    },
    onError: (errors) => {
      sug.conciliando = false
      Swal.fire('Error', errors.error || 'Ocurrió un error al conciliar.', 'error')
    }
  })
}

// Auto-seleccionar primer movimiento al montar si existe
onMounted(() => {
  if (props.movimientos.data && props.movimientos.data.length > 0) {
    // Seleccionar el primer movimiento pendiente si existe
    const primero = props.movimientos.data.find(m => m.estado === 'pendiente')
    if (primero) {
      selectedMov.value = primero
    }
  }
})
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
