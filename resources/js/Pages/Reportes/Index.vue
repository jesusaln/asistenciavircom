<template>
    <AppLayout title="Centro de Reportes">
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h2 class="font-black text-2xl text-slate-900 dark:text-white leading-tight tracking-tight uppercase">
                        Inteligencia de Negocios
                    </h2>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mt-1">
                        Reportes de {{ tabs.find(t => t.key === activeTab)?.label }}
                    </p>
                </div>
                
                <!-- Actions en el Header Premium -->
                <div class="flex items-center gap-2 bg-white/60 dark:bg-slate-900/60 backdrop-blur-md p-2 rounded-2xl border border-gray-100 dark:border-slate-800/60 shadow-lg ring-1 ring-black/5 dark:ring-white/5">
                    <div class="flex items-center gap-1 group">
                         <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-tight pl-2">Desde:</span>
                         <input
                            v-model="fechaInicio"
                            type="date"
                            class="bg-transparent border-none text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-1 cursor-pointer"
                            @change="filtrarDatos"
                        />
                    </div>
                    
                    <div class="w-px h-4 bg-slate-200 dark:bg-slate-700"></div>

                    <div class="flex items-center gap-1 group">
                         <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-tight">Hasta:</span>
                         <input
                            v-model="fechaFin"
                            type="date"
                            class="bg-transparent border-none text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-0 p-1 cursor-pointer"
                            @change="filtrarDatos"
                        />
                    </div>
                    
                    <button
                        v-if="fechaInicio || fechaFin"
                        @click="limpiarFiltros"
                        class="ml-2 w-8 h-8 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-all duration-300"
                        title="Limpiar Filtros"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8 bg-slate-50 dark:bg-slate-950 min-h-screen transition-colors duration-500">
            <div class="w-full px-4 sm:px-6 lg:px-8">
                <!-- Mobile Tab Selector Premium -->
                <div class="sm:hidden mb-6">
                    <div class="relative group">
                        <select
                            id="tabs"
                            v-model="activeTab"
                            class="block w-full pl-4 pr-10 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl text-sm font-black uppercase tracking-widest text-slate-700 dark:text-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-lg select-arrow-premium"
                        >
                            <option v-for="tab in tabs" :key="tab.key" :value="tab.key">{{ tab.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Desktop/Tablet Tab Navigation Premium Glassmorphism -->
                <div class="hidden sm:block mb-10 overflow-x-auto custom-scrollbar">
                    <div class="inline-flex p-1.5 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[2rem] border border-gray-100 dark:border-slate-800/60 shadow-xl ring-1 ring-black/5 dark:ring-white/5 min-w-full">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="changeTab(tab.key)"
                            :class="[
                                activeTab === tab.key
                                    ? 'bg-blue-600 text-white shadow-xl shadow-blue-500/30'
                                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-800/40',
                                'relative whitespace-nowrap py-3 px-6 rounded-[1.5rem] font-black text-[10px] uppercase tracking-[0.1em] flex items-center transition-all duration-300 gap-2'
                            ]"
                        >
                            <FontAwesomeIcon :icon="tab.icon" :class="activeTab === tab.key ? 'text-white' : 'text-slate-400'" />
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Main Content Surface -->
                <div class="bg-white dark:bg-slate-900/40 dark:backdrop-blur-2xl overflow-hidden shadow-2xl rounded-[2.5rem] border border-gray-100 dark:border-slate-800/60 ring-1 ring-black/5 dark:ring-white/5 transition-all duration-500">
                    <div class="p-8 lg:p-10">
                        <!-- Tab General -->
                        <div v-if="activeTab === 'general'" class="animate-in fade-in duration-500">
                            <GeneralTab />
                        </div>

                        <!-- Tab Ventas -->
                        <div v-if="activeTab === 'ventas'" class="animate-in slide-in-from-bottom-2 duration-500">
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
                        <div v-show="activeTab === 'compras'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <ComprasTab
                                :compras-filtradas="comprasFiltradas"
                                :total-compras-filtrado="totalComprasFiltrado"
                                :proveedores-unicos="proveedoresUnicos"
                                :productos-comprados="productosComprados"
                                :compras-por-proveedor="comprasPorProveedor"
                            />
                        </div>

                        <!-- Tab Inventario -->
                        <div v-show="activeTab === 'inventario'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <InventarioTab
                                :inventario-filtrado="inventarioFiltrado"
                                :productos-en-stock="productosEnStock"
                                :productos-bajo-stock="productosBajoStock"
                                :productos-agotados="productosAgotados"
                            />
                        </div>

                        <!-- Tab Corte Diario -->
                        <div v-show="activeTab === 'corte'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="mb-10">
                                <div class="flex items-center gap-3 mb-6">
                                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                        <FontAwesomeIcon icon="calculator" />
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Corte Diario</h3>
                                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest mt-0.5">Visión consolidada de cobros y flujos de caja</p>
                                    </div>
                                </div>

                                <!-- Filtros específicos para corte Premium -->
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10 p-6 bg-slate-50/50 dark:bg-slate-950/40 rounded-3xl border border-gray-100 dark:border-slate-800/40">
                                    <div class="space-y-2">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">Fecha del Corte</label>
                                        <input
                                            v-model="fechaCorte"
                                            type="date"
                                            class="block w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all cursor-pointer"
                                            @change="filtrarCorte"
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">Usuario que Cobró</label>
                                        <select
                                            v-model="usuarioCorte"
                                            class="block w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm select-arrow-premium"
                                            @change="filtrarCorte"
                                        >
                                            <option value="">Todos los usuarios</option>
                                            <option v-for="usuario in usuariosActivos" :key="usuario.id" :value="usuario.id">
                                                {{ usuario.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1">Tipo de Cobro</label>
                                        <select
                                            v-model="tipoCobro"
                                            class="block w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-700 dark:text-slate-200 focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all shadow-sm select-arrow-premium"
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
                                            class="w-full inline-flex items-center justify-center px-6 py-4 bg-emerald-500 text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-xl hover:bg-emerald-600 hover:shadow-xl hover:shadow-emerald-500/20 transition-all duration-300 transform hover:-translate-y-1"
                                        >
                                            <FontAwesomeIcon icon="file-excel" class="mr-2" />
                                            Exportar Inteligencia
                                        </button>
                                    </div>
                                </div>

                                <!-- Resumen del corte Premium Cards -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                                    <div class="group bg-white dark:bg-slate-900/60 p-6 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-lg hover:shadow-2xl transition-all duration-300">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 group-hover:scale-110 transition-transform">
                                                <FontAwesomeIcon icon="shopping-cart" class="text-xl" />
                                            </div>
                                            <span class="text-[10px] font-black text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-lg uppercase tracking-wider">Ventas</span>
                                        </div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Ventas</div>
                                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ formatCurrency(totalCorteVentas) }}</div>
                                    </div>

                                    <div class="group bg-white dark:bg-slate-900/60 p-6 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-lg hover:shadow-2xl transition-all duration-300">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="w-12 h-12 rounded-2xl bg-blue-500/10 flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform">
                                                <FontAwesomeIcon icon="file-contract" class="text-xl" />
                                            </div>
                                            <span class="text-[10px] font-black text-blue-500 bg-blue-500/10 px-2 py-1 rounded-lg uppercase tracking-wider">Rentas</span>
                                        </div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Rentas</div>
                                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ formatCurrency(totalCorteRentas) }}</div>
                                    </div>

                                    <div class="group bg-white dark:bg-slate-900/60 p-6 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-lg hover:shadow-2xl transition-all duration-300">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="w-12 h-12 rounded-2xl bg-purple-500/10 flex items-center justify-center text-purple-500 group-hover:scale-110 transition-transform">
                                                <FontAwesomeIcon icon="receipt" class="text-xl" />
                                            </div>
                                            <span class="text-[10px] font-black text-purple-500 bg-purple-500/10 px-2 py-1 rounded-lg uppercase tracking-wider">Volumen</span>
                                        </div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Núm. Cobros</div>
                                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ pagosCorteFiltrados.length }}</div>
                                    </div>

                                    <div class="group bg-white dark:bg-slate-900/60 p-6 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-lg hover:shadow-2xl transition-all duration-300">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 group-hover:scale-110 transition-transform">
                                                <FontAwesomeIcon icon="users" class="text-xl" />
                                            </div>
                                            <span class="text-[10px] font-black text-amber-500 bg-amber-500/10 px-2 py-1 rounded-lg uppercase tracking-wider">Staff</span>
                                        </div>
                                        <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cobradores</div>
                                        <div class="text-2xl font-black text-slate-900 dark:text-white">{{ usuariosCobradores }}</div>
                                    </div>
                                </div>

                                <!-- Tabla de corte diario Premium -->
                                <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl ring-1 ring-black/5 dark:ring-white/5">
                                    <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                                            <tr>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Fecha/Hora</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Tipo</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Número</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Cliente</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Método</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Monto</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Cobrador</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                            <tr v-for="cobro in pagosCorteFiltrados" :key="cobro.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors duration-200">
                                                <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">
                                                    {{ formatDateTime(cobro.fecha_pago) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span :class="[
                                                        cobro.tipo === 'venta' 
                                                            ? 'bg-emerald-500/10 text-emerald-600' 
                                                            : 'bg-blue-500/10 text-blue-600',
                                                        'inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider'
                                                    ]">
                                                        {{ cobro.tipo === 'venta' ? 'Venta' : 'Renta' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-xs font-black font-mono text-slate-900 dark:text-white">
                                                    {{ cobro.numero }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-700 dark:text-slate-200">
                                                    {{ cobro.cliente }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">
                                                    {{ cobro.metodo_pago || 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-slate-900 dark:text-white">
                                                    {{ formatCurrency(cobro.total) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-400">
                                                    {{ cobro.cobrado_por }}
                                                </td>
                                            </tr>
                                            <tr v-if="pagosCorteFiltrados.length === 0">
                                                <td colspan="7" class="px-6 py-12 text-center">
                                                    <div class="flex flex-col items-center">
                                                        <FontAwesomeIcon icon="receipt" class="text-4xl text-slate-200 dark:text-slate-800 mb-4" />
                                                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Sin cobros registrados en este periodo</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Préstamos Premium -->
                        <div v-show="activeTab === 'prestamos'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <PrestamosTab :prestamos="prestamos" />
                        </div>

                        <!-- Tab Clientes Premium -->
                        <div v-show="activeTab === 'clientes'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="mb-10">
                                <div class="flex items-center gap-3 mb-8">
                                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                        <FontAwesomeIcon icon="users" />
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Ecosistema de Clientes</h3>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
                                    <div class="bg-blue-500/5 dark:bg-blue-500/10 p-6 rounded-3xl border border-blue-100 dark:border-blue-500/20">
                                        <div class="text-sm font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest mb-1">Activos</div>
                                        <div class="text-3xl font-black text-blue-700 dark:text-blue-300">{{ clientesActivos.length }}</div>
                                    </div>
                                    <div class="bg-emerald-500/5 dark:bg-emerald-500/10 p-6 rounded-3xl border border-emerald-100 dark:border-emerald-500/20">
                                        <div class="text-sm font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1">Compradores</div>
                                        <div class="text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ clientesConCompras }}</div>
                                    </div>
                                    <div class="bg-indigo-500/5 dark:bg-indigo-500/10 p-6 rounded-3xl border border-indigo-100 dark:border-indigo-500/20">
                                        <div class="text-sm font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest mb-1">Arrendatarios</div>
                                        <div class="text-3xl font-black text-indigo-700 dark:text-indigo-300">{{ clientesConRentas }}</div>
                                    </div>
                                    <div class="bg-rose-500/5 dark:bg-rose-500/10 p-6 rounded-3xl border border-rose-100 dark:border-rose-500/20">
                                        <div class="text-sm font-black text-rose-600 dark:text-rose-400 uppercase tracking-widest mb-1">Deudores</div>
                                        <div class="text-3xl font-black text-rose-700 dark:text-rose-300">{{ clientesDeudores }}</div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl ring-1 ring-black/5 dark:ring-white/5">
                                    <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                        <thead class="bg-slate-50 dark:bg-slate-900/50">
                                            <tr>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Contacto</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Ventas</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Rentas</th>
                                                <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                            <tr v-for="cliente in clientesActivos" :key="cliente.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-xs font-black text-slate-900 dark:text-white">{{ cliente.nombre_razon_social }}</div>
                                                    <div class="text-[10px] font-bold text-slate-400 uppercase mt-0.5 tracking-tighter">{{ cliente.email || 'Sin email' }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ cliente.telefono || 'N/A' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="text-xs font-black text-slate-900 dark:text-white">{{ cliente.ventas_count || 0 }}</span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="text-xs font-black text-slate-900 dark:text-white">{{ cliente.rentas_count || 0 }}</span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span :class="[
                                                        (cliente.ventas_count || 0) > 0 || (cliente.rentas_count || 0) > 0
                                                            ? 'bg-emerald-500/10 text-emerald-600'
                                                            : 'bg-slate-500/10 text-slate-500',
                                                        'inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest'
                                                    ]">
                                                        {{ (cliente.ventas_count || 0) > 0 || (cliente.rentas_count || 0) > 0 ? 'Activo' : 'Inactivo' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Servicios Premium -->
                        <div v-show="activeTab === 'servicios'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400">
                                    <FontAwesomeIcon icon="wrench" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Top Servicios</h3>
                            </div>
                            <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl ring-1 ring-black/5 dark:ring-white/5">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Servicio</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Demanda (Q)</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                        <tr v-for="(item, i) in reportesServicios" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-slate-900 dark:text-white">{{ item.servicio?.nombre || 'Desconocido' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ item.total_cantidad }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(item.total_ingreso) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                         <!-- Citas Premium -->
                        <div v-show="activeTab === 'citas'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-600 dark:text-orange-400">
                                    <FontAwesomeIcon icon="calendar-alt" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Últimas Citas</h3>
                            </div>
                            <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl ring-1 ring-black/5 dark:ring-white/5">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Técnico</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                        <tr v-for="cita in reportesCitas" :key="cita.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ formatDateTime(cita.fecha_inicio) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-slate-900 dark:text-white">{{ cita.cliente?.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ cita.tecnico?.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-500">{{ cita.estado }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mantenimientos Premium -->
                        <div v-show="activeTab === 'mantenimientos'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-600 dark:text-rose-400">
                                    <FontAwesomeIcon icon="tools" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Mantenimientos Recientes</h3>
                            </div>
                            <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl ring-1 ring-black/5 dark:ring-white/5">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Equipo</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                        <tr v-for="mant in reportesMantenimientos" :key="mant.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ formatDateTime(mant.fecha_programada) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-slate-900 dark:text-white">{{ mant.cliente?.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ mant.carro?.modelo }} {{ mant.carro?.placas }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest bg-rose-500/10 text-rose-600">{{ mant.estado }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Rentas Premium -->
                        <div v-show="activeTab === 'rentas'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <FontAwesomeIcon icon="file-contract" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Rentas Recientes</h3>
                            </div>
                             <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl ring-1 ring-black/5 dark:ring-white/5">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Contrato</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Equipo</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                        <tr v-for="renta in reportesRentas" :key="renta.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black font-mono text-indigo-600">{{ renta.numero_contrato }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-slate-900 dark:text-white">{{ renta.cliente?.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ renta.equipo?.nombre }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest bg-indigo-500/10 text-indigo-600">{{ renta.estado }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Cobranzas Premium -->
                        <div v-show="activeTab === 'cobranzas'" class="animate-in slide-in-from-bottom-2 duration-500">
                             <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-teal-500/10 flex items-center justify-center text-teal-600 dark:text-teal-400">
                                    <FontAwesomeIcon icon="wallet" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Últimas Cobranzas</h3>
                            </div>
                             <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl ring-1 ring-black/5 dark:ring-white/5">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Cliente</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Monto</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                        <tr v-for="cob in reportesCobranzas" :key="cob.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ formatDateTime(cob.created_at) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-slate-900 dark:text-white">{{ cob.renta?.cliente?.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(cob.monto_pagado) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest bg-teal-500/10 text-teal-600">{{ cob.estado }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Ganancias Premium -->
                        <div v-show="activeTab === 'ganancias'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                    <FontAwesomeIcon icon="chart-line" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Estructura de Rentabilidad</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
                                <div class="bg-emerald-500/5 dark:bg-emerald-500/10 p-8 rounded-[2rem] border border-emerald-100 dark:border-emerald-500/20 shadow-xl shadow-emerald-500/5">
                                    <div class="text-[10px] font-black text-emerald-600/60 dark:text-emerald-400/60 uppercase tracking-[0.2em] mb-2">Ingresos Brutos</div>
                                    <div class="text-4xl font-black text-emerald-700 dark:text-emerald-400">{{ formatCurrency(reportesGanancias.ventas || 0) }}</div>
                                    <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-emerald-600/40 uppercase">
                                        <FontAwesomeIcon icon="arrow-up" />
                                        <span>Flujo de ventas total</span>
                                    </div>
                                </div>
                                 <div class="bg-rose-500/5 dark:bg-rose-500/10 p-8 rounded-[2rem] border border-rose-100 dark:border-rose-500/20 shadow-xl shadow-rose-500/5">
                                    <div class="text-[10px] font-black text-rose-600/60 dark:text-rose-400/60 uppercase tracking-[0.2em] mb-2">Inversión Compras</div>
                                    <div class="text-4xl font-black text-rose-700 dark:text-rose-400">{{ formatCurrency(reportesGanancias.compras || 0) }}</div>
                                    <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-rose-600/40 uppercase">
                                        <FontAwesomeIcon icon="shopping-bag" />
                                        <span>Adquisición de activos</span>
                                    </div>
                                </div>
                                <div class="bg-amber-500/5 dark:bg-amber-500/10 p-8 rounded-[2rem] border border-amber-100 dark:border-amber-500/20 shadow-xl shadow-amber-500/5">
                                    <div class="text-[10px] font-black text-amber-600/60 dark:text-amber-400/60 uppercase tracking-[0.2em] mb-2">Costos Operativos</div>
                                    <div class="text-4xl font-black text-amber-700 dark:text-amber-400">{{ formatCurrency(reportesGanancias.gastos || 0) }}</div>
                                    <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-amber-600/40 uppercase">
                                        <FontAwesomeIcon icon="file-invoice-dollar" />
                                        <span>Gastos generales</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Proveedores Premium -->
                        <div v-show="activeTab === 'proveedores'" class="animate-in slide-in-from-bottom-2 duration-500">
                             <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-slate-500/10 flex items-center justify-center text-slate-600 dark:text-slate-400">
                                    <FontAwesomeIcon icon="truck-loading" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Ecosistema de Proveedores</h3>
                            </div>
                            <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Proveedor</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Volumen Compras</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                        <tr v-for="prov in reportesProveedores" :key="prov.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-slate-900 dark:text-white">{{ prov.nombre_razon_social }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-4 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-600 dark:text-slate-400">{{ prov.compras_count }} Órdenes</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Personal Premium -->
                        <div v-show="activeTab === 'personal'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                    <FontAwesomeIcon icon="user-tie" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Productividad Humana</h3>
                            </div>
                            <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Colaborador</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Ventas</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Citas</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Soporte</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                        <tr v-for="user in reportesPersonal" :key="user.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-xs font-black text-slate-900 dark:text-white">{{ user.name }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ user.ventas_count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ user.citas_count }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-300">{{ user.mantenimientos_count }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Auditoría Premium -->
                        <div v-show="activeTab === 'auditoria'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-orange-500/10 flex items-center justify-center text-orange-600 dark:text-orange-400">
                                    <FontAwesomeIcon icon="fingerprint" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Trazabilidad Operativa</h3>
                            </div>
                            <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Marca Temporal</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Agente</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Acción</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                        <tr v-for="log in reportesAuditoria" :key="log.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-[10px] font-bold text-slate-400">{{ formatDateTime(log.created_at) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-slate-700 dark:text-slate-200">{{ log.user?.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-widest bg-orange-500/10 text-orange-600">{{ log.accion || 'Acción' }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600 dark:text-slate-400 max-w-xs truncate">{{ log.descripcion }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Gastos Operativos Premium -->
                       <div v-show="activeTab === 'gastos'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-600 dark:text-rose-400">
                                    <FontAwesomeIcon icon="file-invoice-dollar" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Análisis de Gastos</h3>
                            </div>
                            
                            <!-- Totales de Gastos Premium -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10">
                                <div class="bg-white dark:bg-slate-900/60 p-6 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-lg">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Egreso</div>
                                    <div class="text-2xl font-black text-rose-600">{{ formatCurrency(gastosOperativos.totales?.total) }}</div>
                                </div>
                                <div class="bg-white dark:bg-slate-900/60 p-6 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-lg">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Subtotal</div>
                                    <div class="text-2xl font-black text-amber-600">{{ formatCurrency(gastosOperativos.totales?.subtotal) }}</div>
                                </div>
                                <div class="bg-white dark:bg-slate-900/60 p-6 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-lg">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">IVA Soportado</div>
                                    <div class="text-2xl font-black text-blue-600">{{ formatCurrency(gastosOperativos.totales?.iva) }}</div>
                                </div>
                                <div class="bg-white dark:bg-slate-900/60 p-6 rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-lg">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Transacciones</div>
                                    <div class="text-2xl font-black text-indigo-600">{{ gastosOperativos.totales?.cantidad || 0 }}</div>
                                </div>
                            </div>

                            <!-- Desglose por Categoría Premium -->
                            <div class="mb-10">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Distribución por Categoría</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div v-for="cat in gastosOperativos.porCategoria" :key="cat.nombre" class="group bg-slate-50/50 dark:bg-slate-900/40 border border-gray-100 dark:border-slate-800/60 rounded-3xl p-6 flex items-center justify-between hover:shadow-xl transition-all duration-300">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-600 group-hover:scale-110 transition-transform">
                                                <FontAwesomeIcon icon="tag" />
                                            </div>
                                            <div>
                                                <div class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ cat.nombre }}</div>
                                                <div class="text-[10px] font-bold text-slate-400 uppercase mt-0.5">{{ cat.cantidad }} Registros • {{ cat.porcentaje }}%</div>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-black text-slate-900 dark:text-white">{{ formatCurrency(cat.total) }}</div>
                                            <div class="w-32 h-1.5 bg-gray-200 dark:bg-slate-800 rounded-full mt-2 overflow-hidden">
                                                <div class="h-full bg-rose-500 rounded-full" :style="{ width: cat.porcentaje + '%' }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Listado Detallado Premium -->
                            <div class="overflow-hidden rounded-3xl border border-gray-100 dark:border-slate-800/60 shadow-xl">
                                <table class="min-w-full divide-y divide-gray-100 dark:divide-slate-800/60">
                                    <thead class="bg-slate-50 dark:bg-slate-900/50">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Categoría</th>
                                            <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Proveedor</th>
                                            <th class="px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50 dark:divide-slate-800/40">
                                        <tr v-for="gasto in gastosOperativos.gastos" :key="gasto.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-bold text-slate-600 dark:text-slate-400">{{ formatDateTime(gasto.fecha_compra) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">{{ gasto.categoria_gasto?.nombre || 'General' }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-black text-slate-900 dark:text-white">{{ gasto.proveedor?.nombre_razon_social || 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <span class="text-sm font-black text-rose-600">{{ formatCurrency(gasto.total) }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Balance Ventas vs Compras Premium -->
                        <div v-show="activeTab === 'balance'" class="animate-in slide-in-from-bottom-2 duration-500">
                            <div class="flex items-center gap-3 mb-8">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <FontAwesomeIcon icon="balance-scale" />
                                </div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Equilibrio Financiero</h3>
                            </div>
                            
                            <!-- Tarjetas de Balance Premium -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                                <div class="relative overflow-hidden group bg-gradient-to-br from-emerald-500 to-teal-600 p-8 rounded-[2rem] text-white shadow-2xl shadow-emerald-500/20">
                                    <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-110 transition-transform duration-500">
                                        <FontAwesomeIcon icon="arrow-trend-up" size="4x" />
                                    </div>
                                    <div class="text-[10px] font-black opacity-60 uppercase tracking-[0.2em] mb-2">Ingresos Consolidados</div>
                                    <h4 class="text-4xl font-black">{{ formatCurrency(balanceData.balance?.ventas) }}</h4>
                                    <div class="mt-6 text-[10px] font-black bg-white/10 backdrop-blur-md inline-flex items-center px-3 py-1 rounded-full uppercase tracking-widest">Afluencia Total</div>
                                </div>
                                
                                <div class="relative overflow-hidden group bg-gradient-to-br from-rose-500 to-pink-600 p-8 rounded-[2rem] text-white shadow-2xl shadow-rose-500/20">
                                    <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-110 transition-transform duration-500">
                                        <FontAwesomeIcon icon="arrow-trend-down" size="4x" />
                                    </div>
                                    <div class="text-[10px] font-black opacity-60 uppercase tracking-[0.2em] mb-2">Egresos Totales</div>
                                    <h4 class="text-4xl font-black">{{ formatCurrency(balanceData.balance?.total_egresos) }}</h4>
                                    <div class="mt-6 text-[10px] font-black bg-white/10 backdrop-blur-md inline-flex items-center px-3 py-1 rounded-full uppercase tracking-widest">Inversión + Gastos</div>
                                </div>
                                
                                <div :class="[
                                    balanceData.metricas?.diferencia >= 0 
                                        ? 'from-blue-600 to-indigo-700 shadow-blue-500/20' 
                                        : 'from-amber-500 to-orange-600 shadow-amber-500/20',
                                    'relative overflow-hidden group bg-gradient-to-br p-8 rounded-[2rem] text-white shadow-2xl transition-all duration-500'
                                ]">
                                    <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-110 transition-transform duration-500">
                                        <FontAwesomeIcon icon="vault" size="4x" />
                                    </div>
                                    <div class="text-[10px] font-black opacity-60 uppercase tracking-[0.2em] mb-2">Utilidad Neta</div>
                                    <h4 class="text-4xl font-black">{{ formatCurrency(balanceData.metricas?.diferencia) }}</h4>
                                    <div class="mt-6 text-[10px] font-black bg-white/10 backdrop-blur-md inline-flex items-center px-3 py-1 rounded-full uppercase tracking-widest">Resultado Operativo</div>
                                </div>
                            </div>

                            <!-- Métricas de Eficiencia Premium -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                                <div class="bg-white dark:bg-slate-900/40 border border-gray-100 dark:border-slate-800/60 rounded-[2rem] p-8 shadow-xl">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-10">KPIs de Desempeño</h4>
                                    <div class="space-y-12">
                                        <div class="group">
                                            <div class="flex justify-between items-end mb-4">
                                                <div>
                                                    <span class="block text-xs font-black text-slate-900 dark:text-white uppercase tracking-wide">Margen Operativo</span>
                                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Rentabilidad sobre ventas</span>
                                                </div>
                                                <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ balanceData.metricas?.margen_operativo }}%</span>
                                            </div>
                                            <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                <div class="h-full bg-indigo-500 rounded-full transition-all duration-1000 ease-out" :style="{ width: balanceData.metricas?.margen_operativo + '%' }"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="group">
                                            <div class="flex justify-between items-end mb-4">
                                                <div>
                                                    <span class="block text-xs font-black text-slate-900 dark:text-white uppercase tracking-wide">Eficiencia de Inventario</span>
                                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Ratio Inversión / Retorno</span>
                                                </div>
                                                <span class="text-2xl font-black text-amber-500">{{ balanceData.metricas?.ratio_inventario }}%</span>
                                            </div>
                                            <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                <div class="h-full bg-amber-500 rounded-full transition-all duration-1000 ease-out" :style="{ width: balanceData.metricas?.ratio_inventario + '%' }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-white dark:bg-slate-900/40 border border-gray-100 dark:border-slate-800/60 rounded-[2rem] p-8 shadow-xl flex flex-col">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-10">Composición de Egresos</h4>
                                    <div class="flex-1 flex items-center justify-around">
                                        <div class="text-center group">
                                            <div class="w-16 h-16 rounded-2xl bg-rose-500/10 flex items-center justify-center text-rose-600 mb-4 mx-auto group-hover:rotate-12 transition-transform">
                                                <FontAwesomeIcon icon="box-archive" size="lg" />
                                            </div>
                                            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ formatCurrency(balanceData.balance?.compras_inventario) }}</div>
                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Inventario</div>
                                        </div>
                                        <div class="w-px h-24 bg-gradient-to-b from-transparent via-gray-200 dark:via-slate-800 to-transparent"></div>
                                        <div class="text-center group">
                                            <div class="w-16 h-16 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 mb-4 mx-auto group-hover:-rotate-12 transition-transform">
                                                <FontAwesomeIcon icon="gears" size="lg" />
                                            </div>
                                            <div class="text-2xl font-black text-slate-900 dark:text-white">{{ formatCurrency(balanceData.balance?.gastos_operativos) }}</div>
                                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Operación</div>
                                        </div>
                                    </div>
                                    <div class="mt-8 pt-8 border-t border-gray-100 dark:border-slate-800/60">
                                        <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            <span>Métrica de Riesgo</span>
                                            <span class="text-slate-900 dark:text-white">Estable</span>
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




