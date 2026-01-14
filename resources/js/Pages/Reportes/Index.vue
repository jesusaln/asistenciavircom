<template>
    <AppLayout title="Centro de Reportes">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4 md:mb-0">
                    Reportes - {{ tabs.find(t => t.key === activeTab)?.label }}
                </h2>
                <!-- Actions moved to header for better visibility -->
                <div class="flex space-x-2">
                    <input
                        v-model="fechaInicio"
                        type="date"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        @change="filtrarDatos"
                        placeholder="Fecha Inicio"
                    />
                    <input
                        v-model="fechaFin"
                        type="date"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        @change="filtrarDatos"
                         placeholder="Fecha Fin"
                    />
                     <button
                        v-if="fechaInicio || fechaFin"
                        @click="limpiarFiltros"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Limpiar
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Mobile Tab Selector -->
                <div class="sm:hidden mb-4 px-4">
                    <label for="tabs" class="sr-only">Seleccionar reporte</label>
                    <select
                        id="tabs"
                        v-model="activeTab"
                        class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option v-for="tab in tabs" :key="tab.key" :value="tab.key">{{ tab.label }}</option>
                    </select>
                </div>

                <!-- Desktop/Tablet Tab Navigation -->
                <div class="hidden sm:block mb-6 border-b border-gray-200 bg-white rounded-lg shadow-sm overflow-hidden">
                    <nav class="-mb-px flex overflow-x-auto custom-scrollbar" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="changeTab(tab.key)"
                            :class="[
                                activeTab === tab.key
                                    ? 'border-blue-500 text-blue-600 bg-blue-50'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 hover:bg-gray-50',
                                'whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm flex items-center transition-colors duration-200 min-w-max'
                            ]"
                        >
                            <FontAwesomeIcon :icon="tab.icon" class="mr-2" :class="activeTab === tab.key ? 'text-blue-500' : 'text-gray-400'" />
                            {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg min-h-[600px]">
                    <div class="p-6">
                        <!-- Tab General -->
                        <div v-if="activeTab === 'general'">
                            <GeneralTab />
                        </div>

                        <!-- Tab Ventas -->
                        <div v-if="activeTab === 'ventas'">
                            <VentasTab
                                :ventas-filtradas="ventasFiltradas"
                                :corte-filtrado="totalVentasFiltradas"
                                :iva-filtrado="ivaFiltrado"
                                :utilidad-filtrada="utilidadFiltrada"
                                :clientes-unicos-ventas="clientesUnicosVentas"
                                :ventas-pagadas-y-aprobadas="ventasPagadasYAprobadas"
                                :ventas-pendientes-pago="ventasPendientesPago"
                                :ventas-borrador="ventasBorrador"
                                :top-clientes="topClientes"
                            />
                        </div>

                        <!-- Tab Compras -->
                        <div v-show="activeTab === 'compras'">
                            <ComprasTab
                                :compras-filtradas="comprasFiltradas"
                                :total-compras-filtrado="totalComprasFiltrado"
                                :proveedores-unicos="proveedoresUnicos"
                                :productos-comprados="productosComprados"
                                :compras-por-proveedor="comprasPorProveedor"
                            />
                        </div>

                        <!-- Tab Inventario -->
                        <div v-show="activeTab === 'inventario'">
                            <InventarioTab
                                :inventario-filtrado="inventarioFiltrado"
                                :productos-en-stock="productosEnStock"
                                :productos-bajo-stock="productosBajoStock"
                                :productos-agotados="productosAgotados"
                            />
                        </div>

                        <!-- Tab Corte Diario -->
                        <div v-show="activeTab === 'corte'">
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Corte Diario - Cobros del Día</h3>
                                <p class="text-sm text-gray-600 mb-4">Todas las ventas y rentas cobradas en el período seleccionado</p>

                                <!-- Filtros específicos para corte -->
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha del Corte</label>
                                        <input
                                            v-model="fechaCorte"
                                            type="date"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            @change="filtrarCorte"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Usuario que Cobró</label>
                                        <select
                                            v-model="usuarioCorte"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            @change="filtrarCorte"
                                        >
                                            <option value="">Todos los usuarios</option>
                                            <option v-for="usuario in usuariosActivos" :key="usuario.id" :value="usuario.id">
                                                {{ usuario.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Cobro</label>
                                        <select
                                            v-model="tipoCobro"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                            @change="filtrarCorte"
                                        >
                                            <option value="">Todos</option>
                                            <option value="venta">Solo Ventas</option>
                                            <option value="renta">Solo Rentas</option>
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button
                                            @click="exportarCorte"
                                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors shadow-sm w-full md:w-auto"
                                        >
                                            Exportar Excel
                                        </button>
                                    </div>
                                </div>

                                <!-- Resumen del corte -->
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                    <div class="bg-green-50 p-4 rounded-lg flex items-center justify-between border border-green-100">
                                        <div>
                                            <div class="text-xs text-green-600 font-bold uppercase tracking-wide">Total Ventas</div>
                                            <div class="text-2xl font-bold text-green-700">{{ formatCurrency(totalCorteVentas) }}</div>
                                        </div>
                                        <div class="p-3 bg-green-100 rounded-full text-green-600">
                                            <FontAwesomeIcon icon="shopping-cart" />
                                        </div>
                                    </div>
                                    <div class="bg-blue-50 p-4 rounded-lg flex items-center justify-between border border-blue-100">
                                        <div>
                                            <div class="text-xs text-blue-600 font-bold uppercase tracking-wide">Total Rentas</div>
                                            <div class="text-2xl font-bold text-blue-700">{{ formatCurrency(totalCorteRentas) }}</div>
                                        </div>
                                        <div class="p-3 bg-blue-100 rounded-full text-blue-600">
                                            <FontAwesomeIcon icon="file-contract" />
                                        </div>
                                    </div>
                                    <div class="bg-purple-50 p-4 rounded-lg flex items-center justify-between border border-purple-100">
                                        <div>
                                            <div class="text-xs text-purple-600 font-bold uppercase tracking-wide">Num. Cobros</div>
                                            <div class="text-2xl font-bold text-purple-700">{{ pagosCorteFiltrados.length }}</div>
                                        </div>
                                        <div class="p-3 bg-purple-100 rounded-full text-purple-600">
                                            <FontAwesomeIcon icon="receipt" />
                                        </div>
                                    </div>
                                    <div class="bg-orange-50 p-4 rounded-lg flex items-center justify-between border border-orange-100">
                                        <div>
                                            <div class="text-xs text-orange-600 font-bold uppercase tracking-wide">Cobradores</div>
                                            <div class="text-2xl font-bold text-orange-700">{{ usuariosCobradores }}</div>
                                        </div>
                                        <div class="p-3 bg-orange-100 rounded-full text-orange-600">
                                            <FontAwesomeIcon icon="users" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabla de corte diario -->
                                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha/Hora</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Concepto</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cobrado Por</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <tr v-for="cobro in pagosCorteFiltrados" :key="cobro.id" class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ formatDateTime(cobro.fecha_pago) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span :class="{
                                                        'bg-green-100 text-green-800': cobro.tipo === 'venta',
                                                        'bg-blue-100 text-blue-800': cobro.tipo === 'renta'
                                                    }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                        {{ cobro.tipo === 'venta' ? 'Venta' : 'Renta' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                                    {{ cobro.numero }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ cobro.cliente }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ cobro.concepto }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ cobro.metodo_pago || 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ formatCurrency(cobro.total) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ cobro.cobrado_por }}
                                                </td>
                                            </tr>
                                            <tr v-if="pagosCorteFiltrados.length === 0">
                                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                                    No hay cobros en el período seleccionado
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Préstamos -->
                        <div v-show="activeTab === 'prestamos'">
                            <PrestamosTab :prestamos="prestamos" />
                        </div>

                        <!-- Tab Clientes -->
                        <div v-show="activeTab === 'clientes'">
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Reporte de Clientes</h3>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <div class="text-2xl font-bold text-blue-600">{{ clientesActivos.length }}</div>
                                        <div class="text-sm text-blue-600">Clientes Activos</div>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <div class="text-2xl font-bold text-green-600">{{ clientesConCompras }}</div>
                                        <div class="text-sm text-green-600">Con Compras</div>
                                    </div>
                                    <div class="bg-orange-50 p-4 rounded-lg">
                                        <div class="text-2xl font-bold text-orange-600">{{ clientesConRentas }}</div>
                                        <div class="text-sm text-orange-600">Con Rentas</div>
                                    </div>
                                    <div class="bg-red-50 p-4 rounded-lg">
                                        <div class="text-2xl font-bold text-red-600">{{ clientesDeudores }}</div>
                                        <div class="text-sm text-red-600">Deudores</div>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ventas</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rentas</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="cliente in clientesActivos" :key="cliente.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ cliente.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ cliente.email || 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ cliente.telefono || 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ cliente.ventas_count || 0 }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ cliente.rentas_count || 0 }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="{
                                                    'bg-green-100 text-green-800': (cliente.ventas_count || 0) > 0 || (cliente.rentas_count || 0) > 0,
                                                    'bg-gray-100 text-gray-800': (cliente.ventas_count || 0) === 0 && (cliente.rentas_count || 0) === 0
                                                }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ (cliente.ventas_count || 0) > 0 || (cliente.rentas_count || 0) > 0 ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Servicios -->
                        <div v-show="activeTab === 'servicios'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Top Servicios</h3>
                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Ventas</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ingresos</th></tr></thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(item, i) in reportesServicios" :key="i">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.servicio?.nombre || 'Desconocido' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.total_cantidad }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(item.total_ingreso) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                         <!-- Citas -->
                        <div v-show="activeTab === 'citas'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Últimas Citas</h3>
                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Técnico</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th></tr></thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="cita in reportesCitas" :key="cita.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDateTime(cita.fecha_inicio) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ cita.cliente?.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ cita.tecnico?.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ cita.estado }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mantenimientos -->
                        <div v-show="activeTab === 'mantenimientos'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Mantenimientos Recientes</h3>
                            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Equipo</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th></tr></thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="mant in reportesMantenimientos" :key="mant.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDateTime(mant.fecha_programada) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ mant.cliente?.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ mant.carro?.modelo }} {{ mant.carro?.placas }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ mant.estado }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Rentas -->
                        <div v-show="activeTab === 'rentas'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Rentas Recientes</h3>
                             <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contrato</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Equipo</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th></tr></thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="renta in reportesRentas" :key="renta.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ renta.numero_contrato }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ renta.cliente?.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ renta.equipo?.nombre }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ renta.estado }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Cobranzas -->
                        <div v-show="activeTab === 'cobranzas'">
                             <h3 class="text-lg font-medium text-gray-900 mb-4">Últimas Cobranzas</h3>
                             <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monto</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th></tr></thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="cob in reportesCobranzas" :key="cob.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDateTime(cob.created_at) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ cob.renta?.cliente?.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">{{ formatCurrency(cob.monto_pagado) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ cob.estado }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Ganancias -->
                        <div v-show="activeTab === 'ganancias'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Resumen de Ganancias</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="bg-green-50 p-6 rounded-lg border border-green-200">
                                    <h4 class="text-sm font-medium text-green-800 uppercase mb-2">Total Ventas</h4>
                                    <p class="text-3xl font-bold text-green-900">{{ formatCurrency(reportesGanancias.ventas || 0) }}</p>
                                </div>
                                 <div class="bg-red-50 p-6 rounded-lg border border-red-200">
                                    <h4 class="text-sm font-medium text-red-800 uppercase mb-2">Total Compras</h4>
                                    <p class="text-3xl font-bold text-red-900">{{ formatCurrency(reportesGanancias.compras || 0) }}</p>
                                </div>
                                <div class="bg-orange-50 p-6 rounded-lg border border-orange-200">
                                    <h4 class="text-sm font-medium text-orange-800 uppercase mb-2">Total Gastos</h4>
                                    <p class="text-3xl font-bold text-orange-900">{{ formatCurrency(reportesGanancias.gastos || 0) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Proveedores -->
                        <div v-show="activeTab === 'proveedores'">
                             <h3 class="text-lg font-medium text-gray-900 mb-4">Proveedores Principales</h3>
                             <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compras Realizadas</th></tr></thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="prov in reportesProveedores" :key="prov.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ prov.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ prov.compras_count }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Personal -->
                        <div v-show="activeTab === 'personal'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Rendimiento de Personal</h3>
                             <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ventas</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Citas</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mantenimientos</th></tr></thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="user in reportesPersonal" :key="user.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ user.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.ventas_count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.citas_count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.mantenimientos_count }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Auditoría -->
                        <div v-show="activeTab === 'auditoria'">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Auditoría Reciente</h3>
                             <div class="overflow-x-auto border border-gray-200 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th></tr></thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="log in reportesAuditoria" :key="log.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDateTime(log.created_at) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ log.user?.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ log.accion || 'Acción' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate">{{ log.descripcion }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Gastos Operativos -->
                       <div v-show="activeTab === 'gastos'">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Detalle de Gastos Operativos</h3>
                            
                            <!-- Totales de Gastos -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                                <div class="bg-gray-50 p-6 rounded-xl border-b-4 border-red-500 shadow-sm">
                                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Total Gastos</p>
                                    <h4 class="text-2xl font-black text-red-600">{{ formatCurrency(gastosOperativos.totales?.total) }}</h4>
                                </div>
                                <div class="bg-gray-50 p-6 rounded-xl border-b-4 border-orange-500 shadow-sm">
                                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Subtotal</p>
                                    <h4 class="text-2xl font-black text-orange-600">{{ formatCurrency(gastosOperativos.totales?.subtotal) }}</h4>
                                </div>
                                <div class="bg-gray-50 p-6 rounded-xl border-b-4 border-blue-500 shadow-sm">
                                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">IVA Pagado</p>
                                    <h4 class="text-2xl font-black text-blue-600">{{ formatCurrency(gastosOperativos.totales?.iva) }}</h4>
                                </div>
                                <div class="bg-gray-50 p-6 rounded-xl border-b-4 border-indigo-500 shadow-sm">
                                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider mb-1">Cantidad</p>
                                    <h4 class="text-2xl font-black text-indigo-600">{{ gastosOperativos.totales?.cantidad || 0 }}</h4>
                                </div>
                            </div>

                            <!-- Desglose por Categoría -->
                            <div class="mb-8">
                                <h4 class="text-md font-bold text-gray-700 mb-4">Gasto por Categoría</h4>
                                <div class="grid grid-cols-1 gap-4">
                                    <div v-for="cat in gastosOperativos.porCategoria" :key="cat.nombre" class="bg-white border rounded-lg p-4 flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 mr-4">
                                                <FontAwesomeIcon icon="folder" />
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-800">{{ cat.nombre }}</div>
                                                <div class="text-sm text-gray-500">{{ cat.cantidad }} registros • {{ cat.porcentaje }}% del total</div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold text-gray-900">{{ formatCurrency(cat.total) }}</div>
                                            <div class="w-32 h-2 bg-gray-100 rounded-full mt-1 overflow-hidden">
                                                <div class="h-full bg-red-500" :style="{ width: cat.porcentaje + '%' }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Listado Detallado -->
                            <div class="overflow-x-auto border rounded-xl shadow-sm">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Fecha</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Categoría</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Proveedor</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="gasto in gastosOperativos.gastos" :key="gasto.id" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 text-sm text-gray-600">{{ formatDateTime(gasto.fecha_compra) }}</td>
                                            <td class="px-6 py-4 text-sm font-bold text-gray-800">{{ gasto.categoria_gasto?.nombre || 'General' }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-600">{{ gasto.proveedor?.nombre_razon_social || 'N/A' }}</td>
                                            <td class="px-6 py-4 text-sm font-black text-red-600 text-right">{{ formatCurrency(gasto.total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Balance Ventas vs Compras -->
                        <div v-show="activeTab === 'balance'">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Balance Financiero: Ventas vs Egresos</h3>
                            
                            <!-- Tarjetas de Balance -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 rounded-2xl text-white shadow-lg">
                                    <p class="text-sm font-bold opacity-80 uppercase mb-2">Total Ingresos (Ventas)</p>
                                    <h4 class="text-3xl font-black">{{ formatCurrency(balanceData.balance?.ventas) }}</h4>
                                    <div class="mt-4 text-xs font-bold bg-white/20 inline-block px-2 py-1 rounded">Ingresos aprobados</div>
                                </div>
                                <div class="bg-gradient-to-br from-red-500 to-red-600 p-6 rounded-2xl text-white shadow-lg">
                                    <p class="text-sm font-bold opacity-80 uppercase mb-2">Total Egresos</p>
                                    <h4 class="text-3xl font-black">{{ formatCurrency(balanceData.balance?.total_egresos) }}</h4>
                                    <div class="mt-4 text-xs font-bold bg-white/20 inline-block px-2 py-1 rounded">Compras + Gastos</div>
                                </div>
                                <div :class="[balanceData.metricas?.diferencia >= 0 ? 'bg-gradient-to-br from-blue-500 to-indigo-600' : 'bg-gradient-to-br from-amber-500 to-orange-600', 'p-6 rounded-2xl text-white shadow-lg']">
                                    <p class="text-sm font-bold opacity-80 uppercase mb-2">Utilidad Operativa</p>
                                    <h4 class="text-3xl font-black">{{ formatCurrency(balanceData.metricas?.diferencia) }}</h4>
                                    <div class="mt-4 text-xs font-bold bg-white/20 inline-block px-2 py-1 rounded">Resultado del periodo</div>
                                </div>
                            </div>

                            <!-- Métricas de Eficiencia -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="bg-white border rounded-xl p-6 shadow-sm">
                                    <h4 class="text-sm font-bold text-gray-500 uppercase mb-6">Méridicas de Desempeño</h4>
                                    <div class="space-y-6">
                                        <div>
                                            <div class="flex justify-between text-sm mb-2">
                                                <span class="font-bold text-gray-700">Margen Operativo</span>
                                                <span class="font-black text-blue-600">{{ balanceData.metricas?.margen_operativo }}%</span>
                                            </div>
                                            <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-blue-500" :style="{ width: balanceData.metricas?.margen_operativo + '%' }"></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex justify-between text-sm mb-2">
                                                <span class="font-bold text-gray-700">Inversión en Inventario / Ventas</span>
                                                <span class="font-black text-orange-600">{{ balanceData.metricas?.ratio_inventario }}%</span>
                                            </div>
                                            <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-orange-500" :style="{ width: balanceData.metricas?.ratio_inventario + '%' }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-white border rounded-xl p-6 shadow-sm">
                                    <h4 class="text-sm font-bold text-gray-500 uppercase mb-6">Desglose de Egresos</h4>
                                    <div class="flex items-center justify-around h-full">
                                        <div class="text-center">
                                            <div class="text-2xl font-black text-red-600">{{ formatCurrency(balanceData.balance?.compras_inventario) }}</div>
                                            <div class="text-xs text-gray-500 font-bold uppercase">Inventario</div>
                                        </div>
                                        <div class="w-px h-12 bg-gray-200"></div>
                                        <div class="text-center">
                                            <div class="text-2xl font-black text-orange-600">{{ formatCurrency(balanceData.balance?.gastos_operativos) }}</div>
                                            <div class="text-xs text-gray-500 font-bold uppercase">G. Operativos</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, defineAsyncComponent, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { format, isWithinInterval } from 'date-fns';
import { es } from 'date-fns/locale';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

// Lazy load components
const GeneralTab = defineAsyncComponent(() => import('@/Pages/Reportes/Partials/GeneralTab.vue'));
const VentasTab = defineAsyncComponent(() => import('@/Pages/Reportes/Partials/VentasTab.vue'));
const ComprasTab = defineAsyncComponent(() => import('@/Pages/Reportes/Partials/ComprasTab.vue'));
const InventarioTab = defineAsyncComponent(() => import('@/Pages/Reportes/Partials/InventarioTab.vue'));
const PrestamosTab = defineAsyncComponent(() => import('@/Pages/Reportes/Partials/PrestamosTab.vue'));

const props = defineProps({
    activeTab: String,
    reportesVentas: { type: Array, default: () => [] },
    corteVentas: { type: Number, default: 0 },
    utilidadVentas: { type: Number, default: 0 },
    ivaVentas: { type: Number, default: 0 },
    reportesCompras: { type: Array, default: () => [] },
    totalCompras: { type: Number, default: 0 },
    inventario: { type: Array, default: () => [] },
    valorInventario: { type: Number, default: 0 },
    movimientosInventario: { type: Array, default: () => [] },
    corteDiario: { type: Array, default: () => [] },
    usuarios: { type: Array, default: () => [] },
    prestamos: { type: Array, default: () => [] },
    // Nuevos reportes
    reportesServicios: { type: Array, default: () => [] },
    reportesCitas: { type: Array, default: () => [] },
    reportesMantenimientos: { type: Array, default: () => [] },
    reportesRentas: { type: Array, default: () => [] },
    reportesCobranzas: { type: Array, default: () => [] },
    reportesGanancias: { type: Object, default: () => ({}) },
    reportesProveedores: { type: Array, default: () => [] },
    reportesPersonal: { type: Array, default: () => [] },
    reportesAuditoria: { type: Array, default: () => [] },
    reportesProductos: { type: Array, default: () => [] },
    gastosOperativos: { type: Object, default: () => ({ gastos: [], totales: {}, porCategoria: [] }) },
    balanceData: { type: Object, default: () => ({ balance: {}, metricas: {}, grafica: {} }) },
});

const page = usePage();
const urlParams = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
const activeTab = ref(props.activeTab || 'general');

watch(() => props.activeTab, (newVal) => {
    activeTab.value = newVal;
});

const changeTab = (key) => {
    router.visit(route('reportes.index'), {
        data: { tab: key },
        preserveState: true,
        preserveScroll: true,
        only: [
            'reportesVentas', 'reportesCompras', 'inventario', 'prestamos', 
            'reportesServicios', 'reportesCitas', 'reportesMantenimientos',
            'reportesRentas', 'reportesCobranzas', 'reportesGanancias', 
            'reportesProveedores', 'reportesPersonal', 'reportesAuditoria', 
            'reportesProductos', 'gastosOperativos', 'balanceData', 'activeTab'
        ],
        onSuccess: () => {
             activeTab.value = key;
        }
    });
};

const tabs = [
    { key: 'general', label: 'General', icon: 'chart-pie' },
    { key: 'ventas', label: 'Ventas', icon: 'shopping-cart' },
    { key: 'compras', label: 'Compras', icon: 'shopping-bag' },
    { key: 'inventario', label: 'Inventario', icon: 'boxes' },
    { key: 'movimientos', label: 'Movimientos', icon: 'history' },
    { key: 'corte', label: 'Corte Diario', icon: 'calculator' },
    { key: 'clientes', label: 'Clientes', icon: 'users' },
    { key: 'prestamos', label: 'Préstamos', icon: 'hand-holding-usd' },
    { key: 'cobranzas', label: 'Cobranzas', icon: 'wallet' },
    { key: 'servicios', label: 'Servicios', icon: 'wrench' },
    { key: 'citas', label: 'Citas', icon: 'calendar-alt' },
    { key: 'mantenimientos', label: 'Mantenimientos', icon: 'tools' },
    { key: 'rentas', label: 'Rentas', icon: 'file-contract' },
    { key: 'ganancias', label: 'Ganancias', icon: 'chart-line' },
    { key: 'proveedores', label: 'Proveedores', icon: 'truck' },
    { key: 'personal', label: 'Personal', icon: 'user' },
    { key: 'auditoria', label: 'Auditoría', icon: 'clipboard-list' },
    { key: 'productos', label: 'Productos', icon: 'tags' },
    { key: 'gastos', label: 'Gestión Gastos', icon: 'file-invoice-dollar' },
    { key: 'balance', label: 'Balance Ventas/Compras', icon: 'balance-scale' },
];

const fechaInicio = ref('');
const fechaFin = ref('');
// Variables para corte diario
const fechaCorte = ref(new Date().toISOString().split('T')[0]);
const usuarioCorte = ref('');
const tipoCobro = ref('');

// --- Helper Functions ---
const formatCurrency = (value) => {
     return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(Number.parseFloat(value) || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    try {
        return format(new Date(date), 'MMM d, yyyy h:mm a', { locale: es });
    } catch {
        return '-';
    }
};

const formatDateTime = (date) => {
    if (!date) return '-';
    try {
        return format(new Date(date), 'yyyy-MM-dd HH:mm', { locale: es });
    } catch {
        return '-';
    }
};

const calculateProfit = (venta) => Number.parseFloat(venta.ganancia_total) || 0;

// --- Filtering Logic ---
const filtrarPorFecha = (items) => {
    if (!fechaInicio.value || !fechaFin.value) return items;
    const start = new Date(fechaInicio.value + 'T00:00:00');
    const end = new Date(fechaFin.value + 'T23:59:59');
    return items.filter(item => {
        const fecha = new Date(item.created_at);
        return isWithinInterval(fecha, { start, end });
    });
};

const filtrarDatos = () => {
    // Computed properties recalculate automatically
};

const limpiarFiltros = () => {
    fechaInicio.value = '';
    fechaFin.value = '';
    // Also reset corte filters if needed
    usuarioCorte.value = '';
    tipoCobro.value = '';
    fechaCorte.value = new Date().toISOString().split('T')[0];
};

// --- Computed Data ---
// ... (Reusing logic from original file)
const ventasFiltradas = computed(() => filtrarPorFecha(props.reportesVentas));
const comprasFiltradas = computed(() => filtrarPorFecha(props.reportesCompras));
const inventarioFiltrado = computed(() => props.inventario);
const movimientosFiltrados = computed(() => filtrarPorFecha(props.movimientosInventario));

const utilidadFiltrada = computed(() => ventasFiltradas.value.reduce((acc, v) => acc + calculateProfit(v), 0));
const ivaFiltrado = computed(() => ventasFiltradas.value.reduce((acc, v) => acc + Number.parseFloat(v.iva || 0), 0));
const totalComprasFiltrado = computed(() => comprasFiltradas.value.reduce((acc, c) => acc + Number.parseFloat(c.total), 0));

const productosEnStock = computed(() => inventarioFiltrado.value.filter(p => p.stock > 0).length);
const productosBajoStock = computed(() => inventarioFiltrado.value.filter(p => p.stock > 0 && p.stock <= (p.stock_minimo || 0)).length);
const productosAgotados = computed(() => inventarioFiltrado.value.filter(p => p.stock <= 0).length);

// Corte Logic
const usuariosActivos = computed(() => props.usuarios || []);
const filtrarCorte = () => {}; // Trigger update
const exportarCorte = () => Swal.fire({
    icon: 'info',
    title: 'Función pendiente',
    text: 'Función de exportar corte - pendiente implementar'
});

const pagosCorteFiltrados = computed(() => {
    let corte = props.corteDiario || [];
    if (fechaCorte.value) {
        corte = corte.filter(cobro => new Date(cobro.fecha_pago).toISOString().split('T')[0] === fechaCorte.value);
    }
    if (usuarioCorte.value) {
        corte = corte.filter(cobro => cobro.cobrado_por === usuarioCorte.value);
    }
    if (tipoCobro.value) {
        corte = corte.filter(cobro => cobro.tipo === tipoCobro.value);
    }
    return corte;
});

const corteFiltrado = computed(() => pagosCorteFiltrados.value.reduce((acc, c) => acc + Number.parseFloat(c.total || 0), 0));
const totalCorteVentas = computed(() => pagosCorteFiltrados.value.filter(c => c.tipo === 'venta').reduce((acc, c) => acc + Number.parseFloat(c.total || 0), 0));
const totalCorteRentas = computed(() => pagosCorteFiltrados.value.filter(c => c.tipo === 'renta').reduce((acc, c) => acc + Number.parseFloat(c.total || 0), 0));
const usuariosCobradores = computed(() => new Set(pagosCorteFiltrados.value.map(c => c.cobrado_por).filter(Boolean)).size);
const totalVentasFiltradas = computed(() => ventasFiltradas.value.reduce((acc, v) => acc + Number.parseFloat(v.total || 0), 0));

// Purchases logic
const proveedoresUnicos = computed(() => new Set(comprasFiltradas.value.map(c => c.proveedor?.nombre_razon_social).filter(Boolean)).size);
const productosComprados = computed(() => comprasFiltradas.value.reduce((acc, c) => acc + (c.productos?.length || 0), 0));
const comprasPorProveedor = computed(() => {
    const map = {};
    comprasFiltradas.value.forEach(c => {
        const name = c.proveedor?.nombre_razon_social || 'Desconocido';
        if (!map[name]) map[name] = { nombre: name, compras: 0, total: 0 };
        map[name].compras++;
        map[name].total += Number.parseFloat(c.total || 0);
    });
    return Object.values(map).sort((a, b) => b.total - a.total);
});

// Sales logic
const clientesUnicosVentas = computed(() => new Set(ventasFiltradas.value.map(v => v.cliente?.nombre_razon_social).filter(Boolean)).size);
const ventasPagadasYAprobadas = computed(() => ventasFiltradas.value.filter(v => v.pagado && v.estado === 'aprobada').length);
const ventasPendientesPago = computed(() => ventasFiltradas.value.filter(v => !v.pagado).length);
const ventasBorrador = computed(() => ventasFiltradas.value.filter(v => v.estado === 'borrador').length);
const topClientes = computed(() => {
    const map = {};
    ventasFiltradas.value.forEach(v => {
        const name = v.cliente?.nombre_razon_social || 'Desconocido';
        if (!map[name]) map[name] = { nombre: name, total: 0, ventas: 0 };
        map[name].total += Number.parseFloat(v.total || 0);
        map[name].ventas++;
    });
    return Object.values(map).sort((a, b) => b.total - a.total).slice(0, 5);
});

// Clientes logic
const clientesActivos = computed(() => {
    const map = {};
    ventasFiltradas.value.forEach(v => {
        if(v.cliente) {
            const name = v.cliente.nombre_razon_social;
            if(!map[name]) map[name] = { ...v.cliente, ventas_count: 0, rentas_count: 0 };
            map[name].ventas_count++;
        }
    });
    return Object.values(map);
});
const clientesConCompras = computed(() => clientesActivos.value.filter(c => c.ventas_count > 0).length);
const clientesConRentas = computed(() => clientesActivos.value.filter(c => c.rentas_count > 0).length);
const clientesDeudores = computed(() => 0); // Mock

</script>

<style scoped>
@import "tailwindcss" reference;
.custom-scrollbar::-webkit-scrollbar {
    height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>




