<template>
  <Head title="Detalles de Venta" />

  <div class="min-h-screen bg-white py-8">
    <div class="w-full px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <Link :href="route('ventas.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Volver a Ventas
        </Link>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-gray-900">Detalles de Venta</h1>
              <p class="text-sm text-gray-500 mt-1">#{{ venta.id }} - {{ venta.numero_venta }}</p>
            </div>
            <div class="flex items-center space-x-3">
              <span :class="getEstadoClass(venta.estado)" class="px-4 py-2 rounded-full text-sm font-medium">
                {{ getEstadoLabel(venta.estado) }}
              </span>
              <span v-if="venta.pagado" class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                Pagado
              </span>
              <span v-else class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                Pendiente de Pago
              </span>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
          <!-- Sale Information -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 text-white rounded-t-lg" :style="{ background: `linear-gradient(135deg, ${colors.principal} 0%, ${colors.secundario} 100%)` }">
              <h2 class="text-xl font-bold">Información de la Venta</h2>
            </div>
            <div class="px-6 py-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Client -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Cliente</h3>
                  <div class="space-y-2">
                    <p class="text-lg font-bold text-gray-900">{{ venta.cliente?.nombre_razon_social || 'Sin cliente' }}</p>
                    <p v-if="venta.cliente?.email" class="text-sm text-gray-600">{{ venta.cliente?.email }}</p>
                    <p v-if="venta.cliente?.telefono" class="text-sm text-gray-600">{{ venta.cliente?.telefono }}</p>
                    <p v-if="venta.cliente?.rfc" class="text-sm text-gray-600">RFC: {{ venta.cliente?.rfc }}</p>
                  </div>
                </div>

                <!-- Sale Details -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Detalles</h3>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Fecha:</span>
                      <span class="font-medium">{{ formatearFecha(venta.fecha) }}</span>
                    </div>
                    <div v-if="venta.almacen" class="flex justify-between">
                      <span class="text-gray-600">Almacén:</span>
                      <span class="font-medium">{{ venta.almacen.nombre }}</span>
                    </div>
                    <div v-if="venta.vendedor" class="flex justify-between">
                      <span class="text-gray-600">Vendedor:</span>
                      <span class="font-medium">{{ venta.vendedor.nombre || venta.vendedor.name }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Método de Pago:</span>
                      <span class="font-medium capitalize">{{ venta.metodo_pago || 'No especificado' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="venta.notas" class="mt-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Notas</h3>
                <p class="text-sm text-gray-700 bg-white p-3 rounded">{{ venta.notas }}</p>
              </div>
            </div>
          </div>

          <!-- Products and Services -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-xl font-bold text-gray-900">Productos y Servicios</h2>
              <p class="text-sm text-gray-500 mt-1">{{ venta.productos.length }} items</p>
            </div>

            <div v-if="venta.productos.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-white">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto/Servicio</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Cant.</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Unit.</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Desc.</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <template v-for="producto in venta.productos" :key="producto.id">
                    <tr class="hover:bg-white">
                      <td class="px-6 py-4">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-10 w-10 flex items-center justify-center rounded-lg"
                               :class="producto.tipo === 'producto' ? 'bg-purple-100' : 'bg-green-100'">
                            <svg v-if="producto.tipo === 'producto'" class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                            <svg v-else class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <!-- Series Row -->
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
                            <span class="ml-2 text-xs text-gray-500">• {{ serie.almacen || 'N/A' }}</span>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>

            <!-- Empty State -->
            <div v-else class="px-6 py-12 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No hay productos asociados</h3>
              <p class="mt-1 text-sm text-gray-500">Esta venta no tiene productos o servicios registrados.</p>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Summary -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Resumen</h2>
            </div>
            <div class="px-6 py-4 space-y-3">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(venta.subtotal) }}</span>
              </div>
              <div v-if="venta.descuento_general > 0" class="flex justify-between text-sm">
                <span class="text-gray-600">Descuento General</span>
                <span class="font-medium text-red-600">-{{ formatCurrency(venta.descuento_general) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">IVA (16%)</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(venta.iva) }}</span>
              </div>
              <div class="pt-3 border-t-2 border-gray-300">
                <div class="flex justify-between items-center">
                  <span class="text-base font-semibold text-gray-900">Total</span>
                  <span class="text-2xl font-bold" :style="{ color: colors.principal }">{{ formatCurrency(venta.total) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Status -->
          <div :class="venta.pagado ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200'"
               class="rounded-lg border p-4">
            <div class="flex items-center mb-3">
              <svg v-if="venta.pagado" class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg v-else class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 :class="venta.pagado ? 'text-green-900' : 'text-yellow-900'" class="text-sm font-semibold">
                {{ venta.pagado ? 'Pago Completado' : 'Pago Pendiente' }}
              </h3>
            </div>
            <dl v-if="venta.pagado" class="space-y-2">
              <div>
                <dt class="text-xs text-green-700">Método de Pago</dt>
                <dd class="text-sm font-medium text-green-900 capitalize">{{ venta.metodo_pago || 'N/A' }}</dd>
              </div>
              <div v-if="venta.fecha_pago">
                <dt class="text-xs text-green-700">Fecha de Pago</dt>
                <dd class="text-sm font-medium text-green-900">{{ formatearFecha(venta.fecha_pago) }}</dd>
              </div>
            </dl>
            <p v-else class="text-sm text-yellow-800">
              Esta venta aún no ha sido pagada.
            </p>
          </div>

          <!-- Audit -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h2 class="text-lg font-semibold text-gray-900">Auditoría</h2>
            </div>
            <div class="px-6 py-4 space-y-3 text-sm">
              <div>
                <dt class="text-xs font-medium text-gray-500 flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Creado
                </dt>
                <dd class="mt-1 text-gray-900">{{ formatearFechaHora(venta.created_at) }}</dd>
              </div>
              <div>
                <dt class="text-xs font-medium text-gray-500 flex items-center">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Última Actualización
                </dt>
                <dd class="mt-1 text-gray-900">{{ formatearFechaHora(venta.updated_at) }}</dd>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
            <Link :href="route('ventas.pdf', venta.id)" target="_blank"
                  class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-white transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              Descargar PDF
            </Link>
            <Link :href="route('ventas.ticket', venta.id)" target="_blank"
                  class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-white transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              Imprimir Ticket
            </Link>
            <Link v-if="canEdit" :href="route('ventas.edit', venta.id)"
                  class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white transition-colors"
                  :style="{ backgroundColor: colors.principal }">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Editar Venta
            </Link>
            <button v-if="!venta.pagado" @click="showPagoModal = true"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Marcar como Pagado
            </button>
            <button v-if="canCancel" @click="cancelarVenta(venta.id)"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Cancelar Venta
            </button>
            <button v-if="canDelete" @click="eliminarVenta(venta.id)"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Eliminar Venta
            </button>
          </div>

          <!-- SAT Invoicing -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 space-y-3">
            <h3 class="text-sm font-semibold text-gray-900 border-b pb-2 mb-2">Facturación SAT</h3>
            
            <div v-if="venta.esta_facturada" class="space-y-3">
              <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
                <p class="text-xs text-blue-800 font-medium">CFDI Timbrado</p>
                <p class="text-xs text-blue-600 font-mono mt-1 break-all">{{ venta.factura?.uuid }}</p>
              </div>
              <a :href="route('cfdi.descargar-xml', venta.factura?.uuid)" target="_blank"
                 class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-white transition-colors">
                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Descargar XML
              </a>
              <a :href="route('cfdi.ver-pdf-view', venta.factura?.uuid)" target="_blank"
                 class="w-full inline-flex justify-center items-center px-4 py-2 border border-blue-600 rounded-md shadow-sm text-sm font-medium text-blue-600 bg-white hover:bg-blue-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                Ver Factura (PDF)
              </a>
              <button @click="abrirCancelarFacturaModal"
                      class="w-full inline-flex justify-center items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Cancelar Factura (SAT)
              </button>
            </div>

            <button v-else @click="showFacturarModal = true"
                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="isProcessingFactura">
              <svg v-if="!isProcessingFactura" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04M12 2.944V22m0-19.056c1.11 0 2.22.12 3.291.352 3.174.694 5.254 3.012 5.254 6.225 0 2.969-1.928 5.48-4.686 6.305" />
              </svg>
              <svg v-else class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isProcessingFactura ? 'Procesando...' : 'Facturar (CFDI 4.0)' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Modals -->
      <!-- Cancel Modal -->
      <DialogModal :show="showCancelModal" @close="cerrarCancelModal" max-width="md">
        <template #title>
          Cancelar Venta
        </template>

        <template #content>
          <div class="space-y-4 modal-content">
            <div class="bg-red-50 p-4 rounded-lg">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-red-800">&iquest;Est&aacute; seguro de que desea cancelar esta venta?</h3>
                  <div class="mt-2 text-sm text-red-700">
                    <p>Esta acci&oacute;n:</p>
                    <ul class="list-disc list-inside mt-1 space-y-1">
                      <li>Devolver&aacute; el inventario al almac&eacute;n</li>
                      <li>Devolver&aacute; las series a estado disponible</li>
                      <li>Cambiar&aacute; el estado de la venta a "Cancelada"</li>
                      <li>No se podr&aacute; deshacer esta acci&oacute;n</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-white p-4 rounded-lg">
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700">Venta:</span>
                <span class="text-sm font-mono text-gray-900">{{ venta.numero_venta }}</span>
              </div>
              <div class="flex justify-between items-center mt-2">
                <span class="text-sm font-medium text-gray-700">Total:</span>
                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(venta.total) }}</span>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de cancelaci&oacute;n (opcional)</label>
              <textarea v-model="motivoCancelacion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Explique el motivo de la cancelaci&oacute;n..."></textarea>
            </div>
            <!-- Admin Force Cancel Option - Always show for admin -->
            <div v-if="isAdmin" class="bg-orange-50 p-4 rounded-lg border border-orange-200">
              <div class="flex items-start">
                <input id="forceCancel" type="checkbox" v-model="forceWithPayments" 
                       class="h-4 w-4 mt-1 text-orange-600 focus:ring-orange-500 border-gray-300 rounded" />
                <label for="forceCancel" class="ml-3">
                  <span class="text-sm font-medium text-orange-800">Forzar cancelaci&oacute;n (Admin)</span>
                  <p class="text-xs text-orange-700 mt-1">
                    Esta venta tiene pagos registrados. Al marcar esta opci&oacute;n, se eliminar&aacute;n los registros de pago y entrega de dinero.
                  </p>
                </label>
              </div>
            </div>
          </div>
        </template>

        <template #footer>
          <div class="flex justify-end gap-3">
            <button @click="cerrarCancelModal" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
              Cancelar
            </button>
            <button @click="confirmarCancelacion" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
              Confirmar Cancelación
            </button>
          </div>
        </template>
      </DialogModal>

      <!-- Payment Modal -->
      <DialogModal :show="showPagoModal" @close="showPagoModal = false" max-width="md">
        <template #title>
          Marcar Venta como Pagada
        </template>

        <template #content>
          <div class="space-y-4">
            <div class="bg-white p-4 rounded-lg">
              <div class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-700">Monto Total:</span>
                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(venta.total) }}</span>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago *</label>
              <select v-model="metodoPago" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Seleccionar método</option>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="cheque">Cheque</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="otros">Otros</option>
              </select>
            </div>
            <!-- Banco SOLO para tarjeta/transferencia (van directo al banco) -->
            <div v-if="requiresBankAccount">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Cuenta Bancaria (destino) <span class="text-red-500">*</span>
              </label>
              <select v-model="cuentaBancariaId" 
                      :class="{'border-red-500 ring-red-500': !cuentaBancariaId}"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Seleccionar cuenta bancaria *</option>
                <option v-for="cuenta in cuentasBancarias" :key="cuenta.id" :value="cuenta.id">
                  {{ cuenta.banco }} - {{ cuenta.nombre }}
                </option>
              </select>
              <p class="text-xs text-blue-600 mt-1">💳 Tarjeta/Transferencia va directo al banco seleccionado</p>
            </div>
            <p v-else-if="metodoPago === 'efectivo'" class="text-xs text-yellow-600 mt-1">
              💵 El efectivo se registra en "Entregas de Dinero" cuando el vendedor lo entregue
            </p>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
              <textarea v-model="notasPago" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Agregar notas..."></textarea>
            </div>
          </div>
        </template>

        <template #footer>
          <div class="flex justify-end gap-3">
            <button @click="showPagoModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">Cancelar</button>
            <button @click="confirmarPago" :disabled="!canConfirmPayment" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
              Confirmar Pago
            </button>
          </div>
        </template>
      </DialogModal>

      <!-- Invoice Cancel Modal -->
      <DialogModal :show="showInvoiceCancelModal" @close="showInvoiceCancelModal = false" max-width="md">
        <template #title>
          Cancelar Factura Fiscal (SAT)
        </template>

        <template #content>
          <div class="space-y-4 modal-content">
            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-yellow-800">Atención</h3>
                  <div class="mt-2 text-sm text-yellow-700">
                    <p>Esta acción solicitará la cancelación oficial ante el SAT. Asegúrese de seleccionar el motivo correcto.</p>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de Cancelación (SAT) *</label>
              <select v-model="invoiceCancelReason" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Seleccione un motivo</option>
                <option value="02">02 - Comprobante emitido con errores sin relación</option>
                <option value="01">01 - Comprobante emitido con errores con relación</option>
                <option value="03">03 - No se llevó a cabo la operación</option>
                <option value="04">04 - Operación nominativa relacionada en factura global</option>
              </select>
            </div>

            <div v-if="invoiceCancelReason === '01'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Folio de Sustitución (UUID) *</label>
              <input v-model="invoiceSubstitutionFolio" type="text" placeholder="Ej: 5FB2DB8A-..." 
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              <p class="text-xs text-gray-500 mt-1">Ingrese el UUID de la factura que sustituye a la cancelada.</p>
            </div>
          </div>
        </template>

        <template #footer>
          <div class="flex justify-end gap-3">
            <button @click="showInvoiceCancelModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium">Cancelar</button>
            <button @click="confirmarCancelarFactura" 
                    :disabled="!isValidInvoiceCancel"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium shadow-sm transition-colors">
              Confirmar Cancelación
            </button>
          </div>
        </template>
      </DialogModal>

      <!-- Facturar Modal -->
      <DialogModal :show="showFacturarModal" @close="showFacturarModal = false" max-width="md">
        <template #title>
          Facturar (CFDI 4.0)
        </template>

        <template #content>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Factura</label>
              <select v-model="facturaForm.tipo_factura" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="ingreso">Ingreso (Normal)</option>
                <option value="anticipo">Anticipo</option>
              </select>
            </div>

            <div v-if="facturaForm.tipo_factura === 'anticipo'" class="space-y-4">
              <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800">
                Se generará un CFDI de ingreso con concepto de anticipo. Luego relaciona la factura final con tipo 07.
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Monto del Anticipo (Total)</label>
                <input v-model="facturaForm.anticipo_monto" type="number" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago</label>
                <select v-model="facturaForm.anticipo_metodo_pago" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                  <option value="">Seleccionar método</option>
                  <option value="efectivo">Efectivo</option>
                  <option value="transferencia">Transferencia</option>
                  <option value="cheque">Cheque</option>
                  <option value="tarjeta">Tarjeta</option>
                  <option value="otros">Otros</option>
                </select>
              </div>
            </div>

            <div v-if="facturaForm.tipo_factura === 'ingreso'" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Relación CFDI (opcional)</label>
                <select v-model="facturaForm.cfdi_relacion_tipo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                  <option value="">Sin relación</option>
                  <option value="01">01 - Nota de crédito de documentos relacionados</option>
                  <option value="02">02 - Nota de débito de documentos relacionados</option>
                  <option value="03">03 - Devolución de mercancía sobre facturas o traslados previos</option>
                  <option value="04">04 - Sustitución de los CFDI previos</option>
                  <option value="05">05 - Traslados de mercancías facturados previamente</option>
                  <option value="06">06 - Factura generada por los traslados previos</option>
                  <option value="07">07 - CFDI por aplicación de anticipo</option>
                </select>
              </div>
              <div v-if="facturaForm.cfdi_relacion_tipo">
                <label class="block text-sm font-medium text-gray-700 mb-2">UUID(s) Relacionados</label>
                <textarea v-model="facturaForm.cfdi_relacion_uuids_raw" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Uno por línea o separados por coma"></textarea>
              </div>
            </div>
          </div>
        </template>

        <template #footer>
          <div class="flex justify-end gap-3">
            <button @click="showFacturarModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 font-medium">Cancelar</button>
            <button @click="confirmarFacturacion" :disabled="isProcessingFactura" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed font-medium shadow-sm transition-colors">
              {{ isProcessingFactura ? 'Procesando...' : 'Facturar' }}
            </button>
          </div>
        </template>
      </DialogModal>
    </div>
  </div>
</template>

<style>
/* Fix for UTF-8 character encoding in modals */
.modal-content {
    font-family: 'Figtree', 'Inter', 'Segoe UI', 'Roboto', 'Helvetica Neue', sans-serif;
}

/* Ensure proper rendering of Spanish characters */
body, #app {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
}
</style>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import DialogModal from '@/Components/DialogModal.vue';
import { useCompanyColors } from '@/Composables/useCompanyColors';

// Colores de empresa
const { colors } = useCompanyColors();

const props = defineProps({
  venta: Object,
  canEdit: Boolean,
  canDelete: Boolean,
  canCancel: Boolean,
  isAdmin: Boolean,
  cuentasBancarias: Array
});

const notyf = new Notyf({ duration: 3000, position: { x: 'right', y: 'top' } });
const showPagoModal = ref(false);
const metodoPago = ref('');
const cuentaBancariaId = ref('');
const notasPago = ref('');
const showCancelModal = ref(false);
const motivoCancelacion = ref('');
const forceWithPayments = ref(false);
const isProcessingFactura = ref(false);
const showFacturarModal = ref(false);

const facturaForm = ref({
  tipo_factura: 'ingreso',
  cfdi_relacion_tipo: '',
  cfdi_relacion_uuids_raw: '',
  anticipo_monto: '',
  anticipo_metodo_pago: ''
});

// Computed: banco obligatorio SOLO para tarjeta/transferencia (van directo al banco)
const requiresBankAccount = computed(() => {
  return ['tarjeta', 'transferencia'].includes(metodoPago.value);
});

// Computed: puede confirmar pago
const canConfirmPayment = computed(() => {
  if (!metodoPago.value) return false;
  // Si es tarjeta/transferencia, requiere banco
  if (requiresBankAccount.value && !cuentaBancariaId.value) return false;
  return true;
});

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatearFecha = (fecha) => {
  if (!fecha) return 'N/A';
  return new Date(fecha).toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatearFechaHora = (fecha) => {
  if (!fecha) return 'N/A';
  return new Date(fecha).toLocaleString('es-MX', {
    year: 'numeric', month: 'long', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
};

const getEstadoClass = (estado) => {
  const classes = {
    'borrador': 'bg-gray-100 text-gray-800',
    'pendiente': 'bg-yellow-100 text-yellow-800',
    'aprobada': 'bg-green-100 text-green-800',
    'cancelada': 'bg-red-100 text-red-800'
  };
  return classes[estado] || 'bg-gray-100 text-gray-800';
};

const getEstadoLabel = (estado) => {
  const labels = {
    'borrador': 'Borrador',
    'pendiente': 'Pendiente',
    'aprobada': 'Aprobada',
    'cancelada': 'Cancelada'
  };
  return labels[estado] || estado;
};

const confirmarPago = () => {
  // Validar cuenta bancaria para tarjeta/transferencia
  if (requiresBankAccount.value && !cuentaBancariaId.value) {
    notyf.error('Debe seleccionar una cuenta bancaria para pagos con tarjeta o transferencia');
    return;
  }
  
  router.post(route('ventas.marcar-pagado', props.venta.id), {
    metodo_pago: metodoPago.value,
    cuenta_bancaria_id: cuentaBancariaId.value || null,
    notas_pago: notasPago.value
  }, {
    onSuccess: () => {
      notyf.success('Venta marcada como pagada');
      showPagoModal.value = false;
      cuentaBancariaId.value = '';
    },
    onError: () => notyf.error('Error al marcar como pagada')
  });
};

const cancelarVenta = (id) => {
  showCancelModal.value = true;
};

const confirmarCancelacion = () => {
  router.post(route('ventas.cancel', props.venta.id), {
    motivo: motivoCancelacion.value,
    force_with_payments: forceWithPayments.value
  }, {
    onSuccess: () => {
      notyf.success('Venta cancelada exitosamente');
      showCancelModal.value = false;
      motivoCancelacion.value = '';
      forceWithPayments.value = false;
      router.visit(route('ventas.show', props.venta.id));
    },
    onError: (errors) => {
      const msg = errors.error || 'Error al cancelar la venta';
      notyf.error(msg);
    }
  });
};

const cerrarCancelModal = () => {
  showCancelModal.value = false;
  motivoCancelacion.value = '';
  forceWithPayments.value = false;
};

const eliminarVenta = (id) => {
  if (confirm('¿Estás seguro de que deseas eliminar esta venta permanentemente?')) {
    router.visit(route('ventas.destroy', id), {
      method: 'DELETE',
      preserveState: false,
      preserveScroll: false,
      onSuccess: () => {
        notyf.success('Venta eliminada exitosamente');
        router.visit(route('ventas.index'));
      },
      onError: (errors) => {
        console.error('Error al eliminar:', errors);
        const msg = errors.error || 'Error al eliminar la venta';
        notyf.error(msg);
      }
    });
  }
};

const confirmarFacturacion = () => {
  const payload = {
    tipo_factura: facturaForm.value.tipo_factura
  };

  if (facturaForm.value.tipo_factura === 'anticipo') {
    payload.anticipo_monto = facturaForm.value.anticipo_monto;
    payload.anticipo_metodo_pago = facturaForm.value.anticipo_metodo_pago;
  } else {
    payload.cfdi_relacion_tipo = facturaForm.value.cfdi_relacion_tipo || null;
    if (facturaForm.value.cfdi_relacion_tipo) {
      const uuids = facturaForm.value.cfdi_relacion_uuids_raw
        .split(/[\s,]+/)
        .map((value) => value.trim())
        .filter((value) => value.length > 0);
      payload.cfdi_relacion_uuids = uuids;
    }
  }

  isProcessingFactura.value = true;
  router.post(route('ventas.facturar', props.venta.id), payload, {
    onSuccess: () => {
      isProcessingFactura.value = false;
      showFacturarModal.value = false;
      notyf.success('Factura generada exitosamente');
    },
    onError: (errors) => {
      isProcessingFactura.value = false;
      const msg = errors.error || Object.values(errors)[0] || 'Error al generar factura';
      notyf.error(msg);
    }
  });
};


// Invoice Cancellation Logic
const showInvoiceCancelModal = ref(false);
const invoiceCancelReason = ref('');
const invoiceSubstitutionFolio = ref('');

const abrirCancelarFacturaModal = () => {
    invoiceCancelReason.value = '';
    invoiceSubstitutionFolio.value = '';
    showInvoiceCancelModal.value = true;
};

const isValidInvoiceCancel = computed(() => {
    if (!invoiceCancelReason.value) return false;
    if (invoiceCancelReason.value === '01' && !invoiceSubstitutionFolio.value) return false;
    return true;
});

const confirmarCancelarFactura = () => {
    if (!isValidInvoiceCancel.value) return;

    if (!confirm('¿Está seguro de solicitar la cancelación de esta factura ante el SAT?')) return;

    router.post(route('ventas.factura.cancelar', props.venta.id), {
        motivo: invoiceCancelReason.value,
        folio_sustitucion: invoiceSubstitutionFolio.value
    }, {
        onSuccess: () => {
            showInvoiceCancelModal.value = false;
            notyf.success('Solicitud de cancelación enviada correctamente');
            invoiceCancelReason.value = '';
            invoiceSubstitutionFolio.value = '';
        },
        onError: (errors) => {
            const msg = errors.error || errors.message || 'Error al cancelar la factura';
            notyf.error(msg);
        }
    });
};
</script>
