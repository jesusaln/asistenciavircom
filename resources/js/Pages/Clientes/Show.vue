<template>
  <Head :title="`Cliente: ${cliente.nombre_razon_social}`" />
  <div class="w-full p-4" :style="cssVars">
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-xl dark:shadow-none rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
      <!-- Header moderno con gradiente -->
      <div class="p-6 text-white" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold">{{ cliente.nombre_razon_social }}</h1>
              <p class="text-white/80 text-sm mt-1">Cliente #{{ cliente.id }}</p>
              <div class="mt-3 flex gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm" v-if="cliente.activo">
                  <span class="w-2 h-2 bg-green-400 rounded-full mr-1.5"></span>
                  Activo
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm" v-else>
                  <span class="w-2 h-2 bg-gray-400 rounded-full mr-1.5"></span>
                  Inactivo
                </span>
                <span v-if="cliente.credito_activo" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 backdrop-blur-sm">
                  <span class="w-2 h-2 bg-purple-400 rounded-full mr-1.5"></span>
                  Crédito Activo
                </span>
              </div>
            </div>
          </div>
          <div class="flex flex-col items-end space-y-3">
            <div class="flex space-x-2">
              <Link
                :href="route('clientes.edit', cliente.id)"
                class="inline-flex items-center px-4 py-2 text-sm font-medium bg-white/20 backdrop-blur-sm text-white border border-white/30 rounded-xl hover:bg-white/30 transition-all duration-200"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Editar
              </Link>
              <Link
                :href="route('clientes.index')"
                class="inline-flex items-center px-4 py-2 text-sm font-medium bg-white/20 backdrop-blur-sm text-white border border-white/30 rounded-xl hover:bg-white/30 transition-all duration-200"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Regresar
              </Link>
            </div>
            <!-- Quick Actions -->
            <div class="flex space-x-2">
              <Link
                :href="route('ventas.create', { cliente_id: cliente.id })"
                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-green-500 text-white rounded-lg hover:bg-green-600 shadow-lg transition-all duration-200"
              >
                + Venta
              </Link>
              <Link
                :href="route('cotizaciones.create', { cliente_id: cliente.id })"
                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 shadow-lg transition-all duration-200"
              >
                + Cotización
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Contenido principal -->
      <div class="p-6">

      <!-- Mensaje de éxito/error -->
      <div v-if="flash.success" class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md">
        <p class="text-sm text-green-800 dark:text-green-300">{{ flash.success }}</p>
      </div>
      <div v-if="flash.error" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md">
        <p class="text-sm text-red-800 dark:text-red-300">{{ flash.error }}</p>
      </div>

      <!-- Información General -->
      <section class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Información General</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre/Razón Social</label>
            <p class="text-gray-900 dark:text-gray-100 font-medium">{{ cliente.nombre_razon_social }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
            <p class="text-gray-900 dark:text-gray-100">{{ cliente.email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Teléfono</label>
            <p class="text-gray-900 dark:text-gray-100" v-if="cliente.telefono">{{ cliente.telefono }}</p>
            <p class="text-gray-500 dark:text-gray-400 italic" v-else>Sin teléfono</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Persona</label>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="cliente.tipo_persona === 'fisica' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300'">
              {{ cliente.tipo_persona_nombre }}
            </span>
          </div>
          <div v-if="cliente.notas">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notas</label>
            <p class="text-gray-900 dark:text-gray-100 whitespace-pre-wrap">{{ cliente.notas }}</p>
          </div>
        </div>
      </section>

      <!-- Información Fiscal -->
      <section class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Información Fiscal</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">RFC</label>
            <p class="text-gray-900 dark:text-gray-100 font-mono bg-white dark:bg-gray-700 px-2 py-1 rounded inline-block">{{ cliente.rfc }}</p>
          </div>
          <div v-if="cliente.curp">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CURP</label>
            <p class="text-gray-900 dark:text-gray-100 font-mono">{{ cliente.curp }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Régimen Fiscal</label>
            <p class="text-gray-900 dark:text-gray-100">{{ cliente.regimen_fiscal }} - {{ cliente.regimen_fiscal_nombre }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uso CFDI</label>
            <p class="text-gray-900 dark:text-gray-100">{{ cliente.uso_cfdi }} - {{ cliente.uso_cfdi_nombre }}</p>
          </div>
          <div v-if="cliente.cfdi_default_use">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Uso CFDI Predeterminado</label>
            <p class="text-gray-900 dark:text-gray-100">{{ cliente.cfdi_default_use }}</p>
          </div>
          <div v-if="cliente.payment_form_default">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Forma de Pago Predeterminada</label>
            <p class="text-gray-900 dark:text-gray-100">{{ cliente.payment_form_default }}</p>
          </div>
        </div>
      </section>

      <!-- Estado de Cuenta (Crédito) -->
      <section class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6" v-if="cliente.credito_activo">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: colors.principal }"></span>
          Estado de Cuenta
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="p-4 rounded-xl border" :style="{ backgroundColor: `${colors.principal}10`, borderColor: `${colors.principal}30` }">
            <h3 class="text-xs font-semibold uppercase tracking-wider" :style="{ color: colors.principal }">Límite de Crédito</h3>
            <p class="text-2xl font-bold mt-2" :style="{ color: colors.secundario }">
              ${{ Number(cliente.limite_credito).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
            </p>
          </div>
          <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-xl border border-purple-200 dark:border-purple-700">
            <h3 class="text-xs font-semibold text-purple-700 dark:text-purple-300 uppercase tracking-wider">Días de Crédito</h3>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100 mt-2">
              {{ cliente.dias_credito || 0 }} <span class="text-sm font-normal">días</span>
            </p>
          </div>
          <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-xl border border-yellow-200 dark:border-yellow-700">
            <h3 class="text-xs font-semibold text-yellow-700 dark:text-yellow-300 uppercase tracking-wider">Saldo Utilizado</h3>
            <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100 mt-2">
              ${{ Number(cliente.saldo_pendiente).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
            </p>
          </div>
          <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl border border-green-200 dark:border-green-700">
            <h3 class="text-xs font-semibold text-green-700 dark:text-green-300 uppercase tracking-wider">Crédito Disponible</h3>
            <p class="text-2xl font-bold text-green-900 dark:text-green-100 mt-2">
              ${{ Number(cliente.credito_disponible).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
            </p>
          </div>
        </div>
        
        <!-- Barra de Progreso -->
        <div class="mt-4">
          <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
            <span>Uso de Crédito</span>
            <span>{{ Math.min(100, Math.round((cliente.saldo_pendiente / (cliente.limite_credito || 1)) * 100)) }}%</span>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
            <div 
              class="h-3 rounded-full duration-300 transition-all" 
              :style="{
                width: `${Math.min(100, (cliente.saldo_pendiente / (cliente.limite_credito || 1)) * 100)}%`,
                background: (cliente.saldo_pendiente / (cliente.limite_credito || 1)) > 0.9 
                  ? 'linear-gradient(90deg, #ef4444 0%, #dc2626 100%)' 
                  : `linear-gradient(90deg, ${colors.principal} 0%, ${colors.secundario} 100%)`
              }"
            ></div>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" v-if="(cliente.saldo_pendiente / (cliente.limite_credito || 1)) > 0.9">
            <span class="text-red-500 dark:text-red-400 font-medium">¡Atención!</span> El cliente está próximo a exceder su límite de crédito o ya lo ha excedido.
          </p>
        </div>
      </section>

      <!-- Dirección -->
      <section class="border-b border-gray-200 dark:border-gray-700 pb-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Dirección</h2>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
               <div>
                  <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Calle y Números</h4>
                  <p class="text-base text-gray-900 dark:text-gray-100">
                    {{ cliente.calle || 'No especificada' }} 
                    {{ cliente.numero_exterior ? '#' + cliente.numero_exterior : '' }}
                    {{ cliente.numero_interior ? 'Int. ' + cliente.numero_interior : '' }}
                  </p>
               </div>
               <div>
                  <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Ubicación</h4>
                  <p class="text-base text-gray-900 dark:text-gray-100">
                    {{ cliente.colonia }}, CP {{ cliente.codigo_postal }}
                  </p>
                  <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ cliente.municipio }}, {{ cliente.estado_nombre || cliente.estado }}
                  </p>
               </div>
             </div>
        </div>
      </section>

      <!-- Estadísticas Relacionadas -->
      <section>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Módulos Relacionados</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
          <Link :href="route('cotizaciones.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-100 dark:border-gray-700 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-gray-100 dark:group-hover:bg-gray-700 text-center">
                <h3 class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Cots</h3>
                <p class="text-xl font-black text-gray-800 dark:text-gray-100">{{ cliente.cotizaciones_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('ventas.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-xl border border-blue-100 dark:border-blue-700 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 text-center">
                <h3 class="text-[10px] font-bold text-blue-400 dark:text-blue-300 uppercase tracking-wider mb-1">Ventas</h3>
                <p class="text-xl font-black text-blue-900 dark:text-blue-100">{{ cliente.ventas_count || 0 }}</p>
              </div>
          </Link>
          <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-xl border border-green-100 dark:border-green-700 text-center">
                <h3 class="text-[10px] font-bold text-green-400 dark:text-green-300 uppercase tracking-wider mb-1">Pedidos</h3>
                <p class="text-xl font-black text-green-900 dark:text-green-100">{{ cliente.pedidos_count || 0 }}</p>
          </div>
          <Link :href="route('soporte.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-red-50 dark:bg-red-900/20 p-3 rounded-xl border border-red-100 dark:border-red-700 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-red-100 dark:group-hover:bg-red-900/40 text-center">
                <h3 class="text-[10px] font-bold text-red-400 dark:text-red-300 uppercase tracking-wider mb-1">Tickets</h3>
                <p class="text-xl font-black text-red-900 dark:text-red-100">{{ cliente.tickets_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('citas.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded-xl border border-amber-100 dark:border-amber-700 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-amber-100 dark:group-hover:bg-amber-900/40 text-center">
                <h3 class="text-[10px] font-bold text-amber-400 dark:text-amber-300 uppercase tracking-wider mb-1">Citas</h3>
                <p class="text-xl font-black text-amber-900 dark:text-amber-100">{{ cliente.citas_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('polizas-servicio.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-700 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/40 text-center">
                <h3 class="text-[10px] font-bold text-indigo-400 dark:text-indigo-300 uppercase tracking-wider mb-1">Pólizas</h3>
                <p class="text-xl font-black text-indigo-900 dark:text-indigo-100">{{ cliente.polizas_count || 0 }}</p>
              </div>
          </Link>
           <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-xl border border-purple-100 dark:border-purple-700 text-center">
             <h3 class="text-[10px] font-bold text-purple-400 dark:text-purple-300 uppercase tracking-wider mb-1">Facturas</h3>
             <p class="text-xl font-black text-purple-900 dark:text-purple-100">{{ cliente.facturas_count || 0 }}</p>
          </div>
        </div>
      </section>

      <!-- Pólizas de Servicio -->
      <section class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6" v-if="polizas && polizas.length > 0">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pólizas de Servicio Vigentes</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
           <div v-for="poliza in polizas" :key="poliza.id" class="p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-700 rounded-xl">
              <div class="flex justify-between items-start">
                 <div>
                    <h3 class="font-bold text-indigo-900 dark:text-indigo-100">{{ poliza.plan?.nombre || 'Póliza Personalizada' }}</h3>
                    <p class="text-xs text-indigo-700 dark:text-indigo-300">Folio: {{ poliza.folio }}</p>
                 </div>
                 <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-[10px] font-bold rounded-full uppercase">
                    {{ poliza.estado }}
                 </span>
              </div>
              <div class="mt-3 text-sm text-indigo-800 dark:text-indigo-200 flex justify-between">
                 <span>Vence: {{ poliza.fecha_vencimiento ? new Date(poliza.fecha_vencimiento).toLocaleDateString() : 'N/A' }}</span>
                 <Link :href="route('polizas-servicio.show', poliza.id)" class="font-bold hover:underline">Detalles</Link>
              </div>
           </div>
        </div>
      </section>

      <!-- Soporte y Citas (Grid 2 columnas) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
          <!-- Últimos Tickets -->
          <section>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tickets de Soporte</h2>
                <Link :href="route('soporte.index', { cliente_id: cliente.id })" class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline">Ver todos</Link>
            </div>
            <div v-if="tickets && tickets.length > 0" class="space-y-3">
               <div v-for="ticket in tickets" :key="ticket.id" class="p-3 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                  <div class="flex justify-between items-start mb-1">
                     <Link :href="route('soporte.show', ticket.id)" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline">#{{ ticket.numero }} - {{ ticket.titulo }}</Link>
                     <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold uppercase', 
                        ticket.estado === 'abierto' ? 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300' :
                        ticket.estado === 'resuelto' ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300' :
                        'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                     ]">
                        {{ ticket.estado }}
                     </span>
                  </div>
                  <div class="flex justify-between items-center text-[11px] text-gray-500 dark:text-gray-400">
                     <span>{{ ticket.categoria?.nombre }}</span>
                     <span>{{ new Date(ticket.created_at).toLocaleDateString() }}</span>
                  </div>
               </div>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">No hay tickets registrados</p>
          </section>

          <!-- Próximas Citas -->
          <section>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Visitas Técnicas</h2>
                <Link :href="route('citas.index', { cliente_id: cliente.id })" class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline">Ver historial</Link>
            </div>
            <div v-if="citas && citas.length > 0" class="space-y-3">
               <div v-for="cita in citas" :key="cita.id" class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-700 rounded-xl shadow-sm">
                  <div class="flex justify-between items-start mb-1">
                     <Link :href="route('citas.show', cita.id)" class="text-sm font-bold text-amber-900 dark:text-amber-100 hover:underline">#{{ cita.id }} - {{ cita.tipo_servicio }}</Link>
                     <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold uppercase', 
                        cita.estado === 'completado' ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300' :
                        cita.estado === 'cancelado' ? 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300' :
                        'bg-amber-100 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300'
                     ]">
                        {{ cita.estado }}
                     </span>
                  </div>
                  <div class="flex justify-between items-center text-[11px] text-amber-800 dark:text-amber-200">
                     <span>Téc: {{ cita.tecnico?.name }}</span>
                     <span class="font-bold">{{ new Date(cita.fecha_hora).toLocaleDateString('es-MX', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'}) }}</span>
                  </div>
               </div>
            </div>
            <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">No hay citas programadas</p>
          </section>
      </div>

      <!-- Historial de Compras -->
      <section class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6" v-if="historialCompras && historialCompras.length > 0">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Historial de Ventas (Últimas 50)</h2>
          <Link :href="route('ventas.index', { cliente_id: cliente.id })" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
            Ver todas las ventas &rarr;
          </Link>
        </div>
        <div class="overflow-x-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm dark:shadow-none">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-white dark:bg-gray-800">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Folio</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Método Pago</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="compra in historialCompras" :key="compra.id" class="hover:bg-white dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">
                  <Link :href="route('ventas.show', compra.id)">{{ compra.numero_venta }}</Link>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ new Date(compra.fecha).toLocaleDateString('es-MX') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-semibold">
                  ${{ Number(compra.total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 capitalize">
                  {{ compra.metodo_pago }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                   <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="compra.pagado ? 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300'">
                    {{ compra.pagado ? 'Pagado' : 'Pendiente' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Expediente de Crédito -->
      <section class="border-t border-gray-200 pt-6 mt-6">
           <ExpedienteCredito :cliente="cliente" :documentos="cliente.documentos" />
      </section>

      <!-- Historial de Crédito -->
      <section class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6" v-if="historialCredito && historialCredito.length > 0">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Historial de Crédito (Cuentas por Cobrar)</h2>
          <Link :href="route('cuentas-por-cobrar.index', { cliente_id: cliente.id })" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
            Ver todo el historial &rarr;
          </Link>
        </div>
        <div class="overflow-x-auto bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm dark:shadow-none">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-white dark:bg-gray-800">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Venta Origen</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vencimiento</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pagado</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Pendiente</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="credito in historialCredito" :key="credito.id" class="hover:bg-white dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">
                   <Link v-if="credito.venta" :href="route('ventas.show', credito.venta_id)">{{ credito.venta.numero_venta }}</Link>
                   <span v-else>N/A</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ credito.fecha_vencimiento ? new Date(credito.fecha_vencimiento).toLocaleDateString('es-MX') : '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  ${{ Number(credito.monto_total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400">
                  ${{ Number(credito.monto_pagado).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400 font-bold">
                  ${{ Number(credito.monto_pendiente).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                   <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                      :class="{
                        'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300': credito.estado === 'pagado',
                        'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300': credito.estado === 'pendiente' || credito.estado === 'parcial',
                        'bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300': credito.estado === 'vencida' || credito.estado === 'vencido',
                        'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200': credito.estado === 'cancelada'
                      }">
                    {{ credito.estado }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Bóveda de Credenciales -->
      <section class="mt-8">
        <VaultSection 
            v-if="cliente.id"
            :credentialable-id="cliente.id"
            credentialable-type="App\Models\Cliente"
            :items="cliente.credenciales || []"
        />
      </section>

      <!-- Debug (desarrollo) -->
      <div v-if="isDevelopment" class="mt-6 p-4 bg-white dark:bg-gray-800 rounded-md text-xs">
        <h3 class="font-semibold mb-2">Debug: Cliente ID {{ cliente.id }}</h3>
        <pre class="text-xs overflow-auto">{{ JSON.stringify(cliente, null, 2) }}</pre>
      </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'
import VaultSection from '@/Components/VaultSection.vue'
import ExpedienteCredito from './Partials/ExpedienteCredito.vue'

defineOptions({ layout: AppLayout })

// Colores de empresa
const { cssVars, colors } = useCompanyColors()

const props = defineProps({
  cliente: {
    type: Object,
    required: true
  },
  historialCompras: {
    type: Array,
    default: () => []
  },
  historialCredito: {
    type: Array,
    default: () => []
  },
  tickets: {
    type: Array,
    default: () => []
  },
  citas: {
    type: Array,
    default: () => []
  },
  polizas: {
    type: Array,
    default: () => []
  },
  flash: {
    type: Object,
    default: () => ({})
  }
})

const isDevelopment = import.meta.env?.DEV || false

// Computed para counts si no vienen del backend
const cliente = computed(() => ({
  ...props.cliente,
  cotizaciones_count: props.cliente.cotizaciones_count || 0,
  ventas_count: props.cliente.ventas_count || 0,
  pedidos_count: props.cliente.pedidos_count || 0,
  tickets_count: props.cliente.tickets_count || 0,
  citas_count: props.cliente.citas_count || 0,
  polizas_count: props.cliente.polizas_count || 0,
  facturas_count: props.cliente.facturas_count || 0,
  direccion_completa: props.cliente.direccion_completa || `${props.cliente.calle} ${props.cliente.numero_exterior}${props.cliente.numero_interior ? ' Int. ' + props.cliente.numero_interior : ''}, ${props.cliente.colonia}, ${props.cliente.codigo_postal} ${props.cliente.municipio}, ${props.cliente.estado} ${props.cliente.pais}`
}))
</script>

<style scoped>
/* Estilos opcionales para mejorar layout */
section + section { margin-top: 2rem; }
</style>
