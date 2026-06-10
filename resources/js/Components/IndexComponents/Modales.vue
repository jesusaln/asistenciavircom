<template>
  <!-- Modal principal -->
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="onClose"
    >
      <div
        :class="{
          'max-w-sm': mode === 'confirm-email',
          'max-w-md': mode === 'confirm' || mode === 'confirm-duplicate' || mode === 'confirm-cancel' || mode === 'close',
          'max-w-4xl': mode === 'details'
        }"
        class="bg-white rounded-xl shadow-xl w-full max-h-[90vh] overflow-y-auto custom-scrollbar p-6 outline-none"
        role="dialog"
        aria-modal="true"
        :aria-label="`Modal de ${config.titulo}`"
        tabindex="-1"
        ref="modalRef"
        @keydown.esc.prevent="onClose"
      >
        <!-- Modo: Confirmación de eliminación -->
        <div v-if="mode === 'confirm'" class="text-center">
          <div class="w-12 h-12 mx-auto bg-rose-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
              />
            </svg>
          </div>
          <h3 class="text-lg font-medium mb-2">
            ¿Eliminar {{ config.titulo.toLowerCase() }}?
          </h3>
          <p class="text-slate-600 mb-6">
            Esta acción no se puede deshacer.
          </p>
          <div class="flex gap-3">
            <button
              @click="onCancel"
              class="flex-1 px-4 py-2 bg-slate-200 rounded-xl hover:bg-slate-300 transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="onConfirm"
              class="flex-1 px-4 py-2 bg-rose-600 text-white rounded-xl hover:bg-rose-700 transition-colors"
            >
              Eliminar
            </button>
          </div>
        </div>

        <!-- Modo: Confirmación de envío de email -->
        <div v-if="mode === 'confirm-email'" class="text-center">
          <div class="w-10 h-10 mx-auto bg-emerald-100 rounded-full flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
          </div>
          <h3 class="text-base font-medium mb-2">
            {{ selected?.tipo_envio === 'recordatorio_pago'
               ? (selected?.email_enviado ? '¿Reenviar recordatorio de pago?' : '¿Enviar recordatorio de pago?')
               : (selected?.email_enviado ? `¿Reenviar ${config.titulo.toLowerCase()}?` : `¿Enviar ${config.titulo.toLowerCase()}?`) }}
          </h3>
          <div v-if="selected?.numero_cotizacion || selected?.numero_pedido || selected?.numero_venta" class="text-slate-600 mb-3">
            <p class="mb-1 text-sm">
              {{ config.titulo }} <strong>#{{ selected.numero_cotizacion || selected.numero_pedido || selected.numero_venta }}</strong>
            </p>
            <p v-if="selected?.email_destino" class="text-xs text-slate-500">
              📧 {{ selected.email_destino }}
            </p>
            <p v-if="selected?.email_enviado" class="text-xs text-blue-600">
              ✉️ Enviado: {{ selected.email_enviado_fecha || 'N/A' }}
            </p>
          </div>
          <p class="text-slate-500 mb-4 text-xs">
            {{ selected?.tipo_envio === 'recordatorio_pago'
               ? 'El cliente recibirá el recordatorio de pago con la factura adjunta por email'
               : 'El cliente recibirá el PDF por email' }}
          </p>
          <div class="flex gap-2">
            <button
              @click="onCancel"
              class="flex-1 px-3 py-2 bg-slate-200 rounded-xl hover:bg-slate-300 transition-colors text-xs"
            >
              Cancelar
            </button>
            <button
              @click="onConfirmEmail"
              class="flex-1 px-3 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors text-xs"
            >
              {{ selected?.tipo_envio === 'recordatorio_pago'
                 ? (selected?.email_enviado ? 'Reenviar Recordatorio' : 'Enviar Recordatorio')
                 : (selected?.email_enviado ? 'Reenviar' : 'Enviar') }}
            </button>
          </div>
        </div>

        <!-- Modo: Confirmación de duplicado -->
        <div v-if="mode === 'confirm-duplicate'" class="text-center">
          <div class="w-12 h-12 mx-auto bg-sky-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V7M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium mb-2">
            ¿Duplicar {{ config.titulo.toLowerCase() }}?
          </h3>
          <p class="text-slate-600 mb-6" v-if="selected?.numero_cotizacion">
            Se creará una copia de la {{ config.titulo.toLowerCase() }} <strong>#{{ selected.numero_cotizacion }}</strong> con estado "Borrador".
          </p>
          <p class="text-slate-600 mb-6" v-else>
            Se creará una copia de esta {{ config.titulo.toLowerCase() }} con estado "Borrador".
          </p>
          <div class="flex gap-3">
            <button
              @click="onCancel"
              class="flex-1 px-4 py-2 bg-slate-200 rounded-xl hover:bg-slate-300 transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="onConfirmDuplicate"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
            >
              Duplicar
            </button>
          </div>
        </div>

        <!-- Modo: Cerrar pedido -->
        <div v-if="mode === 'close'" class="text-center">
          <div class="w-12 h-12 mx-auto bg-brand-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium mb-2">
            ¿Cerrar {{ config.titulo.toLowerCase() }}?
          </h3>
          <p class="text-slate-600 mb-6">
            Esta acción marcará el {{ config.titulo.toLowerCase() }} como cerrado.
          </p>
          <div class="flex gap-3">
            <button
              @click="onCancel"
              class="flex-1 px-4 py-2 bg-slate-200 rounded-xl hover:bg-slate-300 transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="emit('cerrar-pedido')"
              class="flex-1 px-4 py-2 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition-colors"
            >
              Cerrar {{ config.titulo }}
            </button>
          </div>
        </div>

        <!-- Modo: Detalles -->
        <div v-else-if="mode === 'details'" class="space-y-4">
          <h3 class="text-lg font-medium mb-1 flex items-center gap-2">
            Detalles de {{ config.titulo }}
            <span v-if="selected?.id" class="text-sm text-slate-500">#{{ selected.id }}</span>
          </h3>

          <!-- NUEVO: Folio detectado automáticamente -->
          <p v-if="folioValue" class="text-sm text-slate-600">
            <strong>{{ folioLabel }}:</strong> {{ folioValue }}
          </p>

          <!-- NUEVO: Auditoría -->
          <div v-if="auditoriaBoxVisible" class="mt-2 p-4 bg-transparent rounded-xl border border-slate-200">
            <h4 class="text-sm font-semibold text-slate-800 mb-3">Auditoría</h4>
            <div class="grid md:grid-cols-3 gap-3 text-sm">
              <div>
                <span class="text-slate-500">Creado por:</span>
                <div class="font-medium text-slate-900">
                  {{ auditoriaSafe.creado_por || '—' }}
                </div>
                <div class="text-slate-500">
                  {{ auditoriaSafe.creado_en ? formatearFecha(auditoriaSafe.creado_en) : '—' }}
                </div>
              </div>
              <div>
                <span class="text-slate-500">Actualizado por:</span>
                <div class="font-medium text-slate-900">
                  {{ auditoriaSafe.actualizado_por || '—' }}
                </div>
                <div class="text-slate-500">
                  {{ auditoriaSafe.actualizado_en ? formatearFecha(auditoriaSafe.actualizado_en) : '—' }}
                </div>
              </div>
              <div v-if="auditoriaSafe.eliminado_en">
                <span class="text-slate-500">Eliminado por:</span>
                <div class="font-medium text-slate-900">
                  {{ auditoriaSafe.eliminado_por || '—' }}
                </div>
                <div class="text-slate-500">
                  {{ auditoriaSafe.eliminado_en ? formatearFecha(auditoriaSafe.eliminado_en) : '—' }}
                </div>
              </div>
            </div>
          </div>

          <div v-if="selected" class="space-y-4">
            <!-- Información general -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Columna izquierda -->
              <div v-if="isEquipos">
                <p class="text-sm text-slate-600">
                  <strong>Equipo:</strong> {{ selected.nombre || 'Sin nombre' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.marca || selected.modelo">
                  <strong>Marca/Modelo:</strong>
                  {{ [selected.marca, selected.modelo].filter(Boolean).join(' · ') }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.codigo_interno">
                  <strong>Código interno:</strong> {{ selected.codigo_interno }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha:</strong>
                  {{ formatearFecha(selected.created_at || selected.fecha) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.estado)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full mr-1.5"
                      :class="obtenerColorPuntoEstado(selected.estado)"
                    ></span>
                    {{ obtenerLabelEstado(selected.estado) }}
                  </span>
                </p>
              </div>

              <div v-else-if="isRentas">
                <p class="text-sm text-slate-600">
                  <strong>Cliente:</strong> {{ selected.cliente?.nombre || 'Sin cliente' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.cliente?.email">
                  <strong>Email:</strong> {{ selected.cliente.email }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha inicio:</strong> {{ formatearFecha(selected.fecha_inicio || selected.created_at) }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.fecha_fin">
                  <strong>Fecha fin:</strong> {{ formatearFecha(selected.fecha_fin) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.estado)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="obtenerColorPuntoEstado(selected.estado)"></span>
                    {{ obtenerLabelEstado(selected.estado) }}
                  </span>
                </p>
              </div>

              <!-- ÓRDENES DE COMPRA -->
              <div v-else-if="isOrdenesCompra">
                <p class="text-sm text-slate-600">
                  <strong>Proveedor:</strong> {{ selected.proveedor?.nombre_razon_social || 'Sin proveedor' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.proveedor?.email">
                  <strong>Email:</strong> {{ selected.proveedor.email }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.proveedor?.telefono">
                  <strong>Teléfono:</strong> {{ selected.proveedor.telefono }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha de creación:</strong>
                  {{ formatearFecha(selected.created_at) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.estado)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="obtenerColorPuntoEstado(selected.estado)"></span>
                    {{ obtenerLabelEstado(selected.estado) }}
                  </span>
                </p>
              </div>

              <!-- COMPRAS -->
              <div v-else-if="isCompras">
                <p class="text-sm text-slate-600">
                  <strong>Proveedor:</strong> {{ selected.proveedor?.nombre_razon_social || 'Sin proveedor' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.proveedor?.email">
                  <strong>Email:</strong> {{ selected.proveedor.email }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha:</strong>
                  {{ formatearFecha(selected.created_at || selected.fecha) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.estado)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="obtenerColorPuntoEstado(selected.estado)"></span>
                    {{ obtenerLabelEstado(selected.estado) }}
                  </span>
                </p>
              </div>

              <!-- CLIENTES -->
              <div v-else-if="isClientes">
                <p class="text-sm text-slate-600">
                  <strong>Nombre/Razón Social:</strong> {{ selected.nombre_razon_social || 'Sin nombre' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.email">
                  <strong>Email:</strong> {{ selected.email }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.telefono">
                  <strong>Teléfono:</strong> {{ selected.telefono }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha de registro:</strong>
                  {{ formatearFecha(selected.created_at) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.activo ? '1' : '0')"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="obtenerColorPuntoEstado(selected.activo ? '1' : '0')"></span>
                    {{ obtenerLabelEstado(selected.activo ? '1' : '0') }}
                  </span>
                </p>
              </div>

              <!-- PRODUCTOS -->
              <div v-else-if="props.tipo === 'productos'">
                <p class="text-sm text-slate-600">
                  <strong>Nombre:</strong> {{ selected.nombre || 'Sin nombre' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.descripcion">
                  <strong>Descripción:</strong> {{ selected.descripcion }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.categoria">
                  <strong>Categoría:</strong> {{ selected.categoria?.nombre || 'Sin categoría' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.marca">
                  <strong>Marca:</strong> {{ selected.marca?.nombre || 'Sin marca' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.proveedor">
                  <strong>Proveedor:</strong> {{ selected.proveedor?.nombre_razon_social || 'Sin proveedor' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.precio_venta">
                  <strong>Precio de Venta:</strong> ${{ formatearMoneda(selected.precio_venta) }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.stock !== undefined">
                  <strong>Stock:</strong> {{ selected.stock }} unidades
                </p>
                <p class="text-sm text-slate-600" v-if="selected.stock_minimo">
                  <strong>Stock Mínimo:</strong> {{ selected.stock_minimo }} unidades
                </p>
                <p class="text-sm text-slate-600" v-if="selected.codigo">
                  <strong>Código:</strong> {{ selected.codigo }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.codigo_barras">
                  <strong>Código de Barras:</strong> {{ selected.codigo_barras }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha de creación:</strong>
                  {{ formatearFecha(selected.created_at) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.estado)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="obtenerColorPuntoEstado(selected.estado)"></span>
                    {{ obtenerLabelEstado(selected.estado) }}
                  </span>
                </p>
              </div>

              <!-- HERRAMIENTAS -->
              <div v-else-if="props.tipo === 'herramientas'">
                <p class="text-sm text-slate-600">
                  <strong>Nombre:</strong> {{ selected.nombre || 'Sin nombre' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.numero_serie">
                  <strong>Número de serie:</strong> {{ selected.numero_serie }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.tecnico">
                  <strong>Técnico asignado:</strong> {{ selected.tecnico.name || `${selected.tecnico.nombre || ''} ${selected.tecnico.apellido || ''}`.trim() }}
                </p>
                <p class="text-sm text-slate-600" v-else>
                  <strong>Técnico asignado:</strong> Sin asignar
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha de creación:</strong>
                  {{ formatearFecha(selected.created_at) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.tecnico ? 'asignada' : 'sin_asignar')"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="obtenerColorPuntoEstado(selected.tecnico ? 'asignada' : 'sin_asignar')"></span>
                    {{ obtenerLabelEstado(selected.tecnico ? 'asignada' : 'sin_asignar') }}
                  </span>
                </p>
              </div>

              <!-- SERVICIOS -->
              <div v-else-if="props.tipo === 'servicios'">
                <p class="text-sm text-slate-600">
                  <strong>Nombre:</strong> {{ selected.nombre || 'Sin nombre' }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.codigo">
                  <strong>Código:</strong> {{ selected.codigo }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.descripcion">
                  <strong>Descripción:</strong> {{ selected.descripcion }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.precio">
                  <strong>Precio:</strong> ${{ formatearMoneda(selected.precio) }}
                </p>
                <p class="text-sm text-slate-600" v-if="selected.duracion">
                  <strong>Duración:</strong> {{ selected.duracion }} minutos
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha de creación:</strong>
                  {{ formatearFecha(selected.created_at) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.estado)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="obtenerColorPuntoEstado(selected.estado)"></span>
                    {{ obtenerLabelEstado(selected.estado) }}
                  </span>
                </p>
              </div>

              <!-- Cotizaciones -->
              <div v-else-if="isCotizaciones">
                <p class="text-sm text-slate-600">
                  <strong>Cliente:</strong> {{ selected.cliente?.nombre || 'Sin cliente' }}
                </p>
                <p v-if="selected.cliente?.email" class="text-sm text-slate-600">
                  <strong>Email:</strong> {{ selected.cliente.email }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha:</strong>
                  {{ formatearFecha(selected.fecha_cotizacion || selected.created_at || selected.fecha) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.estado)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="obtenerColorPuntoEstado(selected.estado)"></span>
                    {{ obtenerLabelEstado(selected.estado) }}
                  </span>
                </p>
                <p v-if="isNumber(selected.total)" class="text-sm text-slate-600">
                  <strong>Total:</strong> ${{ formatearMoneda(selected.total) }}
                </p>
              </div>

              <!-- Otros (pedidos, ventas, etc.) -->
              <div v-else>
                <p class="text-sm text-slate-600">
                  <strong>Cliente:</strong> {{ selected.cliente?.nombre || 'Sin cliente' }}
                </p>
                <p v-if="selected.cliente?.email" class="text-sm text-slate-600">
                  <strong>Email:</strong> {{ selected.cliente.email }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Fecha:</strong>
                  {{ formatearFecha(selected.created_at || selected.fecha) }}
                </p>
                <p class="text-sm text-slate-600">
                  <strong>Estado:</strong>
                  <span
                    :class="obtenerClasesEstado(selected.estado)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="obtenerColorPuntoEstado(selected.estado)"></span>
                    {{ obtenerLabelEstado(selected.estado) }}
                  </span>
                </p>
              </div>

              <!-- Columna derecha -->
              <div>
                <p v-if="config.mostrarCampoExtra && config.campoExtra?.key" class="text-sm text-slate-600">
                  <strong>{{ config.campoExtra.label }}:</strong>
                  {{ selected[config.campoExtra.key] ?? 'N/A' }}
                </p>

                <p v-if="isNumber(selected.total)" class="text-sm text-slate-600">
                  <strong>Total:</strong> ${{ formatearMoneda(selected.total) }}
                </p>

                <template v-if="!isEquipos && !isOrdenesCompra">
                  <p class="text-sm text-slate-600">
                    <strong>Productos:</strong>
                    {{ (selected.productos || selected.items)?.length || 0 }} items
                  </p>
                </template>

                <template v-else>
                  <p class="text-sm text-slate-600" v-if="selected.numero_serie">
                    <strong>Número de serie:</strong> {{ selected.numero_serie }}
                  </p>
                </template>

                <!-- Dirección para clientes -->
                <div v-if="isClientes && (selected.calle || selected.colonia || selected.municipio)" class="mt-3 pt-3 border-t border-slate-200">
                  <p class="text-sm font-medium text-slate-900 mb-1">Dirección</p>
                  <p class="text-sm text-slate-600">
                    {{ [
                      selected.calle,
                      selected.numero_exterior,
                      selected.numero_interior ? 'Int. ' + selected.numero_interior : null,
                      selected.colonia,
                      selected.codigo_postal,
                      selected.municipio,
                      selected.estado,
                      selected.pais
                    ].filter(Boolean).join(', ') || 'Sin dirección' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Tabla de productos (no aplica a equipos ni clientes) -->
            <div v-if="!isEquipos && !isClientes && (selected.productos || selected.items)?.length" class="mt-4">
              <h4 class="text-sm font-medium text-slate-900 mb-2">Productos y Servicios</h4>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                  <thead class="bg-transparent dark:bg-slate-800/50">
                    <tr>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Nombre
                      </th>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Tipo
                      </th>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Cantidad
                      </th>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Precio
                      </th>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Descuento
                      </th>
                      <th class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Subtotal
                      </th>
                      <!-- Columnas adicionales para compras -->
                      <th v-if="isCompras" class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Stock Antes
                      </th>
                      <th v-if="isCompras" class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Stock Después
                      </th>
                      <th v-if="isCompras" class="px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Diferencia
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                    <tr v-for="producto in (selected.productos || selected.items)" :key="producto.id || producto.nombre">
                      <td class="px-4 py-2 text-sm text-slate-900">
                        {{ producto.nombre || 'Sin nombre' }}
                      </td>
                      <td class="px-4 py-2 text-sm text-slate-600 capitalize">
                        {{ producto.tipo || 'N/A' }}
                      </td>
                      <td class="px-4 py-2 text-sm text-slate-600">
                        {{ producto.cantidad || producto.pivot?.cantidad || 0 }}
                      </td>
                      <td class="px-4 py-2 text-sm text-slate-600">
                        ${{ formatearMoneda(producto.precio || producto.pivot?.precio || 0) }}
                      </td>
                      <td class="px-4 py-2 text-sm text-slate-600">
                        {{ producto.descuento || producto.pivot?.descuento || 0 }}%
                      </td>
                      <td class="px-4 py-2 text-sm text-slate-600">
                        ${{ formatearMoneda(producto.subtotal || ((producto.cantidad || producto.pivot?.cantidad || 0) * (producto.precio || producto.pivot?.precio || 0) * (1 - (producto.descuento || producto.pivot?.descuento || 0) / 100))) }}
                      </td>
                      <!-- Columnas adicionales para compras -->
                      <td v-if="isCompras" class="px-4 py-2 text-sm text-slate-600">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">
                          {{ producto.stock_antes || 0 }}
                        </span>
                      </td>
                      <td v-if="isCompras" class="px-4 py-2 text-sm text-slate-600">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                          {{ producto.stock_despues || 0 }}
                        </span>
                      </td>
                      <td v-if="isCompras" class="px-4 py-2 text-sm text-slate-600">
                        <span
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="{
                            'bg-emerald-100 text-emerald-800': (producto.diferencia_stock || 0) > 0,
                            'bg-rose-100 text-rose-800': (producto.diferencia_stock || 0) < 0,
                            'bg-slate-100 text-slate-800': (producto.diferencia_stock || 0) === 0
                          }"
                        >
                          <svg v-if="(producto.diferencia_stock || 0) > 0" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                          </svg>
                          <svg v-else-if="(producto.diferencia_stock || 0) < 0" class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L9 14.586V3a1 1 0 012 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                          </svg>
                          {{ (producto.diferencia_stock || 0) > 0 ? '+' : '' }}{{ producto.diferencia_stock || 0 }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <p v-else-if="!isEquipos && !isClientes && !isOrdenesCompra" class="text-sm text-slate-600">No hay productos asociados.</p>

            <!-- Totales para cotizaciones -->
            <div v-if="isCotizaciones && selected.productos?.length" class="mt-4 p-4 bg-transparent rounded-xl border border-slate-200">
              <h4 class="text-sm font-medium text-slate-900 mb-3">Resumen de Cotización</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-600">Subtotal:</span>
                  <span class="font-medium">${{ formatearMoneda(calcularSubtotalCotizacion()) }}</span>
                </div>
                <div v-if="calcularDescuentosItems() > 0" class="flex justify-between">
                  <span class="text-slate-600">Descuentos por Items:</span>
                  <span class="font-medium text-rose-600">-${{ formatearMoneda(calcularDescuentosItems()) }}</span>
                </div>
                <div v-if="(selected.descuento_general || 0) > 0" class="flex justify-between">
                  <span class="text-slate-600">Descuento General:</span>
                  <span class="font-medium text-rose-600">-${{ formatearMoneda(selected.descuento_general || 0) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">IVA:</span>
                  <span class="font-medium">${{ formatearMoneda(calcularIVACotizacion()) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-300 pt-2">
                  <span class="text-slate-900 font-semibold">Total:</span>
                  <span class="text-slate-900 font-bold">${{ formatearMoneda(calcularTotalCotizacion()) }}</span>
                </div>
              </div>
            </div>

            <!-- Totales para órdenes de compra -->
            <div v-if="isOrdenesCompra && selected.productos?.length" class="mt-4 p-4 bg-transparent rounded-xl border border-slate-200">
              <h4 class="text-sm font-medium text-slate-900 mb-3">Resumen de Orden de Compra</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-600">Subtotal:</span>
                  <span class="font-medium">${{ formatearMoneda(selected.subtotal || 0) }}</span>
                </div>
                <div v-if="(selected.descuento_items || 0) > 0" class="flex justify-between">
                  <span class="text-slate-600">Descuentos por Items:</span>
                  <span class="font-medium text-rose-600">-${{ formatearMoneda(selected.descuento_items || 0) }}</span>
                </div>
                <div v-if="(selected.descuento_general || 0) > 0" class="flex justify-between">
                  <span class="text-slate-600">Descuento General:</span>
                  <span class="font-medium text-rose-600">-${{ formatearMoneda(selected.descuento_general || 0) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">IVA:</span>
                  <span class="font-medium">${{ formatearMoneda(selected.iva || 0) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-300 pt-2">
                  <span class="text-slate-900 font-semibold">Total:</span>
                  <span class="text-slate-900 font-bold">${{ formatearMoneda(selected.total || 0) }}</span>
                </div>
              </div>
            </div>

            <!-- Totales para compras -->
            <div v-if="isCompras && selected.productos?.length" class="mt-4 p-4 bg-transparent rounded-xl border border-slate-200">
              <h4 class="text-sm font-medium text-slate-900 mb-3">Resumen de Compra</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-600">Subtotal:</span>
                  <span class="font-medium">${{ formatearMoneda(selected.subtotal || 0) }}</span>
                </div>
                <div v-if="(selected.descuento_items || 0) > 0" class="flex justify-between">
                  <span class="text-slate-600">Descuentos por Items:</span>
                  <span class="font-medium text-rose-600">-${{ formatearMoneda(selected.descuento_items || 0) }}</span>
                </div>
                <div v-if="(selected.descuento_general || 0) > 0" class="flex justify-between">
                  <span class="text-slate-600">Descuento General:</span>
                  <span class="font-medium text-rose-600">-${{ formatearMoneda(selected.descuento_general || 0) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">IVA:</span>
                  <span class="font-medium">${{ formatearMoneda(selected.iva || 0) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-300 pt-2">
                  <span class="text-slate-900 font-semibold">Total:</span>
                  <span class="text-slate-900 font-bold">${{ formatearMoneda(selected.total || 0) }}</span>
                </div>
              </div>
            </div>

            <!-- Totales para pedidos -->
            <div v-if="isPedidos && (selected.productos || selected.items)?.length" class="mt-4 p-4 bg-transparent rounded-xl border border-slate-200">
              <h4 class="text-sm font-medium text-slate-900 mb-3">Resumen de Pedido</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-600">Subtotal:</span>
                  <span class="font-medium">${{ formatearMoneda(selected.subtotal || 0) }}</span>
                </div>
                <div v-if="(selected.descuento_items || 0) > 0" class="flex justify-between">
                  <span class="text-slate-600">Descuentos por Items:</span>
                  <span class="font-medium text-rose-600">-${{ formatearMoneda(selected.descuento_items || 0) }}</span>
                </div>
                <div v-if="(selected.descuento_general || 0) > 0" class="flex justify-between">
                  <span class="text-slate-600">Descuento General:</span>
                  <span class="font-medium text-rose-600">-${{ formatearMoneda(selected.descuento_general || 0) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-600">IVA:</span>
                  <span class="font-medium">${{ formatearMoneda(selected.iva || 0) }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-300 pt-2">
                  <span class="text-slate-900 font-semibold">Total:</span>
                  <span class="text-slate-900 font-bold">${{ formatearMoneda(selected.total || 0) }}</span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-slate-600">No hay datos disponibles.</div>

          <!-- Botones de acción -->
          <div class="flex flex-wrap justify-end gap-2 mt-6">
            <!-- Cotizaciones -->
            <template v-if="isCotizaciones">
              <button
                v-if="config.acciones.enviarPedido && selected?.estado !== 'cancelado'"
                @click="confirmarEnvioPedido"
                class="px-4 py-2 text-white rounded-xl transition-colors"
                :class="{
                  'bg-brand-500 hover:bg-amber-600': !yaEnviado,
                  'bg-sky-600 hover:bg-sky-700': yaEnviado
                }"
              >
                {{ yaEnviado ? 'Reenviar a Pedido' : 'Enviar a Pedido' }}
              </button>
              <button
                v-if="config.acciones.imprimir && selected?.estado !== 'cancelado'"
                @click="onImprimir"
                class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
              >
                Imprimir
              </button>
            </template>

            <!-- Pedidos -->
            <template v-if="isPedidos">
              <button
                v-if="config.acciones.enviarAVenta"
                @click="confirmarEnvioAVenta"
                class="px-4 py-2 text-white rounded-xl transition-colors"
                :class="{
                  'bg-brand-500 hover:bg-amber-600': !yaConvertidoAVenta,
                  'bg-sky-600 hover:bg-sky-700': yaConvertidoAVenta
                }"
              >
                {{ yaConvertidoAVenta ? 'Reenviar a Venta' : 'Enviar a Venta' }}
              </button>
              <button
                v-if="config.acciones.imprimir && selected?.estado !== 'cancelado'"
                @click="onImprimir"
                class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
              >
                Imprimir
              </button>
              <button
                v-if="selected?.estado !== 'cancelado' && selected?.estado !== 'cerrado'"
                @click="emit('cerrar-pedido', selected)"
                class="px-4 py-2 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition-colors"
              >
                Cerrar Pedido
              </button>
            </template>

            <!-- Compras -->
            <template v-if="isCompras">
              <button
                v-if="config.acciones.recibirCompra && selected?.estado !== 'cancelado'"
                @click="confirmarRecibirCompra"
                class="px-4 py-2 text-white rounded-xl transition-colors"
                :class="{
                  'bg-emerald-600 hover:bg-emerald-700': !yaRecibida,
                  'bg-sky-600 hover:bg-sky-700': yaRecibida
                }"
              >
                {{ yaRecibida ? 'Reconfirmar Recepción' : 'Recibir Compra' }}
              </button>
            </template>

            <!-- Órdenes de Compra -->
            <template v-if="isOrdenesCompra">
              <button
                v-if="selected?.estado !== 'cancelado' && selected?.estado !== 'enviada'"
                @click="emit('confirmar-recepcion', selected)"
                class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors"
              >
                Enviar a Compra
              </button>
              <button
                v-if="selected?.estado !== 'cancelado' && !selected?.urgente"
                @click="emit('marcar-urgente', selected)"
                class="px-4 py-2 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition-colors"
              >
                Marcar como Urgente
              </button>
            </template>

            <!-- Rentas -->
            <template v-if="isRentas">
              <button
                class="px-3 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white"
                @click="emit('renovar', selected)"
                v-if="['activo','proximo_vencimiento','vencido'].includes(selected?.estado)"
                title="Renovar"
              >
                Renovar
              </button>
              <button
                class="px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white"
                @click="emit('suspender', selected)"
                v-if="selected?.estado === 'activo'"
                title="Suspender"
              >
                Suspender
              </button>
              <button
                class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white"
                @click="emit('reactivar', selected)"
                v-if="selected?.estado === 'suspendido'"
                title="Reactivar"
              >
                Reactivar
              </button>
            </template>

            <!-- Equipos -->
            <template v-if="isEquipos">
              <div class="flex gap-2 flex-wrap">
                <button class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white"
                        @click="emit('marcar-disponible', selected)">
                  Marcar disponible
                </button>
                <button class="px-3 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white"
                        @click="emit('marcar-reparacion', selected)">
                  En reparación
                </button>
                <button class="px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white"
                        @click="emit('marcar-fuera-servicio', selected)">
                  Fuera de servicio
                </button>
                <button class="px-3 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white"
                        @click="emit('programar-mantenimiento', selected)">
                  Programar mantenimiento
                </button>
              </div>
            </template>

            <!-- CLIENTES -->
            <template v-if="isClientes">
              <button
                v-if="config.acciones.imprimir"
                @click="onImprimir"
                class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
              >
                Imprimir
              </button>
              <button
                v-if="config.acciones.editar"
                @click="onEditar"
                class="px-4 py-2 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition-colors"
              >
                Editar
              </button>
            </template>

            <!-- Ventas -->
            <template v-if="isVentas">
              <button
                v-if="config.acciones.imprimir && selected?.estado !== 'cancelado'"
                @click="onImprimir"
                class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
              >
                Imprimir
              </button>
            </template>

            <!-- Comunes -->
            <button
              v-if="!isClientes && !isCotizaciones && !isPedidos && !isVentas && config.acciones.imprimir && selected?.estado !== 'cancelado'"
              @click="onImprimir"
              class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors"
            >
              Imprimir
            </button>
            <button
              v-if="!isClientes && config.acciones.editar && selected?.estado !== 'cancelado'"
              @click="onEditar"
              class="px-4 py-2 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition-colors"
            >
              Editar
            </button>
            <button
              @click="onClose"
              class="px-4 py-2 bg-slate-300 rounded-xl hover:bg-slate-400 transition-colors"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Modal de confirmación: Reenviar a Pedido -->
  <Transition name="modal">
    <div
      v-if="showConfirmReenvioPedido"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showConfirmReenvioPedido = false"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="text-center">
          <div class="w-12 h-12 mx-auto bg-sky-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium mb-2">¿Reenviar a Pedido?</h3>
          <p class="text-slate-600 mb-6">
            Este documento ya fue enviado anteriormente ({{ formatearFecha(selected?.updated_at) }}).
            ¿Deseas crear un nuevo pedido?
          </p>
          <div class="flex gap-3">
            <button
              @click="showConfirmReenvioPedido = false"
              class="flex-1 px-4 py-2 bg-slate-200 rounded-xl hover:bg-slate-300 transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="reenviarAPedido"
              class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
            >
              Reenviar
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Modal de confirmación: Reenviar a Venta -->
  <Transition name="modal">
    <div
      v-if="showConfirmReenvioVenta"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showConfirmReenvioVenta = false"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="text-center">
          <div class="w-12 h-12 mx-auto bg-emerald-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
            </svg>
          </div>
          <h3 class="text-lg font-medium mb-2">¿Reenviar a Venta?</h3>
          <p class="text-slate-600 mb-6">
            Este pedido ya fue convertido en venta anteriormente ({{ formatearFecha(selected?.updated_at) }}).
            ¿Deseas crear una nueva venta?
          </p>
          <div class="flex gap-3">
            <button
              @click="showConfirmReenvioVenta = false"
              class="flex-1 px-4 py-2 bg-slate-200 rounded-xl hover:bg-slate-300 transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="reenviarAVenta"
              class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors"
            >
              Reenviar
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { computed, ref, watch, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  mode: { type: String, default: 'details', validator: (v) => ['confirm','confirm-duplicate','confirm-email','confirm-cancel','close','details'].includes(v) },
  selected: { type: Object, default: null },
  tipo: {
    type: String,
    required: true,
    validator: (v) => ['cotizaciones','pedidos','ventas','compras','ordenescompra','rentas','equipos','clientes','productos','herramientas','servicios','citas'].includes(v)
  },
  // NUEVO: pasar auditoría desde el padre (o venir en selected.metadata)
  auditoria: {
    type: Object,
    default: null // { creado_por, actualizado_por, eliminado_por, creado_en, actualizado_en, eliminado_en }
  }
})

const emit = defineEmits([
  'close', 'confirm-delete', 'confirm-duplicate', 'confirm-email', 'confirm-venta', 'editar', 'imprimir',
  'enviar-pedido', 'enviar-a-venta',
  'recibir-compra',
  'enviarVenta',
  'enviarCotizacion',
  'confirmar-recepcion',
  'enviarOrden',
  'recibirOrden',
  // Acciones para equipos
  'renovar', 'suspender', 'reactivar',
  'cambiar-estado', 'programar-mantenimiento',
  'marcar-disponible', 'marcar-reparacion', 'marcar-fuera-servicio'
])

const isCotizaciones = computed(() => props.tipo === 'cotizaciones')
const isPedidos      = computed(() => props.tipo === 'pedidos')
const isVentas       = computed(() => props.tipo === 'ventas')
const isCompras      = computed(() => props.tipo === 'compras')
const isOrdenesCompra= computed(() => props.tipo === 'ordenescompra')
const isRentas       = computed(() => props.tipo === 'rentas')
const isEquipos      = computed(() => props.tipo === 'equipos')
const isClientes     = computed(() => props.tipo === 'clientes')

// Estados de confirmación
const showConfirmReenvioPedido = ref(false)
const showConfirmReenvioVenta  = ref(false)

// Focus management
const modalRef = ref(null)
const focusFirst = () => { try { modalRef.value?.focus() } catch {} }
watch(() => props.show, (v) => { if (v) setTimeout(focusFirst, 0) })

// Cerrar con tecla ESC también desde window
const onKey = (e) => { if (e.key === 'Escape' && props.show) onClose() }
onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))

// Config dinámica por tipo (NUEVO: activo folio en cotizaciones)
const config = computed(() => {
  const baseEstados = (map) => map || {}
  const configs = {
    cotizaciones: {
      titulo: 'Cotización',
      mostrarCampoExtra: true, // habilitado
      campoExtra: { key: 'numero_cotizacion', label: 'N° Cotización' },
      acciones: { editar: true, imprimir: true, enviarPedido: true, enviarAVenta: false },
      estados: baseEstados({
        borrador: { label: 'Borrador', classes: 'bg-slate-100 text-slate-800', color: 'bg-slate-400' },
        pendiente: { label: 'Pendiente', classes: 'bg-yellow-100 text-yellow-800', color: 'bg-yellow-400' },
        enviado_pedido: { label: 'Enviado a Pedido', classes: 'bg-sky-100 text-sky-800', color: 'bg-blue-400' },
        enviado_venta: { label: 'Enviado a Venta', classes: 'bg-sky-100 text-sky-800', color: 'bg-sky-400' },
        aprobado: { label: 'Aprobada', classes: 'bg-emerald-100 text-emerald-800', color: 'bg-emerald-400' },
        rechazado: { label: 'Rechazada', classes: 'bg-rose-100 text-rose-800', color: 'bg-rose-400' },
        cancelado: { label: 'Cancelada', classes: 'bg-slate-100 text-slate-800', color: 'bg-slate-400' }
      })
    },
    pedidos: {
      titulo: 'Pedido',
      mostrarCampoExtra: true,
      campoExtra: { key: 'numero_pedido', label: 'N° Pedido' },
      acciones: { editar: true, imprimir: true, enviarPedido: false, enviarAVenta: true },
      estados: baseEstados({
        borrador: { label: 'Borrador', classes: 'bg-slate-100 text-slate-800', color: 'bg-slate-400' },
        pendiente: { label: 'Pendiente', classes: 'bg-yellow-100 text-yellow-800', color: 'bg-yellow-400' },
        confirmado: { label: 'Confirmado', classes: 'bg-sky-100 text-sky-800', color: 'bg-blue-400' },
        en_preparacion: { label: 'En Preparación', classes: 'bg-brand-100 text-amber-800', color: 'bg-orange-400' },
        listo_entrega: { label: 'Listo para Entrega', classes: 'bg-purple-100 text-purple-800', color: 'bg-purple-400' },
        entregado: { label: 'Entregado', classes: 'bg-emerald-100 text-emerald-800', color: 'bg-emerald-400' },
        enviado_venta: { label: 'Enviado a Venta', classes: 'bg-sky-100 text-sky-800', color: 'bg-sky-400' },
        cancelado: { label: 'Cancelado', classes: 'bg-rose-100 text-rose-800', color: 'bg-rose-400' }
      })
    },
    ventas: {
      titulo: 'Venta',
      mostrarCampoExtra: true,
      // soporta numero_venta o numero_factura
      campoExtra: { key: null, label: 'N° Venta/Factura' },
      acciones: { editar: false, imprimir: true, enviarPedido: false, enviarAVenta: false },
      estados: baseEstados({
        borrador: { label: 'Borrador', classes: 'bg-slate-100 text-slate-800', color: 'bg-slate-400' },
        facturado: { label: 'Facturado', classes: 'bg-sky-100 text-sky-800', color: 'bg-blue-400' },
        pagado: { label: 'Pagado', classes: 'bg-emerald-100 text-emerald-800', color: 'bg-emerald-400' },
        vencido: { label: 'Vencido', classes: 'bg-rose-100 text-rose-800', color: 'bg-rose-400' },
        anulado: { label: 'Anulado', classes: 'bg-slate-100 text-slate-800', color: 'bg-slate-400' }
      })
    },
    rentas: {
      titulo: 'Renta',
      mostrarCampoExtra: true,
      campoExtra: { key: 'numero_contrato', label: 'N° Contrato' },
      acciones: { editar: true, imprimir: true, enviarPedido: false, enviarAVenta: false },
      estados: baseEstados({
        borrador: { label: 'Borrador', classes: 'bg-slate-100 text-slate-700', color: 'bg-slate-400' },
        activo: { label: 'Activo', classes: 'bg-emerald-100 text-emerald-700', color: 'bg-emerald-400' },
        proximo_vencimiento: { label: 'Próximo Vencimiento', classes: 'bg-brand-100 text-amber-800', color: 'bg-orange-400' },
        vencido: { label: 'Vencido', classes: 'bg-rose-100 text-rose-700', color: 'bg-rose-400' },
        moroso: { label: 'Moroso', classes: 'bg-rose-200 text-rose-800', color: 'bg-rose-500' },
        suspendido: { label: 'Suspendido', classes: 'bg-yellow-100 text-yellow-700', color: 'bg-yellow-400' },
        finalizado: { label: 'Finalizado', classes: 'bg-slate-100 text-slate-600', color: 'bg-slate-400' },
        anulado: { label: 'Anulado', classes: 'bg-slate-100 text-slate-500', color: 'bg-slate-400' },
        sin_estado: { label: 'Sin Estado', classes: 'bg-slate-100 text-slate-500', color: 'bg-slate-400' }
      })
    },
    equipos: {
      titulo: 'Equipo',
      mostrarCampoExtra: true,
      campoExtra: { key: 'numero_serie', label: 'Serie' },
      acciones: { editar: true, imprimir: true, enviarPedido: false, enviarAVenta: false },
      estados: baseEstados({
        disponible: { label: 'Disponible', classes: 'bg-emerald-100 text-emerald-700', color: 'bg-emerald-500' },
        rentado: { label: 'Rentado', classes: 'bg-sky-100 text-sky-800', color: 'bg-brand-500' },
        mantenimiento: { label: 'Mantenimiento', classes: 'bg-brand-100 text-amber-700', color: 'bg-brand-500' },
        reparacion: { label: 'Reparación', classes: 'bg-brand-100 text-amber-800', color: 'bg-orange-500' },
        fuera_servicio: { label: 'Fuera de servicio', classes: 'bg-rose-100 text-rose-700', color: 'bg-rose-500' },
        sin_estado: { label: 'Sin Estado', classes: 'bg-slate-100 text-slate-600', color: 'bg-slate-400' }
      })
    },
    clientes: {
      titulo: 'Cliente',
      mostrarCampoExtra: true,
      campoExtra: { key: 'rfc', label: 'RFC' },
      acciones: { editar: true, imprimir: false, enviarPedido: false, enviarAVenta: false },
      estados: baseEstados({
        '1': { label: 'Activo',   classes: 'bg-emerald-100 text-emerald-700', color: 'bg-emerald-400' },
        '0': { label: 'Inactivo', classes: 'bg-rose-100 text-rose-700',        color: 'bg-rose-400' },
      })
    },
    productos: {
      titulo: 'Producto',
      mostrarCampoExtra: false,
      campoExtra: null,
      acciones: { editar: true, imprimir: false, enviarPedido: false, enviarAVenta: false },
      estados: baseEstados()
    },
    herramientas: {
      titulo: 'Herramienta',
      mostrarCampoExtra: true,
      campoExtra: { key: 'numero_serie', label: 'N° Serie' },
      acciones: { editar: true, imprimir: false, enviarPedido: false, enviarAVenta: false },
      estados: baseEstados({
        'asignada': { label: 'Asignada', classes: 'bg-emerald-100 text-emerald-700', color: 'bg-emerald-400' },
        'sin_asignar': { label: 'Sin asignar', classes: 'bg-brand-100 text-amber-800', color: 'bg-orange-400' }
      })
    },
    servicios: {
      titulo: 'Servicio',
      mostrarCampoExtra: true,
      campoExtra: { key: 'codigo', label: 'Código' },
      acciones: { editar: true, imprimir: false, enviarPedido: false, enviarAVenta: false },
      estados: baseEstados({
        'activo': { label: 'Activo', classes: 'bg-emerald-100 text-emerald-700', color: 'bg-emerald-400' },
        'inactivo': { label: 'Inactivo', classes: 'bg-rose-100 text-rose-700', color: 'bg-rose-400' }
      })
    },
    compras: {
      titulo: 'Compra',
      mostrarCampoExtra: true,
      campoExtra: { key: 'numero_compra', label: 'N° Compra' },
      acciones: { editar: true, imprimir: true, enviarPedido: false, enviarAVenta: false, recibirCompra: false },
      estados: baseEstados({
        borrador: { label: 'Borrador', classes: 'bg-slate-100 text-slate-800', color: 'bg-slate-400' },
        pendiente: { label: 'Pendiente', classes: 'bg-yellow-100 text-yellow-800', color: 'bg-yellow-400' },
        aprobado: { label: 'Aprobada', classes: 'bg-sky-100 text-sky-800', color: 'bg-blue-400' },
        recibido: { label: 'Recibida', classes: 'bg-emerald-100 text-emerald-800', color: 'bg-emerald-400' },
        rechazado: { label: 'Rechazada', classes: 'bg-rose-100 text-rose-800', color: 'bg-rose-400' },
        cancelado: { label: 'Cancelada', classes: 'bg-slate-100 text-slate-800', color: 'bg-slate-400' }
      })
    },
    ordenescompra: {
      titulo: 'Orden de Compra',
      mostrarCampoExtra: true,
      campoExtra: { key: 'numero_orden', label: 'N° Orden' },
      acciones: { editar: true, imprimir: true, enviarPedido: false, enviarAVenta: false, recibirCompra: false },
      estados: baseEstados({
        borrador: { label: 'Borrador', classes: 'bg-slate-100 text-slate-800', color: 'bg-slate-400' },
        pendiente: { label: 'Pendiente', classes: 'bg-yellow-100 text-yellow-800', color: 'bg-yellow-400' },
        aprobado: { label: 'Aprobado', classes: 'bg-sky-100 text-sky-800', color: 'bg-blue-400' },
        recibida: { label: 'Recibida', classes: 'bg-emerald-100 text-emerald-800', color: 'bg-emerald-400' },
        cancelada: { label: 'Cancelada', classes: 'bg-rose-100 text-rose-800', color: 'bg-rose-400' }
      })
    },
    citas: {
      titulo: 'Cita',
      mostrarCampoExtra: true,
      campoExtra: { key: 'fecha_hora', label: 'Fecha' },
      acciones: { editar: true, imprimir: false, enviarPedido: false, enviarAVenta: false },
      estados: baseEstados({
        pendiente: { label: 'Pendiente', classes: 'bg-brand-100 text-amber-700', color: 'bg-brand-500' },
        programado: { label: 'Programado', classes: 'bg-sky-100 text-sky-800', color: 'bg-blue-500' },
        en_proceso: { label: 'En proceso', classes: 'bg-sky-100 text-sky-800', color: 'bg-sky-500' },
        completado: { label: 'Completado', classes: 'bg-emerald-100 text-emerald-700', color: 'bg-emerald-500' },
        cancelado: { label: 'Cancelado', classes: 'bg-rose-100 text-rose-700', color: 'bg-rose-500' }
      })
    }
  }
  return configs[props.tipo] || configs.cotizaciones
})

// NUEVO: Folio (detecta varias claves según tipo/datos)
const folioLabel = computed(() => {
  if (isCotizaciones.value) return 'N° Cotización'
  if (isPedidos.value)      return 'N° Pedido'
  if (isOrdenesCompra.value) return 'N° Orden'
  if (props.tipo === 'ventas') return 'N° Venta/Factura'
  if (isRentas.value)       return 'N° Contrato'
  return 'Folio'
})
const folioValue = computed(() => {
  const s = props.selected || {}
  // soporta llaves alternativas
  return s.numero_cotizacion || s.numero_pedido || s.numero_orden || s.numero_venta || s.numero_factura || s.numero_contrato || null
})

// NUEVO: Auditoría (usa prop o selected.metadata)
const auditoriaSafe = computed(() => {
  return props.auditoria ?? props.selected?.metadata ?? null
})
const auditoriaBoxVisible = computed(() => !!auditoriaSafe.value)

// Verificaciones de estado para flujos
const yaEnviado = computed(() => ['enviado_pedido','convertido_pedido'].includes(props.selected?.estado))
const yaConvertidoAVenta = computed(() => ['enviado_venta','facturado','pagado','convertido_venta'].includes(props.selected?.estado))
const yaRecibida = computed(() => ['recibido','recibida'].includes(props.selected?.estado))

// Helpers de formato
const isNumber = (n) => Number.isFinite(parseFloat(n))

const formatearFecha = (date) => {
  if (!date) return 'Fecha no disponible'
  try {
    const t = new Date(date).getTime()
    if (Number.isNaN(t)) return 'Fecha inválida'
    return new Date(t).toLocaleDateString('es-MX', {
      year: 'numeric', month: 'long', day: 'numeric',
      hour: '2-digit', minute: '2-digit'
    })
  } catch { return 'Fecha inválida' }
}

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe)
}

// Funciones de cálculo para cotizaciones
const calcularSubtotalCotizacion = () => {
  if (!props.selected?.productos) return 0
  return props.selected.productos.reduce((sum, item) => {
    const cantidad = parseFloat(item.cantidad || 0)
    const precio = parseFloat(item.precio || 0)
    return sum + (cantidad * precio)
  }, 0)
}

const calcularDescuentosItems = () => {
  if (!props.selected?.productos) return 0
  return props.selected.productos.reduce((sum, item) => {
    const cantidad = parseFloat(item.cantidad || 0)
    const precio = parseFloat(item.precio || 0)
    const descuento = parseFloat(item.descuento || 0)
    return sum + (cantidad * precio * descuento / 100)
  }, 0)
}

const calcularIVACotizacion = () => {
  return parseFloat(props.selected?.iva || 0)
}

const calcularTotalCotizacion = () => {
  const subtotal = calcularSubtotalCotizacion()
  const descuentosItems = calcularDescuentosItems()
  const descuentoGeneral = parseFloat(props.selected?.descuento_general || 0)
  const subtotalConDescuentos = subtotal - descuentosItems - descuentoGeneral
  const iva = parseFloat(props.selected?.iva || 0)
  return subtotalConDescuentos + iva
}

// Visual estados
const obtenerClasesEstado = (estado) => config.value.estados[estado]?.classes || 'bg-slate-100 text-slate-800'
const obtenerColorPuntoEstado = (estado) => config.value.estados[estado]?.color || 'bg-slate-400'
const obtenerLabelEstado = (estado) => config.value.estados[estado]?.label || 'Pendiente'

// Flujo cotizaciones/pedidos
const confirmarEnvioPedido = () => { yaEnviado.value ? (showConfirmReenvioPedido.value = true) : emit('enviar-pedido', props.selected) }
const confirmarEnvioAVenta  = () => { yaConvertidoAVenta.value ? (showConfirmReenvioVenta.value = true) : emit('enviar-a-venta', props.selected) }
const reenviarAPedido       = () => { showConfirmReenvioPedido.value = false; emit('enviar-pedido', { ...props.selected, forzarReenvio: true }) }
const reenviarAVenta        = () => { showConfirmReenvioVenta.value = false; emit('enviar-a-venta', { ...props.selected, forzarReenvio: true }) }

// Flujo compras
const confirmarRecibirCompra = () => { emit('recibir-compra', props.selected) }

// Emits comunes
const onCancel  = () => emit('close')
const onConfirm = () => emit('confirm-delete')
const onConfirmDuplicate = () => emit('confirm-duplicate')
const onConfirmEmail = () => emit('confirm-email')
const onConfirmVenta = () => emit('confirm-venta')
const onClose   = () => emit('close')
const onImprimir= () => emit('imprimir', props.selected)
const onEditar  = () => emit('editar', props.selected?.id)
const onDuplicar= () => emit('confirm-duplicate')
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; transform: scale(0.97); }
.modal-enter-to,
.modal-leave-from { opacity: 1; transform: scale(1); }
</style>
