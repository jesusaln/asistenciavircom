<template>
  <Head :title="`Cliente: ${cliente.nombre_razon_social}`" />
  <div class="max-w-4xl mx-auto p-4" :style="cssVars">
    <div class="bg-white/80 backdrop-blur-sm shadow-xl rounded-2xl overflow-hidden border border-gray-100">
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
      <div v-if="flash.success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
        <p class="text-sm text-green-800">{{ flash.success }}</p>
      </div>
      <div v-if="flash.error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
        <p class="text-sm text-red-800">{{ flash.error }}</p>
      </div>

      <!-- Información General -->
      <section class="border-b border-gray-200 pb-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Información General</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre/Razón Social</label>
            <p class="text-gray-900 font-medium">{{ cliente.nombre_razon_social }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <p class="text-gray-900">{{ cliente.email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <p class="text-gray-900" v-if="cliente.telefono">{{ cliente.telefono }}</p>
            <p class="text-gray-500 italic" v-else>Sin teléfono</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Persona</label>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="cliente.tipo_persona === 'fisica' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'">
              {{ cliente.tipo_persona_nombre }}
            </span>
          </div>
          <div v-if="cliente.notas">
            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
            <p class="text-gray-900 whitespace-pre-wrap">{{ cliente.notas }}</p>
          </div>
        </div>
      </section>

      <!-- Información Fiscal -->
      <section class="border-b border-gray-200 pb-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Información Fiscal</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">RFC</label>
            <p class="text-gray-900 font-mono bg-gray-50 px-2 py-1 rounded inline-block">{{ cliente.rfc }}</p>
          </div>
          <div v-if="cliente.curp">
            <label class="block text-sm font-medium text-gray-700 mb-1">CURP</label>
            <p class="text-gray-900 font-mono">{{ cliente.curp }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Régimen Fiscal</label>
            <p class="text-gray-900">{{ cliente.regimen_fiscal }} - {{ cliente.regimen_fiscal_nombre }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Uso CFDI</label>
            <p class="text-gray-900">{{ cliente.uso_cfdi }} - {{ cliente.uso_cfdi_nombre }}</p>
          </div>
          <div v-if="cliente.cfdi_default_use">
            <label class="block text-sm font-medium text-gray-700 mb-1">Uso CFDI Predeterminado</label>
            <p class="text-gray-900">{{ cliente.cfdi_default_use }}</p>
          </div>
          <div v-if="cliente.payment_form_default">
            <label class="block text-sm font-medium text-gray-700 mb-1">Forma de Pago Predeterminada</label>
            <p class="text-gray-900">{{ cliente.payment_form_default }}</p>
          </div>
        </div>
      </section>

      <!-- Estado de Cuenta (Crédito) -->
      <section class="border-b border-gray-200 pb-6 mb-6" v-if="cliente.credito_activo">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
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
          <div class="bg-purple-50 p-4 rounded-xl border border-purple-200">
            <h3 class="text-xs font-semibold text-purple-700 uppercase tracking-wider">Días de Crédito</h3>
            <p class="text-2xl font-bold text-purple-900 mt-2">
              {{ cliente.dias_credito || 0 }} <span class="text-sm font-normal">días</span>
            </p>
          </div>
          <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-200">
            <h3 class="text-xs font-semibold text-yellow-700 uppercase tracking-wider">Saldo Utilizado</h3>
            <p class="text-2xl font-bold text-yellow-900 mt-2">
              ${{ Number(cliente.saldo_pendiente).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
            </p>
          </div>
          <div class="bg-green-50 p-4 rounded-xl border border-green-200">
            <h3 class="text-xs font-semibold text-green-700 uppercase tracking-wider">Crédito Disponible</h3>
            <p class="text-2xl font-bold text-green-900 mt-2">
              ${{ Number(cliente.credito_disponible).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
            </p>
          </div>
        </div>
        
        <!-- Barra de Progreso -->
        <div class="mt-4">
          <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>Uso de Crédito</span>
            <span>{{ Math.min(100, Math.round((cliente.saldo_pendiente / (cliente.limite_credito || 1)) * 100)) }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
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
          <p class="text-xs text-gray-500 mt-1" v-if="(cliente.saldo_pendiente / (cliente.limite_credito || 1)) > 0.9">
            <span class="text-red-500 font-medium">¡Atención!</span> El cliente está próximo a exceder su límite de crédito o ya lo ha excedido.
          </p>
        </div>
      </section>

      <!-- Dirección -->
      <section class="border-b border-gray-200 pb-6 mb-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Dirección</h2>
        <div class="bg-gray-50 rounded-lg p-4">
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
               <div>
                  <h4 class="text-xs font-semibold text-gray-500 uppercase">Calle y Números</h4>
                  <p class="text-base text-gray-900">
                    {{ cliente.calle || 'No especificada' }} 
                    {{ cliente.numero_exterior ? '#' + cliente.numero_exterior : '' }}
                    {{ cliente.numero_interior ? 'Int. ' + cliente.numero_interior : '' }}
                  </p>
               </div>
               <div>
                  <h4 class="text-xs font-semibold text-gray-500 uppercase">Ubicación</h4>
                  <p class="text-base text-gray-900">
                    {{ cliente.colonia }}, CP {{ cliente.codigo_postal }}
                  </p>
                  <p class="text-sm text-gray-600">
                    {{ cliente.municipio }}, {{ cliente.estado_nombre || cliente.estado }}
                  </p>
               </div>
             </div>
        </div>
      </section>

      <!-- Estadísticas Relacionadas -->
      <section>
        <h2 class="text-lg font-medium text-gray-900 mb-4">Módulos Relacionados</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
          <Link :href="route('cotizaciones.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-gray-100 text-center">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Cots</h3>
                <p class="text-xl font-black text-gray-800">{{ cliente.cotizaciones_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('ventas.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-blue-50 p-3 rounded-xl border border-blue-100 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-blue-100 text-center">
                <h3 class="text-[10px] font-bold text-blue-400 uppercase tracking-wider mb-1">Ventas</h3>
                <p class="text-xl font-black text-blue-900">{{ cliente.ventas_count || 0 }}</p>
              </div>
          </Link>
          <div class="bg-green-50 p-3 rounded-xl border border-green-100 text-center">
                <h3 class="text-[10px] font-bold text-green-400 uppercase tracking-wider mb-1">Pedidos</h3>
                <p class="text-xl font-black text-green-900">{{ cliente.pedidos_count || 0 }}</p>
          </div>
          <Link :href="route('soporte.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-red-50 p-3 rounded-xl border border-red-100 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-red-100 text-center">
                <h3 class="text-[10px] font-bold text-red-400 uppercase tracking-wider mb-1">Tickets</h3>
                <p class="text-xl font-black text-red-900">{{ cliente.tickets_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('citas.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-amber-50 p-3 rounded-xl border border-amber-100 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-amber-100 text-center">
                <h3 class="text-[10px] font-bold text-amber-400 uppercase tracking-wider mb-1">Citas</h3>
                <p class="text-xl font-black text-amber-900">{{ cliente.citas_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('polizas-servicio.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-indigo-50 p-3 rounded-xl border border-indigo-100 transform transition-all duration-200 group-hover:shadow-md group-hover:bg-indigo-100 text-center">
                <h3 class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider mb-1">Pólizas</h3>
                <p class="text-xl font-black text-indigo-900">{{ cliente.polizas_count || 0 }}</p>
              </div>
          </Link>
           <div class="bg-purple-50 p-3 rounded-xl border border-purple-100 text-center">
             <h3 class="text-[10px] font-bold text-purple-400 uppercase tracking-wider mb-1">Facturas</h3>
             <p class="text-xl font-black text-purple-900">{{ cliente.facturas_count || 0 }}</p>
          </div>
        </div>
      </section>

      <!-- Pólizas de Servicio -->
      <section class="border-t border-gray-200 pt-6 mt-6" v-if="polizas && polizas.length > 0">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Pólizas de Servicio Vigentes</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
           <div v-for="poliza in polizas" :key="poliza.id" class="p-4 bg-indigo-50 border border-indigo-100 rounded-xl">
              <div class="flex justify-between items-start">
                 <div>
                    <h3 class="font-bold text-indigo-900">{{ poliza.plan?.nombre || 'Póliza Personalizada' }}</h3>
                    <p class="text-xs text-indigo-700">Folio: {{ poliza.folio }}</p>
                 </div>
                 <span class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold rounded-full uppercase">
                    {{ poliza.estado }}
                 </span>
              </div>
              <div class="mt-3 text-sm text-indigo-800 flex justify-between">
                 <span>Vence: {{ poliza.fecha_vencimiento ? new Date(poliza.fecha_vencimiento).toLocaleDateString() : 'N/A' }}</span>
                 <Link :href="route('polizas-servicio.show', poliza.id)" class="font-bold hover:underline">Detalles</Link>
              </div>
           </div>
        </div>
      </section>

      <!-- Soporte y Citas (Grid 2 columnas) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 border-t border-gray-200 pt-6 mt-6">
          <!-- Últimos Tickets -->
          <section>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900">Tickets de Soporte</h2>
                <Link :href="route('soporte.index', { cliente_id: cliente.id })" class="text-xs text-blue-600 font-bold hover:underline">Ver todos</Link>
            </div>
            <div v-if="tickets && tickets.length > 0" class="space-y-3">
               <div v-for="ticket in tickets" :key="ticket.id" class="p-3 bg-white border border-gray-100 rounded-xl shadow-sm hover:shadow-md transition-shadow">
                  <div class="flex justify-between items-start mb-1">
                     <Link :href="route('soporte.show', ticket.id)" class="text-sm font-bold text-blue-600 hover:underline">#{{ ticket.numero }} - {{ ticket.titulo }}</Link>
                     <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold uppercase', 
                        ticket.estado === 'abierto' ? 'bg-red-100 text-red-700' :
                        ticket.estado === 'resuelto' ? 'bg-green-100 text-green-700' :
                        'bg-blue-100 text-blue-700'
                     ]">
                        {{ ticket.estado }}
                     </span>
                  </div>
                  <div class="flex justify-between items-center text-[11px] text-gray-500">
                     <span>{{ ticket.categoria?.nombre }}</span>
                     <span>{{ new Date(ticket.created_at).toLocaleDateString() }}</span>
                  </div>
               </div>
            </div>
            <p v-else class="text-sm text-gray-500 italic">No hay tickets registrados</p>
          </section>

          <!-- Próximas Citas -->
          <section>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-gray-900">Visitas Técnicas</h2>
                <Link :href="route('citas.index', { cliente_id: cliente.id })" class="text-xs text-blue-600 font-bold hover:underline">Ver historial</Link>
            </div>
            <div v-if="citas && citas.length > 0" class="space-y-3">
               <div v-for="cita in citas" :key="cita.id" class="p-3 bg-amber-50 border border-amber-100 rounded-xl shadow-sm">
                  <div class="flex justify-between items-start mb-1">
                     <Link :href="route('citas.show', cita.id)" class="text-sm font-bold text-amber-900 hover:underline">#{{ cita.id }} - {{ cita.tipo_servicio }}</Link>
                     <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold uppercase', 
                        cita.estado === 'completado' ? 'bg-green-100 text-green-700' :
                        cita.estado === 'cancelado' ? 'bg-red-100 text-red-700' :
                        'bg-amber-100 text-amber-700'
                     ]">
                        {{ cita.estado }}
                     </span>
                  </div>
                  <div class="flex justify-between items-center text-[11px] text-amber-800">
                     <span>Téc: {{ cita.tecnico?.name }}</span>
                     <span class="font-bold">{{ new Date(cita.fecha_hora).toLocaleDateString('es-MX', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'}) }}</span>
                  </div>
               </div>
            </div>
            <p v-else class="text-sm text-gray-500 italic">No hay citas programadas</p>
          </section>
      </div>

      <!-- Historial de Compras -->
      <section class="border-t border-gray-200 pt-6 mt-6" v-if="historialCompras && historialCompras.length > 0">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-gray-900">Historial de Ventas (Últimas 50)</h2>
          <Link :href="route('ventas.index', { cliente_id: cliente.id })" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            Ver todas las ventas &rarr;
          </Link>
        </div>
        <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg shadow-sm">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Folio</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método Pago</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="compra in historialCompras" :key="compra.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                  <Link :href="route('ventas.show', compra.id)">{{ compra.numero_venta }}</Link>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ new Date(compra.fecha).toLocaleDateString('es-MX') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                  ${{ Number(compra.total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                  {{ compra.metodo_pago }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                   <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="compra.pagado ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
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
      <section class="border-t border-gray-200 pt-6 mt-6" v-if="historialCredito && historialCredito.length > 0">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-gray-900">Historial de Crédito (Cuentas por Cobrar)</h2>
          <Link :href="route('cuentas-por-cobrar.index', { cliente_id: cliente.id })" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            Ver todo el historial &rarr;
          </Link>
        </div>
        <div class="overflow-x-auto bg-white border border-gray-200 rounded-lg shadow-sm">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venta Origen</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pagado</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pendiente</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="credito in historialCredito" :key="credito.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">
                   <Link v-if="credito.venta" :href="route('ventas.show', credito.venta_id)">{{ credito.venta.numero_venta }}</Link>
                   <span v-else>N/A</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ credito.fecha_vencimiento ? new Date(credito.fecha_vencimiento).toLocaleDateString('es-MX') : '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  ${{ Number(credito.monto_total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                  ${{ Number(credito.monto_pagado).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-bold">
                  ${{ Number(credito.monto_pendiente).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                   <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                      :class="{
                        'bg-green-100 text-green-800': credito.estado === 'pagado',
                        'bg-yellow-100 text-yellow-800': credito.estado === 'pendiente' || credito.estado === 'parcial',
                        'bg-red-100 text-red-800': credito.estado === 'vencida' || credito.estado === 'vencido',
                        'bg-gray-100 text-gray-800': credito.estado === 'cancelada'
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
      <div v-if="isDevelopment" class="mt-6 p-4 bg-gray-50 rounded-md text-xs">
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
@import "tailwindcss" reference;
/* Estilos opcionales para mejorar layout */
section + section { margin-top: 2rem; }
</style>
