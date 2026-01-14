<template>
  <Transition name="modal">
    <div
      v-if="show"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="onClose"
    >
      <div
        class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 outline-none"
        role="dialog"
        aria-modal="true"
        aria-label="Modal de Venta"
        tabindex="-1"
        ref="modalRef"
        @keydown.esc.prevent="onClose"
      >

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
          <!-- Columna Principal -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Información de Venta -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-t-lg">
                <div class="flex justify-between items-start">
                  <div>
                    <h2 class="text-xl font-bold">NOTA DE VENTA</h2>
                    <p class="text-blue-100 text-sm mt-1">{{ selected.numero_venta }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-blue-100">Fecha</p>
                    <p class="font-semibold">{{ formatearFecha(selected.created_at) }}</p>
                  </div>
                </div>
              </div>

              <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="grid grid-cols-2 gap-6">
                  <!-- Cliente -->
                  <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Cliente</h3>
                    <p class="text-base font-bold text-gray-900">{{ selected.cliente?.nombre_razon_social || 'Desconocido' }}</p>
                    <div class="mt-2 space-y-1 text-sm text-gray-600">
                      <p v-if="selected.cliente?.rfc && selected.cliente.rfc !== 'N/A'">
                        <span class="font-medium">RFC:</span> {{ selected.cliente.rfc }}
                      </p>
                      <p v-if="selected.cliente?.email && selected.cliente.email !== 'N/A'">
                        <span class="font-medium">Email:</span> {{ selected.cliente.email }}
                      </p>
                      <p v-if="selected.cliente?.telefono && selected.cliente.telefono !== 'N/A'">
                        <span class="font-medium">Tel:</span> {{ selected.cliente.telefono }}
                      </p>
                    </div>
                  </div>

                  <!-- Información Adicional -->
                  <div>
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Información</h3>
                    <div class="space-y-2 text-sm">
                      <div v-if="selected.almacen" class="space-y-1">
                        <div class="flex items-center">
                          <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                          </svg>
                          <span class="text-gray-600">Almacén:</span>
                          <span class="ml-2 font-medium text-gray-900">{{ selected.almacen.nombre }}</span>
                        </div>
                        <div v-if="selected.almacen.descripcion" class="ml-6 text-sm text-gray-500">
                          {{ selected.almacen.descripcion }}
                        </div>
                        <div v-if="selected.almacen.ubicacion" class="ml-6 text-sm text-gray-500">
                          📍 {{ selected.almacen.ubicacion }}
                        </div>
                      </div>
                      <div v-if="selected.vendedor" class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-gray-600">Vendedor:</span>
                        <span class="ml-2 font-medium text-gray-900">{{ selected.vendedor.nombre }}</span>
                      </div>
                      <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span class="text-gray-600">Método de pago:</span>
                        <span class="ml-2 font-medium text-gray-900 capitalize">{{ selected.metodo_pago || selected.metodo_pago_nombre || 'No especificado' }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Notas de Venta -->
              <div v-if="selected.notas" class="px-6 py-3 bg-yellow-50 border-b border-yellow-100">
                <div class="flex items-start">
                  <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                  </svg>
                  <div>
                    <p class="text-xs font-semibold text-yellow-800 uppercase">Notas Adicionales</p>
                    <p class="text-sm text-yellow-900 mt-1">{{ selected.notas }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Productos y Servicios -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <div class="flex justify-between items-center">
                  <h2 class="text-lg font-semibold text-gray-900">Productos y Servicios</h2>
                  <span class="text-sm text-gray-500">{{ itemsCalculados.length + (productoPrincipal ? 1 : 0) }} items</span>
                </div>
              </div>

              <div v-if="itemsCalculados.length > 0 || productoPrincipal" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto/Servicio</th>
                      <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cant.</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Unit.</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Desc.</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <template v-for="producto in productoPrincipal ? [productoPrincipal] : itemsCalculados" :key="producto.id">
                      <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                          <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg"
                                 :class="producto.tipo === 'producto' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700'">
                              <svg v-if="producto.tipo === 'producto'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                              </svg>
                              <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                              </svg>
                            </div>
                            <div class="ml-4">
                              <div class="text-sm font-medium text-gray-900">{{ producto.nombre }}</div>
                              <div class="flex items-center mt-1 space-x-2">
                                <span :class="producto.tipo === 'producto' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium capitalize">
                                  {{ producto.tipo }}
                                </span>
                                <span v-if="producto.requiere_serie && producto.series && producto.series.length > 0"
                                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                  </svg>
                                  {{ producto.series.length }} serie(s)
                                </span>
                              </div>
                            </div>
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                          <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 text-sm font-semibold text-gray-900">
                            {{ producto.pivot.cantidad }}
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">{{ formatCurrency(producto.pivot.precio) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                          <span v-if="producto.pivot.descuento > 0" class="text-red-600 font-medium">
                            {{ producto.pivot.descuento }}%
                          </span>
                          <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-gray-900">
                          {{ formatCurrency(producto.pivot.subtotal) }}
                        </td>
                      </tr>
                      <tr v-if="producto.requiere_serie && producto.series && producto.series.length > 0" class="bg-blue-50">
                        <td colspan="5" class="px-6 py-3">
                          <div class="text-xs font-medium text-blue-900 mb-2 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            Series vendidas:
                          </div>
                          <div class="flex flex-wrap gap-2">
                            <div v-for="(serie, idx) in producto.series" :key="idx"
                                 class="inline-flex items-center bg-white border border-blue-200 rounded-md px-3 py-1.5 shadow-sm">
                              <span class="text-sm font-mono font-semibold text-gray-900">{{ serie.numero_serie }}</span>
                              <span class="ml-2 text-xs text-gray-500">Almacén {{ serie.almacen || 'N/A' }}</span>
                            </div>
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>

              <div v-else class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay productos asociados</h3>
                <p class="mt-1 text-sm text-gray-500">Esta venta no tiene productos o servicios registrados.</p>
              </div>
            </div>
          </div>

          <!-- Columna Lateral -->
          <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Resumen</h2>
              </div>
              <div class="px-6 py-4 space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Subtotal</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(selected.subtotal || 0) }}</span>
                </div>
                <div v-if="selected.descuento_general > 0" class="flex justify-between text-sm">
                  <span class="text-gray-600">Descuento General</span>
                  <span class="font-medium text-red-600">-{{ formatCurrency(selected.descuento_general) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">IVA (16%)</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(selected.iva || 0) }}</span>
                </div>
                <div class="pt-3 border-t-2 border-gray-300">
                  <div class="flex justify-between items-center">
                    <span class="text-base font-semibold text-gray-900">Total</span>
                    <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(selected.total || 0) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div :class="selected.pagado ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'"
                 class="rounded-lg border p-4">
              <div class="flex items-center mb-3">
                <svg v-if="selected.pagado" class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 :class="selected.pagado ? 'text-green-900' : 'text-yellow-900'" class="text-sm font-semibold">
                  {{ selected.pagado ? 'Pago Completado' : 'Pago Pendiente' }}
                </h3>
              </div>
              <dl v-if="selected.pagado" class="space-y-2">
                <div>
                  <dt class="text-xs text-green-700">Método de Pago</dt>
                  <dd class="text-sm font-medium text-green-900 capitalize">{{ selected.metodo_pago || selected.metodo_pago_nombre || 'N/A' }}</dd>
                </div>
                <!-- Estado de depósito basado en EntregaDinero -->
                <div v-if="selected.entrega_dinero && selected.entrega_dinero.estado === 'recibido' && selected.entrega_dinero.cuenta_bancaria">
                  <dt class="text-xs text-green-700">Depositado en</dt>
                  <dd class="text-sm font-medium text-green-900">
                    🏦 {{ selected.entrega_dinero.cuenta_bancaria.banco }} - {{ selected.entrega_dinero.cuenta_bancaria.nombre }}
                  </dd>
                </div>
                <div v-else-if="selected.cuenta_bancaria">
                  <dt class="text-xs text-green-700">Depositado en</dt>
                  <dd class="text-sm font-medium text-green-900">
                    🏦 {{ selected.cuenta_bancaria.banco }} - {{ selected.cuenta_bancaria.nombre }}
                  </dd>
                </div>
                <div v-else>
                  <dt class="text-xs text-yellow-700">Estado del Depósito</dt>
                  <dd class="text-sm font-medium text-yellow-800">
                    ⏳ {{ selected.entrega_dinero?.estado === 'pendiente' ? 'Pendiente de entrega' : 'No depositado' }}
                  </dd>
                </div>
                <div v-if="selected.fecha_pago">
                  <dt class="text-xs text-green-700">Fecha de Pago</dt>
                  <dd class="text-sm font-medium text-green-900">{{ formatearFecha(selected.fecha_pago) }}</dd>
                </div>
              </dl>
              <p v-else class="text-sm text-yellow-800">
                Esta venta aún no ha sido pagada.
              </p>
            </div>

            <div v-if="auditoriaSafe" class="bg-white rounded-lg shadow-sm border border-gray-200">
              <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Auditoría</h2>
              </div>
              <div class="px-6 py-4 space-y-3 text-sm">
                <div>
                  <dt class="text-xs font-medium text-gray-500 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Creado por
                  </dt>
                  <dd class="mt-1 text-gray-900 font-medium">{{ auditoriaSafe.creado_por || 'N/A' }}</dd>
                  <dd class="text-xs text-gray-500">{{ formatearFechaHora(auditoriaSafe.creado_en || selected.created_at) }}</dd>
                </div>
                <div>
                  <dt class="text-xs font-medium text-gray-500 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Última Actualización por
                  </dt>
                  <dd class="mt-1 text-gray-900 font-medium">{{ auditoriaSafe.actualizado_por || 'N/A' }}</dd>
                  <dd class="text-xs text-gray-500">{{ formatearFechaHora(auditoriaSafe.actualizado_en || selected.updated_at) }}</dd>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-2">
              <!-- ✅ FIX: Usar JavaScript para mostrar PDF con blob (bypassa problemas de IIS) -->
              <button @click="verPdfEnNavegador(selected.id)"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Ver PDF
              </button>
              <button @click="descargarPdf(selected.id, selected.numero_venta)"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Descargar PDF
              </button>
              <Link :href="route('ventas.ticket', selected.id)" target="_blank"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Imprimir Ticket
              </Link>
              <Link v-if="!selected.pagado && selected.estado !== 'cancelada'" :href="route('ventas.edit', selected.id)"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar Venta
              </Link>
              <button v-if="!selected.pagado" @click="$emit('marcar-pagado', selected)"
                      class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Marcar como Pagado
              </button>
              <button v-if="selected.estado !== 'cancelada'" @click="$emit('cancelar', selected.id)"
                      class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Cancelar Venta
              </button>
              <button v-if="selected.estado === 'cancelada' && !selected.esta_facturada && !selected.cfdi_cancelado" @click="$emit('eliminar', selected.id)"
                      class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Eliminar Venta
              </button>

              <!-- Indicador de que no se puede eliminar (tiene CFDI) -->
              <div v-if="selected.estado === 'cancelada' && (selected.esta_facturada || selected.cfdi_cancelado)"
                   class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-200 rounded-md shadow-sm text-sm font-medium text-gray-400 bg-gray-100 cursor-not-allowed">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                No se puede eliminar (CFDI SAT)
              </div>

              <!-- Facturación SAT -->
              <div v-if="selected.estado !== 'cancelada'" class="border-t border-gray-100 pt-2 mt-2 space-y-2">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-1">Facturación SAT</p>
                
                <div v-if="selected.esta_facturada" class="space-y-2">
                  <div class="p-2 bg-blue-50 border border-blue-100 rounded text-[10px] text-blue-700 font-mono break-all">
                    UUID: {{ selected.factura_uuid || 'Generado' }}
                  </div>
                  <div class="grid grid-cols-2 gap-2 mt-2">
                    <button @click="verFacturaSat(selected.id)"
                           class="w-full inline-flex justify-center items-center px-3 py-1.5 border border-blue-200 rounded text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                      <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      Ver PDF
                    </button>
                    
                    <button @click="verXmlSat(selected.id)"
                           class="w-full inline-flex justify-center items-center px-3 py-1.5 border border-gray-200 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                      <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                      </svg>
                      Ver XML
                    </button>

                    <button @click="descargarXmlSat(selected.id)"
                           class="w-full inline-flex justify-center items-center px-3 py-1.5 border border-gray-200 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors col-span-2">
                      <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                      </svg>
                      Descargar XML
                    </button>

                    <button @click="enviarCorreoFactura(selected.id)"
                           :disabled="isSendingEmail"
                           class="w-full inline-flex justify-center items-center px-3 py-1.5 border border-purple-200 rounded text-xs font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 transition-colors col-span-2">
                      <svg v-if="!isSendingEmail" class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                      <svg v-else class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-purple-700" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      {{ isSendingEmail ? 'Enviando...' : 'Enviar por Correo' }}
                    </button>
                  </div>

                  <div v-if="!showCancelFacturaOptions" class="mt-1">
                    <button @click="showCancelFacturaOptions = true"
                           class="w-full inline-flex justify-center items-center px-3 py-1.5 border border-red-200 rounded text-[10px] font-medium text-red-700 bg-white hover:bg-red-50 transition-colors">
                      <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Cancelar CFDI
                    </button>
                  </div>

                  <div v-else class="mt-2 p-2 bg-red-50 rounded border border-red-100 space-y-2">
                    <div>
                      <label class="block text-[9px] font-bold text-red-700 uppercase mb-1">Motivo de Cancelación</label>
                      <select v-model="cancelFacturaParams.motivo" class="w-full text-[10px] py-1 px-2 border-red-200 rounded bg-white">
                        <option value="01">01 - Comprobante emitido con errores con relación</option>
                        <option value="02">02 - Comprobante emitido con errores sin relación</option>
                        <option value="03">03 - No se llevó a cabo la operación</option>
                        <option value="04">04 - Operación nominativa relacionada en la factura global</option>
                      </select>
                    </div>
                    
                    <div v-if="cancelFacturaParams.motivo === '01'">
                      <label class="block text-[9px] font-bold text-red-700 uppercase mb-1">UUID Sustitución</label>
                      <input v-model="cancelFacturaParams.folio_sustitucion" type="text" placeholder="UUID que sustituye..." 
                             class="w-full text-[10px] py-1 px-2 border-red-200 rounded bg-white" />
                    </div>

                    <div class="flex space-x-2">
                      <button @click="cancelarFacturaSat(selected.id)"
                              :disabled="isCancellingFactura"
                              class="flex-1 px-2 py-1 bg-red-600 text-white text-[10px] font-bold rounded hover:bg-red-700 disabled:opacity-50">
                        {{ isCancellingFactura ? '...' : 'Confirmar' }}
                      </button>
                      <button @click="showCancelFacturaOptions = false"
                              class="flex-1 px-2 py-1 bg-white border border-gray-300 text-gray-700 text-[10px] font-bold rounded hover:bg-gray-50">
                        Atrás
                      </button>
                    </div>
                  </div>
                </div>


                <div v-if="!selected.pagado && selected.metodo_pago !== 'credito'" class="mt-4">
                  <p class="text-[10px] text-yellow-600 font-medium px-1">
                    ⚠️ Debe marcar la venta como pagada para facturar (o ser venta a crédito).
                  </p>
                </div>

                <button v-else-if="!selected.esta_facturada" @click="facturarVenta(selected.id)"
                        :disabled="isProcessingFactura"
                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors disabled:opacity-50">
                  <svg v-if="!isProcessingFactura" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944V22m0-19.056c1.11 0 2.22.12 3.291.352 3.174.694 5.254 3.012 5.254 6.225 0 2.969-1.928 5.48-4.686 6.305" />
                  </svg>
                  <svg v-else class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ isProcessingFactura ? 'Procesando...' : 'Facturar SAT' }}
                </button>
              </div>

              <!-- CFDI Cancelado - Mostrar información del CFDI cancelado -->
              <div v-if="selected.cfdi_cancelado" class="border-t border-red-200 pt-2 mt-2 space-y-2">
                <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest px-1">CFDI Cancelado</p>
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                  <div class="flex items-center mb-2">
                    <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-xs font-semibold text-red-700">Factura Cancelada ante el SAT</span>
                  </div>
                  <div class="text-[10px] text-red-600 font-mono break-all bg-white p-2 rounded border border-red-100">
                    UUID: {{ selected.cfdi_cancelado_uuid || selected.factura_uuid || 'No disponible' }}
                  </div>
                  <p class="text-[9px] text-red-500 mt-2 italic">
                    Este CFDI fue cancelado. No se puede eliminar esta venta porque el registro permanece en el SAT.
                  </p>
                </div>
              </div>

              <button
                @click="onClose"
                class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 transition-colors"
              >
                Cerrar
              </button>
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

const props = defineProps({
  show: { type: Boolean, default: false },
  selected: { type: Object, default: null },
  auditoria: { type: Object, default: null }
})

const emit = defineEmits(['close', 'marcar-pagado', 'cancelar', 'eliminar'])

const notyf = new Notyf({ duration: 4000, position: { x: 'right', y: 'top' } })
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

const formatearFechaHora = formatearFecha

const formatearMoneda = (num) => {
  const value = parseFloat(num)
  const safe = Number.isFinite(value) ? value : 0
  return new Intl.NumberFormat('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(safe)
}

const formatCurrency = formatearMoneda

const getEstadoClass = (estado) => {
  const map = {
    borrador: 'bg-gray-100 text-gray-800',
    facturado: 'bg-blue-100 text-blue-800',
    pagado: 'bg-green-100 text-green-800',
    vencido: 'bg-red-100 text-red-800',
    anulado: 'bg-gray-100 text-gray-800',
    cancelada: 'bg-red-100 text-red-800'
  }
  return map[estado] || 'bg-gray-100 text-gray-800'
}

const getEstadoLabel = (estado) => {
  const map = {
    borrador: 'Borrador',
    facturado: 'Facturado',
    pagado: 'Pagado',
    vencido: 'Vencido',
    anulado: 'Anulado',
    cancelada: 'Cancelada'
  }
  return map[estado] || estado
}

// Items desde backend (selected.items)
const itemsCalculados = computed(() => {
  const lista = Array.isArray(props.selected?.items) ? props.selected.items : []
  return lista.map((item) => {
    const cantidad = parseFloat(item.cantidad || 1)
    const precio = parseFloat(item.precio || 0)
    const descuento = parseFloat(item.descuento || 0)
    const subtotalBase = precio * (cantidad || 1)
    const subtotal = subtotalBase - (subtotalBase * (descuento / 100))
    return {
      ...item,
      pivot: {
        cantidad: cantidad || 1,
        precio,
        descuento,
        subtotal,
      },
      requiere_serie: item.requiere_serie || false,
      series: item.series || [],
      tipo: item.tipo || 'producto',
      nombre: item.nombre || 'Producto/Servicio',
    }
  })
})

// Producto principal si no vienen items
const productoPrincipal = computed(() => {
  const s = props.selected || {}
  if ((Array.isArray(s.productos) && s.productos.length > 0) || (Array.isArray(s.items) && s.items.length > 0)) return null
  const nombre = s.producto_nombre || s.producto || s.concepto || null
  if (!nombre) return null
  const cantidad = parseFloat(s.cantidad || 1)
  const precio = parseFloat(s.precio_unitario || s.precio || s.total || 0)
  const subtotal = precio * (cantidad || 1)
  return {
    id: `venta-${s.id || 'sin-id'}`,
    nombre,
    tipo: 'producto',
    pivot: {
      cantidad: cantidad || 1,
      precio,
      descuento: 0,
      subtotal,
    },
    requiere_serie: false,
    series: [],
  }
})

const auditoriaSafe = computed(() => {
  // Primero intentar obtener de props.auditoria (legacy)
  if (props.auditoria) {
    return props.auditoria
  }

  // Si no hay props.auditoria, buscar directamente en selected
  if (props.selected) {
    return {
      creado_por: props.selected.created_by_user_name || 'N/A',
      actualizado_por: props.selected.updated_by_user_name || 'N/A',
      creado_en: props.selected.created_at,
      actualizado_en: props.selected.updated_at,
    }
  }

  // Fallback a metadata
  return props.selected?.metadata ?? null
})

// ✅ FIX: Funciones para manejar PDF con JavaScript (bypassa problemas de IIS headers)
const verPdfEnNavegador = async (ventaId) => {
  try {
    console.log(`[PDF Debug] Solicitando PDF para venta ${ventaId}...`);
    const response = await fetch(`/ventas/${ventaId}/pdf`, {
      method: 'GET',
      headers: {
        'Accept': 'application/pdf'
      }
    })
   
    // console.log('[PDF Debug] Response status:', response.status);
    // console.log('[PDF Debug] Content-Type header:', response.headers.get('Content-Type'));

    if (!response.ok) throw new Error('Error al obtener PDF')
   
    const blob = await response.blob()
    // console.log('[PDF Debug] Blob recibido:', blob);
    // console.log('[PDF Debug] Blob size:', blob.size);
    // console.log('[PDF Debug] Blob type:', blob.type);

    // Inspeccionar los primeros bytes
    const arrayBuffer = await blob.slice(0, 20).arrayBuffer();
    const decoder = new TextDecoder();
    const headerText = decoder.decode(arrayBuffer);
    // console.log('[PDF Debug] Primeros 20 bytes (texto):', headerText);

    // Crear blob con tipo correcto aunque IIS no lo envíe
    // Si el blob type está vacío o es text/plain, forzamos application/pdf
    const finalType = blob.type === 'application/pdf' ? 'application/pdf' : 'application/pdf';
    const pdfBlob = new Blob([blob], { type: finalType })
   
    const url = URL.createObjectURL(pdfBlob)
    // console.log('[PDF Debug] URL creada:', url);
   
    // Abrir en nueva pestaña
    window.open(url, '_blank')
   
    // Liberar URL después de un tiempo
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch (error) {
    console.error('Error al ver PDF:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error al generar el PDF',
      text: 'No se pudo generar el PDF. Por favor, inténtelo de nuevo.'
    })
  }
}

const descargarPdf = async (ventaId, numeroVenta) => {
  try {
    const response = await fetch(`/ventas/${ventaId}/pdf?modo=download`, {
      method: 'GET',
      headers: {
        'Accept': 'application/pdf'
      }
    })
   
    if (!response.ok) throw new Error('Error al obtener PDF')
   
    const blob = await response.blob()
    const pdfBlob = new Blob([blob], { type: 'application/pdf' })
    const url = URL.createObjectURL(pdfBlob)
   
    // Crear enlace de descarga
    const link = document.createElement('a')
    link.href = url
    link.download = `venta-${numeroVenta || ventaId}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
   
    URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error al descargar PDF:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error al descargar el PDF',
      text: 'No se pudo descargar el PDF. Por favor, inténtelo de nuevo.'
    })
  }
}

const facturarVenta = (id) => {
  if (!confirm('¿Deseas generar la factura electrónica SAT para esta venta?')) return;
  
  isProcessingFactura.value = true;
  router.post(route('ventas.facturar', id), {}, {
    onSuccess: () => {
      isProcessingFactura.value = false;
      notyf.success('Factura generada exitosamente');
    },
    onError: (errors) => {
      isProcessingFactura.value = false;
      const msg = errors.error || 'Error al generar factura';
      notyf.error(msg);
    },
    onFinish: () => {
        isProcessingFactura.value = false;
    }
  });
};

const verFacturaSat = (id) => {
    // Abrir directamente el visor de PDF en una nueva pestaña
    if (props.selected?.factura_uuid) {
        const url = route('cfdi.ver-pdf-view', props.selected.factura_uuid);
        window.open(url, '_blank');
    } else {
        notyf.error('No se encontró el UUID de la factura');
    }
};

const verXmlSat = (id) => {
    if (props.selected?.factura_uuid) {
        // Enforce route names from web.php: 'cfdi.xml' is for download/view
        const url = route('cfdi.xml', { uuid: props.selected.factura_uuid, inline: 1 });
        window.open(url, '_blank');
    } else {
        notyf.error('No se encontró el UUID de la factura');
    }
};

const descargarXmlSat = (id) => {
    if (props.selected?.factura_uuid) {
        const url = route('cfdi.xml', { uuid: props.selected.factura_uuid });
        // Usar window.location para forzar descarga directa en la misma ventana
        window.location.href = url;
    } else {
        notyf.error('No se encontró el UUID de la factura');
    }
};

const enviarCorreoFactura = async (id) => {
    if (!props.selected?.factura_uuid) {
        notyf.error('No hay factura disponible para enviar');
        return;
    }

    if (!confirm('¿Deseas enviar los archivos (XML y PDF) por correo al cliente?')) return;

    isSendingEmail.value = true;
    
    try {
        const response = await axios.post(route('cfdi.enviar-correo', props.selected.factura_uuid));
        
        if (response.data.success) {
            notyf.success(response.data.message || 'Correo enviado exitosamente');
        } else {
            notyf.error(response.data.message || 'No se pudo enviar el correo');
        }
    } catch (error) {
        console.error('Error enviando correo:', error);
        const msg = error.response?.data?.message || error.message || 'Error de conexión al enviar correo';
        notyf.error(msg);
    } finally {
        isSendingEmail.value = false;
    }
};

const cancelarFacturaSat = (id) => {
  /* console.log('=== CANCELAR FACTURA SAT ===' );
  console.log('Venta ID:', id);
  console.log('Params:', cancelFacturaParams.value);
  console.log('Route:', route('ventas.factura.cancelar', id)); */
  
  if (cancelFacturaParams.value.motivo === '01' && !cancelFacturaParams.value.folio_sustitucion) {
    // console.log('ERROR: Falta UUID sustitución para motivo 01');
    notyf.error('Debes ingresar el UUID de sustitución para el motivo 01');
    return;
  }

  if (!confirm('¿Seguro que deseas CANCELAR esta factura ante el SAT? Esta acción es irreversible.')) {
    // console.log('Usuario canceló la confirmación');
    return;
  }

  // console.log('Enviando solicitud de cancelación...');
  isCancellingFactura.value = true;
  router.post(route('ventas.factura.cancelar', id), cancelFacturaParams.value, {
    onSuccess: (page) => {
      // console.log('SUCCESS - Factura cancelada:', page);
      isCancellingFactura.value = false;
      showCancelFacturaOptions.value = false;
      notyf.success('Factura cancelada correctamente ante el SAT');
    },
    onError: (errors) => {
      // console.log('ERROR - Cancelación fallida:', errors);
      isCancellingFactura.value = false;
      const msg = errors.error || Object.values(errors)[0] || 'Error al cancelar factura';
      notyf.error(msg);
    },
    onFinish: () => {
      // console.log('FINISH - Solicitud terminada');
    }
  });
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-from,
.modal-leave-to { opacity: 0; }
</style>

