<template>
    <AppLayout title="Caja Chica">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 dark:text-gray-100 leading-tight">
                    Caja Chica
                </h2>
                <div class="flex flex-wrap gap-2">
                    <button @click="openModal('ingreso')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Nuevo Ingreso
                    </button>
                    <button @click="openModal('egreso')" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 px-4 rounded">
                        Nuevo Egreso
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-slate-950 dark:bg-gray-900">
            <div class="w-full mx-auto sm:px-6 lg:px-8">
                <!-- Barra de acciones -->
                <div class="flex flex-wrap justify-between items-center mb-4 gap-3">
                    <div class="flex items-center gap-3">
                        <div class="px-4 py-2 bg-white dark:bg-slate-900 dark:bg-gray-800 shadow-sm rounded-lg">
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Exportar</div>
                            <a :href="route('caja-chica.export', filters)" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-semibold">CSV</a>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white dark:bg-slate-900 dark:bg-gray-800 shadow-sm rounded-lg px-4 py-2">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Balance</div>
                            <div class="text-lg font-bold" :class="balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">${{ formatMoney(balance) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Ingreso</div>
                            <div class="text-lg font-bold text-blue-600 dark:text-blue-400">${{ formatMoney(totalIngresos) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 uppercase">Egreso</div>
                            <div class="text-lg font-bold text-red-600 dark:text-red-400">${{ formatMoney(totalEgresos) }}</div>
                        </div>
                        <div class="w-32 h-12">
                            <Sparkline :data="trend" />
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-1" for="search">
                                Buscar concepto
                            </label>
                            <input v-model="filters.q" id="search" type="text" placeholder="Ej. gasolina" @keyup.enter="applyFilters"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-1" for="tipoFiltro">
                                Tipo
                            </label>
                            <select v-model="filters.tipo" id="tipoFiltro" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600">
                                <option value="">Todos</option>
                                <option value="ingreso">Ingreso</option>
                                <option value="egreso">Egreso</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-1" for="categoriaFiltro">
                                Categoría
                            </label>
                            <input v-model="filters.categoria" id="categoriaFiltro" type="text" placeholder="Ej. Combustible"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-1" for="desde">
                                Desde
                            </label>
                            <input v-model="filters.desde" id="desde" type="date"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-1" for="hasta">
                                Hasta
                            </label>
                            <input v-model="filters.hasta" id="hasta" type="date"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 dark:bg-gray-700 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600">
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-between items-center gap-3 mt-4">
                        <div class="flex flex-wrap gap-2">
                            <span class="text-sm text-gray-600 dark:text-gray-300 dark:text-gray-400">Rangos rápidos:</span>
                            <button @click="quickDate('hoy')" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">Hoy</button>
                            <button @click="quickDate('semana')" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">Esta semana</button>
                            <button @click="quickDate('mes')" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 rounded">Este mes</button>
                        </div>
                        <div class="flex items-center gap-3">
                            <div>
                                <label class="block text-gray-700 dark:text-gray-300 text-xs font-bold mb-1" for="perPage">Por página</label>
                                <select v-model.number="filters.per_page" id="perPage" class="shadow appearance-none border rounded py-1.5 px-2 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600 dark:bg-gray-700">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button @click="clearFilters" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-9500 text-gray-700 dark:text-gray-200 font-semibold py-2 px-4 rounded">
                                    Limpiar
                                </button>
                                <button @click="applyFilters" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Aplicar filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium uppercase">Balance Actual</div>
                        <div class="text-3xl font-bold" :class="balance >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                            ${{ formatMoney(balance) }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium uppercase">Total Ingresos</div>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                            ${{ formatMoney(totalIngresos) }}
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <div class="text-gray-500 dark:text-gray-400 dark:text-gray-400 text-sm font-medium uppercase">Total Egresos</div>
                        <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                            ${{ formatMoney(totalEgresos) }}
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                        <thead class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                                    <button class="flex items-center gap-1" @click="toggleSort('fecha')">
                                        Fecha
                                        <span v-if="filters.sort_by === 'fecha'">
                                            {{ filters.sort_dir === 'asc' ? '▲' : '▼' }}
                                        </span>
                                    </button>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Concepto</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                                    <button class="flex items-center gap-1" @click="toggleSort('categoria')">
                                        Categoría
                                        <span v-if="filters.sort_by === 'categoria'">
                                            {{ filters.sort_dir === 'asc' ? '▲' : '▼' }}
                                        </span>
                                    </button>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                                    <button class="flex items-center gap-1" @click="toggleSort('monto')">
                                        Monto
                                        <span v-if="filters.sort_by === 'monto'">
                                            {{ filters.sort_dir === 'asc' ? '▲' : '▼' }}
                                        </span>
                                    </button>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Comprobantes</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">
                                    <button class="flex items-center gap-1" @click="toggleSort('usuario')">
                                        Usuario
                                        <span v-if="filters.sort_by === 'usuario'">
                                            {{ filters.sort_dir === 'asc' ? '▲' : '▼' }}
                                        </span>
                                    </button>
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 dark:bg-gray-800 divide-y divide-gray-200 dark:divide-slate-800 dark:divide-gray-700">
                            <tr v-for="movimiento in movimientos.data" :key="movimiento.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ formatDate(movimiento.fecha) }}
                                    <div class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">
                                        {{ new Date(movimiento.fecha).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' }) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-gray-100">
                                    <div class="font-semibold">{{ movimiento.concepto }}</div>
                                    <div v-if="movimiento.nota" class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1 whitespace-normal max-w-xs">
                                        {{ movimiento.nota }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-gray-100">
                                    {{ movimiento.categoria || '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="movimiento.tipo === 'ingreso' ? 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300'">
                                        {{ movimiento.tipo }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                    :class="movimiento.tipo === 'ingreso' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    ${{ formatMoney(movimiento.monto) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    <button
                                        v-if="(movimiento.adjuntos && movimiento.adjuntos.length) || movimiento.comprobante_url"
                                        @click="verComprobante(movimiento)"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 font-semibold"
                                    >
                                        Ver ({{ movimiento.adjuntos?.length || (movimiento.comprobante_url ? 1 : 0) }})
                                    </button>
                                    <span v-else class="text-gray-400 dark:text-gray-500 dark:text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    {{ movimiento.user?.name || 'Desconocido' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <!-- Editar -->
                                        <button @click="editModal(movimiento)" 
                                            class="inline-flex items-center justify-center w-8 h-8 bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/60 transition-colors"
                                            title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <!-- Eliminar -->
                                        <button @click="deleteMovimiento(movimiento)" 
                                            class="inline-flex items-center justify-center w-8 h-8 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/60 transition-colors"
                                            title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="movimientos.data.length === 0">
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                    No hay movimientos registrados.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="movimientos.data.length">
                            <tr class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
                                <td colspan="4" class="px-6 py-3 text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase">Totales página</td>
                                <td class="px-6 py-3 text-sm font-bold text-right text-gray-900 dark:text-white dark:text-gray-100">
                                    ${{ formatMoney(totalPagina.monto) }}
                                </td>
                                <td colspan="3"></td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-slate-950 dark:bg-gray-700/50">
                                <td colspan="4" class="px-6 py-3 text-xs font-semibold text-gray-600 dark:text-gray-300 dark:text-gray-300 uppercase">Ingresos / Egresos página</td>
                                <td class="px-6 py-3 text-sm font-bold text-right">
                                    <span class="text-green-600 dark:text-green-400">+${{ formatMoney(totalPagina.ingresos) }}</span>
                                    /
                                    <span class="text-red-600 dark:text-red-400">-${{ formatMoney(totalPagina.egresos) }}</span>
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-gray-700 bg-white dark:bg-slate-900 dark:bg-gray-800" v-if="movimientos.links.length > 3">
                        <div class="flex justify-between">
                            <template v-for="(link, key) in movimientos.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 dark:text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-600 rounded" v-html="link.label" />
                                <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-700 focus:border-amber-500 focus:text-amber-500 dark:focus:border-amber-400 dark:focus:text-amber-400" :class="{ 'bg-blue-700 text-white': link.active, 'dark:bg-blue-600 dark:text-white dark:border-blue-600': link.active }" :href="link.url" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal formulario -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6 bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white dark:text-gray-100 mb-4">
                    {{ form.id ? 'Editar Transaccion' : 'Nueva Transaccion' }}
                </h2>
                
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="concepto">
                        Concepto
                    </label>
                    <input v-model="form.concepto" id="concepto" type="text" autofocus class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600 dark:bg-gray-700">
                    <div v-if="form.errors.concepto" class="text-red-500 text-xs italic">{{ form.errors.concepto }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="categoria">
                        Categoría
                    </label>
                    <input v-model="form.categoria" id="categoria" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600 dark:bg-gray-700" placeholder="Ej. Combustible">
                    <div v-if="form.errors.categoria" class="text-red-500 text-xs italic">{{ form.errors.categoria }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="monto">
                        Monto
                    </label>
                    <input
                        v-model="form.monto"
                        id="monto"
                        type="number"
                        step="0.01"
                        min="0.01"
                        inputmode="decimal"
                        @blur="normalizarMonto"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600 dark:bg-gray-700"
                    >
                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mt-1">Ingresa el monto en positivo; el signo lo define el tipo.</p>
                    <div v-if="form.errors.monto" class="text-red-500 text-xs italic">{{ form.errors.monto }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="tipo">
                        Tipo
                    </label>
                    <select v-model="form.tipo" id="tipo" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600 dark:bg-gray-700">
                        <option value="ingreso">Ingreso</option>
                        <option value="egreso">Egreso</option>
                    </select>
                    <div v-if="form.errors.tipo" class="text-red-500 text-xs italic">{{ form.errors.tipo }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="fecha">
                        Fecha
                    </label>
                    <input v-model="form.fecha" id="fecha" type="date" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600 dark:bg-gray-700">
                    <div v-if="form.errors.fecha" class="text-red-500 text-xs italic">{{ form.errors.fecha }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="comprobante">
                        Comprobante (opcional)
                    </label>
                    <div v-if="form.id && adjuntosEnEdicion.length" class="mb-2 space-y-1">
                        <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200 font-semibold">Comprobantes actuales:</div>
                        <div class="space-y-1">
                            <div v-for="adj in adjuntosEnEdicion" :key="adj.id" class="flex items-center gap-2 text-sm">
                                <input type="checkbox" :value="adj.id" v-model="form.eliminar_adjuntos" class="dark:bg-gray-700 dark:border-gray-600">
                                <button type="button" @click="verAdjuntoDirecto(adj)" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline">Ver</button>
                                <span class="text-gray-600 dark:text-gray-300 dark:text-gray-300 truncate">{{ adj.path?.split('/').pop() || 'archivo' }}</span>
                            </div>
                        </div>
                        <p v-if="form.eliminar_adjuntos.length" class="text-xs text-red-600 dark:text-red-400">Se eliminarán los seleccionados.</p>
                    </div>
                    <div v-if="form.id && comprobanteSeleccionado?.comprobante_url && !form.eliminar_comprobante && !adjuntosEnEdicion.length" class="mb-2 flex items-center gap-3">
                        <button @click="verComprobante(comprobanteSeleccionado)" type="button" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline text-sm">Ver comprobante actual</button>
                        <button @click="form.eliminar_comprobante = true" type="button" class="text-red-600 dark:text-red-400 hover:text-red-800 text-sm font-semibold">Quitar comprobante</button>
                    </div>
                    <p v-if="form.eliminar_comprobante" class="text-xs text-red-600 dark:text-red-400 mb-2">Se eliminará el comprobante actual.</p>
                    <input @change="handleFilesChange" id="comprobante" type="file" multiple class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600 dark:bg-gray-700">
                    <div v-if="form.errors.comprobante" class="text-red-500 text-xs italic">{{ form.errors.comprobante }}</div>
                    <div v-if="form.errors['comprobantes.0']" class="text-red-500 text-xs italic">{{ form.errors['comprobantes.0'] }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="nota">
                        Nota
                    </label>
                    <textarea v-model="form.nota" id="nota" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-200 leading-tight focus:outline-none focus:shadow-outline dark:border-gray-600 dark:bg-gray-700" placeholder="Detalles adicionales"></textarea>
                    <div v-if="form.errors.nota" class="text-red-500 text-xs italic">{{ form.errors.nota }}</div>
                </div>

                <div class="flex justify-end mt-6">
                    <button @click="closeModal" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-9500 text-gray-700 dark:text-gray-200 font-bold py-2 px-4 rounded mr-2" :disabled="form.processing">
                        Cancelar
                    </button>
                    <button @click="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-75" :disabled="form.processing">
                        <span v-if="form.processing">Guardando...</span>
                        <span v-else>Guardar</span>
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal comprobante -->
        <Modal :show="showComprobanteModal" @close="cerrarComprobante">
            <div class="p-6 bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-lg">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white dark:text-gray-100 mb-4">
                    Comprobantes
                </h2>
                <div v-if="comprobanteSeleccionado && ((comprobanteSeleccionado.adjuntos && comprobanteSeleccionado.adjuntos.length) || comprobanteSeleccionado.comprobante_url)">
                    <div class="space-y-4">
                        <div v-for="adj in (comprobanteSeleccionado.adjuntos || [])" :key="adj.id">
                            <div v-if="esImagen(adj.url)" class="rounded-lg overflow-hidden border border-gray-200 dark:border-slate-800 dark:border-gray-700">
                                <img :src="adj.url" alt="Comprobante" class="w-full h-auto max-h-[70vh] object-contain">
                            </div>
                            <div v-else class="text-gray-700 dark:text-gray-200 text-sm mb-1">
                                Archivo: <a :href="adj.url" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline">{{ adj.path?.split('/').pop() }}</a>
                            </div>
                        </div>
                        <div v-if="(!comprobanteSeleccionado.adjuntos || comprobanteSeleccionado.adjuntos.length === 0) && comprobanteSeleccionado.comprobante_url">
                            <div v-if="esImagen(comprobanteSeleccionado.comprobante_url)" class="rounded-lg overflow-hidden border border-gray-200 dark:border-slate-800 dark:border-gray-700">
                                <img :src="comprobanteSeleccionado.comprobante_url" alt="Comprobante" class="w-full h-auto max-h-[70vh] object-contain">
                            </div>
                            <div v-else class="text-gray-700 dark:text-gray-200 text-sm mb-1">
                                Archivo: <a :href="comprobanteSeleccionado.comprobante_url" target="_blank" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 underline">Abrir</a>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        <button @click="cerrarComprobante" class="bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-9500 text-gray-700 dark:text-gray-200 font-bold py-2 px-4 rounded">
                            Cerrar
                        </button>
                    </div>
                </div>
                <div v-else class="text-gray-500 dark:text-gray-400 dark:text-gray-400">
                    Sin comprobante disponible.
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import Sparkline from '@/Components/Sparkline.vue';
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    movimientos: Object,
    balance: Number,
    totalIngresos: Number,
    totalEgresos: Number,
    filters: Object,
    trend: Array,
});

const showModal = ref(false);
const showComprobanteModal = ref(false);
const comprobanteSeleccionado = ref(null);
const adjuntosEnEdicion = ref([]);

// Referencia segura a movimientos para evitar errores si no viene la prop
const movimientos = computed(() => props.movimientos || { data: [], links: [] });

const form = useForm({
    id: null,
    concepto: '',
    categoria: '',
    monto: '',
    tipo: 'egreso',
    fecha: new Date().toISOString().substr(0, 10),
    comprobante: null,
    comprobantes: [],
    eliminar_comprobante: false,
    nota: '',
    eliminar_adjuntos: [],
});

const filters = ref({
    q: props.filters?.q ?? '',
    tipo: props.filters?.tipo ?? '',
    desde: props.filters?.desde ?? '',
    hasta: props.filters?.hasta ?? '',
    sort_by: props.filters?.sort_by ?? 'fecha',
    sort_dir: props.filters?.sort_dir ?? 'desc',
    per_page: props.filters?.per_page ?? 10,
});

const formatMoney = (value) => Number(value).toFixed(2);

const formatDate = (dateString) => {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};

const openModal = (tipo = 'egreso') => {
    form.reset();
    form.clearErrors();
    form.tipo = tipo;
    form.comprobantes = [];
    form.eliminar_adjuntos = [];
    form.eliminar_comprobante = false;
    adjuntosEnEdicion.value = [];
    showModal.value = true;
};

const editModal = (movimiento) => {
    form.clearErrors();
    form.id = movimiento.id;
    form.concepto = movimiento.concepto;
    form.categoria = movimiento.categoria || '';
    form.monto = movimiento.monto;
    form.tipo = movimiento.tipo;
    // Normaliza a YYYY-MM-DD por si viene con tiempo/zona
    form.fecha = movimiento.fecha ? movimiento.fecha.toString().slice(0, 10) : '';
    form.comprobante = null;
    form.comprobantes = [];
    form.eliminar_comprobante = false;
    form.nota = movimiento.nota || '';
    form.eliminar_adjuntos = [];
    adjuntosEnEdicion.value = movimiento.adjuntos || [];
    comprobanteSeleccionado.value = movimiento; // reutilizado para ver comprobante actual
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    comprobanteSeleccionado.value = null;
    adjuntosEnEdicion.value = [];
};

const handleFilesChange = (e) => {
    const files = Array.from(e.target.files || []);
    form.comprobantes = files;
    form.comprobante = files[0] || null;
    form.eliminar_comprobante = false;
    form.eliminar_adjuntos = [];
};

const normalizarMonto = () => {
    if (form.monto === '' || form.monto === null || form.monto === undefined) return;
    const valor = Math.abs(parseFloat(form.monto) || 0);
    form.monto = valor ? valor.toFixed(2) : '';
};

const submit = () => {
    normalizarMonto();
    if (form.id) {
        form.put(route('caja-chica.update', form.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('caja-chica.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteMovimiento = (movimiento) => {
    if (confirm('Estas seguro de que quieres eliminar este movimiento?')) {
        form.delete(route('caja-chica.destroy', movimiento.id));
    }
};

const applyFilters = () => {
    router.get(route('caja-chica.index'), filters.value, {
        preserveState: true,
        replace: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filters.value = { q: '', tipo: '', desde: '', hasta: '', sort_by: 'fecha', sort_dir: 'desc', categoria: '', per_page: 10 };
    applyFilters();
};

const verComprobante = (movimiento) => {
    if (!movimiento.comprobante_url && (!movimiento.adjuntos || movimiento.adjuntos.length === 0)) return;
    comprobanteSeleccionado.value = movimiento;
    showComprobanteModal.value = true;
};

const cerrarComprobante = () => {
    showComprobanteModal.value = false;
    comprobanteSeleccionado.value = null;
};

const esImagen = (url) => {
    if (!url) return false;
    const limpio = url.split('?')[0].toLowerCase();
    return /\.(jpg|jpeg|png|gif|webp|bmp|svg)$/.test(limpio);
};

const verAdjuntoDirecto = (adjunto) => {
    if (adjunto?.url) {
        window.open(adjunto.url, '_blank');
    }
};

const toggleSort = (field) => {
    if (filters.value.sort_by === field) {
        filters.value.sort_dir = filters.value.sort_dir === 'asc' ? 'desc' : 'asc';
    } else {
        filters.value.sort_by = field;
        filters.value.sort_dir = 'desc';
    }
    applyFilters();
};

const quickDate = (range) => {
    const today = new Date();
    const format = (d) => d.toISOString().slice(0, 10);
    let desde = '';
    let hasta = format(today);

    if (range === 'hoy') {
        desde = hasta;
    } else if (range === 'semana') {
        const first = new Date(today);
        first.setDate(today.getDate() - 6);
        desde = format(first);
    } else if (range === 'mes') {
        const first = new Date(today.getFullYear(), today.getMonth(), 1);
        desde = format(first);
    }

    filters.value.desde = desde;
    filters.value.hasta = hasta;
    applyFilters();
};

const totalPagina = computed(() => {
    const datos = movimientos.value?.data || [];
    const ingresos = datos.filter(m => m?.tipo === 'ingreso').reduce((acc, m) => acc + Number(m?.monto || 0), 0);
    const egresos = datos.filter(m => m?.tipo === 'egreso').reduce((acc, m) => acc + Number(m?.monto || 0), 0);
    return {
        ingresos,
        egresos,
        monto: ingresos - egresos,
    };
});
</script>




