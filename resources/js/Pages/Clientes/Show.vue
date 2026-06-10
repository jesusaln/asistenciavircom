<template>
  <Head :title="`Cliente: ${cliente.nombre_razon_social}`" />
  <div class="w-full p-4" :style="cssVars">
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm shadow-xl dark:shadow-none rounded-2xl overflow-hidden border border-slate-100 dark:border-slate-700">
      <!-- Header moderno con gradiente -->
      <div class="p-6 text-white" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
              <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div>
              <h1 class="text-2xl font-bold">{{ cliente.nombre_razon_social }}</h1>
              <p class="text-white/80 text-sm mt-1">Cliente #{{ cliente.id }}</p>
              <div class="mt-3 flex gap-2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-white/20 backdrop-blur-sm" v-if="cliente.activo">
                  <span class="w-2 h-2 bg-emerald-400 rounded-full mr-1.5"></span>
                  Activo
                </span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-white/20 backdrop-blur-sm" v-else>
                  <span class="w-2 h-2 bg-slate-400 rounded-full mr-1.5"></span>
                  Inactivo
                </span>
                <span v-if="cliente.credito_activo" class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-white/20 backdrop-blur-sm">
                  <span class="w-2 h-2 bg-purple-400 rounded-full mr-1.5"></span>
                  Crédito Activo
                </span>
                <!-- WhatsApp Status Indicator -->
                <span v-if="cliente.telefono" class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-emerald-500/30 backdrop-blur-sm text-white">
                  <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.658 1.43 5.63 1.43h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                  </svg>
                  WhatsApp Habilitado
                </span>
              </div>
            </div>
          </div>
          <div class="flex flex-col items-end space-y-3">
            <div class="flex space-x-2">
              <button
                @click="iniciarWhatsApp"
                :disabled="iniciandoWhatsApp"
                class="inline-flex items-center px-4 py-2 text-sm font-bold bg-emerald-500 text-white rounded-xl hover:bg-emerald-600 transition-all duration-200 shadow-lg shadow-emerald-500/30 disabled:opacity-50"
              >
                <svg v-if="iniciandoWhatsApp" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.658 1.43 5.63 1.43h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                </svg>
                {{ iniciandoWhatsApp ? 'Iniciando...' : 'WhatsApp' }}
              </button>
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
                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-brand-500 text-white rounded-xl hover:bg-emerald-600 shadow-xl transition-all duration-200"
              >
                + Venta
              </Link>
              <Link
                :href="route('cotizaciones.create', { cliente_id: cliente.id })"
                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-indigo-500 text-white rounded-xl hover:bg-indigo-600 shadow-xl transition-all duration-200"
              >
                + Cotización
              </Link>
              <Link
                :href="route('citas.create', { cliente_id: cliente.id })"
                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold bg-amber-500 text-white rounded-xl hover:bg-amber-600 shadow-xl transition-all duration-200"
              >
                + Cita
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Contenido principal -->
      <div class="p-6">

      <!-- Mensaje de éxito/error -->
      <div v-if="flash.success" class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 border border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-700 rounded-xl">
        <p class="text-sm text-emerald-800 dark:text-emerald-200 dark:text-emerald-300">{{ flash.success }}</p>
      </div>
      <div v-if="flash.error" class="mb-6 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/30 dark:border-rose-700 rounded-xl">
        <p class="text-sm text-rose-800 dark:text-rose-200 dark:text-rose-300">{{ flash.error }}</p>
      </div>

      <!-- Información General -->
      <section class="border-b border-slate-200 dark:border-slate-700 pb-6 mb-6">
        <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Información General</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Nombre/Razón Social</label>
            <p class="text-slate-900 dark:text-slate-100 font-medium">{{ cliente.nombre_razon_social }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Email</label>
            <p class="text-slate-900 dark:text-slate-100">{{ cliente.email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Teléfono</label>
            <p class="text-slate-900 dark:text-slate-100" v-if="cliente.telefono">{{ cliente.telefono }}</p>
            <p class="text-slate-500 dark:text-slate-400 italic" v-else>Sin teléfono</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Tipo de Persona</label>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium"
                  :class="cliente.tipo_persona === 'fisica' ? 'bg-sky-100 text-sky-800 dark:text-sky-200 dark:bg-sky-900/20 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300'">
              {{ cliente.tipo_persona_nombre }}
            </span>
          </div>
          <div v-if="cliente.notas">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Notas</label>
            <p class="text-slate-900 dark:text-slate-100 whitespace-pre-wrap">{{ cliente.notas }}</p>
          </div>
        </div>
      </section>

      <!-- Información Fiscal -->
      <section class="border-b border-slate-200 dark:border-slate-700 pb-6 mb-6">
        <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Información Fiscal</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">RFC</label>
            <p class="text-slate-900 dark:text-slate-100 font-mono bg-white dark:bg-slate-700 px-2 py-1 rounded-xl inline-block">{{ cliente.rfc }}</p>
          </div>
          <div v-if="cliente.curp">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">CURP</label>
            <p class="text-slate-900 dark:text-slate-100 font-mono">{{ cliente.curp }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Régimen Fiscal</label>
            <p class="text-slate-900 dark:text-slate-100">{{ cliente.regimen_fiscal }} - {{ cliente.regimen_fiscal_nombre }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Uso CFDI</label>
            <p class="text-slate-900 dark:text-slate-100">{{ cliente.uso_cfdi }} - {{ cliente.uso_cfdi_nombre }}</p>
          </div>
          <div v-if="cliente.cfdi_default_use">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Uso CFDI Predeterminado</label>
            <p class="text-slate-900 dark:text-slate-100">{{ cliente.cfdi_default_use }}</p>
          </div>
          <div v-if="cliente.payment_form_default">
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">Forma de Pago Predeterminada</label>
            <p class="text-slate-900 dark:text-slate-100">{{ cliente.payment_form_default }}</p>
          </div>
        </div>
      </section>

      <!-- Estado de Cuenta (Crédito) -->
      <section class="border-b border-slate-200 dark:border-slate-700 pb-6 mb-6" v-if="cliente.credito_activo">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-4 flex items-center gap-2">
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
          <div class="bg-brand-50 dark:bg-brand-900/20 p-4 rounded-xl border border-brand-200 dark:border-brand-800/30 dark:border-amber-700">
            <h3 class="text-xs font-semibold text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-300 uppercase tracking-wider">Saldo Utilizado</h3>
            <p class="text-2xl font-bold text-brand-900 dark:text-brand-100 mt-2">
              ${{ Number(cliente.saldo_pendiente).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
            </p>
          </div>
          <div class="bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-700">
            <h3 class="text-xs font-semibold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300 uppercase tracking-wider">Crédito Disponible</h3>
            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100 mt-2">
              ${{ Number(cliente.credito_disponible).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
            </p>
          </div>
        </div>
        
        <!-- Barra de Progreso -->
        <div class="mt-4">
          <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mb-1">
            <span>Uso de Crédito</span>
            <span>{{ Math.min(100, Math.round((cliente.saldo_pendiente / (cliente.limite_credito || 1)) * 100)) }}%</span>
          </div>
          <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3 overflow-hidden">
            <div 
              class="h-3 rounded-full duration-200 transition-all" 
              :style="{
                width: `${Math.min(100, (cliente.saldo_pendiente / (cliente.limite_credito || 1)) * 100)}%`,
                background: (cliente.saldo_pendiente / (cliente.limite_credito || 1)) > 0.9 
                  ? 'linear-gradient(90deg, #ef4444 0%, #dc2626 100%)' 
                  : `linear-gradient(90deg, ${colors.principal} 0%, ${colors.secundario} 100%)`
              }"
            ></div>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1" v-if="(cliente.saldo_pendiente / (cliente.limite_credito || 1)) > 0.9">
            <span class="text-rose-500 dark:text-rose-400 font-medium">¡Atención!</span> El cliente está próximo a exceder su límite de crédito o ya lo ha excedido.
          </p>
        </div>
      </section>

      <!-- Dirección -->
      <section class="border-b border-slate-200 dark:border-slate-700 pb-6 mb-6">
        <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Dirección</h2>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4">
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
               <div>
                  <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Calle y Números</h4>
                  <p class="text-base text-slate-900 dark:text-slate-100">
                    {{ cliente.calle || 'No especificada' }} 
                    {{ cliente.numero_exterior ? '#' + cliente.numero_exterior : '' }}
                    {{ cliente.numero_interior ? 'Int. ' + cliente.numero_interior : '' }}
                  </p>
               </div>
               <div>
                  <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Ubicación</h4>
                  <p class="text-base text-slate-900 dark:text-slate-100">
                    {{ cliente.colonia }}, CP {{ cliente.codigo_postal }}
                  </p>
                  <p class="text-sm text-slate-500 dark:text-slate-200">
                    {{ cliente.municipio }}, {{ cliente.estado_nombre || cliente.estado }}
                  </p>
               </div>
             </div>
        </div>
      </section>

      <!-- Estadísticas Relacionadas -->
      <section>
        <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Módulos Relacionados</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
          <Link :href="route('cotizaciones.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-100 dark:border-slate-700 transform transition-all duration-200 group-hover:shadow-xl group-hover:bg-slate-100 dark:group-hover:bg-slate-700 text-center">
                <h3 class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Cots</h3>
                <p class="text-xl font-black text-slate-800 dark:text-slate-100">{{ cliente.cotizaciones_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('ventas.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-sky-50 dark:bg-sky-900/20 dark:bg-sky-900/20 p-3 rounded-xl border border-blue-100 dark:border-blue-700 transform transition-all duration-200 group-hover:shadow-xl group-hover:bg-sky-100 dark:group-hover:bg-blue-900/40 text-center">
                <h3 class="text-[10px] font-bold text-blue-400 dark:text-blue-300 uppercase tracking-wider mb-1">Ventas</h3>
                <p class="text-xl font-black text-blue-900 dark:text-blue-100">{{ cliente.ventas_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('pedidos.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 p-3 rounded-xl border border-emerald-100 dark:border-emerald-700 transform transition-all duration-200 group-hover:shadow-xl group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/40 text-center">
                <h3 class="text-[10px] font-bold text-emerald-400 dark:text-emerald-300 uppercase tracking-wider mb-1">Pedidos</h3>
                <p class="text-xl font-black text-emerald-900 dark:text-emerald-100">{{ cliente.pedidos_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('soporte.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-rose-50 dark:bg-rose-900/20 p-3 rounded-xl border border-rose-100 dark:border-rose-700 transform transition-all duration-200 group-hover:shadow-xl group-hover:bg-rose-100 dark:group-hover:bg-rose-900/40 text-center">
                <h3 class="text-[10px] font-bold text-rose-400 dark:text-rose-300 uppercase tracking-wider mb-1">Tickets</h3>
                <p class="text-xl font-black text-rose-900 dark:text-rose-100">{{ cliente.tickets_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('citas.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-brand-50 dark:bg-brand-900/20 p-3 rounded-xl border border-brand-100 dark:border-brand-700 transform transition-all duration-200 group-hover:shadow-xl group-hover:bg-brand-100 dark:group-hover:bg-brand-900/40 text-center">
                <h3 class="text-[10px] font-bold text-brand-400 dark:text-brand-300 uppercase tracking-wider mb-1">Citas</h3>
                <p class="text-xl font-black text-brand-900 dark:text-amber-100">{{ cliente.citas_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('polizas-servicio.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-indigo-50 dark:bg-sky-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-700 transform transition-all duration-200 group-hover:shadow-xl group-hover:bg-sky-100 dark:group-hover:bg-indigo-900/40 text-center">
                <h3 class="text-[10px] font-bold text-indigo-400 dark:text-indigo-300 uppercase tracking-wider mb-1">Pólizas</h3>
                <p class="text-xl font-black text-indigo-900 dark:text-indigo-100">{{ cliente.polizas_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('taller.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-orange-50 dark:bg-brand-900/20 p-3 rounded-xl border border-orange-100 dark:border-brand-700 transform transition-all duration-200 group-hover:shadow-xl group-hover:bg-orange-100 dark:group-hover:bg-brand-900/40 text-center">
                 <h3 class="text-[10px] font-bold text-orange-400 dark:text-orange-300 uppercase tracking-wider mb-1">Taller</h3>
                 <p class="text-xl font-black text-orange-900 dark:text-orange-100">{{ cliente.taller_ordenes_count || 0 }}</p>
              </div>
          </Link>
          <Link :href="route('facturas.index', { cliente_id: cliente.id })" class="block group">
              <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-xl border border-purple-100 dark:border-purple-700 transform transition-all duration-200 group-hover:shadow-xl group-hover:bg-purple-100 dark:group-hover:bg-purple-900/40 text-center">
                 <h3 class="text-[10px] font-bold text-purple-400 dark:text-purple-300 uppercase tracking-wider mb-1">Facturas</h3>
                 <p class="text-xl font-black text-purple-900 dark:text-purple-100">{{ cliente.facturas_count || 0 }}</p>
              </div>
          </Link>
        </div>
      </section>

      <!-- Pólizas de Servicio -->
      <section class="border-t border-slate-200 dark:border-slate-700 pt-6 mt-6" v-if="polizas && polizas.length > 0">
        <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Pólizas de Servicio Vigentes</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
           <div v-for="poliza in polizas" :key="poliza.id" class="p-4 bg-indigo-50 dark:bg-sky-900/20 border border-indigo-100 dark:border-indigo-700 rounded-xl">
              <div class="flex justify-between items-start">
                 <div>
                    <h3 class="font-bold text-indigo-900 dark:text-indigo-100">{{ poliza.plan?.nombre || 'Póliza Personalizada' }}</h3>
                    <p class="text-xs text-indigo-700 dark:text-indigo-300">Folio: {{ poliza.folio }}</p>
                 </div>
                 <span class="px-2 py-0.5 bg-emerald-100 dark:bg-slate-800/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300 text-[10px] font-bold rounded-full uppercase">
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
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 border-t border-slate-200 dark:border-slate-700 pt-6 mt-6">
          <!-- Últimos Tickets -->
          <section>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100">Tickets de Soporte</h2>
                <Link :href="route('soporte.index', { cliente_id: cliente.id })" class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline">Ver todos</Link>
            </div>
            <div v-if="tickets && tickets.length > 0" class="space-y-3">
               <div v-for="ticket in tickets" :key="ticket.id" class="p-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl shadow-sm hover:shadow-xl transition-shadow">
                  <div class="flex justify-between items-start mb-1">
                     <Link :href="route('soporte.show', ticket.id)" class="text-sm font-bold text-blue-600 dark:text-blue-400 hover:underline">#{{ ticket.numero }} - {{ ticket.titulo }}</Link>
                     <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold uppercase', 
                        ticket.estado === 'abierto' ? 'bg-rose-50 dark:bg-rose-900/20/20 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300' :
                        ticket.estado === 'resuelto' ? 'bg-emerald-100 dark:bg-slate-800/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300' :
                        'bg-blue-50 dark:bg-sky-900/20/20 text-sky-800 dark:text-sky-200 dark:text-blue-300'
                     ]">
                        {{ ticket.estado }}
                     </span>
                  </div>
                  <div class="flex justify-between items-center text-[11px] text-slate-500 dark:text-slate-400">
                     <span>{{ ticket.categoria?.nombre }}</span>
                     <span>{{ new Date(ticket.created_at).toLocaleDateString() }}</span>
                  </div>
               </div>
            </div>
            <p v-else class="text-sm text-slate-500 dark:text-slate-400 italic">No hay tickets registrados</p>
          </section>

          <!-- Próximas Citas -->
          <section>
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100">Visitas Técnicas</h2>
                <Link :href="route('citas.index', { cliente_id: cliente.id })" class="text-xs text-blue-600 dark:text-blue-400 font-bold hover:underline">Ver historial</Link>
            </div>
            <div v-if="citas && citas.length > 0" class="space-y-3">
               <div v-for="cita in citas" :key="cita.id" class="p-3 bg-brand-50 dark:bg-brand-900/20 border border-brand-100 dark:border-brand-700 rounded-2xl shadow-sm">
                  <div class="flex justify-between items-start mb-1">
                     <Link :href="route('citas.show', cita.id)" class="text-sm font-bold text-brand-900 dark:text-brand-100 hover:underline">#{{ cita.id }} - {{ cita.tipo_servicio }}</Link>
                     <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold uppercase', 
                        cita.estado === 'completado' ? 'bg-emerald-100 dark:bg-slate-800/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300' :
                        cita.estado === 'cancelado' ? 'bg-rose-50 dark:bg-rose-900/20/20 text-rose-800 dark:text-rose-200 dark:text-rose-200 dark:text-rose-300' :
                        'bg-brand-50 dark:bg-brand-900/20/20 text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-amber-300'
                     ]">
                        {{ cita.estado }}
                     </span>
                  </div>
                  <div class="flex justify-between items-center text-[11px] text-brand-800 dark:text-brand-200 dark:text-amber-200">
                     <span>Téc: {{ cita.tecnico?.name }}</span>
                     <span class="font-bold">{{ new Date(cita.fecha_hora).toLocaleDateString('es-MX', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'}) }}</span>
                  </div>
               </div>
            </div>
            <p v-else class="text-sm text-slate-500 dark:text-slate-400 italic">No hay citas programadas</p>
          </section>
      </div>

      <!-- Historial de Compras -->
      <section class="border-t border-slate-200 dark:border-slate-700 pt-6 mt-6" v-if="historialCompras && historialCompras.length > 0">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100">Historial de Ventas (Últimas 50)</h2>
          <Link :href="route('ventas.index', { cliente_id: cliente.id })" class="text-sm text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 dark:hover:text-blue-300 font-medium">
            Ver todas las ventas &rarr;
          </Link>
        </div>
        <div class="overflow-x-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm dark:shadow-none">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Folio</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fecha</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Método Pago</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="compra in historialCompras" :key="compra.id" class="hover:bg-white dark:hover:bg-slate-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">
                  <Link :href="route('ventas.show', compra.id)">{{ compra.numero_venta }}</Link>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                  {{ new Date(compra.fecha).toLocaleDateString('es-MX') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100 font-semibold">
                  ${{ Number(compra.total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400 capitalize">
                  {{ compra.metodo_pago }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                   <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium"
                      :class="compra.pagado ? 'bg-emerald-100 dark:bg-slate-800/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-300' : 'bg-brand-50 dark:bg-brand-900/20/20 text-brand-800 dark:text-brand-200 dark:text-amber-300'">
                    {{ compra.pagado ? 'Pagado' : 'Pendiente' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Expediente de Crédito -->
      <section class="border-t border-slate-200 pt-6 mt-6">
           <ExpedienteCredito :cliente="cliente" :documentos="cliente.documentos" />
      </section>

      <!-- Historial de Crédito -->
      <section class="border-t border-slate-200 dark:border-slate-700 pt-6 mt-6" v-if="historialCredito && historialCredito.length > 0">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100">Historial de Crédito (Cuentas por Cobrar)</h2>
          <Link :href="route('cuentas-por-cobrar.index', { cliente_id: cliente.id })" class="text-sm text-blue-600 dark:text-blue-400 hover:text-sky-800 dark:text-sky-200 dark:hover:text-blue-300 font-medium">
            Ver todo el historial &rarr;
          </Link>
        </div>
        <div class="overflow-x-auto bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm dark:shadow-none">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Venta Origen</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Vencimiento</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pagado</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pendiente</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="credito in historialCredito" :key="credito.id" class="hover:bg-white dark:hover:bg-slate-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600 dark:text-blue-400">
                    <Link v-if="credito.venta" :href="route('ventas.show', credito.venta.id || credito.venta_id)">{{ credito.venta.numero_venta }}</Link>
                   <span v-else>N/A</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                  {{ credito.fecha_vencimiento ? new Date(credito.fecha_vencimiento).toLocaleDateString('es-MX') : '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-slate-100">
                  ${{ Number(credito.monto_total).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-emerald-600 dark:text-slate-400">
                  ${{ Number(credito.monto_pagado).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-rose-600 dark:text-rose-400 font-bold">
                  ${{ Number(credito.monto_pendiente).toLocaleString('es-MX', { minimumFractionDigits: 2 }) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                   <span class="inline-flex items-center px-2.5 py-0.5 rounded-xl text-xs font-medium capitalize"
                      :class="{
                        'bg-emerald-100 dark:bg-slate-800/20 text-emerald-800 dark:text-emerald-200 dark:text-emerald-300': credito.estado === 'pagado',
                        'bg-brand-50 dark:bg-brand-900/20/20 text-brand-800 dark:text-brand-200 dark:text-amber-300': credito.estado === 'pendiente' || credito.estado === 'parcial',
                        'bg-rose-50 dark:bg-rose-900/20/20 text-rose-800 dark:text-rose-200 dark:text-rose-300': credito.estado === 'vencida' || credito.estado === 'vencido',
                        'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200': credito.estado === 'cancelada'
                      }">
                    {{ credito.estado }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Historial de Taller -->
      <section class="mt-8" v-if="taller && taller.length > 0">
        <h2 class="text-lg font-medium text-slate-900 dark:text-slate-100 mb-4">Revisiones en Taller (Equipos)</h2>
        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 overflow-hidden shadow-sm">
          <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-900/50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Folio / Fecha</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Equipo</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Diagnóstico / Trabajo</th>
                <th class="px-6 py-3 text-left text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider text-center">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
              <tr v-for="orden in taller" :key="orden.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                   <div class="text-sm font-bold text-slate-900 dark:text-white">{{ orden.folio }}</div>
                   <div class="text-xs text-slate-500">{{ new Date(orden.fecha_recepcion).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-slate-900 dark:text-slate-200">{{ orden.equipo_marca }} {{ orden.equipo_modelo }}</div>
                  <div class="text-xs text-slate-500">S/N: {{ orden.equipo_serie || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4">
                  <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-1"><strong>D:</strong> {{ orden.diagnostico || 'Pendiente' }}</p>
                  <p class="text-xs text-slate-600 dark:text-slate-400 line-clamp-1"><strong>T:</strong> {{ orden.trabajo_realizado || 'Pendiente' }}</p>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span 
                    class="px-2 py-1 text-[10px] font-bold rounded-lg uppercase tracking-wider"
                    :class="{
                      'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-amber-400': orden.estado === 'recibido',
                      'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-blue-400': orden.estado === 'reparacion',
                      'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': orden.estado === 'listo',
                      'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-indigo-400': orden.estado === 'entregado',
                      'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': orden.estado === 'cancelado'
                    }"
                  >
                    {{ orden.estado }}
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

      </div>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useCompanyColors } from '@/Composables/useCompanyColors'
import VaultSection from '@/Components/VaultSection.vue'
import ExpedienteCredito from './Partials/ExpedienteCredito.vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import Swal from 'sweetalert2'

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
  taller: {
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
  taller_ordenes_count: props.cliente.taller_ordenes_count || 0,
  direccion_completa: props.cliente.direccion_completa || `${props.cliente.calle} ${props.cliente.numero_exterior}${props.cliente.numero_interior ? ' Int. ' + props.cliente.numero_interior : ''}, ${props.cliente.colonia}, ${props.cliente.codigo_postal} ${props.cliente.municipio}, ${props.cliente.estado} ${props.cliente.pais}`
}))

const iniciandoWhatsApp = ref(false)

const iniciarWhatsApp = async () => {
  if (!props.cliente.telefono) {
    Swal.fire({
      icon: 'error',
      title: 'Teléfono faltante',
      text: 'Este cliente no tiene un teléfono registrado para iniciar WhatsApp.',
      confirmButtonColor: colors.principal
    })
    return
  }

  // Preguntar antes de enviar
  const result = await Swal.fire({
    title: '¿Iniciar conversación?',
    text: `Se enviará un mensaje de bienvenida vía WhatsApp a ${props.cliente.nombre_razon_social}. ¿Deseas continuar?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, enviar mensaje',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#10b981', // Verde esmeralda
    cancelButtonColor: '#64748b', // Slate
    reverseButtons: true
  })

  if (!result.isConfirmed) return

  try {
    iniciandoWhatsApp.value = true
    const response = await axios.post(route('clientes.whatsapp', props.cliente.id))
    
    if (response.data.success) {
      Swal.fire({
        icon: 'success',
        title: '¡Mensaje Enviado!',
        text: response.data.message,
        timer: 2000,
        showConfirmButton: false
      })
      
      // Redirigir al Inbox
      setTimeout(() => {
        router.visit(response.data.redirect_url)
      }, 1500)
    }
  } catch (error) {
    console.error('Error al iniciar WhatsApp:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.response?.data?.message || 'Hubo un problema al conectar con la API de WhatsApp.',
      confirmButtonColor: colors.principal
    })
  } finally {
    iniciandoWhatsApp.value = false
  }
}
</script>

<style scoped>
/* Estilos opcionales para mejorar layout */
section + section { margin-top: 2rem; }
</style>
