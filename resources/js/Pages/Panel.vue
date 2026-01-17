<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-slate-100 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 relative overflow-hidden transition-colors duration-300">

    <Head title="Panel" />

    <div class="container mx-auto px-6 py-8 relative z-10">
      <!-- Dashboard Header - Hero Section -->
      <div class="mb-10 relative">

        
        <div 
          class="relative rounded-3xl p-8 shadow-xl overflow-hidden transition-all duration-500"
          :style="{ 
            background: isDark 
              ? 'linear-gradient(135deg, #1f2937 0%, #111827 100%)' 
              : `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` 
          }"
        >
          
          <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <!-- Left: Greeting & Date -->
            <div>
              <div class="flex items-center gap-4 mb-3">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg border border-white/30">
                  <FontAwesomeIcon :icon="['fas', 'chart-line']" class="h-8 w-8 text-white" />
                </div>
                <div>
                  <h1 class="text-3xl lg:text-4xl font-bold text-white drop-shadow-lg transition-colors">
                    {{ saludo }}, {{ nombreUsuario }}
                  </h1>
                  <p class="text-white/80 dark:text-gray-300 text-lg mt-1 flex items-center gap-2 transition-colors">
                    <FontAwesomeIcon :icon="['fas', 'calendar']" class="opacity-80" />
                    {{ fechaHoy }}
                  </p>
                </div>
              </div>
            </div>
            
            <!-- Right: Quick Actions -->
            <div class="flex flex-wrap gap-3">
              <PanLink
                :href="route('ventas.create')"
                class="group inline-flex items-center gap-2 px-5 py-3 bg-white text-amber-600 font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
              >
                <FontAwesomeIcon :icon="['fas', 'plus']" class="group-hover:rotate-90 transition-transform duration-300" />
                Nueva Venta
              </PanLink>
              <PanLink
                :href="route('citas.create')"
                class="group inline-flex items-center gap-2 px-5 py-3 bg-white/20 backdrop-blur-sm text-white font-semibold rounded-xl shadow border border-white/30 hover:bg-white/30 hover:shadow-lg transition-all duration-300"
              >
                <FontAwesomeIcon :icon="['fas', 'calendar-plus']" />
                Nueva Cita
              </PanLink>
              <PanLink
                :href="route('clientes.create')"
                class="group inline-flex items-center gap-2 px-5 py-3 bg-white/20 backdrop-blur-sm text-white font-semibold rounded-xl shadow border border-white/30 hover:bg-white/30 hover:shadow-lg transition-all duration-300"
              >
                <FontAwesomeIcon :icon="['fas', 'user-plus']" />
                Nuevo Cliente
              </PanLink>
            </div>
          </div>
        </div>
      </div>

    <!-- Tarjetas de Resumen - Premium Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-10 stagger-animation">
      <!-- Clientes -->
      <PanLink
        :href="clientesHref"
        class="group relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm p-6 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 transition-all duration-500 hover:shadow-xl hover:-translate-y-2 overflow-hidden"
        aria-label="Ir a clientes"
      >
        <!-- Gradient Overlay on Hover -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/0 via-blue-500/0 to-blue-600/0 group-hover:from-blue-500/10 group-hover:via-blue-500/5 group-hover:to-blue-600/10 transition-all duration-500 rounded-2xl"></div>
        <!-- Top Accent Line -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 via-blue-500 to-blue-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
        <div class="relative flex flex-col items-center justify-center space-y-3 text-center">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg group-hover:shadow-blue-500/30 group-hover:scale-110 transition-all duration-300">
            <FontAwesomeIcon :icon="['fas', 'users']" class="h-7 w-7 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-blue-600 transition-colors">
              {{ n(clientesCount) }}
            </h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Clientes</p>
          </div>
          <p class="text-xs text-gray-400 bg-blue-50 px-3 py-1 rounded-full">
            +{{ n(clientesNuevosCount) }} este mes
          </p>
        </div>
      </PanLink>

      <!-- Productos -->
      <PanLink
        :href="productosHref"
        class="group relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm p-6 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 transition-all duration-500 hover:shadow-xl hover:-translate-y-2 overflow-hidden"
        aria-label="Ir a productos"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/0 via-emerald-500/0 to-emerald-600/0 group-hover:from-emerald-500/10 group-hover:via-emerald-500/5 group-hover:to-emerald-600/10 transition-all duration-500 rounded-2xl"></div>
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 via-emerald-500 to-emerald-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
        <div class="relative flex flex-col items-center justify-center space-y-3 text-center">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg group-hover:shadow-emerald-500/30 group-hover:scale-110 transition-all duration-300">
            <FontAwesomeIcon :icon="['fas', 'box']" class="h-7 w-7 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 transition-colors">
              {{ n(productosCount) }}
            </h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Productos</p>
          </div>
          <p class="text-xs text-gray-400 bg-emerald-50 px-3 py-1 rounded-full" :class="{ 'text-red-500 bg-red-50': productosBajoStockCount > 0 }">
            {{ productosBajoStockCount > 0 ? `⚠️ ${n(productosBajoStockCount)} bajo stock` : '✓ Stock OK' }}
          </p>
        </div>
      </PanLink>

      <!-- Proveedores -->
      <PanLink
        :href="proveedoresHref"
        class="group relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm p-6 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 transition-all duration-500 hover:shadow-xl hover:-translate-y-2 overflow-hidden"
        aria-label="Ir a proveedores"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-purple-500/0 via-purple-500/0 to-purple-600/0 group-hover:from-purple-500/10 group-hover:via-purple-500/5 group-hover:to-purple-600/10 transition-all duration-500 rounded-2xl"></div>
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-purple-400 via-purple-500 to-purple-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
        <div class="relative flex flex-col items-center justify-center space-y-3 text-center">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg group-hover:shadow-purple-500/30 group-hover:scale-110 transition-all duration-300">
            <FontAwesomeIcon :icon="['fas', 'truck']" class="h-7 w-7 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-purple-600 transition-colors">
              {{ n(proveedoresCount) }}
            </h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Proveedores</p>
          </div>
          <p class="text-xs text-gray-400 bg-purple-50 px-3 py-1 rounded-full">
            {{ n(proveedoresPedidosPendientesCount) }} pedidos pend.
          </p>
        </div>
      </PanLink>

      <!-- Citas -->
      <PanLink
        :href="citasHref"
        class="group relative bg-white/90 backdrop-blur-sm p-6 rounded-2xl shadow-md border border-gray-100 transition-all duration-500 hover:shadow-xl hover:-translate-y-2 overflow-hidden"
        aria-label="Ir a citas"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/0 via-amber-500/0 to-orange-600/0 group-hover:from-amber-500/10 group-hover:via-amber-500/5 group-hover:to-orange-600/10 transition-all duration-500 rounded-2xl"></div>
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
        <div class="relative flex flex-col items-center justify-center space-y-3 text-center">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg group-hover:shadow-amber-500/30 group-hover:scale-110 transition-all duration-300">
            <FontAwesomeIcon :icon="['fas', 'calendar-alt']" class="h-7 w-7 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-amber-600 transition-colors">
              {{ n(citasCount) }}
            </h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Citas</p>
          </div>
          <p class="text-xs text-gray-400 bg-amber-50 px-3 py-1 rounded-full" :class="{ 'text-amber-600 font-medium': citasHoyCount > 0 }">
            {{ citasHoyCount > 0 ? `📅 ${n(citasHoyCount)} hoy` : 'Sin citas hoy' }}
          </p>
        </div>
      </PanLink>

      <!-- Mantenimientos -->
      <PanLink
        :href="mantenimientosHref"
        class="group relative bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm p-6 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 transition-all duration-500 hover:shadow-xl hover:-translate-y-2 overflow-hidden"
        aria-label="Ir a mantenimientos"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-rose-500/0 via-rose-500/0 to-rose-600/0 group-hover:from-rose-500/10 group-hover:via-rose-500/5 group-hover:to-rose-600/10 transition-all duration-500 rounded-2xl"></div>
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-400 via-rose-500 to-rose-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500"></div>
        <div class="relative flex flex-col items-center justify-center space-y-3 text-center">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-400 to-rose-600 flex items-center justify-center shadow-lg group-hover:shadow-rose-500/30 group-hover:scale-110 transition-all duration-300">
            <FontAwesomeIcon :icon="['fas', 'wrench']" class="h-7 w-7 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white group-hover:text-rose-600 transition-colors">
              {{ n(mantenimientosCount) }}
            </h2>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Mantenimientos</p>
          </div>
          <p class="text-xs px-3 py-1 rounded-full" :class="{
            'text-red-600 bg-red-50 font-medium animate-pulse': mantenimientosVencidosCount > 0,
            'text-orange-600 bg-orange-50': mantenimientosCriticosCount > 0 && mantenimientosVencidosCount === 0,
            'text-green-600 bg-green-50': mantenimientosVencidosCount === 0 && mantenimientosCriticosCount === 0
          }">
            <span v-if="mantenimientosVencidosCount > 0">⚠️ {{ n(mantenimientosVencidosCount) }} vencidos</span>
            <span v-else-if="mantenimientosCriticosCount > 0">⚡ {{ n(mantenimientosCriticosCount) }} críticos</span>
            <span v-else>✅ Al día</span>
          </p>
        </div>
      </PanLink>
    </div>

    <!-- Sección de Alertas -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- Alerta de Stock Bajo -->
      <div
        v-if="productosBajoStockNombresSafe.length > 0"
        class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border-l-8 border-red-500 flex flex-col justify-between items-start text-left transition-colors"
      >
        <div class="w-full">
          <div class="flex items-center mb-4">
            <FontAwesomeIcon :icon="['fas', 'exclamation-triangle']" class="h-8 w-8 text-red-600 mr-3" />
            <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white">Alerta de Stock Bajo</h3>
          </div>
          <p class="text-base text-gray-700 dark:text-gray-300 mb-4 transition-colors">
            Actualmente tienes
            <strong>{{ n(productosBajoStockNombresSafe.length) }} producto(s) con stock críticamente bajo.</strong>
            Considera reponerlos pronto para evitar interrupciones.
          </p>
          <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-2 transition-colors">Productos afectados:</h4>
          <ul class="text-gray-700 dark:text-gray-300 space-y-1 list-none transition-colors">
            <li
              v-for="(productoNombre, i) in productosBajoStockNombresSafe"
              :key="`low-${i}-${productoNombre}`"
              class="text-base"
            >
              {{ productoNombre }}
            </li>
          </ul>
        </div>
        <PanLink
          :href="productosLowHref"
          class="mt-6 px-6 py-2 bg-red-500 text-white font-semibold rounded-lg shadow hover:bg-red-600 transition-colors duration-300 transform hover:scale-105 list-none"
          aria-label="Gestionar inventario bajo en stock"
        >
          Gestionar Inventario
          <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="ml-2" />
        </PanLink>
      </div>

      <!-- Alerta de Órdenes de Compra Pendientes -->
      <div
        v-if="proveedoresPedidosPendientesCount > 0"
        class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border-l-8 border-amber-500 flex flex-col justify-between items-start text-left transition-colors"
      >
        <div class="w-full">
          <div class="flex items-center mb-4">
            <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="h-8 w-8 text-amber-600 mr-3" />
            <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white">Órdenes de Compra Pendientes</h3>
          </div>
          <p class="text-base text-gray-700 dark:text-gray-300 mb-4 transition-colors">
            Tienes <strong>{{ n(proveedoresPedidosPendientesCount) }} orden(es) de compra pendientes</strong> con proveedores.
          </p>
          <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-2 transition-colors">Detalles de órdenes:</h4>
          <ul class="text-gray-700 dark:text-gray-300 space-y-2 list-none transition-colors">
            <li
              v-for="orden in ordenesPendientesDetallesSafe"
              :key="`oc-${orden.id ?? orden.proveedor ?? Math.random()}`"
              class="text-base bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md transition-colors"
            >
              <div class="font-medium">{{ orden.proveedor ?? 'Proveedor N/D' }}</div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Prioridad: <span :class="getPrioridadClass(orden.prioridad)">{{ orden.prioridad ?? 'N/D' }}</span></div>
              <div class="text-sm text-gray-600 dark:text-gray-400">Fecha esperada: {{ orden.fecha_esperada ?? 'N/D' }}</div>
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
          class="mt-6 px-6 py-2 bg-amber-500 text-white font-semibold rounded-lg shadow hover:bg-amber-600 transition-colors duration-300 transform hover:scale-105 list-none"
          aria-label="Ver órdenes de compra pendientes"
        >
          Ver Órdenes Pendientes
          <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="ml-2" />
        </PanLink>
        </div>

        <!-- Alerta de Órdenes de Compra Enviadas -->
        <div
          v-if="ordenesEnviadasDetallesSafe.length > 0"
          class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border-l-8 border-green-500 flex flex-col justify-between items-start text-left transition-colors"
        >
          <div class="w-full">
            <div class="flex items-center mb-4">
              <FontAwesomeIcon :icon="['fas', 'paper-plane']" class="h-8 w-8 text-green-600 mr-3" />
              <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white">Órdenes de Compra Enviadas</h3>
            </div>
            <p class="text-base text-gray-700 dark:text-gray-300 mb-4 transition-colors">
              Tienes <strong>{{ n(ordenesEnviadasDetallesSafe.length) }} orden(es) de compra enviada(s)</strong> a proveedores esperando confirmación.
            </p>

            <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-2 transition-colors">Últimas órdenes enviadas:</h4>
            <ul class="text-gray-700 dark:text-gray-300 space-y-2 list-none transition-colors">
              <li
                v-for="orden in ordenesEnviadasDetallesSafe"
                :key="`env-${orden.id ?? orden.proveedor ?? Math.random()}`"
                class="text-base bg-gray-50 dark:bg-gray-700/50 p-3 rounded-md transition-colors"
              >
                <div class="font-medium">{{ orden.proveedor ?? 'Proveedor N/D' }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Total: ${{ orden.total ?? 'N/D' }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Fecha envío: {{ orden.fecha_envio ?? 'N/D' }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Fecha esperada: {{ orden.fecha_esperada ?? 'N/D' }}</div>
              </li>
            </ul>
          </div>
          <PanLink
            :href="ordenesEnviadasHref"
            class="mt-6 px-6 py-2 bg-green-500 text-white font-semibold rounded-lg shadow hover:bg-green-600 transition-colors duration-300 transform hover:scale-105 list-none"
            aria-label="Ver órdenes de compra enviadas"
          >
            Ver Órdenes Enviadas
            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="ml-2" />
          </PanLink>
        </div>

        <!-- Alerta de Mantenimientos Críticos -->
        <div
          v-if="mantenimientosVencidosCount > 0 || mantenimientosCriticosCount > 0"
          class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border-l-8 border-red-500 flex flex-col justify-between items-start text-left transition-colors"
        >
          <div class="w-full">
            <div class="flex items-center mb-4">
              <FontAwesomeIcon :icon="['fas', 'wrench']" class="h-8 w-8 text-red-600 mr-3" />
              <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white">Mantenimientos Urgentes</h3>
            </div>
            <p class="text-base text-gray-700 dark:text-gray-300 mb-4 transition-colors">
              <span v-if="mantenimientosVencidosCount > 0" class="text-red-600 dark:text-red-400 font-bold">
                ¡ATENCIÓN! {{ n(mantenimientosVencidosCount) }} mantenimiento(s) vencido(s)
              </span>
              <span v-if="mantenimientosCriticosCount > 0" class="text-orange-600 dark:text-orange-400 font-bold">
                {{ n(mantenimientosCriticosCount) }} mantenimiento(s) crítico(s) requieren atención inmediata
              </span>
            </p>

            <div v-if="mantenimientosCriticosDetallesSafe.length > 0" class="space-y-2 transition-colors">
              <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200 mb-2">Mantenimientos críticos:</h4>
              <ul class="text-gray-700 dark:text-gray-300 space-y-2 list-none transition-colors">
                <li
                  v-for="mantenimiento in mantenimientosCriticosDetallesSafe.slice(0, 3)"
                  :key="`mantenimiento-${mantenimiento.id ?? Math.random()}`"
                  class="text-base bg-red-50 dark:bg-red-900/20 p-3 rounded-md border-l-4 border-red-500 transition-colors"
                >
                  <div class="font-medium">{{ mantenimiento.carro?.marca }} {{ mantenimiento.carro?.modelo }}</div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">{{ mantenimiento.tipo }}</div>
                  <div class="text-sm text-red-600 dark:text-red-400 font-medium whitespace-nowrap">
                    {{ mantenimiento.dias_restantes < 0 ? `${Math.abs(mantenimiento.dias_restantes)} días vencido` : 'Próximo a vencer' }}
                  </div>
                </li>
              </ul>
              <div v-if="mantenimientosCriticosDetallesSafe.length > 3" class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Y {{ n(mantenimientosCriticosDetallesSafe.length - 3) }} más...
              </div>
            </div>
          </div>
          <PanLink
            :href="mantenimientosHref"
            class="mt-6 px-6 py-2 bg-red-500 text-white font-semibold rounded-lg shadow hover:bg-red-600 transition-colors duration-300 transform hover:scale-105 list-none"
            aria-label="Gestionar mantenimientos urgentes"
          >
            Gestionar Mantenimientos
            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="ml-2" />
          </PanLink>
        </div>
      </div>

    <!-- Citas activas del día de hoy -->
    <div
      v-if="citasHoyDetallesSafe.length > 0"
      class="mt-8 bg-white dark:bg-gray-800/90 p-6 rounded-2xl shadow-lg border-l-8 border-blue-500 transition-colors"
    >
      <div class="flex items-center mb-4 transition-colors">
        <FontAwesomeIcon :icon="['fas', 'calendar-alt']" class="h-8 w-8 text-blue-600 mr-3" />
        <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white transition-colors">Citas activas del día de hoy</h3>
      </div>
      <p class="text-base text-gray-700 dark:text-gray-300 mb-4 transition-colors">
        Tienes <strong>{{ n(citasHoyDetallesSafe.length) }} cita(s) activa(s)</strong> programadas para hoy (en proceso y pendientes).
      </p>
      <ul class="space-y-2 transition-colors">
        <li
          v-for="cita in citasHoyDetallesSafe"
          :key="`cita-${cita.id ?? cita.titulo ?? Math.random()}`"
          class="flex items-center justify-between text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-md shadow-sm border-l-4 transition-colors"
          :class="cita.estado === 'en_proceso' ? 'border-l-blue-500' : 'border-l-yellow-500'"
        >
          <div class="flex flex-col text-left flex-1 transition-colors">
            <div class="font-semibold text-lg text-gray-900 dark:text-white mb-1 transition-colors">
              {{ cita.titulo ?? 'Sin título' }}
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-400 transition-colors">Cliente: {{ cita.cliente ?? 'N/D' }}</div>
            <div class="text-sm text-gray-700 dark:text-gray-400 transition-colors">Técnico: {{ cita.tecnico ?? 'N/D' }}</div>
            <div class="mt-2 transition-colors">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="cita.estado === 'en_proceso' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300'"
              >
                <FontAwesomeIcon
                  :icon="cita.estado === 'en_proceso' ? ['fas', 'cog'] : ['fas', 'clock']"
                  class="w-3 h-3 mr-1"
                />
                {{ cita.estado_label ?? cita.estado }}
              </span>
            </div>
          </div>
          <div class="text-right">
            <div class="text-base font-medium text-blue-600 mb-1">
              {{ cita.hora ?? '—' }}
            </div>
            <div class="text-xs text-gray-400 dark:text-gray-500">
              Hoy
            </div>
          </div>
        </li>
      </ul>
      <PanLink
        :href="citasHref"
        class="mt-6 inline-block px-5 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow hover:bg-blue-600 transition-colors"
        aria-label="Ver todas las citas"
      >
        Ver todas las citas
        <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="ml-2" />
      </PanLink>
    </div>

    <!-- Mis Tareas Pendientes -->
    <div
      v-if="tareasPendientesSafe.total > 0"
      class="mt-8 bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-lg border-l-8 border-purple-500 transition-colors"
    >
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
          <FontAwesomeIcon :icon="['fas', 'tasks']" class="h-8 w-8 text-purple-600 mr-3" />
          <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white transition-colors">Mis Tareas Pendientes</h3>
        </div>
        <div class="flex items-center gap-2">
          <span v-if="tareasPendientesSafe.en_proceso > 0" class="bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-2 py-1 rounded-full text-xs font-medium transition-colors">
            {{ tareasPendientesSafe.en_proceso }} en proceso
          </span>
          <span v-if="tareasPendientesSafe.pendientes > 0" class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 px-2 py-1 rounded-full text-xs font-medium transition-colors">
            {{ tareasPendientesSafe.pendientes }} pendientes
          </span>
        </div>
      </div>
      <p class="text-base text-gray-700 dark:text-gray-300 mb-4 transition-colors">
        Tienes <strong>{{ n(tareasPendientesSafe.total) }} tarea(s)</strong> asignadas que requieren tu atención.
      </p>
      <ul class="space-y-2 transition-colors">
        <li
          v-for="tarea in tareasPendientesSafe.tareas"
          :key="`tarea-${tarea.id}`"
          class="flex items-center justify-between text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-md shadow-sm border-l-4 transition-colors"
          :class="{
            'border-l-blue-500': tarea.estado === 'en_proceso',
            'border-l-yellow-500': tarea.estado === 'pendiente',
            'border-l-red-500': tarea.vencida
          }"
        >
          <div class="flex flex-col text-left flex-1 transition-colors">
            <div class="font-semibold text-lg text-gray-900 dark:text-white mb-1 transition-colors">
              {{ tarea.titulo }}
            </div>
            <div v-if="tarea.descripcion" class="text-sm text-gray-600 dark:text-gray-400 mb-1 transition-colors">
              {{ tarea.descripcion }}
            </div>
            <div class="flex flex-wrap gap-2 text-xs text-gray-500 dark:text-gray-400 transition-colors">
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
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors"
                :class="tarea.estado === 'en_proceso' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300'"
              >
                {{ tarea.estado_label }}
              </span>
              <span
                v-if="tarea.vencida"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 transition-colors"
              >
                <FontAwesomeIcon :icon="['fas', 'exclamation-triangle']" class="mr-1" />
                Vencida
              </span>
              <span class="text-xs text-gray-500">
                Fecha: {{ tarea.fecha }}
              </span>
            </div>
          </div>
          <div class="flex flex-col items-end gap-2 ml-4">
            <button
              @click="completarTarea(tarea.id)"
              class="px-3 py-1.5 bg-green-500 text-white text-sm font-medium rounded-lg hover:bg-green-600 transition-colors flex items-center"
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
        href="/bitacora?estado=pendiente"
        class="mt-6 inline-block px-5 py-2 bg-purple-500 text-white font-semibold rounded-lg shadow hover:bg-purple-600 transition-colors"
        aria-label="Ver todas las tareas"
      >
        Ver todas las tareas
        <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="ml-2" />
      </PanLink>
    </div>

    <!-- Alertas de Vencimientos de Cuentas -->
    <div v-if="hayAlertasVencimientos" class="mt-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
        <FontAwesomeIcon :icon="['fas', 'bell']" class="h-6 w-6 text-amber-500 mr-3" />
        Alertas de Vencimientos
      </h2>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Cuentas por Pagar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors">
          <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
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
                 class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-red-800 dark:text-red-300 font-bold flex items-center transition-colors">
                  <span class="w-3 h-3 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                  VENCIDAS
                </span>
                <span class="text-red-600 dark:text-red-400 font-bold text-lg">${{ formatMonto(sumaMontos(alertasCuentasPagarSafe.vencidas)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasPagarSafe.vencidas.slice(0, 3)" :key="cuenta.id" 
                     class="flex justify-between text-sm text-red-700 dark:text-red-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.proveedor }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }}</span>
                </div>
                <div v-if="alertasCuentasPagarSafe.vencidas.length > 3" class="text-xs text-red-500 dark:text-red-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasPagarSafe.vencidas.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 1 Semana -->
            <div v-if="alertasCuentasPagarSafe.semana_count > 0"
                 class="bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-orange-800 dark:text-orange-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                  Próximos 7 días
                </span>
                <span class="text-orange-600 dark:text-orange-400 font-bold">${{ formatMonto(sumaMontos(alertasCuentasPagarSafe.semana)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasPagarSafe.semana.slice(0, 2)" :key="cuenta.id"
                     class="flex justify-between text-sm text-orange-700 dark:text-orange-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.proveedor }}</span>
                  <span class="whitespace-nowrap">{{ cuenta.fecha_vencimiento }}</span>
                </div>
              </div>
            </div>

            <!-- 15 días -->
            <div v-if="alertasCuentasPagarSafe.quincena_count > 0"
                 class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-yellow-800 dark:text-yellow-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                  En 15 días ({{ alertasCuentasPagarSafe.quincena_count }})
                </span>
                <span class="text-yellow-600 dark:text-yellow-400 font-bold">${{ formatMonto(sumaMontos(alertasCuentasPagarSafe.quincena)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasPagarSafe.quincena.slice(0, 3)" :key="cuenta.id"
                     class="flex justify-between text-sm text-yellow-700 dark:text-yellow-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.numero }} - {{ cuenta.proveedor }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }} ({{ cuenta.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasCuentasPagarSafe.quincena.length > 3" class="text-xs text-yellow-500 dark:text-yellow-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasPagarSafe.quincena.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 30 días -->
            <div v-if="alertasCuentasPagarSafe.mes_count > 0"
                 class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-blue-800 dark:text-blue-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                  En 30 días ({{ alertasCuentasPagarSafe.mes_count }})
                </span>
                <span class="text-blue-600 dark:text-blue-400 font-bold">${{ formatMonto(sumaMontos(alertasCuentasPagarSafe.mes)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasPagarSafe.mes.slice(0, 5)" :key="cuenta.id"
                     class="flex justify-between text-sm text-blue-700 dark:text-blue-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.numero }} - {{ cuenta.proveedor }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }} ({{ cuenta.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasCuentasPagarSafe.mes.length > 5" class="text-xs text-blue-500 dark:text-blue-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasPagarSafe.mes.length - 5 }} más...
                </div>
              </div>
            </div>

            <!-- Sin alertas -->
            <div v-if="totalCuentasPagar === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 transition-colors">
              <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-10 w-10 text-green-400 mb-2" />
              <p class="font-medium">¡Todo al día!</p>
            </div>
          </div>

          <div class="px-4 pb-4">
            <PanLink href="/cuentas-por-pagar" 
                     class="block w-full text-center px-4 py-2 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition-colors">
              Ver Cuentas por Pagar
            </PanLink>
          </div>
        </div>

        <!-- Cuentas por Cobrar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors">
          <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-4">
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
                 class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-red-800 dark:text-red-300 font-bold flex items-center transition-colors">
                  <span class="w-3 h-3 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                  VENCIDAS
                </span>
                <span class="text-red-600 dark:text-red-400 font-bold text-lg">${{ formatMonto(sumaMontos(alertasCuentasCobrarSafe.vencidas)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasCobrarSafe.vencidas.slice(0, 3)" :key="cuenta.id"
                     class="flex justify-between text-sm text-red-700 dark:text-red-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.cliente }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }}</span>
                </div>
                <div v-if="alertasCuentasCobrarSafe.vencidas.length > 3" class="text-xs text-red-500 dark:text-red-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasCobrarSafe.vencidas.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 1 Semana -->
            <div v-if="alertasCuentasCobrarSafe.semana_count > 0"
                 class="bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-orange-800 dark:text-orange-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                  Próximos 7 días
                </span>
                <span class="text-orange-600 dark:text-orange-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasCuentasCobrarSafe.semana)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasCobrarSafe.semana.slice(0, 2)" :key="cuenta.id"
                     class="flex justify-between text-sm text-orange-700 dark:text-orange-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.cliente }}</span>
                  <span class="whitespace-nowrap">{{ cuenta.fecha_vencimiento }}</span>
                </div>
              </div>
            </div>

            <!-- 15 días -->
            <div v-if="alertasCuentasCobrarSafe.quincena_count > 0"
                 class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-yellow-800 dark:text-yellow-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                  En 15 días ({{ alertasCuentasCobrarSafe.quincena_count }})
                </span>
                <span class="text-yellow-600 dark:text-yellow-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasCuentasCobrarSafe.quincena)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasCobrarSafe.quincena.slice(0, 3)" :key="cuenta.id"
                     class="flex justify-between text-sm text-yellow-700 dark:text-yellow-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.numero }} - {{ cuenta.cliente }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }} ({{ cuenta.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasCuentasCobrarSafe.quincena.length > 3" class="text-xs text-yellow-500 dark:text-yellow-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasCobrarSafe.quincena.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 30 días -->
            <div v-if="alertasCuentasCobrarSafe.mes_count > 0"
                 class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-blue-800 dark:text-blue-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                  En 30 días ({{ alertasCuentasCobrarSafe.mes_count }})
                </span>
                <span class="text-blue-600 dark:text-blue-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasCuentasCobrarSafe.mes)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="cuenta in alertasCuentasCobrarSafe.mes.slice(0, 5)" :key="cuenta.id"
                     class="flex justify-between text-sm text-blue-700 dark:text-blue-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ cuenta.numero }} - {{ cuenta.cliente }}</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(cuenta.monto_pendiente) }} ({{ cuenta.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasCuentasCobrarSafe.mes.length > 5" class="text-xs text-blue-500 dark:text-blue-400/80 mt-1 transition-colors">
                  +{{ alertasCuentasCobrarSafe.mes.length - 5 }} más...
                </div>
              </div>
            </div>

            <!-- Sin alertas -->
            <div v-if="totalCuentasCobrar === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 transition-colors">
              <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-10 w-10 text-green-400 mb-2" />
              <p class="font-medium">¡Todo al día!</p>
            </div>
          </div>

          <div class="px-4 pb-4">
            <PanLink href="/cuentas-por-cobrar"
                     class="block w-full text-center px-4 py-2 bg-emerald-500 text-white font-semibold rounded-lg hover:bg-emerald-600 transition-colors">
              Ver Cuentas por Cobrar
            </PanLink>
          </div>
        </div>
        
        <!-- Préstamos -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors">
          <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4">
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
                 class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-red-800 dark:text-red-300 font-bold flex items-center transition-colors">
                  <span class="w-3 h-3 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                  VENCIDAS
                </span>
                <span class="text-red-600 dark:text-red-400 font-bold text-lg transition-colors">${{ formatMonto(sumaMontos(alertasPrestamosSafe.vencidas)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="pago in alertasPrestamosSafe.vencidas.slice(0, 3)" :key="pago.id"
                     class="flex justify-between text-sm text-red-700 dark:text-red-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ pago.cliente }} (Pago {{ pago.numero_pago }})</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(pago.monto_pendiente) }}</span>
                </div>
                <div v-if="alertasPrestamosSafe.vencidas.length > 3" class="text-xs text-red-500 dark:text-red-400/80 mt-1 transition-colors">
                  +{{ alertasPrestamosSafe.vencidas.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 1 Semana -->
            <div v-if="alertasPrestamosSafe.semana_count > 0"
                 class="bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-orange-800 dark:text-orange-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                  Próximos 7 días
                </span>
                <span class="text-orange-600 dark:text-orange-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasPrestamosSafe.semana)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="pago in alertasPrestamosSafe.semana.slice(0, 2)" :key="pago.id"
                     class="flex justify-between text-sm text-orange-700 dark:text-orange-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ pago.cliente }}</span>
                  <span class="whitespace-nowrap">{{ pago.fecha_vencimiento }}</span>
                </div>
              </div>
            </div>

            <!-- 15 días -->
            <div v-if="alertasPrestamosSafe.quincena_count > 0"
                 class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-yellow-800 dark:text-yellow-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                  En 15 días ({{ alertasPrestamosSafe.quincena_count }})
                </span>
                <span class="text-yellow-600 dark:text-yellow-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasPrestamosSafe.quincena)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="pago in alertasPrestamosSafe.quincena.slice(0, 3)" :key="pago.id"
                     class="flex justify-between text-sm text-yellow-700 dark:text-yellow-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ pago.cliente }} (Pago {{ pago.numero_pago }})</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(pago.monto_pendiente) }} ({{ pago.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasPrestamosSafe.quincena.length > 3" class="text-xs text-yellow-500 dark:text-yellow-400/80 mt-1 transition-colors">
                  +{{ alertasPrestamosSafe.quincena.length - 3 }} más...
                </div>
              </div>
            </div>

            <!-- 30 días -->
            <div v-if="alertasPrestamosSafe.mes_count > 0"
                 class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 p-4 rounded-r-lg transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-blue-800 dark:text-blue-300 font-semibold flex items-center transition-colors">
                  <span class="w-2 h-2 bg-blue-400 rounded-full mr-2"></span>
                  En 30 días ({{ alertasPrestamosSafe.mes_count }})
                </span>
                <span class="text-blue-600 dark:text-blue-400 font-bold transition-colors">${{ formatMonto(sumaMontos(alertasPrestamosSafe.mes)) }}</span>
              </div>
              <div class="space-y-1">
                <div v-for="pago in alertasPrestamosSafe.mes.slice(0, 5)" :key="pago.id"
                     class="flex justify-between text-sm text-blue-700 dark:text-blue-400 transition-colors">
                  <span class="truncate flex-1 mr-2">{{ pago.cliente }} (Pago {{ pago.numero_pago }})</span>
                  <span class="font-medium whitespace-nowrap">${{ formatMonto(pago.monto_pendiente) }} ({{ pago.fecha_vencimiento }})</span>
                </div>
                <div v-if="alertasPrestamosSafe.mes.length > 5" class="text-xs text-blue-500 dark:text-blue-400/80 mt-1 transition-colors">
                  +{{ alertasPrestamosSafe.mes.length - 5 }} más...
                </div>
              </div>
            </div>

            <!-- Sin alertas -->
            <div v-if="totalPrestamos === 0" class="text-center py-6 text-gray-500 dark:text-gray-400 transition-colors">
              <FontAwesomeIcon :icon="['fas', 'check-circle']" class="h-10 w-10 text-green-400 mb-2" />
              <p class="font-medium">¡Todo al día!</p>
            </div>
          </div>

          <div class="px-4 pb-4">
            <PanLink href="/pagos"
                     class="block w-full text-center px-4 py-2 bg-amber-500 text-white font-semibold rounded-lg hover:bg-amber-500 transition-colors">
              Ver Pagos de Préstamos
            </PanLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Sección de Gráficos - Premium Analytics -->
    <div class="mt-10 mb-10">
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg">
            <FontAwesomeIcon :icon="['fas', 'chart-pie']" class="h-6 w-6 text-white" />
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors">Análisis y Tendencias</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm transition-colors">Visualiza el rendimiento de tu negocio</p>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Gráfico de Ventas Mensuales -->
        <div class="group relative bg-white/60 dark:bg-gray-800/60 backdrop-blur-md p-8 rounded-3xl shadow-xl border border-white/50 dark:border-gray-700/50 overflow-hidden hover:shadow-xl transition-all duration-500">
          <!-- Decorative gradient -->
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400/20 to-blue-600/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
          <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-blue-400/10 to-transparent rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>
          
          <div class="relative">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg">
                  <FontAwesomeIcon :icon="['fas', 'chart-line']" class="h-5 w-5 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white transition-colors">Ventas Mensuales</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">Últimos 6 meses</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-blue-50 text-blue-600 text-xs font-medium rounded-full">Tendencia</span>
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
            <div v-else class="h-72 flex items-center justify-center bg-gradient-to-br from-gray-50 dark:from-gray-700/50 to-blue-50/30 dark:to-blue-900/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 transition-colors">
              <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mx-auto mb-4">
                  <FontAwesomeIcon :icon="['fas', 'chart-line']" class="h-8 w-8 text-blue-400 dark:text-blue-300" />
                </div>
                <p class="text-gray-600 dark:text-gray-300 font-semibold mb-2">Sin datos de ventas</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Registra tus primeras ventas</p>
                <PanLink :href="route('ventas.create')" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-300">
                  <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                  Nueva Venta
                </PanLink>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico de Productos Más Vendidos -->
        <div class="group relative bg-white/60 dark:bg-gray-800/60 backdrop-blur-md p-8 rounded-3xl shadow-xl border border-white/50 dark:border-gray-700/50 overflow-hidden hover:shadow-xl transition-all duration-500">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-emerald-400/20 to-emerald-600/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
          <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-emerald-400/10 to-transparent rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>
          
          <div class="relative">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg">
                  <FontAwesomeIcon :icon="['fas', 'box']" class="h-5 w-5 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white transition-colors">Productos Más Vendidos</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">Últimos 30 días</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-xs font-medium rounded-full">Top 5</span>
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
            <div v-else class="h-72 flex items-center justify-center bg-gradient-to-br from-gray-50 dark:from-gray-700/50 to-emerald-50/30 dark:to-emerald-900/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 transition-colors">
              <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mx-auto mb-4">
                  <FontAwesomeIcon :icon="['fas', 'box']" class="h-8 w-8 text-emerald-400 dark:text-emerald-300" />
                </div>
                <p class="text-gray-600 dark:text-gray-300 font-semibold mb-2">Sin ventas recientes</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Registra ventas para ver el ranking</p>
                <PanLink :href="route('ventas.create')" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-300">
                  <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                  Nueva Venta
                </PanLink>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico de Estados de Órdenes -->
        <div class="group relative bg-white/60 dark:bg-gray-800/60 backdrop-blur-md p-8 rounded-3xl shadow-xl border border-white/50 dark:border-gray-700/50 overflow-hidden hover:shadow-xl transition-all duration-500">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-400/20 to-purple-600/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
          <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-purple-400/10 to-transparent rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>
          
          <div class="relative">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg">
                  <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="h-5 w-5 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white transition-colors">Órdenes de Compra</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">Por estado</p>
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
            <div v-else class="h-72 flex items-center justify-center bg-gradient-to-br from-gray-50 dark:from-gray-700/50 to-purple-50/30 dark:to-purple-900/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 transition-colors">
              <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mx-auto mb-4">
                  <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="h-8 w-8 text-purple-400 dark:text-purple-300" />
                </div>
                <p class="text-gray-600 dark:text-gray-300 font-semibold mb-2">Sin órdenes de compra</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Gestiona tus proveedores</p>
                <PanLink :href="route('ordenescompra.create')" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-purple-500 to-purple-600 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-300">
                  <FontAwesomeIcon :icon="['fas', 'plus']" class="mr-2" />
                  Crear Orden
                </PanLink>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico de Crecimiento de Clientes -->
        <div class="group relative bg-white/60 dark:bg-gray-800/60 backdrop-blur-md p-8 rounded-3xl shadow-xl border border-white/50 dark:border-gray-700/50 overflow-hidden hover:shadow-xl transition-all duration-500">
          <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-amber-400/20 to-orange-500/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
          <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-amber-400/10 to-transparent rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>
          
          <div class="relative">
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg">
                  <FontAwesomeIcon :icon="['fas', 'users']" class="h-5 w-5 text-white" />
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white transition-colors">Crecimiento de Clientes</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 transition-colors">Últimos 6 meses</p>
                </div>
              </div>
              <span class="px-3 py-1 bg-amber-50 text-amber-600 text-xs font-medium rounded-full">Evolución</span>
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
            <div v-else class="h-72 flex items-center justify-center bg-gradient-to-br from-gray-50 dark:from-gray-700/50 to-amber-50/30 dark:to-orange-900/20 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 transition-colors">
              <div class="text-center">
                <div class="w-16 h-16 rounded-2xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mx-auto mb-4">
                  <FontAwesomeIcon :icon="['fas', 'users']" class="h-8 w-8 text-amber-400 dark:text-amber-300" />
                </div>
                <p class="text-gray-600 dark:text-gray-300 font-semibold mb-2">Sin registro de clientes</p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Agrega clientes para visualizar el crecimiento</p>
                <PanLink :href="route('clientes.create')" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-sm font-semibold rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-300">
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
import { computed, ref, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()

const isDark = ref(false)

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
  return props.alertasCuentasPagar.vencidas_count +
         props.alertasCuentasPagar.semana_count +
         props.alertasCuentasPagar.quincena_count +
         props.alertasCuentasPagar.mes_count
})

const totalCuentasCobrar = computed(() => {
  return props.alertasCuentasCobrar.vencidas_count +
         props.alertasCuentasCobrar.semana_count +
         props.alertasCuentasCobrar.quincena_count +
         props.alertasCuentasCobrar.mes_count
})

const totalPrestamos = computed(() => {
  return props.alertasPrestamos.vencidas_count +
         props.alertasPrestamos.semana_count +
         props.alertasPrestamos.quincena_count +
         props.alertasPrestamos.mes_count
})

const hayAlertasVencimientos = computed(() => {
    return totalCuentasPagar.value > 0 || totalCuentasCobrar.value > 0 || totalPrestamos.value > 0
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
  tareasPendientes: { type: Object, default: () => ({ tareas: [], total: 0, en_proceso: 0, pendientes: 0 }) }
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
      return 'text-red-600 dark:text-red-400 font-semibold'
    case 'alta':
      return 'text-orange-600 dark:text-orange-400 font-medium'
    case 'media':
      return 'text-yellow-600 dark:text-yellow-400'
    case 'baja':
      return 'text-green-600 dark:text-green-400'
    default:
      return 'text-gray-600 dark:text-gray-400'
  }
}

const getRetrasoClass = (diasRetraso) => {
  if (diasRetraso === 0) {
    return 'text-green-600 dark:text-green-400'
  } else if (diasRetraso > 0) {
    return 'text-red-600 dark:text-red-400 font-semibold'
  }
  return 'text-gray-600 dark:text-gray-400'
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

