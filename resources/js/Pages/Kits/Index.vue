<template>
  <AppLayout title="Kits Premium">
    <div class="min-h-screen bg-[var(--ui-surface)] text-slate-900 dark:text-slate-100 font-sans selection:bg-brand-500 selection:text-white transition-colors duration-200">
      <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-12 py-10">
        
        <!-- Header -->
        <div class="mb-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
          <div>
            <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-brand-500 to-brand-600 dark:from-brand-400 dark:to-brand-500 tracking-tight">
              Kits de Productos
            </h1>
            <p class="mt-2 text-slate-500 dark:text-slate-400 text-lg">
              Gestiona los paquetes y configuraciones compuestas
            </p>
          </div>
          <Link href="/kits/create" 
            class="inline-flex items-center px-6 py-3 border border-transparent rounded-2xl shadow-xl shadow-brand-900/20 text-sm font-bold text-white bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-400 hover:to-brand-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 focus:ring-offset-slate-900 transition-all duration-200 transform hover:scale-105 active:scale-95">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Nuevo Kit
          </Link>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
          <!-- Total Kits -->
          <div class="bg-white dark:bg-slate-800/50 backdrop-blur-md overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:bg-slate-50 dark:hover:bg-slate-700/80 transition-all duration-200 group">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-brand-500/10 rounded-xl group-hover:bg-slate-500/20 transition-colors">
                  <svg class="h-8 w-8 text-brand-600 dark:text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Total Kits</dt>
                    <dd class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ stats.totalKits }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Activos -->
          <div class="bg-white dark:bg-slate-800/50 backdrop-blur-md overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:bg-slate-50 dark:hover:bg-slate-700/80 transition-all duration-200 group">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-brand-500/10 rounded-xl group-hover:bg-slate-500/20 transition-colors">
                  <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Kits Activos</dt>
                    <dd class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ stats.kitsActivos }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Valor Total -->
          <div class="bg-white dark:bg-slate-800/50 backdrop-blur-md overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700/50 shadow-xl hover:shadow-2xl hover:bg-slate-50 dark:hover:bg-slate-700/80 transition-all duration-200 group">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-brand-500/10 rounded-xl group-hover:bg-slate-500/20 transition-colors">
                  <svg class="h-8 w-8 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-slate-500 dark:text-slate-400 truncate">Valor Total</dt>
                    <dd class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ formatCurrency(stats.valorTotal) }}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700/50 mb-8 p-6">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex-1 max-w-xl relative">
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
              <input v-model="searchQuery" @input="debouncedSearch" type="text"
                     class="block w-full pl-11 pr-4 py-3 bg-[var(--ui-surface)] dark:bg-black/50 border border-slate-200 dark:border-slate-700 rounded-xl leading-5 text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all duration-200"
                     placeholder="Buscar por nombre, código o descripción...">
            </div>
            
            <button v-if="searchQuery" @click="clearSearch"
                    class="inline-flex items-center px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm text-sm font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-700 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 focus:ring-offset-slate-900 transition-colors">
              <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              Limpiar
            </button>
          </div>
        </div>

        <!-- Kits Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700/50 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
              <thead class="bg-slate-50 dark:bg-slate-800/50">
                <tr>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Kit</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Componentes</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Precio</th>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Estado</th>
                  <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Destacado</th>
                  <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                <tr v-if="loading">
                  <td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex justify-center">
                      <svg class="animate-spin h-8 w-8 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="kits.length === 0">
                  <td colspan="6" class="px-6 py-16 text-center">
                     <div class="flex flex-col items-center justify-center">
                        <div class="bg-slate-100 dark:bg-slate-700/50 p-4 rounded-full mb-3">
                           <svg class="h-8 w-8 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                           </svg>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 text-lg">No se encontraron kits</p>
                        <p class="text-slate-400 dark:text-slate-500 text-sm mt-1">Intenta con otra búsqueda o crea uno nuevo</p>
                     </div>
                  </td>
                </tr>
                <tr v-else v-for="kit in kits" :key="kit.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors duration-150 group">
                  <td class="px-6 py-4">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-12 w-12 bg-slate-100 dark:bg-slate-700 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700">
                        <img v-if="kit.imagen" :src="kit.imagen.startsWith('http') ? kit.imagen : `/storage/${kit.imagen}`" class="h-full w-full object-cover" />
                        <div v-else class="h-full w-full flex items-center justify-center bg-[var(--ui-surface)] dark:bg-slate-800 text-slate-400 dark:text-slate-500">
                           <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                           </svg>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-semibold text-slate-900 dark:text-white flex items-center">
                          {{ kit.nombre }}
                          <span v-if="kit.destacado" class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-xl text-[10px] font-bold bg-brand-500/10 text-brand-600 dark:text-brand-500 border border-brand-500/20 uppercase tracking-wide">
                            <svg class="w-2 h-2 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                            Destacado
                          </span>
                        </div>
                        <div class="text-xs text-brand-600 dark:text-brand-500/80 font-mono">{{ kit.codigo }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-brand-500/10 text-sky-800 dark:text-sky-200 dark:text-blue-400 border border-blue-500/20">
                      {{ kit.componentes_count }} items
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-emerald-600 dark:text-slate-400 font-semibold tracking-wide">
                    <div class="flex flex-col">
                      <span>{{ formatCurrency(kit.precio_venta) }}</span>
                      <span v-if="!kit.incluye_iva" class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mt-0.5">MÁS IVA</span>
                      <span v-else class="text-[9px] font-black text-blue-600 dark:text-blue-500/80 uppercase tracking-wide mt-0.5 italic">IVA INCLUIDO</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="[
                      'inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium border transition-colors',
                      kit.estado === 'activo' 
                        ? 'bg-emerald-50 dark:bg-emerald-900/20 dark:bg-brand-500/10 text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-slate-400 border-emerald-200 dark:border-emerald-800/30 dark:border-emerald-500/20' 
                        : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700'
                    ]">
                      <span :class="[
                        'w-1.5 h-1.5 rounded-full mr-1.5',
                         kit.estado === 'activo' ? 'bg-brand-500 dark:bg-emerald-400' : 'bg-slate-400'
                      ]"></span>
                      {{ kit.estado === 'activo' ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-center">
                    <button 
                      @click="toggleDestacado(kit.id)"
                      class="transition-all duration-200 transform hover:scale-125 focus:outline-none"
                      :class="kit.destacado ? 'text-brand-500 scale-110' : 'text-slate-400 dark:text-slate-500 hover:text-brand-500 dark:hover:text-amber-400'"
                      :title="kit.destacado ? 'Quitar de portada' : 'Mostrar en portada'"
                    >
                      <svg class="w-10 h-10 fill-current" viewBox="0 0 24 24">
                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                      </svg>
                    </button>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end space-x-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                      <button @click="viewKitDetails(kit.id)" class="text-slate-400 hover:text-brand-600 dark:hover:text-brand-400 transition-colors p-1" title="Ver detalles">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                      </button>
                      <Link :href="`/kits/${kit.id}/edit`" class="text-slate-400 hover:text-brand-600 dark:hover:text-blue-400 transition-colors p-1" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                      </Link>
                      <button @click="deleteKit(kit.id)" class="text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors p-1" title="Eliminar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="bg-white dark:bg-slate-800 px-4 py-4 flex items-center justify-between border-t border-slate-200 dark:border-slate-700/50 sm:px-6">
             <div class="flex-1 flex justify-between sm:hidden">
                <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                      class="relative inline-flex items-center px-4 py-2 border border-slate-200 dark:border-slate-700 text-sm font-medium rounded-xl text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-700 dark:hover:bg-slate-700 disabled:opacity-50">
                   Anterior
                </button>
                <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
                      class="ml-3 relative inline-flex items-center px-4 py-2 border border-slate-200 dark:border-slate-700 text-sm font-medium rounded-xl text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-700 dark:hover:bg-slate-700 disabled:opacity-50">
                   Siguiente
                </button>
             </div>
             <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                   <p class="text-sm text-slate-500 dark:text-slate-400">
                      Mostrando <span class="font-medium text-slate-900 dark:text-white">{{ pagination.from }}</span> a <span class="font-medium text-slate-900 dark:text-white">{{ pagination.to }}</span> de <span class="font-medium text-slate-900 dark:text-white">{{ pagination.total }}</span> resultados
                   </p>
                </div>
                <div>
                   <nav class="relative z-0 inline-flex rounded-xl shadow-sm -space-x-px" aria-label="Pagination">
                      <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50">
                         <span class="sr-only">Anterior</span>
                         <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                         </svg>
                      </button>
                      <button v-for="page in visiblePages" :key="page" @click="changePage(page)"
                            :class="[
                               'relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors',
                               page === pagination.current_page
                                  ? 'z-10 bg-brand-500/10 border-brand-500/50 text-brand-600 dark:text-brand-500'
                                  : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white'
                            ]">
                         {{ page }}
                      </button>
                      <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50">
                         <span class="sr-only">Siguiente</span>
                         <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                         </svg>
                      </button>
                   </nav>
                </div>
             </div>
          </div>
        </div>
      </div>

      <!-- Modal de Detalles del Kit -->
      <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto custom-scrollbar" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

          <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-2xl border border-slate-200 dark:border-slate-700 transform transition-all sm:my-8 sm:align-middle sm:w-full sm:max-w-2xl">
            <div class="bg-white dark:bg-slate-800 px-6 pt-6 pb-4">
              <div class="flex justify-between items-start mb-6 border-b border-slate-200 dark:border-slate-700 pb-4">
                <h3 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center">
                   <span class="p-2 bg-brand-500/10 rounded-xl mr-3">
                      <svg class="h-6 w-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                   </span>
                   Detalles del Kit
                </h3>
                <button @click="closeModal" class="text-slate-400 hover:text-brand-600 dark:hover:text-white transition-colors">
                  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>

              <div v-if="loadingDetails" class="flex justify-center py-12">
                <svg class="animate-spin h-10 w-10 text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>

              <div v-else-if="selectedKit" class="space-y-6">
                <!-- Información Básica -->
                <div class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 border border-slate-200 dark:border-slate-700/50">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Código</label>
                      <p class="mt-1 text-lg font-mono text-brand-600 dark:text-amber-400">{{ selectedKit.codigo }}</p>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Nombre</label>
                      <p class="mt-1 text-lg font-semibold text-slate-900 dark:text-white">{{ selectedKit.nombre }}</p>
                    </div>
                    <div class="md:col-span-2" v-if="selectedKit.descripcion">
                      <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Descripción</label>
                      <p class="mt-1 text-slate-700 dark:text-slate-200">{{ selectedKit.descripcion }}</p>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Precio de Venta</label>
                      <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-slate-400">{{ formatCurrency(selectedKit.precio_venta) }}</p>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide">Costo Estimado</label>
                      <p class="mt-1 text-lg font-semibold text-blue-600 dark:text-blue-400">{{ formatCurrency(selectedKit.costo_estimado) }}</p>
                    </div>
                  </div>
                </div>

                <!-- Componentes del Kit -->
                <div>
                  <h4 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Componentes ({{ selectedKit.kit_items?.length || 0 }})</h4>
                  <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/50">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                      <thead class="bg-slate-50 dark:bg-slate-800/50">
                        <tr>
                          <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Código</th>
                          <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Producto</th>
                          <th class="px-4 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Cant.</th>
                          <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">P. Unit.</th>
                          <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
                        <tr v-for="item in selectedKit.kit_items" :key="item.id">
                          <td class="px-4 py-3 text-sm font-mono text-brand-600 dark:text-brand-500/80">{{ item.producto?.codigo || 'N/A' }}</td>
                          <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">{{ item.producto?.nombre || 'Producto sin nombre' }}</td>
                          <td class="px-4 py-3 text-sm text-center text-slate-900 dark:text-white font-bold">{{ item.cantidad }}</td>
                          <td class="px-4 py-3 text-sm text-right text-slate-500 dark:text-slate-400">{{ formatCurrency(item.precio_unitario) }}</td>
                          <td class="px-4 py-3 text-sm text-right font-medium text-emerald-600 dark:text-slate-400">{{ formatCurrency(item.precio_unitario * item.cantidad) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Margen de Ganancia -->
                <div class="bg-gradient-to-r from-blue-50 dark:from-blue-900/40 to-blue-100 dark:to-blue-800/40 border border-sky-200 dark:border-sky-800/30 dark:border-blue-500/20 rounded-xl p-4">
                  <div class="flex justify-between items-center">
                    <div>
                      <h4 class="text-sm font-bold text-sky-800 dark:text-sky-200 dark:text-blue-300 uppercase tracking-wide">Margen de Ganancia</h4>
                      <p class="text-xs text-blue-600 dark:text-blue-400/70 mt-1">Calculado sobre precio sin IVA vs costo</p>
                    </div>
                    <div class="text-right">
                      <span class="text-2xl font-black text-sky-800 dark:text-sky-200 dark:text-blue-100">{{ calculateMargin(selectedKit) }}%</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-slate-50 dark:bg-black/50 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-slate-200 dark:border-slate-700">
              <Link :href="`/kits/${selectedKit?.id}/edit`" v-if="selectedKit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-xl px-4 py-2 bg-gradient-to-r from-brand-500 to-brand-600 text-base font-bold text-white hover:from-brand-400 hover:to-brand-500 transition-all duration-200 sm:ml-3 sm:w-auto sm:text-sm">
                Editar Kit
              </Link>
              <button @click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm px-4 py-2 bg-white dark:bg-slate-800 text-base font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-slate-900 dark:hover:text-white transition-all duration-200 sm:mt-0 sm:w-auto sm:text-sm">
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link, router, Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import Swal from '@/Utils/Swal'

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

// State
const kits = ref([])
const loading = ref(false)
const searchQuery = ref('')
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
})
const stats = ref({
  totalKits: 0,
  kitsActivos: 0,
  valorTotal: 0
})
const showModal = ref(false)
const selectedKit = ref(null)
const loadingDetails = ref(false)

// Computed (sin cambios)
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2
  const range = []
  const rangeWithDots = []

  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i)
  }

  if (current - delta > 2) {
    rangeWithDots.push(1, '...')
  } else {
    rangeWithDots.push(1)
  }

  rangeWithDots.push(...range)

  if (current + delta < last - 1) {
    rangeWithDots.push('...', last)
  } else if (last > 1) {
    rangeWithDots.push(last)
  }

  return rangeWithDots.filter(p => p !== '...' || rangeWithDots.indexOf(p) === rangeWithDots.lastIndexOf(p))
})

// Methods (sin cambios en la lógica, solo en la UI que ya fue cambiada arriba)
const fetchKits = async (page = 1) => {
  loading.value = true
  try {
    const params = new URLSearchParams({
      page: page,
      length: 10,
      search: searchQuery.value
    })

    const response = await fetch(`/kits/api/data?${params}`)
    const data = await response.json()

    kits.value = data.data
    pagination.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      from: data.from,
      to: data.to,
      total: data.recordsFiltered
    }

    updateStats(data)
  } catch (error) {
    console.error('Error fetching kits:', error)
    notyf.error('Error al cargar los kits')
  } finally {
    loading.value = false
  }
}

const updateStats = (data) => {
  if (data.stats) {
    stats.value.totalKits = data.stats.totalKits
    stats.value.kitsActivos = data.stats.kitsActivos
    stats.value.valorTotal = data.stats.valorTotal
  } else {
    stats.value.totalKits = data.recordsTotal
    stats.value.kitsActivos = kits.value.filter(k => k.estado === 'activo').length
    stats.value.valorTotal = kits.value.reduce((sum, k) => sum + parseFloat(k.precio_venta || 0), 0)
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    fetchKits(page)
  }
}

let searchTimeout
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchKits(1)
  }, 250)
}

const clearSearch = () => {
  searchQuery.value = ''
  fetchKits(1)
}

const deleteKit = async (kitId) => {
  const result = await Swal.fire({
    title: 'Eliminar kit',
    text: '¿Estás seguro de que deseas eliminar este kit?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#ef4444',
  })

  if (!result.isConfirmed) {
    return
  }

  try {
    const response = await fetch(`/kits/${kitId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      }
    })

    const data = await response.json()

    if (data.success) {
      notyf.success('Kit eliminado exitosamente')
      fetchKits(pagination.value.current_page)
    } else {
      notyf.error(data.message || 'Error al eliminar el kit')
    }
  } catch (error) {
    console.error('Error:', error)
    notyf.error('Error al eliminar el kit')
  }
}

const toggleDestacado = (id) => {
  router.put(route('kits.toggle-destacado', id), {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Visibilidad del kit actualizada')
      fetchKits(pagination.value.current_page)
    }
  })
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN'
  }).format(value || 0)
}

const viewKitDetails = async (kitId) => {
  showModal.value = true
  loadingDetails.value = true
  selectedKit.value = null

  try {
    const response = await fetch(`/kits/${kitId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    const data = await response.json()
    selectedKit.value = data
  } catch (error) {
    console.error('Error loading kit details:', error)
    notyf.error('Error al cargar los detalles del kit')
    closeModal()
  } finally {
    loadingDetails.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  selectedKit.value = null
}

const calculateMargin = (kit) => {
  if (!kit || !kit.precio_venta || !kit.costo_estimado) return 0
  const precioVentaSinIVA = kit.precio_venta / 1.16
  const margen = ((precioVentaSinIVA - kit.costo_estimado) / kit.costo_estimado) * 100
  return margen.toFixed(1)
}

onMounted(() => {
  fetchKits()
})
</script>

<style scoped>
/* Transiciones suaves */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>
