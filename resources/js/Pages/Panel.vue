<template>
  <div class="relative overflow-hidden transition-colors duration-500">

    <Head title="Panel" />

    <div class="w-full px-4 lg:px-8 py-8 relative z-10">
      <!-- Dashboard Header - Hero Section -->
      <div class="mb-10 relative">

        
        <div 
          class="relative rounded-[2.5rem] p-10 shadow-2xl overflow-hidden transition-all duration-700 border border-white/10"
          :style="{ 
            background: isDark 
              ? 'linear-gradient(135deg, #0f172a 0%, #020617 100%)' 
              : `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` 
          }"
        >
          <!-- Shiny overlay -->
          <div class="absolute inset-0 bg-gradient-to-tr from-white/5 via-transparent to-white/10 pointer-events-none"></div>
          
          <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <!-- Left: Greeting & Date -->
            <div>
              <div class="flex items-center gap-6 mb-3">
                <div class="w-16 h-16 rounded-[1.5rem] bg-white/20 backdrop-blur-xl flex items-center justify-center shadow-2xl border border-white/30 transform transition-transform hover:rotate-6 duration-500">
                  <FontAwesomeIcon :icon="['fas', 'chart-line']" class="h-10 w-10 text-white drop-shadow-md" />
                </div>
                <div>
                  <h1 class="text-4xl lg:text-5xl font-black text-white drop-shadow-2xl transition-all tracking-tight">
                    {{ saludo }}, {{ nombreUsuario }}
                  </h1>
                  <p class="text-white/80 dark:text-slate-200 text-xl mt-2 flex items-center gap-2 font-medium tracking-tight">
                    <FontAwesomeIcon :icon="['fas', 'calendar']" class="opacity-60 text-sm" />
                    {{ fechaHoy }}
                  </p>
                  <p class="mt-4 text-white/90 text-sm leading-relaxed max-w-2xl">
                    <span class="font-bold text-white">Resumen</span>
                    <span class="text-white/50 mx-1.5">·</span>
                    <template v-if="citasHoyCount > 0 && $can('view citas')">
                      <a href="#panel-citas-hoy" class="underline decoration-white/40 hover:decoration-white underline-offset-2">{{ n(citasHoyCount) }} citas hoy</a>
                    </template>
                    <template v-if="tareasPendientesSafe.total > 0 && $can('view bitacora')">
                      <span v-if="citasHoyCount > 0 && $can('view citas')" class="text-white/50 mx-1">·</span>
                      <a href="#panel-tareas" class="underline decoration-white/40 hover:decoration-white underline-offset-2">{{ n(tareasPendientesSafe.total) }} tareas</a>
                    </template>
                    <template v-if="proveedoresPedidosPendientesCount > 0 && $can('view ordenes_compra')">
                      <span v-if="(citasHoyCount > 0 && $can('view citas')) || (tareasPendientesSafe.total > 0 && $can('view bitacora'))" class="text-white/50 mx-1">·</span>
                      <a href="#panel-alertas-compras" class="underline decoration-white/40 hover:decoration-white underline-offset-2">{{ n(proveedoresPedidosPendientesCount) }} OC pendientes</a>
                    </template>
                    <template v-if="hayAlertasVencimientos && ( $can('view cuentas_por_pagar') || $can('view cuentas_por_cobrar') || $can('view prestamos') )">
                      <span v-if="citasHoyCount > 0 || tareasPendientesSafe.total > 0 || proveedoresPedidosPendientesCount > 0" class="text-white/50 mx-1">·</span>
                      <a href="#panel-finanzas" class="underline decoration-white/40 hover:decoration-white underline-offset-2">Finanzas</a>
                    </template>
                    <template v-if="$can('view ventas') || $can('view clientes') || $can('view ordenes_compra')">
                      <span class="text-white/50 mx-1">·</span>
                      <a href="#panel-analisis" class="underline decoration-white/40 hover:decoration-white underline-offset-2">Análisis</a>
                    </template>
                  </p>
                </div>
              </div>
            </div>
            
            <!-- Right: Quick Actions -->
            <div class="flex flex-wrap gap-4">
              <PanLink
                v-if="$can('create ventas')"
                :href="route('ventas.create')"
                class="group inline-flex items-center gap-2 px-8 py-4 bg-white dark:bg-brand-500 text-brand-600 dark:text-white font-black uppercase text-xs tracking-wide sm:tracking-wide rounded-2xl shadow-xl hover:shadow-brand-500/20 hover:shadow-xl hover:shadow-xl transition-all duration-200 shine-effect"
              >
                <FontAwesomeIcon :icon="['fas', 'plus']" class="group-hover:rotate-90 transition-transform duration-500" />
                Nueva Venta
              </PanLink>
              <PanLink
                v-if="$can('create citas')"
                :href="route('citas.create')"
                class="group inline-flex items-center gap-2 px-8 py-4 bg-white/10 dark:bg-slate-800/50 backdrop-blur-md text-white font-black uppercase text-xs tracking-wide sm:tracking-wide rounded-2xl shadow-xl border border-white/20 dark:border-slate-700 hover:bg-white/20 hover:shadow-xl hover:shadow-xl transition-all duration-200"
              >
                <FontAwesomeIcon :icon="['fas', 'calendar-plus']" />
                Nueva Cita
              </PanLink>
            </div>
          </div>
        </div>
      </div>

    <!-- Monitor RESICO 3.5M - Premium Widget -->
    <div v-if="resicoStats && isSuperAdmin" class="mb-10 animate-slide-up">
      <div class="relative bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-xl border border-slate-100 dark:border-slate-700 overflow-hidden">
        <!-- Background accents -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-500/5 rounded-full -mr-20 -mt-20 blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-blue-500/5 rounded-full -ml-20 -mb-20 blur-3xl pointer-events-none"></div>

        <div class="relative flex flex-col md:flex-row items-center gap-8">
          <!-- Left Info -->
          <div class="flex-shrink-0 text-center md:text-left">
            <h3 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center justify-center md:justify-start gap-2">
              <span class="w-2 h-8 bg-brand-500 rounded-full"></span>
              Monitor RESICO {{ resicoStats.anio }}
            </h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 font-medium">Límite anual de $3,500,000 MXN</p>
            <div class="mt-4">
              <span class="text-4xl font-black text-slate-800 dark:text-white transition-all tabular-nums">
                ${{ n(resicoStats.ingresos_anuales) }}
              </span>
              <span class="text-slate-400 font-bold ml-2">cobrados</span>
            </div>
          </div>

          <!-- Progress Bar / Thermometer -->
          <div class="flex-1 w-full">
            <div class="flex justify-between items-end mb-3">
              <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Progreso Fiscal</span>
              <span class="text-lg font-black transition-all" 
                :class="{
                  'text-emerald-500': resicoStats.porcentaje < 70,
                  'text-brand-500': resicoStats.porcentaje >= 70 && resicoStats.porcentaje < 90,
                  'text-rose-600': resicoStats.porcentaje >= 90
                }">
                {{ resicoStats.porcentaje }}%
              </span>
            </div>
            <div class="h-6 w-full bg-slate-100 dark:bg-slate-900 rounded-full p-1.5 shadow-inner border border-slate-200 dark:border-slate-700">
              <div 
                class="h-full rounded-full transition-all duration-1000 ease-out relative"
                :style="{ width: Math.min(resicoStats.porcentaje, 100) + '%' }"
                :class="{
                  'bg-gradient-to-r from-emerald-400 to-emerald-600 shadow-[0_0_15px_rgba(16,185,129,0.3)]': resicoStats.porcentaje < 70,
                  'bg-gradient-to-r from-brand-400 to-brand-600 shadow-[0_0_15px_rgba(245,158,11,0.3)]': resicoStats.porcentaje >= 70 && resicoStats.porcentaje < 90,
                  'bg-gradient-to-r from-rose-500 to-rose-700 shadow-[0_0_15px_rgba(225,29,72,0.3)]': resicoStats.porcentaje >= 90
                }"
              >
                <!-- Glowing tip -->
                <div class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 bg-white rounded-full blur-sm opacity-50"></div>
              </div>
            </div>
            <div class="flex justify-between mt-3 text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
              <span>Enero</span>
              <span>$1.75M (50%)</span>
              <span>Diciembre</span>
            </div>
          </div>

          <!-- Right: Advice -->
          <div class="flex-shrink-0 w-full md:w-56 p-5 rounded-3xl border transition-all"
            :class="{
              'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-100 dark:border-emerald-800/50 text-emerald-800 dark:text-emerald-300': resicoStats.porcentaje < 70,
              'bg-brand-50 dark:bg-brand-900/20 border-brand-100 dark:border-brand-800/50 text-brand-800 dark:text-amber-300': resicoStats.porcentaje >= 70 && resicoStats.porcentaje < 90,
              'bg-rose-50 dark:bg-rose-900/20 border-rose-100 dark:border-rose-800/50 text-rose-800 dark:text-rose-300': resicoStats.porcentaje >= 90
            }">
            <div class="flex items-center gap-3 mb-2 font-black text-xs uppercase italic tracking-tighter">
              <FontAwesomeIcon :icon="['fas', resicoStats.porcentaje >= 90 ? 'skull-crossbones' : 'shield-halved']" />
              Estado de Régimen
            </div>
            <p class="text-[11px] leading-tight font-medium">
              <template v-if="resicoStats.porcentaje < 70">
                Todo bajo control. Tienes un margen de <strong>${{ n(3500000 - resicoStats.ingresos_anuales) }}</strong> para facturar.
              </template>
              <template v-else-if="resicoStats.porcentaje < 90">
                Atención: Te acercas al límite. Considera planificar tus cobros de fin de año.
              </template>
              <template v-else>
                <strong>¡ALERTA CRÍTICA!</strong> Estás a punto de salir de RESICO. Consulta a tu contador urgente.
              </template>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Alerta de Cierre Fiscal (Primeros 10 días) -->
    <div v-if="fiscalClosingAlert && fiscalClosingAlert.active" class="mb-10 animate-slide-up">
      <div 
        class="relative overflow-hidden rounded-[2.5rem] p-8 shadow-2xl border-2 transition-all duration-500"
        :class="(fiscalClosingAlert.pending_emitidos_count > 0 || fiscalClosingAlert.pending_recibidos_count > 0)
          ? 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-900/50' 
          : 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-900/50'"
      >
        <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
          <div class="flex items-center gap-6">
            <div 
              class="w-16 h-16 rounded-[1.5rem] flex items-center justify-center shadow-lg transform -rotate-3 transition-transform hover:rotate-0"
              :class="(fiscalClosingAlert.pending_emitidos_count > 0 || fiscalClosingAlert.pending_recibidos_count > 0) ? 'bg-rose-500' : 'bg-emerald-500'"
            >
              <FontAwesomeIcon :icon="['fas', 'file-invoice-dollar']" class="h-8 w-8 text-white" />
            </div>
            <div>
              <h3 
                class="text-2xl font-black tracking-tight"
                :class="(fiscalClosingAlert.pending_emitidos_count > 0 || fiscalClosingAlert.pending_recibidos_count > 0) ? 'text-rose-900 dark:text-rose-100' : 'text-emerald-900 dark:text-emerald-100'"
              >
                Cierre Mensual: {{ fiscalClosingAlert.month_name }}
              </h3>
              <div 
                class="text-lg font-medium space-y-1"
                :class="(fiscalClosingAlert.pending_emitidos_count > 0 || fiscalClosingAlert.pending_recibidos_count > 0) ? 'text-rose-800 dark:text-rose-200' : 'text-emerald-800 dark:text-emerald-200'"
              >
                <p v-if="fiscalClosingAlert.pending_emitidos_count > 0">
                  <FontAwesomeIcon :icon="['fas', 'arrow-up']" class="mr-2 opacity-50" />
                  Tienes <span class="font-black underline">{{ fiscalClosingAlert.pending_emitidos_count }} facturas emitidas</span> sin REP (Tus ingresos).
                </p>
                <p v-if="fiscalClosingAlert.pending_recibidos_count > 0">
                  <FontAwesomeIcon :icon="['fas', 'arrow-down']" class="mr-2 opacity-50" />
                  Te deben <span class="font-black underline">{{ fiscalClosingAlert.pending_recibidos_count }} complementos de pago</span> (Tu IVA acreditable).
                </p>
                <p v-if="fiscalClosingAlert.pending_emitidos_count === 0 && fiscalClosingAlert.pending_recibidos_count === 0">
                  ¡Excelente! Toda tu facturación PPD del mes pasado está al día.
                </p>
              </div>
            </div>
          </div>

          <div class="flex flex-col items-end text-right">
            <div 
              class="px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs mb-3 shadow-sm border"
              :class="(fiscalClosingAlert.pending_emitidos_count > 0 || fiscalClosingAlert.pending_recibidos_count > 0)
                ? 'bg-white/80 dark:bg-rose-900/40 text-rose-600 dark:text-rose-300 border-rose-200 dark:border-rose-800' 
                : 'bg-white/80 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'"
            >
              Límite SAT: Día {{ fiscalClosingAlert.deadline_day }}
            </div>
            <PanLink 
              v-if="fiscalClosingAlert.pending_emitidos_count > 0 || fiscalClosingAlert.pending_recibidos_count > 0"
              :href="route('contabilidad.saldos-xml')" 
              class="inline-flex items-center gap-2 px-8 py-3 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-2xl shadow-xl shadow-rose-600/20 transition-all active:scale-95 shine-effect"
            >
              Revisar y Conciliar
              <FontAwesomeIcon :icon="['fas', 'arrow-right-long']" />
            </PanLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Alertas de Cancelación Detectadas por el Monitor -->
    <div v-if="cancellationAlerts && cancellationAlerts.count > 0" class="mb-10 animate-slide-up">
      <div class="relative bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 shadow-2xl border-2 border-rose-500 overflow-hidden">
        <!-- Pulse background -->
        <div class="absolute inset-0 bg-rose-500/5 animate-pulse pointer-events-none"></div>
        
        <div class="relative flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
          <div class="flex items-center gap-6">
            <div class="w-16 h-16 rounded-[1.5rem] bg-rose-600 flex items-center justify-center shadow-lg transform rotate-3">
              <FontAwesomeIcon :icon="['fas', 'skull-crossbones']" class="h-8 w-8 text-white" />
            </div>
            <div>
              <h3 class="text-2xl font-black text-rose-900 dark:text-rose-100 tracking-tight">
                ¡Alerta de Cancelación SAT!
              </h3>
              <p class="text-rose-800 dark:text-rose-200 font-medium">
                Se detectaron <span class="font-black underline">{{ cancellationAlerts.count }} facturas</span> canceladas recientemente en el portal del SAT.
              </p>
            </div>
          </div>
           <button 
            type="button"
            @click="selectedCancellation = (cancellationAlerts.detalles && cancellationAlerts.detalles[0]) || null; showCancelModal = true"
            class="px-8 py-3 bg-rose-600 hover:bg-rose-700 text-white font-black rounded-2xl shadow-xl transition-all"
          >
            Ver Detalles Críticos
          </button>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="cancelacion in cancellationAlerts.detalles.slice(0, 3)" 
            :key="cancelacion.uuid"
            @click="selectedCancellation = cancelacion; showCancelModal = true"
            class="p-4 rounded-2xl bg-rose-50/50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800 flex justify-between items-center cursor-pointer hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors"
          >
            <div class="min-w-0">
              <div class="text-[10px] font-black uppercase tracking-widest text-rose-400 mb-1">
                {{ cancelacion.direccion === 'emitido' ? 'Factura Cliente' : 'Factura Proveedor' }}
              </div>
              <div class="font-bold text-slate-900 dark:text-white truncate">{{ cancelacion.nombre }}</div>
              <div class="text-xs text-rose-600 dark:text-rose-400 font-medium">${{ n(cancelacion.total) }}</div>
            </div>
            <div class="text-right shrink-0">
              <div class="text-[10px] text-slate-400 uppercase font-black">Detectado</div>
              <div class="text-[10px] text-slate-500 font-bold">{{ cancelacion.fecha_deteccion }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-6 mb-12 stagger-animation">
      <!-- Clientes -->
      <PanLink
        v-if="$can('view clientes')"
        :href="clientesHref"
        class="group relative bg-white dark:bg-slate-800 backdrop-blur-xl p-8 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.2)] border border-slate-100 dark:border-slate-800 transition-all duration-500 hover:shadow-2xl hover:shadow-xl hover:shadow-xl overflow-hidden"
        aria-label="Ir a clientes"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/0 via-transparent to-blue-600/0 group-hover:from-blue-500/5 group-hover:to-blue-600/10 transition-all duration-500"></div>
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-400 to-blue-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-700"></div>
        <div class="relative flex flex-col items-center justify-center space-y-6 text-center">
          <div class="w-16 h-16 rounded-[1.2rem] bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-xl group-hover:shadow-sky-500/40 group-hover:scale-105 transition-all duration-500">
            <FontAwesomeIcon :icon="['fas', 'users']" class="h-8 w-8 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-blue-500 transition-colors tracking-tighter">
              {{ n(clientesCount) }}
            </h2>
            <p class="text-[10px] font-black uppercase tracking-wide sm:tracking-[0.2em] text-slate-400 dark:text-slate-500">Clientes</p>
          </div>
          <div class="text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/30 px-4 py-1.5 rounded-full uppercase tracking-wide transition-colors">
            +{{ n(clientesNuevosCount) }} este mes
          </div>
        </div>
      </PanLink>

      <!-- Productos -->
      <PanLink
        v-if="$can('view productos')"
        :href="productosHref"
        class="group relative bg-white dark:bg-slate-800 backdrop-blur-xl p-8 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.2)] border border-slate-100 dark:border-slate-800 transition-all duration-500 hover:shadow-2xl hover:shadow-xl hover:shadow-xl overflow-hidden"
        aria-label="Ir a productos"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/0 via-transparent to-emerald-600/0 group-hover:from-emerald-500/5 group-hover:to-emerald-600/10 transition-all duration-500"></div>
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-400 to-emerald-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-700"></div>
        <div class="relative flex flex-col items-center justify-center space-y-6 text-center">
          <div class="w-16 h-16 rounded-[1.2rem] bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-xl group-hover:shadow-emerald-500/40 group-hover:scale-105 transition-all duration-500">
            <FontAwesomeIcon :icon="['fas', 'box']" class="h-8 w-8 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-emerald-500 transition-colors tracking-tighter">
              {{ n(productosCount) }}
            </h2>
            <p class="text-[10px] font-black uppercase tracking-wide sm:tracking-[0.2em] text-slate-400 dark:text-slate-500">Productos</p>
          </div>
          <div class="text-[10px] font-bold px-4 py-1.5 rounded-full uppercase tracking-wide transition-colors" :class="productosBajoStockCount > 0 ? 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/30' : 'text-emerald-600 dark:text-slate-400 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/30'">
            {{ productosBajoStockCount > 0 ? `⚠️ ${n(productosBajoStockCount)} Alerta` : 'Stock al día' }}
          </div>
        </div>
      </PanLink>

      <!-- Proveedores -->
      <PanLink
        v-if="$can('view proveedores')"
        :href="proveedoresHref"
        class="group relative bg-white dark:bg-slate-800 backdrop-blur-xl p-8 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.2)] border border-slate-100 dark:border-slate-800 transition-all duration-500 hover:shadow-2xl hover:shadow-xl hover:shadow-xl overflow-hidden"
        aria-label="Ir a proveedores"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/0 via-transparent to-purple-600/0 group-hover:from-purple-500/5 group-hover:to-purple-600/10 transition-all duration-500"></div>
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-purple-400 to-purple-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-700"></div>
        <div class="relative flex flex-col items-center justify-center space-y-6 text-center">
          <div class="w-16 h-16 rounded-[1.2rem] bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-xl group-hover:shadow-purple-500/40 group-hover:scale-105 transition-all duration-500">
            <FontAwesomeIcon :icon="['fas', 'truck']" class="h-8 w-8 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-purple-500 transition-colors tracking-tighter">
              {{ n(proveedoresCount) }}
            </h2>
            <p class="text-[10px] font-black uppercase tracking-wide sm:tracking-[0.2em] text-slate-400 dark:text-slate-500">Proveedores</p>
          </div>
          <div class="text-[10px] font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 px-4 py-1.5 rounded-full uppercase tracking-wide transition-colors">
            {{ n(proveedoresPedidosPendientesCount) }} Pendientes
          </div>
        </div>
      </PanLink>

      <PanLink
        v-if="$can('view citas')"
        :href="citasHref"
        class="group relative bg-white dark:bg-slate-800 backdrop-blur-xl p-8 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.2)] border border-slate-100 dark:border-slate-800 transition-all duration-500 hover:shadow-2xl hover:shadow-xl hover:shadow-xl overflow-hidden"
        aria-label="Ir a citas"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-brand-500/0 via-transparent to-brand-600/0 group-hover:from-brand-500/5 group-hover:to-brand-600/10 transition-all duration-500"></div>
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-brand-400 to-brand-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-700"></div>
        <div class="relative flex flex-col items-center justify-center space-y-6 text-center">
          <div class="w-16 h-16 rounded-[1.2rem] bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center shadow-xl group-hover:shadow-brand-500/40 group-hover:scale-105 transition-all duration-500">
            <FontAwesomeIcon :icon="['fas', 'calendar-alt']" class="h-8 w-8 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-brand-500 transition-colors tracking-tighter">
              {{ n(citasCount) }}
            </h2>
            <p class="text-[10px] font-black uppercase tracking-wide sm:tracking-[0.2em] text-slate-400 dark:text-slate-500">Citas</p>
          </div>
          <div class="text-[10px] font-bold px-4 py-1.5 rounded-full uppercase tracking-wide transition-colors" :class="citasHoyCount > 0 ? 'text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/30' : 'text-slate-400 dark:text-slate-500 bg-[var(--ui-surface)] dark:bg-slate-800/50'">
            {{ citasHoyCount > 0 ? `📅 ${n(citasHoyCount)} HOY` : 'Sin Citas' }}
          </div>
        </div>
      </PanLink>

      <!-- Mantenimientos -->
      <PanLink
        v-if="$can('view mantenimientos')"
        :href="mantenimientosHref"
        class="group relative bg-white dark:bg-slate-800 backdrop-blur-xl p-8 rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.04)] dark:shadow-[0_10px_40px_rgba(0,0,0,0.2)] border border-slate-100 dark:border-slate-800 transition-all duration-500 hover:shadow-2xl hover:shadow-xl hover:shadow-xl overflow-hidden"
        aria-label="Ir a mantenimientos"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-rose-500/0 via-transparent to-rose-600/0 group-hover:from-rose-500/5 group-hover:to-rose-600/10 transition-all duration-500"></div>
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-rose-400 to-rose-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-700"></div>
        <div class="relative flex flex-col items-center justify-center space-y-6 text-center">
          <div class="w-16 h-16 rounded-[1.2rem] bg-gradient-to-br from-rose-400 to-rose-600 flex items-center justify-center shadow-xl group-hover:shadow-rose-500/40 group-hover:scale-105 transition-all duration-500">
            <FontAwesomeIcon :icon="['fas', 'wrench']" class="h-8 w-8 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white group-hover:text-rose-500 transition-colors tracking-tighter">
              {{ n(mantenimientosCount) }}
            </h2>
            <p class="text-[10px] font-black uppercase tracking-wide sm:tracking-[0.2em] text-slate-400 dark:text-slate-500">Mantenimientos</p>
          </div>
          <div class="text-[10px] font-bold px-4 py-1.5 rounded-full uppercase tracking-wide sm:tracking-wide transition-colors" :class="{
            'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 dark:bg-rose-900/30 font-medium animate-pulse': mantenimientosVencidosCount > 0,
            'text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-900/20 dark:bg-brand-900/30': mantenimientosCriticosCount > 0 && mantenimientosVencidosCount === 0,
            'text-emerald-600 dark:text-slate-400 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/30': mantenimientosVencidosCount === 0 && mantenimientosCriticosCount === 0
          }">
            <span v-if="mantenimientosVencidosCount > 0">⚠️ {{ n(mantenimientosVencidosCount) }} Vencidos</span>
            <span v-else-if="mantenimientosCriticosCount > 0">⚡ {{ n(mantenimientosCriticosCount) }} Críticos</span>
            <span v-else>✅ Al día</span>
          </div>
        </div>
      </PanLink>
    </div>

    <!-- Estado tranquilo (operaciones) -->
    <div
      v-if="panelOperativoTranquilo"
      class="mb-10 flex items-start gap-3 p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20/90 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 text-emerald-900 dark:text-emerald-100/90"
    >
      <FontAwesomeIcon :icon="['fas', 'circle-check']" class="h-6 w-6 text-emerald-600 dark:text-slate-400 shrink-0 mt-0.5" />
      <div>
        <p class="font-bold text-emerald-900 dark:text-emerald-100">Operaciones al día</p>
        <p class="text-sm text-emerald-800 dark:text-emerald-200/90 dark:text-emerald-200/80 mt-1">No hay citas urgentes para hoy, tareas pendientes, alertas de stock ni órdenes de compra críticas en este momento.</p>
      </div>
    </div>

    <!-- Citas activas del día de hoy (prioridad) -->
    <div
      v-if="citasHoyDetallesSafe.length > 0 && $can('view citas')"
      id="panel-citas-hoy"
      class="mt-2 scroll-mt-24 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-xl border-l-8 border-blue-500 transition-colors"
    >
      <div class="flex items-center mb-4 transition-colors">
        <FontAwesomeIcon :icon="['fas', 'calendar-alt']" class="h-8 w-8 text-blue-600 mr-3" />
        <h3 class="text-2xl font-black text-slate-900 dark:text-white transition-colors">Citas activas del día de hoy</h3>
      </div>
      <p class="text-base text-slate-700 dark:text-slate-200 mb-4 transition-colors">
        Tienes <strong>{{ n(citasHoyDetallesSafe.length) }} cita(s) activa(s)</strong> programadas para hoy (en proceso y pendientes).
      </p>
      <ul class="space-y-2 transition-colors">
        <li
          v-for="(cita, citaIdx) in citasHoyDetallesSafe"
          :key="cita.id != null ? `cita-${cita.id}` : `cita-hoy-${citaIdx}`"
          class="flex items-center justify-between text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-700/50 p-4 rounded-xl shadow-sm border-l-4 transition-colors"
          :class="cita.estado === 'en_proceso' ? 'border-l-blue-500' : 'border-l-brand-500'"
        >
          <div class="flex flex-col text-left flex-1 transition-colors">
            <div class="font-semibold text-lg text-slate-900 dark:text-white mb-1 transition-colors uppercase">
              {{ (cita.titulo ?? 'Sin título').replace(/_/g, ' ') }}
            </div>
            <div class="text-sm text-slate-700 dark:text-slate-400 transition-colors">Cliente: {{ cita.cliente ?? 'N/D' }}</div>
            <div class="text-sm text-slate-700 dark:text-slate-400 transition-colors">Técnico: {{ cita.tecnico ?? 'N/D' }}</div>
            <div class="mt-2 transition-colors">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium transition-colors"
                :class="{
                  'bg-blue-50 dark:bg-sky-900/20/30 text-sky-800 dark:text-sky-200 dark:text-blue-300': cita.estado === 'en_proceso',
                  'bg-rose-50 dark:bg-rose-900/20/30 text-rose-800 dark:text-rose-200 dark:text-rose-300': cita.es_vencida && cita.estado !== 'en_proceso',
                  'bg-brand-50 dark:bg-brand-900/20/30 text-brand-800 dark:text-brand-200 dark:text-amber-300': !cita.es_vencida && cita.estado !== 'en_proceso'
                }"
              >
                <FontAwesomeIcon
                  :icon="cita.estado === 'en_proceso' ? ['fas', 'cog'] : (cita.es_vencida ? ['fas', 'exclamation-circle'] : ['fas', 'clock'])"
                  class="w-3 h-3 mr-1"
                />
                {{ cita.estado_label ?? cita.estado }}
              </span>
            </div>
          </div>
          <div class="flex flex-col items-end gap-2 ml-4">
            <div class="text-right">
              <div class="text-base font-medium text-blue-600 mb-1">
                {{ cita.hora ?? '—' }}
              </div>
              <div 
                class="text-xs transition-colors"
                :class="cita.es_vencida ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-400 dark:text-slate-500'"
              >
                {{ cita.es_hoy ? 'Hoy' : (cita.es_vencida ? 'Atrasada' : 'Programada') }}
              </div>
            </div>
            <button
              v-if="cita.id"
              @click="abrirDetallesCita(cita.id)"
              class="px-3 py-1.5 bg-brand-500 text-white text-[10px] font-black uppercase tracking-wider rounded-2xl shadow-xl shadow-sky-500/20 hover:bg-blue-600 hover:shadow-xl hover:shadow-xl.5 transition-all duration-200 flex items-center whitespace-nowrap"
              :disabled="cargandoCita === cita.id"
            >
              <FontAwesomeIcon v-if="cargandoCita !== cita.id" :icon="['fas', 'info-circle']" class="mr-1.5" />
              <FontAwesomeIcon v-else :icon="['fas', 'spinner']" spin class="mr-1.5" />
              Detalles de esta cita
            </button>
          </div>
        </li>
      </ul>
      <PanLink
        :href="citasHref"
        class="mt-6 inline-block px-5 py-2 bg-brand-500 text-white font-semibold rounded-2xl shadow-xl hover:bg-blue-600 transition-colors"
        aria-label="Ver todas las citas"
      >
        Ver todas las citas
        <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="ml-2" />
      </PanLink>

      <!-- Modal de Detalles de Cita -->
      <CitaModal 
        v-if="citaSeleccionada"
        :show="showCitaModal" 
        :cita="citaSeleccionada" 
        @close="showCitaModal = false" 
      />

      <!-- Modal de CFDI Cancelado por SAT -->
      <div v-if="showCancelModal && selectedCancellation" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" @click.self="showCancelModal = false">
        <div class="w-full max-w-lg bg-slate-900 border border-white/10 rounded-[2.5rem] shadow-2xl overflow-hidden">
          <div class="p-6 border-b border-white/5 flex items-center justify-between">
            <h3 class="text-lg font-black text-white flex items-center gap-2">
              <span class="text-rose-400">⚠️</span> CFDI Cancelado en SAT
            </h3>
            <button @click="showCancelModal = false" class="text-slate-400 hover:text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="bg-slate-800/50 p-3 rounded-xl"><p class="text-[10px] uppercase tracking-wider text-slate-400">Tipo</p><p class="font-black text-white">{{ selectedCancellation.direccion === 'emitido' ? 'Emitido (Cliente)' : 'Recibido (Proveedor)' }}</p></div>
              <div class="bg-slate-800/50 p-3 rounded-xl"><p class="text-[10px] uppercase tracking-wider text-slate-400">Total</p><p class="font-black text-white">\${{ parseFloat(selectedCancellation.total).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</p></div>
            </div>
            <div class="bg-slate-800/50 p-4 rounded-xl space-y-2">
              <div><span class="text-[10px] uppercase tracking-wider text-slate-400">Folio</span><p class="text-sm font-bold text-slate-200">{{ selectedCancellation.folio }}</p></div>
              <div><span class="text-[10px] uppercase tracking-wider text-slate-400">{{ selectedCancellation.direccion === 'emitido' ? 'Receptor' : 'Emisor' }}</span><p class="text-sm font-bold text-slate-200">{{ selectedCancellation.nombre }}</p></div>
              <div><span class="text-[10px] uppercase tracking-wider text-slate-400">Detectado</span><p class="text-sm text-slate-400">{{ selectedCancellation.fecha_deteccion }}</p></div>
            </div>
            <div class="bg-slate-800/50 p-4 rounded-xl">
              <p class="text-[10px] uppercase tracking-wider text-slate-400 mb-2">Póliza vinculada</p>
              <template v-if="selectedCancellation.tiene_poliza">
                <div class="flex items-center justify-between">
                  <p class="text-sm font-bold text-amber-400">⚠️ Póliza #{{ selectedCancellation.poliza_tipo?.substring(0,1).toUpperCase() }}{{ selectedCancellation.poliza_numero }}</p>
                  <span class="text-xs text-slate-400">\${{ parseFloat(selectedCancellation.poliza_total || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}) }}</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Tipo: {{ selectedCancellation.poliza_tipo?.toUpperCase() }}</p>
                <p class="text-xs text-rose-400 mt-2 font-bold">Debes anular esta póliza antes de eliminar el CFDI.</p>
              </template>
              <template v-else>
                <p class="text-sm text-emerald-400">✅ Sin póliza vinculada — puedes eliminar directamente.</p>
              </template>
            </div>
            <div class="flex gap-3 pt-2">
              <button v-if="selectedCancellation.tiene_poliza" @click="anularPoliza(selectedCancellation)" :disabled="processingCancel" class="flex-1 px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 disabled:opacity-50 font-black text-[10px] uppercase tracking-widest text-white transition-colors">
                {{ processingCancel ? 'Anulando...' : 'Anular Póliza' }}
              </button>
              <button @click="eliminarCfdiCancelado(selectedCancellation)" :disabled="processingCancel" class="flex-1 px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-50 font-black text-[10px] uppercase tracking-widest text-white transition-colors">
                {{ processingCancel ? 'Eliminando...' : 'Eliminar CFDI + XML' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Mis Tareas Pendientes -->
    <div
      v-if="tareasPendientesSafe.total > 0 && $can('view bitacora')"
      id="panel-tareas"
      class="mt-8 scroll-mt-24 bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-xl border-l-8 border-purple-500 transition-colors"
    >
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <FontAwesomeIcon :icon="['fas', 'tasks']" class="h-8 w-8 text-purple-600 mr-3" />
          <h3 class="text-2xl font-black text-slate-900 dark:text-white transition-colors">Mis Tareas Pendientes</h3>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="tareasPendientesSafe.en_proceso > 0" class="bg-blue-50 dark:bg-sky-900/20/30 text-sky-800 dark:text-sky-200 dark:text-blue-300 px-2 py-1 rounded-xl text-xs font-medium transition-colors">
            {{ tareasPendientesSafe.en_proceso }} en proceso
          </span>
          <span v-if="tareasPendientesSafe.pendientes > 0" class="bg-brand-50 dark:bg-brand-900/20/30 text-brand-800 dark:text-brand-200 dark:text-brand-300 px-2 py-1 rounded-xl text-xs font-medium transition-colors">
            {{ tareasPendientesSafe.pendientes }} pendientes
          </span>
        </div>
      </div>
      <p class="text-base text-slate-700 dark:text-slate-200 mb-4 transition-colors">
        Tienes <strong>{{ n(tareasPendientesSafe.total) }} tarea(s)</strong> asignadas que requieren tu atención.
      </p>
      <ul class="space-y-2 transition-colors">
        <li
          v-for="tarea in tareasPendientesSafe.tareas"
          :key="`tarea-${tarea.id}`"
          class="flex items-center justify-between text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-700/50 p-4 rounded-xl shadow-sm border-l-4 transition-colors"
          :class="{
            'border-l-blue-500': tarea.estado === 'en_proceso',
            'border-l-brand-500': tarea.estado === 'pendiente',
            'border-l-rose-500': tarea.vencida
          }"
        >
          <div class="flex flex-col text-left flex-1 transition-colors">
            <div class="font-semibold text-lg text-slate-900 dark:text-white mb-1 transition-colors">
              {{ tarea.titulo }}
            </div>
            <div v-if="tarea.descripcion" class="text-sm text-slate-500 dark:text-slate-400 mb-1 transition-colors">
              {{ tarea.descripcion }}
            </div>
            <div class="flex flex-wrap gap-2 text-xs text-slate-500 dark:text-slate-400 transition-colors">
              <span v-if="tarea.cliente">
                <FontAwesomeIcon :icon="['fas', 'user']" class="mr-1" />
                {{ tarea.cliente }}
              </span>
              <span>
                <FontAwesomeIcon :icon="['fas', 'tag']" class="mr-1" />
                {{ tarea.tipo }}
              </span>
              <span v-if="tarea.creador">
                <FontAwesomeIcon :icon="['fas', 'user-plus']" class="mr-1" />
                Asignado por: {{ tarea.creador }}
              </span>
            </div>
            <div class="mt-2 flex items-center gap-2 transition-colors">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium transition-colors"
                :class="tarea.estado === 'en_proceso' ? 'bg-blue-50 dark:bg-sky-900/20/30 text-sky-800 dark:text-sky-200 dark:text-blue-300' : 'bg-brand-50 dark:bg-brand-900/20/30 text-brand-800 dark:text-brand-200 dark:text-amber-300'"
              >
                {{ tarea.estado_label }}
              </span>
              <span
                v-if="tarea.vencida"
                class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium bg-rose-50 dark:bg-rose-900/20/30 text-rose-800 dark:text-rose-200 dark:text-rose-300 transition-colors"
              >
                <FontAwesomeIcon :icon="['fas', 'exclamation-triangle']" class="mr-1" />
                Vencida
              </span>
              <span class="text-xs text-slate-500">
                Fecha: {{ tarea.fecha }}
              </span>
            </div>
          </div>
          <div class="flex flex-col items-end gap-2 ml-4">
            <button
              v-if="$can('edit bitacora')"
              @click="completarTarea(tarea.id)"
              class="px-3 py-1.5 bg-brand-500 text-white text-sm font-medium rounded-xl hover:bg-emerald-600 transition-colors flex items-center"
              :disabled="completandoTarea === tarea.id"
            >
              <FontAwesomeIcon v-if="completandoTarea !== tarea.id" :icon="['fas', 'check']" class="mr-1" />
              <span v-if="completandoTarea === tarea.id">...</span>
              <span v-else>Completar</span>
            </button>
            <PanLink
              :href="`/bitacora/${tarea.id}`"
              class="text-sm text-purple-600 hover:text-purple-800 hover:underline"
            >
              Ver detalle
            </PanLink>
          </div>
        </li>
      </ul>
      <PanLink
        href="/mis-pendientes"
        class="mt-6 inline-block px-5 py-2 bg-purple-500 text-white font-semibold rounded-2xl shadow-xl hover:bg-purple-600 transition-colors"
        aria-label="Ver todas las tareas"
      >
        Ver todas las tareas
        <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="ml-2" />
      </PanLink>
    </div>

    <!-- Sección de Alertas -->
    <div id="panel-alertas-compras" class="mt-8 scroll-mt-24 grid grid-cols-1 md:grid-cols-3 gap-8">


      <!-- Alerta de Órdenes de Compra Pendientes -->
      <div
        v-if="proveedoresPedidosPendientesCount > 0 && $can('view ordenes_compra')"
        class="bg-white dark:bg-slate-800/80 backdrop-blur-xl p-8 rounded-[2rem] shadow-xl border-l-[10px] border-brand-500 flex flex-col justify-between items-start text-left transition-all border-y border-r border-slate-100 dark:border-slate-800"
      >
        <div class="w-full">
          <div class="flex items-center mb-6">
            <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-900/20/30 flex items-center justify-center mr-4">
               <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="h-6 w-6 text-amber-600" />
            </div>
            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Órdenes Pendientes</h3>
          </div>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium leading-relaxed">
            Tienes <span class="text-brand-600 dark:text-brand-400 font-black">{{ n(proveedoresPedidosPendientesCount) }}</span> órdenes de compra en espera de gestión.
          </p>
          <h4 class="text-[10px] font-black text-slate-400 dark:text-slate-500 mb-4 uppercase tracking-[0.2em]">Detalles Prioritarios</h4>
          <ul class="space-y-3">
            <li
              v-for="(orden, ocIdx) in ordenesPendientesDetallesSafe"
              :key="orden.id != null ? `oc-${orden.id}` : `oc-pend-${ocIdx}`"
              class="text-sm bg-[var(--ui-surface)] dark:bg-slate-950/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 transition-all hover:bg-white dark:hover:bg-slate-900"
            >
              <div class="font-bold text-slate-900 dark:text-slate-100">{{ orden.proveedor ?? 'Proveedor N/D' }}</div>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-[10px] font-bold uppercase" :class="getPrioridadClass(orden.prioridad)">{{ orden.prioridad ?? 'N/D' }}</span>
                <span class="text-[10px] text-slate-500 dark:text-slate-500">{{ orden.fecha_esperada ?? 'N/D' }}</span>
              </div>
              <div v-if="orden.dias_retraso !== null" class="text-sm transition-colors">
                <span :class="getRetrasoClass(orden.dias_retraso)">
                  {{ orden.dias_retraso > 0 ? `${orden.dias_retraso} ${orden.dias_retraso === 1 ? 'día' : 'días'} de retraso` : 'En tiempo' }}
                </span>
              </div>
            </li>
          </ul>
        </div>
        <PanLink
          :href="ordenesPendientesHref"
          class="mt-8 w-full py-4 bg-brand-500 text-white font-black uppercase text-[10px] tracking-wide rounded-2xl shadow-xl shadow-brand-500/20 hover:bg-brand-600 hover:shadow-xl hover:shadow-xl transition-all duration-200 text-center"
        >
          Gestionar Órdenes
        </PanLink>
        </div>

        <!-- Alerta de Órdenes de Compra Enviadas -->
        <div
          v-if="ordenesEnviadasDetallesSafe.length > 0 && $can('view ordenes_compra')"
          class="bg-white dark:bg-slate-800/80 backdrop-blur-xl p-8 rounded-[2rem] shadow-xl border-l-[10px] border-emerald-500 flex flex-col justify-between items-start text-left transition-all border-y border-r border-slate-100 dark:border-slate-800"
        >
          <div class="w-full">
            <div class="flex items-center mb-6">
              <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-slate-800/30 flex items-center justify-center mr-4">
                <FontAwesomeIcon :icon="['fas', 'paper-plane']" class="h-6 w-6 text-emerald-600" />
              </div>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Órdenes Enviadas</h3>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium leading-relaxed">
              Tienes <span class="text-emerald-600 dark:text-slate-400 font-black">{{ n(ordenesEnviadasDetallesSafe.length) }}</span> órdenes enviadas esperando recepción.
            </p>

            <h4 class="text-[10px] font-black text-slate-400 dark:text-slate-500 mb-4 uppercase tracking-[0.2em]">Últimos Envíos</h4>
            <ul class="space-y-3">
              <li
                v-for="(orden, envIdx) in ordenesEnviadasDetallesSafe"
                :key="orden.id != null ? `env-${orden.id}` : `env-${envIdx}`"
                class="text-sm bg-[var(--ui-surface)] dark:bg-slate-950/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 transition-all hover:bg-white dark:hover:bg-slate-900"
              >
                <div class="font-bold text-slate-900 dark:text-slate-100">{{ orden.proveedor ?? 'Proveedor N/D' }}</div>
                <div class="flex items-center gap-2 mt-1">
                  <span class="text-[10px] font-black text-emerald-600">${{ orden.total ?? 'N/D' }}</span>
                  <span class="text-[10px] text-slate-500">{{ orden.fecha_esperada ?? 'N/D' }}</span>
                </div>
              </li>
            </ul>
          </div>
          <PanLink
            :href="ordenesEnviadasHref"
            class="mt-8 w-full py-4 bg-brand-500 text-white font-black uppercase text-[10px] tracking-wide rounded-2xl shadow-xl shadow-emerald-500/20 hover:bg-emerald-600 hover:shadow-xl hover:shadow-xl transition-all duration-200 text-center"
          >
            Ver Envíos
          </PanLink>
        </div>

        <!-- Alerta de Mantenimientos Críticos -->
        <div
          v-if="(mantenimientosVencidosCount > 0 || mantenimientosCriticosCount > 0) && $can('view mantenimientos')"
          class="bg-white dark:bg-slate-800/80 backdrop-blur-xl p-8 rounded-[2rem] shadow-xl border-l-[10px] border-rose-500 flex flex-col justify-between items-start text-left transition-all border-y border-r border-slate-100 dark:border-slate-800"
        >
          <div class="w-full">
            <div class="flex items-center mb-6">
              <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/20/30 flex items-center justify-center mr-4">
                <FontAwesomeIcon :icon="['fas', 'wrench']" class="h-6 w-6 text-rose-600" />
              </div>
              <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider">Urgencias Técnicas</h3>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium leading-relaxed">
              <span v-if="mantenimientosVencidosCount > 0" class="text-rose-600 dark:text-rose-400 font-bold">
                ⚠️ {{ n(mantenimientosVencidosCount) }} mantenimientos ya vencieron.
              </span>
              <span v-else class="text-brand-600 dark:text-brand-400 font-bold">
                ⚡ {{ n(mantenimientosCriticosCount) }} mantenimientos requieren atención hoy.
              </span>
            </p>

            <div v-if="mantenimientosCriticosDetallesSafe.length > 0" class="w-full">
              <h4 class="text-[10px] font-black text-slate-400 dark:text-slate-500 mb-4 uppercase tracking-[0.2em]">Flota en Riesgo</h4>
              <ul class="space-y-3">
                <li
                  v-for="(mantenimiento, manIdx) in mantenimientosCriticosDetallesSafe.slice(0, 3)"
                  :key="mantenimiento.id != null ? `mant-${mantenimiento.id}` : `mant-crit-${manIdx}`"
                  class="text-sm bg-rose-50 dark:bg-rose-900/20 p-4 rounded-2xl border border-rose-100 dark:border-rose-800/50 transition-all"
                >
                  <div class="font-bold text-slate-900 dark:text-rose-100 uppercase tracking-wide">{{ mantenimiento.carro?.marca }} {{ mantenimiento.carro?.modelo }}</div>
                  <div class="flex justify-between items-center mt-1">
                    <span class="text-[10px] text-slate-500">{{ mantenimiento.tipo }}</span>
                    <span class="text-[10px] font-black text-rose-600 dark:text-rose-400 underline">
                        {{ mantenimiento.dias_restantes < 0 ? `${Math.abs(mantenimiento.dias_restantes)} DÍAS ATRASO` : 'POR VENCER' }}
                    </span>
                  </div>
                </li>
              </ul>
            </div>
          </div>
          <PanLink
            :href="mantenimientosHref"
            class="mt-8 w-full py-4 bg-brand-500 text-white font-black uppercase text-[10px] tracking-wide rounded-2xl shadow-xl shadow-rose-500/20 hover:bg-rose-600 hover:shadow-xl hover:shadow-xl transition-all duration-200 text-center"
          >
            Atender Críticos
          </PanLink>
        </div>
      </div>

    <!-- Alertas de Vencimientos de Cuentas -->
    <div
      v-if="hayAlertasVencimientos && ($can('view cuentas_por_pagar') || $can('view cuentas_por_cobrar') || $can('view prestamos'))"
      id="panel-finanzas"
      class="mt-8 scroll-mt-24"
    >
      <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center transition-colors">
        <FontAwesomeIcon :icon="['fas', 'bell']" class="h-6 w-6 text-brand-500 mr-3" />
        Alertas de Vencimientos
      </h2>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Cuentas por Pagar -->
        <div v-if="$can('view cuentas_por_pagar')" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
          <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <FontAwesomeIcon :icon="['fas', 'file-invoice-dollar']" class="h-6 w-6 text-white mr-3" />
                <h3 class="text-xl font-bold text-white">Cuentas por Pagar</h3>
              </div>
              <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-white text-sm font-medium">
                {{ totalCuentasPagar }} pendientes
              </span>
            </div>
          </div>

          <div class="p-4 space-y-3">
            <!-- Vencidas -->
            <div v-if="alertasCuentasPagarSafe.vencidas_count > 0" 
                 class="bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-rose-800 dark:text-rose-200 dark:text-rose-300 font-bold flex items-center transition-colors">
                  <span class="w-3 h-3 bg-brand-500 rounded-full mr-2 animate-pulse"></span>
                  VENCIDAS
                </span>
                <span class="text-rose-600 dark:text-rose-400 font-bold text-lg">${{ formatMonto(sumaMontos(alertasCuentasPagarSafe.vencidas)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasPagarSafe.vencidas.slice(0, 3)" :key="cuenta.id" 
                     class="flex justify-between text-sm text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.proveedor }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }}</span>
                </div>
                <div v-if="alertasCuentasPagarSafe.vencidas.length > 3" class="text-xs text-rose-500 dark:text-rose-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasPagarSafe.vencidas.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 1 Semana -->
            <div v-if="alertasCuentasPagarSafe.semana_count > 0"
                 class="bg-orange-50 dark:bg-brand-900/20 border-l-4 border-brand-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-orange-800 dark:text-orange-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-brand-500 rounded-full mr-2"></span>
                  Próximos 7 días
                </span>
                <span class="text-brand-600 dark:text-orange-400 font-bold">${{ formatMonto(sumaMontos(alertasCuentasPagarSafe.semana)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasPagarSafe.semana.slice(0, 2)" :key="cuenta.id"
                     class="flex justify-between text-sm text-brand-700 dark:text-orange-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.proveedor }}</span>
                  <span class="whitespace-nowrap">{{ cuenta.fecha_vencimiento }}</span>
                </div>
              </div>
            </div>

            <!-- 15 días -->
            <div v-if="alertasCuentasPagarSafe.quincena_count > 0"
                 class="bg-brand-50 dark:bg-brand-900/20 border-l-4 border-brand-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-brand-800 dark:text-brand-200 dark:text-brand-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-brand-500 rounded-full mr-2"></span>
                  En 15 días ({{ alertasCuentasPagarSafe.quincena_count }})
                </span>
                <span class="text-brand-600 dark:text-brand-400 font-bold">${{ formatMonto(sumaMontos(alertasCuentasPagarSafe.quincena)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasPagarSafe.quincena.slice(0, 3)" :key="cuenta.id"
                     class="flex justify-between text-sm text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.numero }} - {{ cuenta.proveedor }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }} ({{ cuenta.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasCuentasPagarSafe.quincena.length > 3" class="text-xs text-brand-500 dark:text-brand-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasPagarSafe.quincena.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 30 días -->
            <div v-if="alertasCuentasPagarSafe.mes_count > 0"
                 class="bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 border-l-4 border-blue-400 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sky-800 dark:text-sky-200 dark:text-blue-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                  En 30 días ({{ alertasCuentasPagarSafe.mes_count }})
                </span>
                <span class="text-blue-600 dark:text-blue-400 font-bold">${{ formatMonto(sumaMontos(alertasCuentasPagarSafe.mes)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasPagarSafe.mes.slice(0, 5)" :key="cuenta.id"
                     class="flex justify-between text-sm text-sky-800 dark:text-sky-200 dark:text-blue-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.numero }} - {{ cuenta.proveedor }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }} ({{ cuenta.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasCuentasPagarSafe.mes.length > 5" class="text-xs text-blue-500 dark:text-blue-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasPagarSafe.mes.length - 5 }} más...
                </div>
              </div>
            </div>

            <!-- Sin alertas -->
            <div v-if="totalCuentasPagar === 0" class="text-center py-6 text-slate-500 dark:text-slate-400 transition-colors">
              <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-10 w-10 text-emerald-400 mb-2" />
              <p class="font-medium">¡Todo al día!</p>
            </div>
          </div>

          <div class="px-4 pb-4">
            <PanLink href="/cuentas-por-pagar" 
                     class="block w-full text-center px-4 py-2 bg-brand-500 text-white font-semibold rounded-xl hover:bg-rose-600 transition-colors">
              Ver Cuentas por Pagar
            </PanLink>
          </div>
        </div>

        <!-- Cuentas por Cobrar -->
        <div v-if="$can('view cuentas_por_cobrar')" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
          <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <FontAwesomeIcon :icon="['fas', 'hand-holding-usd']" class="h-6 w-6 text-white mr-3" />
                <h3 class="text-xl font-bold text-white">Cuentas por Cobrar</h3>
              </div>
              <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-white text-sm font-medium">
                {{ totalCuentasCobrar }} pendientes
              </span>
            </div>
          </div>

          <div class="p-4 space-y-3">
            <!-- Vencidas -->
            <div v-if="alertasCuentasCobrarSafe.vencidas_count > 0"
                 class="bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-rose-800 dark:text-rose-200 dark:text-rose-300 font-bold flex items-center transition-colors">
                  <span class="w-3 h-3 bg-brand-500 rounded-full mr-2 animate-pulse"></span>
                  VENCIDAS
                </span>
                <span class="text-rose-600 dark:text-rose-400 font-bold text-lg">${{ formatMonto(sumaMontos(alertasCuentasCobrarSafe.vencidas)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasCobrarSafe.vencidas.slice(0, 3)" :key="cuenta.id"
                     class="flex justify-between text-sm text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.cliente }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }}</span>
                </div>
                <div v-if="alertasCuentasCobrarSafe.vencidas.length > 3" class="text-xs text-rose-500 dark:text-rose-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasCobrarSafe.vencidas.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 1 Semana -->
            <div v-if="alertasCuentasCobrarSafe.semana_count > 0"
                 class="bg-orange-50 dark:bg-brand-900/20 border-l-4 border-brand-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-orange-800 dark:text-orange-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-brand-500 rounded-full mr-2"></span>
                  Próximos 7 días
                </span>
                <span class="text-brand-600 dark:text-orange-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasCuentasCobrarSafe.semana)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasCobrarSafe.semana.slice(0, 2)" :key="cuenta.id"
                     class="flex justify-between text-sm text-brand-700 dark:text-orange-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.cliente }}</span>
                  <span class="whitespace-nowrap">{{ cuenta.fecha_vencimiento }}</span>
                </div>
              </div>
            </div>

            <!-- 15 días -->
            <div v-if="alertasCuentasCobrarSafe.quincena_count > 0"
                 class="bg-brand-50 dark:bg-brand-900/20 border-l-4 border-brand-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-brand-800 dark:text-brand-200 dark:text-brand-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-brand-500 rounded-full mr-2"></span>
                  En 15 días ({{ alertasCuentasCobrarSafe.quincena_count }})
                </span>
                <span class="text-brand-600 dark:text-brand-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasCuentasCobrarSafe.quincena)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasCobrarSafe.quincena.slice(0, 3)" :key="cuenta.id"
                     class="flex justify-between text-sm text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.numero }} - {{ cuenta.cliente }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }} ({{ cuenta.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasCuentasCobrarSafe.quincena.length > 3" class="text-xs text-brand-500 dark:text-brand-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasCobrarSafe.quincena.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 30 días -->
            <div v-if="alertasCuentasCobrarSafe.mes_count > 0"
                 class="bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 border-l-4 border-blue-400 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sky-800 dark:text-sky-200 dark:text-blue-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                  En 30 días ({{ alertasCuentasCobrarSafe.mes_count }})
                </span>
                <span class="text-blue-600 dark:text-blue-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasCuentasCobrarSafe.mes)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasCobrarSafe.mes.slice(0, 5)" :key="cuenta.id"
                     class="flex justify-between text-sm text-sky-800 dark:text-sky-200 dark:text-blue-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.numero }} - {{ cuenta.cliente }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }} ({{ cuenta.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasCuentasCobrarSafe.mes.length > 5" class="text-xs text-blue-500 dark:text-blue-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasCobrarSafe.mes.length - 5 }} más...
                </div>
              </div>
            </div>

            <!-- Sin alertas -->
            <div v-if="totalCuentasCobrar === 0" class="text-center py-6 text-slate-500 dark:text-slate-400 transition-colors">
              <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-10 w-10 text-emerald-400 mb-2" />
              <p class="font-medium">¡Todo al día!</p>
            </div>
          </div>

          <div class="px-4 pb-4">
            <PanLink href="/cuentas-por-cobrar"
                     class="block w-full text-center px-4 py-2 bg-brand-500 text-white font-semibold rounded-xl hover:bg-emerald-600 transition-colors">
              Ver Cuentas por Cobrar
            </PanLink>
          </div>
        </div>
        
        <!-- Préstamos -->
        <div v-if="$can('view prestamos')" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
          <div class="bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <FontAwesomeIcon :icon="['fas', 'money-bill-wave']" class="h-6 w-6 text-white mr-3" />
                <h3 class="text-xl font-bold text-white">Préstamos</h3>
              </div>
              <span class="bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-white text-sm font-medium">
                {{ totalPrestamos }} pendientes
              </span>
            </div>
          </div>

          <div class="p-4 space-y-3">
            <!-- Vencidas -->
            <div v-if="alertasPrestamosSafe.vencidas_count > 0"
                 class="bg-rose-50 dark:bg-rose-900/20 border-l-4 border-rose-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-rose-800 dark:text-rose-200 dark:text-rose-300 font-bold flex items-center transition-colors">
                  <span class="w-3 h-3 bg-brand-500 rounded-full mr-2 animate-pulse"></span>
                  VENCIDAS
                </span>
                <span class="text-rose-600 dark:text-rose-400 font-bold text-lg transition-colors">${{ formatMonto(sumaMontos(alertasPrestamosSafe.vencidas)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="pago in alertasPrestamosSafe.vencidas.slice(0, 3)" :key="pago.id"
                     class="flex justify-between text-sm text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ pago.cliente }} (Pago {{ pago.numero_pago }})</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(pago.monto_pendiente) }}</span>
                </div>
                <div v-if="alertasPrestamosSafe.vencidas.length > 3" class="text-xs text-rose-500 dark:text-rose-400/80 mt-1 transition-colors">
                  +{{ alertasPrestamosSafe.vencidas.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 1 Semana -->
            <div v-if="alertasPrestamosSafe.semana_count > 0"
                 class="bg-orange-50 dark:bg-brand-900/20 border-l-4 border-brand-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-orange-800 dark:text-orange-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-brand-500 rounded-full mr-2"></span>
                  Próximos 7 días
                </span>
                <span class="text-brand-600 dark:text-orange-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasPrestamosSafe.semana)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="pago in alertasPrestamosSafe.semana.slice(0, 2)" :key="pago.id"
                     class="flex justify-between text-sm text-brand-700 dark:text-orange-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ pago.cliente }}</span>
                  <span class="whitespace-nowrap">{{ pago.fecha_vencimiento }}</span>
                </div>
              </div>
            </div>

            <!-- 15 días -->
            <div v-if="alertasPrestamosSafe.quincena_count > 0"
                 class="bg-brand-50 dark:bg-brand-900/20 border-l-4 border-brand-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-brand-800 dark:text-brand-200 dark:text-brand-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-brand-500 rounded-full mr-2"></span>
                  En 15 días ({{ alertasPrestamosSafe.quincena_count }})
                </span>
                <span class="text-brand-600 dark:text-brand-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasPrestamosSafe.quincena)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="pago in alertasPrestamosSafe.quincena.slice(0, 3)" :key="pago.id"
                     class="flex justify-between text-sm text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ pago.cliente }} (Pago {{ pago.numero_pago }})</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(pago.monto_pendiente) }} ({{ pago.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasPrestamosSafe.quincena.length > 3" class="text-xs text-brand-500 dark:text-brand-400/80 mt-1 transition-colors">
                  +{{ alertasPrestamosSafe.quincena.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 30 días -->
            <div v-if="alertasPrestamosSafe.mes_count > 0"
                 class="bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 border-l-4 border-blue-400 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sky-800 dark:text-sky-200 dark:text-blue-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                  En 30 días ({{ alertasPrestamosSafe.mes_count }})
                </span>
                <span class="text-blue-600 dark:text-blue-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasPrestamosSafe.mes)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="pago in alertasPrestamosSafe.mes.slice(0, 5)" :key="pago.id"
                     class="flex justify-between text-sm text-sky-800 dark:text-sky-200 dark:text-blue-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ pago.cliente }} (Pago {{ pago.numero_pago }})</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(pago.monto_pendiente) }} ({{ pago.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasPrestamosSafe.mes.length > 5" class="text-xs text-blue-500 dark:text-blue-400/80 mt-1 transition-colors">
                  +{{ alertasPrestamosSafe.mes.length - 5 }} más...
                </div>
              </div>
            </div>

            <!-- Sin alertas -->
            <div v-if="totalPrestamos === 0" class="text-center py-6 text-slate-500 dark:text-slate-400 transition-colors">
              <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-10 w-10 text-emerald-400 mb-2" />
              <p class="font-medium">¡Todo al día!</p>
            </div>
          </div>

          <div class="px-4 pb-4">
            <PanLink href="/pagos"
                     class="block w-full text-center px-4 py-2 bg-brand-500 text-white font-semibold rounded-xl hover:bg-slate-500 transition-colors">
              Ver Pagos de Préstamos
            </PanLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Sección de Gráficos - Premium Analytics -->
    <div
      v-if="$can('view ventas') || $can('view ordenes_compra') || $can('view clientes')"
      id="panel-analisis"
      class="mt-10 mb-10 scroll-mt-24"
    >
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center shadow-xl">
            <FontAwesomeIcon :icon="['fas', 'chart-pie']" class="h-6 w-6 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white transition-colors">Análisis y Tendencias</h2>
            <p class="text-slate-500 dark:text-slate-400 text-sm transition-colors">Visualiza el rendimiento de tu negocio</p>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Gráfico de Ventas Mensuales -->
        <div v-if="$can('view ventas')" class="group relative bg-white/60 dark:bg-slate-800/50 backdrop-blur-md p-8 rounded-3xl shadow-xl border border-white/50 dark:border-slate-700/50 overflow-hidden hover:shadow-xl transition-all duration-500">
          <!-- Decorative gradient -->
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/20 to-blue-600/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
          <div class="absolute bottom-0 left-0 w-16 h-16 bg-gradient-to-tr from-blue-400/10 to-transparent rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>
          
          <div class="relative">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-xl">
                  <FontAwesomeIcon :icon="['fas', 'chart-line']" class="h-5 w-5 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-slate-900 dark:text-white transition-colors">Ventas Mensuales</h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 transition-colors">Últimos 6 meses</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-sky-50 dark:bg-sky-900/20 text-blue-600 text-xs font-medium rounded-full">Tendencia</span>
            </div>
            <div v-if="chartVentasLabels && chartVentasLabels.length > 0" class="h-72">
              <LineChart
                :labels="chartVentasLabels"
                :data="chartVentasData"
                label="Ventas Totales"
                border-color="rgb(245, 158, 11)"
                background-color="rgba(245, 158, 11, 0.15)"
                :fill="true"
              />
            </div>
            <div v-else class="h-72 flex items-center justify-center bg-gradient-to-br from-slate-50 dark:from-slate-700/50 to-blue-50/30 dark:to-blue-900/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 transition-colors">
              <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-sky-900/20/30 flex items-center justify-center mx-auto mb-4">
                  <FontAwesomeIcon :icon="['fas', 'chart-line']" class="h-8 w-8 text-blue-400 dark:text-blue-300" />
                </div>
                <p class="text-slate-500 dark:text-slate-200 font-semibold mb-2">Sin datos de ventas</p>
                <p class="text-sm text-slate-400 dark:text-slate-500 mb-4">Registra tus primeras ventas</p>
                <PanLink :href="route('ventas.create')" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 text-white text-sm font-semibold rounded-xl hover:shadow-xl hover:scale-105 transition-all duration-200">
                  <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                  Nueva Venta
                </PanLink>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico de Productos Más Vendidos -->
        <div v-if="$can('view ventas')" class="group relative bg-white/60 dark:bg-slate-800/50 backdrop-blur-md p-8 rounded-3xl shadow-xl border border-white/50 dark:border-slate-700/50 overflow-hidden hover:shadow-xl transition-all duration-500">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-emerald-400/20 to-emerald-600/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
          <div class="absolute bottom-0 left-0 w-16 h-16 bg-gradient-to-tr from-emerald-400/10 to-transparent rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>
          
          <div class="relative">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-xl">
                  <FontAwesomeIcon :icon="['fas', 'box']" class="h-5 w-5 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-slate-900 dark:text-white transition-colors">Productos Más Vendidos</h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 transition-colors">Últimos 30 días</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 text-xs font-medium rounded-full">Top 5</span>
            </div>
            <div v-if="chartProductosLabels && chartProductosLabels.length > 0" class="h-72">
              <BarChart
                :labels="chartProductosLabelsTruncated"
                :data="chartProductosData"
                :full-labels="chartProductosLabels"
                label="Unidades Vendidas"
                :horizontal="true"
                :backgroundColor="['rgba(245, 158, 11, 0.85)', 'rgba(217, 119, 6, 0.85)', 'rgba(180, 83, 9, 0.85)', 'rgba(146, 64, 14, 0.85)', 'rgba(120, 53, 15, 0.85)']"
              />
            </div>
            <div v-else class="h-72 flex items-center justify-center bg-gradient-to-br from-slate-50 dark:from-slate-700/50 to-emerald-50/30 dark:to-emerald-900/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 transition-colors">
              <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-emerald-100 dark:bg-slate-800/30 flex items-center justify-center mx-auto mb-4">
                  <FontAwesomeIcon :icon="['fas', 'box']" class="h-8 w-8 text-emerald-400 dark:text-emerald-300" />
                </div>
                <p class="text-slate-500 dark:text-slate-200 font-semibold mb-2">Sin ventas recientes</p>
                <p class="text-sm text-slate-400 dark:text-slate-500 mb-4">Registra ventas para ver el ranking</p>
                <PanLink :href="route('ventas.create')" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 text-white text-sm font-semibold rounded-xl hover:shadow-xl hover:scale-105 transition-all duration-200">
                  <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                  Nueva Venta
                </PanLink>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico de Estados de Órdenes -->
        <div v-if="$can('view ordenes_compra')" class="group relative bg-white/60 dark:bg-slate-800/50 backdrop-blur-md p-8 rounded-3xl shadow-xl border border-white/50 dark:border-slate-700/50 overflow-hidden hover:shadow-xl transition-all duration-500">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-400/20 to-purple-600/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
          <div class="absolute bottom-0 left-0 w-16 h-16 bg-gradient-to-tr from-purple-400/10 to-transparent rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>
          
          <div class="relative">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-xl">
                  <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="h-5 w-5 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-slate-900 dark:text-white transition-colors">Órdenes de Compra</h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 transition-colors">Por estado</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-purple-50 text-purple-600 text-xs font-medium rounded-full">Distribución</span>
            </div>
            <div v-if="chartOrdenesLabels && chartOrdenesLabels.length > 0" class="h-72">
              <DoughnutChart
                :labels="chartOrdenesLabels"
                :data="chartOrdenesData"
                :backgroundColor="['rgba(245, 158, 11, 0.9)', 'rgba(217, 119, 6, 0.9)', 'rgba(16, 185, 129, 0.9)', 'rgba(99, 102, 241, 0.9)']"
              />
            </div>
            <div v-else class="h-72 flex items-center justify-center bg-gradient-to-br from-slate-50 dark:from-slate-700/50 to-purple-50/30 dark:to-purple-900/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 transition-colors">
              <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mx-auto mb-4">
                  <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="h-8 w-8 text-purple-400 dark:text-purple-300" />
                </div>
                <p class="text-slate-500 dark:text-slate-200 font-semibold mb-2">Sin órdenes de compra</p>
                <p class="text-sm text-slate-400 dark:text-slate-500 mb-4">Gestiona tus proveedores</p>
                <PanLink :href="route('ordenescompra.create')" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 text-white text-sm font-semibold rounded-xl hover:shadow-xl hover:scale-105 transition-all duration-200">
                  <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                  Crear Orden
                </PanLink>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico de Crecimiento de Clientes -->
        <div v-if="$can('view clientes')" class="group relative bg-white/60 dark:bg-slate-800/50 backdrop-blur-md p-8 rounded-3xl shadow-xl border border-white/50 dark:border-slate-700/50 overflow-hidden hover:shadow-xl transition-all duration-500">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-brand-400/20 to-brand-500/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
          <div class="absolute bottom-0 left-0 w-16 h-16 bg-gradient-to-tr from-brand-400/10 to-transparent rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>
          
          <div class="relative">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-brand-500 flex items-center justify-center shadow-xl">
                  <FontAwesomeIcon :icon="['fas', 'users']" class="h-5 w-5 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-slate-900 dark:text-white transition-colors">Crecimiento de Clientes</h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400 transition-colors">Últimos 6 meses</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-brand-50 dark:bg-brand-900/20 text-brand-600 text-xs font-medium rounded-full">Evolución</span>
            </div>
            <div v-if="chartClientesLabels && chartClientesLabels.length > 0" class="h-72">
              <LineChart
                :labels="chartClientesLabels"
                :data="chartClientesData"
                label="Clientes Nuevos"
                border-color="rgb(217, 119, 6)"
                background-color="rgba(217, 119, 6, 0.15)"
                :fill="true"
                :show-currency="false"
              />
            </div>
            <div v-else class="h-72 flex items-center justify-center bg-gradient-to-br from-slate-50 dark:from-slate-700/50 to-brand-50/30 dark:to-orange-900/20 rounded-2xl border border-dashed border-slate-200 dark:border-slate-700 transition-colors">
              <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-brand-50 dark:bg-brand-900/20/30 flex items-center justify-center mx-auto mb-4">
                  <FontAwesomeIcon :icon="['fas', 'users']" class="h-8 w-8 text-brand-400 dark:text-amber-300" />
                </div>
                <p class="text-slate-500 dark:text-slate-200 font-semibold mb-2">Sin registro de clientes</p>
                <p class="text-sm text-slate-400 dark:text-slate-500 mb-4">Agrega clientes para visualizar el crecimiento</p>
                <PanLink :href="route('clientes.create')" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-500 text-white text-sm font-semibold rounded-xl hover:shadow-xl hover:scale-105 transition-all duration-200">
                  <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                  Nuevo Cliente
                </PanLink>
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
import { useFormatters } from '@/Composables/useFormatters';
import { computed, ref, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { Notyf } from 'notyf'
import Swal from '@/Utils/Swal'

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })
import CitaModal from '@/Components/CitaModal.vue'

const page = usePage()

const isDark = ref(false)
const isSuperAdmin = computed(() => {
  const roles = page.props.auth?.user?.roles || []
  return roles.some(r => r.name === 'super-admin')
})

const colors = computed(() => ({
  principal: page.props.empresa_config?.color_principal || '#F59E0B',
  secundario: page.props.empresa_config?.color_secundario || '#D97706',
}))

const checkDarkMode = () => {
    isDark.value = document.documentElement.classList.contains('dark')
}

onMounted(() => {
    checkDarkMode()
    // Observar cambios en la clase dark del html
    const observer = new MutationObserver(() => {
        checkDarkMode()
    })
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

const totalCuentasPagar = computed(() => {
  const safe = alertasCuentasPagarSafe.value
  return (safe.vencidas_count || 0) +
         (safe.semana_count || 0) +
         (safe.quincena_count || 0) +
         (safe.mes_count || 0)
})

const totalCuentasCobrar = computed(() => {
  const safe = alertasCuentasCobrarSafe.value
  return (safe.vencidas_count || 0) +
         (safe.semana_count || 0) +
         (safe.quincena_count || 0) +
         (safe.mes_count || 0)
})

const totalPrestamos = computed(() => {
  const safe = alertasPrestamosSafe.value
  return (safe.vencidas_count || 0) +
         (safe.semana_count || 0) +
         (safe.quincena_count || 0) +
         (safe.mes_count || 0)
})

const hayAlertasVencimientos = computed(() => {
    return totalCuentasPagar.value > 0 || totalCuentasCobrar.value > 0 || totalPrestamos.value > 0
})

/** Sin urgencias operativas (citas, tareas, OC, mantenimiento, stock). */
const panelOperativoTranquilo = computed(() => {
  const sinCitas = (props.citasHoyCount ?? 0) === 0
  const sinTareas = (props.tareasPendientes?.total ?? 0) === 0
  const sinOcPend = (props.proveedoresPedidosPendientesCount ?? 0) === 0
  const sinOcEnv = !((props.ordenesEnviadasDetalles || []).length > 0)
  const sinMantUrg =
    (props.mantenimientosVencidosCount ?? 0) === 0 && (props.mantenimientosCriticosCount ?? 0) === 0
  const sinStock = (props.productosBajoStockCount ?? 0) === 0
  return sinCitas && sinTareas && sinOcPend && sinOcEnv && sinMantUrg && sinStock
})

const alertasCuentasPagarSafe = computed(() => props.alertasCuentasPagar || { vencidas: [], vencidas_count: 0, semana: [], semana_count: 0, quincena: [], quincena_count: 0, mes: [], mes_count: 0 })
const alertasCuentasCobrarSafe = computed(() => props.alertasCuentasCobrar || { vencidas: [], vencidas_count: 0, semana: [], semana_count: 0, quincena: [], quincena_count: 0, mes: [], mes_count: 0 })
const alertasPrestamosSafe = computed(() => props.alertasPrestamos || { vencidas: [], vencidas_count: 0, semana: [], semana_count: 0, quincena: [], quincena_count: 0, mes: [], mes_count: 0 })

import { Head } from '@inertiajs/vue3'
import PanLink from '@/Components/PanLink.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import LineChart from '@/Components/Charts/LineChart.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'

defineOptions({ layout: AppLayout })

// ✅ Props con defaults seguros
const props = defineProps({
  clientesCount: { type: Number, default: 0 },
  clientesNuevosCount: { type: Number, default: 0 },

  productosCount: { type: Number, default: 0 },
  productosBajoStockCount: { type: Number, default: 0 },
  productosBajoStockNombres: { type: Array, default: () => [] },

  proveedoresCount: { type: Number, default: 0 },
  proveedoresPedidosPendientesCount: { type: Number, default: 0 },
  ordenesPendientesDetalles: { type: Array, default: () => [] },
  ordenesEnviadasCount: { type: Number, default: 0 },
  ordenesEnviadasDetalles: { type: Array, default: () => [] },

  citasCount: { type: Number, default: 0 },
  citasHoyCount: { type: Number, default: 0 },
  citasHoyDetalles: { type: Array, default: () => [] },

  mantenimientosCount: { type: Number, default: 0 },
  mantenimientosVencidosCount: { type: Number, default: 0 },
  mantenimientosCriticosCount: { type: Number, default: 0 },
  mantenimientosCriticosDetalles: { type: Array, default: () => [] },

  // Props para gráficos
  chartVentasLabels: { type: Array, default: () => [] },
  chartVentasData: { type: Array, default: () => [] },
  chartProductosLabels: { type: Array, default: () => [] },
  chartProductosData: { type: Array, default: () => [] },
  chartOrdenesLabels: { type: Array, default: () => [] },
  chartOrdenesData: { type: Array, default: () => [] },
  chartClientesLabels: { type: Array, default: () => [] },
  chartClientesData: { type: Array, default: () => [] },
  // Props para alertas de vencimientos
  alertasCuentasPagar: { type: Object, default: () => ({ vencidas: [], vencidas_count: 0, semana: [], semana_count: 0, quincena: [], quincena_count: 0, mes: [], mes_count: 0 }) },
  alertasCuentasCobrar: { type: Object, default: () => ({ vencidas: [], vencidas_count: 0, semana: [], semana_count: 0, quincena: [], quincena_count: 0, mes: [], mes_count: 0 }) },
  alertasPrestamos: { type: Object, default: () => ({ vencidas: [], vencidas_count: 0, semana: [], semana_count: 0, quincena: [], quincena_count: 0, mes: [], mes_count: 0 }) },
  // Props para tareas pendientes
  tareasPendientes: { type: Object, default: () => ({ tareas: [], total: 0, en_proceso: 0, pendientes: 0 }) },
  // RESICO Stats
  resicoStats: { type: Object, default: null },
  // Alerta de Cierre Fiscal
  fiscalClosingAlert: { type: Object, default: () => ({ active: false, pending_emitidos_count: 0, pending_recibidos_count: 0, deadline_day: 5, month_name: '' }) },
  // Alertas de Cancelación
  cancellationAlerts: { type: Object, default: () => ({ count: 0, detalles: [] }) }
})

// ===== Header: Saludo, nombre y fecha =====
const nombreUsuario = computed(() => page.props.auth?.user?.name?.split(' ')[0] || 'Usuario')

const saludo = computed(() => {
  const hora = new Date().getHours()
  if (hora < 12) return 'Buenos días'
  if (hora < 18) return 'Buenas tardes'
  return 'Buenas noches'
})

const fechaHoy = computed(() => {
  const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
  return new Date().toLocaleDateString('es-MX', opciones)
})

// ===== Utilidades de formato (evita repetir lógica)
const n = (val) => Number(val || 0).toLocaleString('es-MX')
const money = (val) => {
  const num = Number(val)
  return Number.isFinite(num)
    ? num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    : '0.00'
}

// Formatear monto para las alertas
const formatMonto = (val) => {
  const num = Number(val) || 0
  return num.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

// Sumar montos de un array de cuentas
const sumaMontos = (cuentas) => {
  if (!Array.isArray(cuentas)) return 0
  return cuentas.reduce((sum, c) => sum + (Number(c.monto_pendiente) || 0), 0)
}

// Función para truncar nombres largos de productos
const truncateProductName = (name, maxLength = 15) => {
  if (!name || name.length <= maxLength) return name
  return name.substring(0, maxLength - 3) + '...'
}

const getPrioridadClass = (prioridad) => {
  switch (prioridad) {
    case 'urgente':
      return 'text-rose-600 dark:text-rose-400 font-semibold'
    case 'alta':
      return 'text-brand-600 dark:text-orange-400 font-medium'
    case 'media':
      return 'text-brand-600 dark:text-amber-400'
    case 'baja':
      return 'text-emerald-600 dark:text-slate-400'
    default:
      return 'text-slate-500 dark:text-slate-400'
  }
}

const getRetrasoClass = (diasRetraso) => {
  if (diasRetraso === 0) {
    return 'text-emerald-600 dark:text-slate-400'
  } else if (diasRetraso > 0) {
    return 'text-rose-600 dark:text-rose-400 font-semibold'
  }
  return 'text-slate-500 dark:text-slate-400'
}

// ===== Fallbacks defensivos
const productosBajoStockNombresSafe = computed(() =>
  Array.isArray(props.productosBajoStockNombres) ? props.productosBajoStockNombres : []
)
const ordenesPendientesDetallesSafe = computed(() =>
  Array.isArray(props.ordenesPendientesDetalles) ? props.ordenesPendientesDetalles : []
)
const ordenesEnviadasDetallesSafe = computed(() =>
  Array.isArray(props.ordenesEnviadasDetalles) ? props.ordenesEnviadasDetalles : []
)
const citasHoyDetallesSafe = computed(() =>
  Array.isArray(props.citasHoyDetalles) ? props.citasHoyDetalles : []
)
const mantenimientosCriticosDetallesSafe = computed(() =>
  Array.isArray(props.mantenimientosCriticosDetalles) ? props.mantenimientosCriticosDetalles : []
)
const tareasPendientesSafe = computed(() => ({
  tareas: Array.isArray(props.tareasPendientes?.tareas) ? props.tareasPendientes.tareas : [],
  total: props.tareasPendientes?.total ?? 0,
  en_proceso: props.tareasPendientes?.en_proceso ?? 0,
  pendientes: props.tareasPendientes?.pendientes ?? 0
}))

// Estado para completar tareas
const completandoTarea = ref(null)


// Función para marcar tarea como completada
const completarTarea = (tareaId) => {
  completandoTarea.value = tareaId
  router.patch(`/bitacora/${tareaId}/cambiar-estado`, { estado: 'completado' }, {
    preserveScroll: true,
    onFinish: () => {
      completandoTarea.value = null
    }
  })
}

// Estado para cargar detalles de cita
const showCitaModal = ref(false)
const citaSeleccionada = ref(null)
const cargandoCita = ref(null)
const showCancelModal = ref(false)
const selectedCancellation = ref(null)
const processingCancel = ref(false)

const anularPoliza = async (cfdi) => {
  processingCancel.value = true
  try {
    await axios.delete(route('cfdi.anular-poliza', cfdi.id))
    notyf.success('Póliza anulada')
    cfdi.tiene_poliza = false
    cfdi.poliza_id = null
    cfdi.poliza_numero = null
    cfdi.poliza_tipo = null
    cfdi.poliza_total = null
  } catch (e) { notyf.error(e.response?.data?.message || 'Error al anular póliza') }
  finally { processingCancel.value = false }
}

const eliminarCfdiCancelado = async (cfdi) => {
  if (!cfdi.id) { notyf.error('Error: CFDI sin ID. Recarga la página.'); return }
  if (!await Swal.fire({ title: '¿Eliminar CFDI?', text: `Se eliminará el CFDI #${cfdi.folio} y su XML del sistema.`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar' }).then(r => r.isConfirmed)) return
  processingCancel.value = true
  try {
    const res = await axios.delete(route('cfdi.destroy', cfdi.id))
    if (res.data.success) {
      notyf.success('CFDI eliminado')
      showCancelModal.value = false
    } else {
      notyf.error(res.data.message || 'Error al eliminar')
    }
  } catch (e) { 
    notyf.error(e.response?.data?.message || 'Error de conexión al eliminar')
    console.error('Delete CFDI error:', e)
  }
  finally { processingCancel.value = false }
}

// Función para abrir detalles de cita en modal
const abrirDetallesCita = async (id) => {
  if (!id) return
  cargandoCita.value = id
  try {
    const response = await axios.get(`/api/citas/${id}`)
    if (response.data && response.data.success) {
      citaSeleccionada.value = response.data.data
      showCitaModal.value = true
    } else {
      alert('No se pudo cargar la información de la cita.')
    }
  } catch (error) {
    console.error('Error al cargar detalles de la cita:', error)
    alert('Error al conectar con el servidor.')
  } finally {
    cargandoCita.value = null
  }
}

// ===== Fallbacks defensivos para alertas de vencimientos


// ===== Labels truncados para gráficos
const chartProductosLabelsTruncated = computed(() =>
  Array.isArray(props.chartProductosLabels)
    ? props.chartProductosLabels.map(name => truncateProductName(name, 12))
    : []
)

// ===== Rutas (usa lo que tengas; si tienes Ziggy, podrías usar route('nombre'))
const clientesHref = '/clientes'
const productosHref = '/productos'
const productosLowHref = '/productos?stock=low'
const proveedoresHref = '/proveedores'
const ordenesPendientesHref = '/ordenescompra?estado=pendiente'
const ordenesEnviadasHref = '/ordenescompra?estado=enviado_a_proveedor'
const citasHref = '/citas'
const mantenimientosHref = '/mantenimientos'
</script>

