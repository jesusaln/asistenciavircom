<template>
  <Head title="Citas" />
  <div class="citas-index min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors">
    <div class="w-full px-4 lg:px-8 py-8">
      <!-- Header específico de citas -->
      <CitasHeader
        :total="estadisticas.total"
        :pendientes="estadisticas.pendientes"
        :enProceso="estadisticas.enProceso"
        :completadas="estadisticas.completadas"
        :canceladas="estadisticas.canceladas"
        v-model:search-term="searchTerm"
        v-model:sort-by="sortBy"
        v-model:filtro-estado-cita="filtroEstadoCita"
        @crear-nueva="crearNuevaCita"
        @search-change="handleSearchChange"
        @filtro-estado-cita-change="handleEstadoCitaChange"
        @sort-change="handleSortChange"
        @limpiar-filtros="limpiarFiltros"
        v-model:view-mode="viewMode"
      />

      <!-- Tabla -->
      <div v-if="viewMode === 'table'" class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
            <thead class="bg-gray-50/50 dark:bg-gray-900/50 transition-colors">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cita</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Técnico</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reporte / Fotos</th>
                <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-50 dark:divide-gray-700 transition-colors">
              <tr v-for="cita in citasDocumentos" :key="cita.id" class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ formatearFecha(cita.raw.fecha_hora) }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">{{ formatearHora(cita.raw.fecha_hora) }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ cita.titulo }}</div>
                  <div class="flex items-center gap-1 mt-1">
                    <span class="w-1.5 h-1.5 rounded-full" :class="cita.raw.estado === 'completado' ? 'bg-green-500' : 'bg-amber-500'"></span>
                    <span class="text-[10px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-tighter">{{ cita.raw.tipo_servicio || 'SERVICIO' }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ cita.subtitulo }}</div>
                  <div class="text-[10px] text-gray-400 dark:text-gray-500 truncate max-w-[150px]">{{ cita.raw.cliente?.direccion || 'Sin dirección' }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-[10px] font-bold text-blue-600 dark:text-blue-400">
                      {{ getInitials(cita.raw.tecnico?.name) }}
                    </div>
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ cita.raw.tecnico?.name || 'N/A' }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <span :class="obtenerEstadoCitaClase(cita.raw.estado)" class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border">
                    {{ obtenerEstadoCitaLabel(cita.raw.estado) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div v-if="cita.raw.fotos_finales?.length > 0" 
                       @click="openGallery(cita.raw.fotos_finales, `Evidencias - Cita #${cita.id}`)"
                       class="cursor-pointer group flex flex-col items-center gap-1">
                    
                    <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-800 dark:group-hover:text-indigo-300 uppercase tracking-wide flex items-center gap-1 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded-full border border-indigo-100 dark:border-indigo-800/50 transition-colors">
                       📸 Fotos #{{ cita.id }}
                    </span>

                    <div class="flex -space-x-2 overflow-hidden justify-center hover:space-x-1 transition-all mt-1">
                      <img 
                        v-for="(foto, idx) in cita.raw.fotos_finales.slice(0, 3)" 
                        :key="idx"
                        :src="'/storage/' + foto" 
                        class="inline-block h-8 w-8 rounded-lg ring-2 ring-white dark:ring-gray-800 object-cover shadow-sm bg-gray-100 dark:bg-gray-700 group-hover:ring-indigo-200 transition-all font-bold"
                        :title="cita.raw.trabajo_realizado || 'Ver evidencias'"
                      >
                      <div v-if="cita.raw.fotos_finales.length > 3" class="flex items-center justify-center h-8 w-8 rounded-lg ring-2 ring-white dark:ring-gray-800 bg-gray-100 dark:bg-gray-700 text-[10px] font-bold text-gray-500 dark:text-gray-400 shadow-sm group-hover:bg-indigo-50 dark:group-hover:bg-indigo-900/50 transition-all">
                        +{{ cita.raw.fotos_finales.length - 3 }}
                      </div>
                    </div>
                  </div>
                  <div v-else-if="cita.raw.trabajo_realizado" class="text-[10px] font-bold text-gray-400 dark:text-gray-500 italic text-center">
                    Reporte sin fotos
                  </div>
                  <div v-else class="text-xs text-gray-300 dark:text-gray-600 text-center">—</div>
                </td>
                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end space-x-2">
                    <button @click="verDetalles(cita)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/60 transition-all shadow-sm" title="Ver detalles">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button @click="editarCita(cita.id)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/60 transition-all shadow-sm" title="Editar">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                    <button @click="confirmarEliminacion(cita.id)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-50 dark:bg-red-900/40 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/60 transition-all shadow-sm" title="Eliminar">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="citasDocumentos.length === 0">
                <td colspan="7" class="px-6 py-20 text-center">
                  <div class="flex flex-col items-center space-y-4">
                    <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700/50 rounded-full flex items-center justify-center transition-colors">
                      <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </div>
                    <div class="space-y-1">
                      <p class="text-gray-700 dark:text-gray-300 font-bold text-lg">No hay citas registradas</p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">Intenta ajustar los filtros de búsqueda</p>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="paginationData.lastPage > 1" class="bg-gray-50/50 dark:bg-gray-900/30 border-t border-gray-100 dark:border-gray-700 px-6 py-4 transition-colors">
          <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
              <p class="text-sm text-gray-600 dark:text-gray-400 transition-colors">
                Mostrando <span class="font-bold text-gray-900 dark:text-white">{{ paginationData.from }}</span> a <span class="font-bold text-gray-900 dark:text-white">{{ paginationData.to }}</span> de <span class="font-bold text-gray-900 dark:text-white">{{ paginationData.total }}</span> resultados
              </p>
              <select
                :value="paginationData.perPage"
                @change="handlePerPageChange(parseInt($event.target.value))"
                class="border border-gray-200 dark:border-gray-700 rounded-lg text-sm font-bold py-1.5 px-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500/50 transition-all outline-none"
              >
                <option value="10">10 por página</option>
                <option value="15">15 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
              </select>
            </div>

            <nav class="flex items-center gap-2">
              <button
                v-if="paginationData.prevPageUrl"
                @click="handlePageChange(paginationData.currentPage - 1)"
                class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              </button>

              <div class="flex items-center gap-1">
                <button
                  v-for="page in [paginationData.currentPage - 1, paginationData.currentPage, paginationData.currentPage + 1].filter(p => p > 0 && p <= paginationData.lastPage)"
                  :key="page"
                  @click="handlePageChange(page)"
                  :class="page === paginationData.currentPage ? 'bg-blue-600 text-white border-blue-600 shadow-blue-200 dark:shadow-none' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700'"
                  class="w-9 h-9 flex items-center justify-center rounded-xl border text-sm font-bold transition-all shadow-sm"
                >
                  {{ page }}
                </button>
              </div>

              <button
                v-if="paginationData.nextPageUrl"
                @click="handlePageChange(paginationData.currentPage + 1)"
                class="w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </nav>
          </div>
        </div>
      </div>

      <!-- Calendario -->
      <div v-else class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/50 transition-colors">
          <div class="flex items-center gap-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-white capitalize transition-colors">{{ monthYearLabel }}</h2>
            <div class="flex gap-2">
              <button @click="changeMonth(-1)" class="w-9 h-9 flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-all shadow-sm">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
              </button>
              <button @click="currentMonth = new Date()" class="px-4 py-1.5 text-xs font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-all border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                Hoy
              </button>
              <button @click="changeMonth(1)" class="w-9 h-9 flex items-center justify-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-all shadow-sm">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </button>
            </div>
          </div>
          <div class="flex gap-4 text-[10px] font-bold uppercase tracking-wider">
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 rounded-full bg-yellow-500 shadow-sm shadow-yellow-200 dark:shadow-none"></div>
              <span class="text-gray-500 dark:text-gray-400 transition-colors">Pendiente</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 rounded-full bg-indigo-500 shadow-sm shadow-indigo-200 dark:shadow-none"></div>
              <span class="text-gray-500 dark:text-gray-400 transition-colors">Proceso</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-2.5 h-2.5 rounded-full bg-green-500 shadow-sm shadow-green-200 dark:shadow-none"></div>
              <span class="text-gray-500 dark:text-gray-400 transition-colors">Hecho</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-700 transition-colors">
          <div v-for="day in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']" :key="day" class="py-4 text-center text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-gray-500 bg-white dark:bg-gray-800 transition-colors">
            {{ day }}
          </div>
        </div>

        <div class="grid grid-cols-7 auto-rows-[130px]">
          <div v-for="(day, idx) in daysInMonth" :key="idx" 
               :class="['border-r border-b border-gray-100 dark:border-gray-700 p-2 transition-all relative group', 
                        day.month === 'current' ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/50 dark:bg-gray-900/30 opacity-60']">
            <div class="flex justify-between items-start mb-2">
              <span :class="['text-xs font-bold w-7 h-7 flex items-center justify-center rounded-full transition-colors', 
                             isToday(day.date) ? 'bg-blue-600 text-white shadow-lg shadow-blue-200 dark:shadow-none' : 'text-gray-400 dark:text-gray-500']">
                {{ day.day }}
              </span>
            </div>
            
            <div class="space-y-1 overflow-y-auto max-h-[85px] custom-scrollbar pr-1">
              <div v-for="cita in getCitasForDay(day.date)" :key="cita.id"
                   @click="verDetalles({ raw: cita, titulo: `Cita #${cita.id}` })"
                   :class="['p-1.5 rounded-lg text-[9px] font-bold cursor-pointer truncate transition-all hover:scale-[1.02] shadow-sm active:scale-95',
                            obtenerEstadoCitaClase(cita.raw?.estado || cita.estado),
                            (cita.raw?.estado || cita.estado) === 'pendiente' ? 'border-l-2 border-yellow-500' : 
                            (cita.raw?.estado || cita.estado) === 'en_proceso' ? 'border-l-2 border-indigo-500' : 
                            (cita.raw?.estado || cita.estado) === 'completado' ? 'border-l-2 border-green-500' : '']"
                   :title="`${cita.raw?.cliente?.nombre_razon_social || cita.cliente?.nombre_razon_social} - ${cita.raw?.problema_reportado || cita.problema_reportado}`">
                <span class="opacity-70">{{ formatearHora(cita.raw?.fecha_hora || cita.fecha_hora) }}</span> {{ cita.raw?.cliente?.nombre_razon_social || cita.cliente?.nombre_razon_social }}
              </div>
            </div>
          </div>
        </div>
      </div>


      <!-- Modal mejorado -->
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 transition-all" @click.self="showModal = false">
          <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col transition-colors border border-gray-100 dark:border-gray-700">
            <!-- Header del modal -->
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 transition-colors">
              <div class="flex items-center gap-3">
                <div :class="[modalMode === 'details' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400']" class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors">
                  <svg v-if="modalMode === 'details'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white transition-colors">
                    {{ modalMode === 'details' ? 'Detalles de la Cita' : 'Confirmar Eliminación' }}
                  </h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Cita #{{ selectedCita?.id || selectedId }}</p>
                </div>
              </div>
              <button @click="showModal = false" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-white dark:bg-gray-800 transition-colors">
              <div v-if="modalMode === 'details' && selectedCita">
                <div class="space-y-6">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                      <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Cliente</label>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700/50 transition-colors">
                          <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-xs">
                            {{ getInitials(selectedCita.cliente?.nombre_razon_social) }}
                          </div>
                          <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ selectedCita.cliente?.nombre_razon_social || 'N/A' }}</p>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate max-w-[150px]">{{ selectedCita.cliente?.email || 'Sin correo' }}</p>
                          </div>
                        </div>
                      </div>
                      <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Técnico Asignado</label>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700/50 transition-colors">
                          <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-xs">
                            {{ getInitials(selectedCita.tecnico?.name) }}
                          </div>
                          <p class="text-sm font-bold text-gray-900 dark:text-white">{{ selectedCita.tecnico?.name || 'N/A' }}</p>
                        </div>
                      </div>
                    </div>
                    <div class="space-y-4">
                      <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Programación</label>
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700/50 transition-colors space-y-2">
                          <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Fecha:</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ formatearFecha(selectedCita.fecha_hora) }}</span>
                          </div>
                          <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Hora:</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ formatearHora(selectedCita.fecha_hora) }}</span>
                          </div>
                        </div>
                      </div>
                      <div>
                        <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Información Extra</label>
                        <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700/50 transition-colors space-y-2">
                           <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Servicio:</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg uppercase transition-colors">{{ selectedCita.tipo_servicio || 'N/A' }}</span>
                          </div>
                          <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Estado:</span>
                            <span :class="obtenerEstadoCitaClase(selectedCita.estado)" class="text-[10px] font-bold px-2 py-0.5 rounded-lg border uppercase transition-colors">
                              {{ obtenerEstadoCitaLabel(selectedCita.estado) }}
                            </span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="selectedCita.descripcion">
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Descripción de la Cita</label>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-2xl border border-gray-100 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 transition-colors italic leading-relaxed">
                      "{{ selectedCita.descripcion }}"
                    </div>
                  </div>

                  <div v-if="selectedCita.problema_reportado">
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Problema Reportado</label>
                    <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-900/30 text-sm text-amber-700 dark:text-amber-400 transition-colors font-medium">
                      {{ selectedCita.problema_reportado }}
                    </div>
                  </div>

                  <!-- Reporte de técnico (Cierre) -->
                  <div v-if="selectedCita.trabajo_realizado || selectedCita.fotos_finales" class="pt-6 border-t border-gray-100 dark:border-gray-700 transition-colors">
                    <h4 class="text-md font-black text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                      <span class="text-green-500">✨</span> Reporte Final del Técnico
                    </h4>
                    
                    <div v-if="selectedCita.trabajo_realizado" class="mb-5">
                      <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Trabajo Realizado</label>
                      <div class="p-4 bg-green-50/50 dark:bg-green-900/10 border border-green-100 dark:border-green-900/30 rounded-2xl text-sm text-gray-900 dark:text-gray-200 transition-colors italic leading-relaxed">
                        "{{ selectedCita.trabajo_realizado }}"
                      </div>
                    </div>

                    <div v-if="selectedCita.fotos_finales?.length > 0">
                      <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2.5">Evidencias Fotográficas ({{ selectedCita.fotos_finales.length }})</label>
                      <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div v-for="(foto, idx) in selectedCita.fotos_finales" :key="idx" class="aspect-square rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 group cursor-pointer hover:shadow-xl hover:scale-[1.02] transition-all">
                          <a :href="'/storage/' + foto" target="_blank" class="block w-full h-full" @click.prevent="openGallery([foto], 'Evidencia')">
                            <img :src="'/storage/' + foto" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" alt="Evidencia final">
                            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="modalMode === 'confirm'" class="py-8">
                <div class="text-center">
                  <div class="w-20 h-20 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-6 transition-colors ring-4 ring-red-50 dark:ring-red-900/20">
                    <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                  </div>
                  <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2 transition-colors">¿Eliminar Cita?</h3>
                  <p class="text-gray-500 dark:text-gray-400 max-w-sm mx-auto transition-colors">
                    ¿Estás seguro de que deseas eliminar la cita <strong>#{{ selectedCita?.id || selectedId }}</strong>? Esta acción es irreversible.
                  </p>
                </div>
              </div>
            </div>

            <!-- Footer del modal -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 transition-colors">
              <button @click="showModal = false" class="px-6 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-all active:scale-95">
                {{ modalMode === 'details' ? 'Cerrar' : 'Cancelar' }}
              </button>
              <div v-if="modalMode === 'details'">
                <button @click="editarCita(selectedCita.id)" class="px-6 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-amber-200 dark:shadow-none active:scale-95">
                  Editar Cita
                </button>
              </div>
              <button v-if="modalMode === 'confirm'" @click="eliminarCita" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-red-200 dark:shadow-none active:scale-95">
                Eliminar Permanente
              </button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Nuevo Modal de Galería de Fotos -->
      <Transition name="modal">
        <div v-if="showGalleryModal" class="fixed inset-0 bg-black/95 z-[60] flex flex-col" @click.self="closeGallery">
          <!-- Toolbar -->
          <div class="flex justify-between items-center p-6 text-white bg-gradient-to-b from-black/80 to-transparent">
             <div class="flex items-center gap-4">
               <div>
                 <p class="text-sm font-bold uppercase tracking-widest text-white/70">{{ imageTitle }}</p>
                 <p class="text-[10px] text-white/50">Imagen {{ currentImageIndex + 1 }} de {{ galleryImages.length }}</p>
               </div>
             </div>
             <button @click="closeGallery" class="w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-full transition-all backdrop-blur-md">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
             </button>
          </div>

          <!-- Main Image Area -->
          <div class="flex-1 flex items-center justify-center relative p-4">
             <button v-if="galleryImages.length > 1" @click.stop="prevImage" class="absolute left-8 w-14 h-14 flex items-center justify-center bg-black/50 hover:bg-indigo-600 text-white rounded-full backdrop-blur-md transition-all hover:scale-110 shadow-2xl z-10 group">
                <svg class="w-8 h-8 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
             </button>
             
             <img :src="galleryImages[currentImageIndex]" class="max-h-[85vh] max-w-[90vw] object-contain rounded-2xl shadow-[0_0_80px_rgba(0,0,0,0.5)] transition-all duration-500 animate-in fade-in zoom-in-95" :key="currentImageIndex">

             <button v-if="galleryImages.length > 1" @click.stop="nextImage" class="absolute right-8 w-14 h-14 flex items-center justify-center bg-black/50 hover:bg-indigo-600 text-white rounded-full backdrop-blur-md transition-all hover:scale-110 shadow-2xl z-10 group">
                <svg class="w-8 h-8 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
             </button>
          </div>

          <!-- Thumbnails Strip -->
          <div v-if="galleryImages.length > 1" class="p-8 bg-gradient-to-t from-black/80 to-transparent flex justify-center gap-4">
             <button 
               v-for="(img, idx) in galleryImages" 
               :key="idx" 
               @click.stop="currentImageIndex = idx"
               :class="['w-20 h-20 rounded-2xl overflow-hidden border-4 transition-all shadow-xl', currentImageIndex === idx ? 'border-indigo-500 scale-110 ring-4 ring-indigo-500/20' : 'border-transparent opacity-40 hover:opacity-100 hover:scale-105']"
             >
               <img :src="img" class="w-full h-full object-cover">
             </button>
          </div>
        </div>
      </Transition>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'

import CitasHeader from '@/Components/IndexComponents/CitasHeader.vue'

defineOptions({ layout: AppLayout })

// Colores e Iniciales
const getInitials = (name) => {
  if (!name) return '?'
  const parts = name.split(' ')
  if (parts.length > 1) return (parts[0][0] + parts[1][0]).toUpperCase()
  return parts[0][0].toUpperCase()
}

// Notificaciones
const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false },
    { type: 'warning', background: '#f59e0b', icon: false }
  ]
})

const page = usePage()

// Props
const props = defineProps({
  citas: { type: [Object, Array], required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'created_at', sort_direction: 'desc' }) },
  pagination: { type: Object, default: () => ({}) },
})

// Estado UI
const showModal = ref(false)
const modalMode = ref('details')
const selectedCita = ref(null)
const selectedId = ref(null)
const viewMode = ref('table') // 'table' or 'calendar'
const currentMonth = ref(new Date())

// Galería de imágenes
const showGalleryModal = ref(false)
const galleryImages = ref([])
const currentImageIndex = ref(0)
const imageTitle = ref('')

// Estado reactivo para Modo Oscuro
const isDark = ref(false)
let observer = null

onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) notyf.success(flash.success)
  if (flash?.error) notyf.error(flash.error)

  window.addEventListener('keydown', handleKeydown)

  // Observer para modo oscuro
  isDark.value = document.documentElement.classList.contains('dark')
  observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === 'class') {
        isDark.value = document.documentElement.classList.contains('dark')
      }
    })
  })
  observer.observe(document.documentElement, { attributes: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown)
  if (observer) observer.disconnect()
})


const openGallery = (images, title = 'Galería') => {
  if (!images || images.length === 0) return
  galleryImages.value = images.map(img => img.startsWith('/') ? img : '/storage/' + img)
  currentImageIndex.value = 0
  imageTitle.value = title
  showGalleryModal.value = true
}

const closeGallery = () => {
  showGalleryModal.value = false
  galleryImages.value = []
}

const nextImage = () => {
  currentImageIndex.value = (currentImageIndex.value + 1) % galleryImages.value.length
}

const prevImage = () => {
  currentImageIndex.value = (currentImageIndex.value - 1 + galleryImages.value.length) % galleryImages.value.length
}

// Navegación con teclado para la galería
const handleKeydown = (e) => {
  if (!showGalleryModal.value) {
    if (e.key === 'Escape' && showModal.value) showModal.value = false
    return
  }
  if (e.key === 'Escape') closeGallery()
  if (e.key === 'ArrowRight') nextImage()
  if (e.key === 'ArrowLeft') prevImage()
}

// Filtros
const searchTerm = ref(props.filters?.search ?? '')
const sortBy = ref('created_at-desc')
const filtroEstadoCita = ref('')

// Paginación
const perPage = ref(10)

// Función para crear nueva cita
const crearNuevaCita = () => {
  router.visit(route('citas.create'))
}

// Función para limpiar filtros
const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'created_at-desc'
  filtroEstadoCita.value = ''
  router.visit(route('citas.index'))
  notyf.success('Filtros limpiados correctamente')
}

// Datos
const citasPaginator = computed(() => props.citas)
const citasData = computed(() => citasPaginator.value?.data || [])


// Estadísticas
const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  pendientes: props.stats?.pendientes ?? 0,
  enProceso: props.stats?.en_proceso ?? 0,
  completadas: props.stats?.completadas ?? 0,
  canceladas: props.stats?.canceladas ?? 0
}))

// Transformación de datos
const citasDocumentos = computed(() => {
  let citas = [...citasData.value];

  // Ordenar por estado: En proceso -> Programado -> Pendientes -> Reprogramado -> Completadas -> Canceladas
  const ordenEstados = {
    'en_proceso': 1,
    'programado': 2,
    'pendiente': 3,
    'reprogramado': 4,
    'completado': 5,
    'cancelado': 6
  };

  citas.sort((a, b) => {
    const estadoA = ordenEstados[a.estado] || 999;
    const estadoB = ordenEstados[b.estado] || 999;

    if (estadoA !== estadoB) return estadoA - estadoB;

    const fechaA = new Date(a.fecha_hora);
    const fechaB = new Date(b.fecha_hora);
    return fechaA - fechaB;
  });

  return citas.map(c => ({
    id: c.id,
    titulo: `Cita #${c.id}`,
    subtitulo: (c.cliente?.nombre_razon_social || 'Cliente no disponible').toUpperCase(),
    estado: c.activo ? 'activo' : 'inactivo',
    extra: `Técnico: ${c.tecnico?.name || 'N/A'} | Estado: ${c.estado}`,
    fecha: c.created_at,
    raw: c
  }))
})

// Handlers
function handleSearchChange(newSearch) {
  searchTerm.value = newSearch
  router.get(route('citas.index'), {
    search: newSearch,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstadoCita.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleEstadoCitaChange(newEstadoCita) {
  filtroEstadoCita.value = newEstadoCita
  router.get(route('citas.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: newEstadoCita,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

function handleSortChange(newSort) {
  sortBy.value = newSort
  router.get(route('citas.index'), {
    search: searchTerm.value,
    sort_by: newSort.split('-')[0],
    sort_direction: newSort.split('-')[1] || 'desc',
    estado: filtroEstadoCita.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const verDetalles = (doc) => {
  selectedCita.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarCita = (id) => {
  router.visit(route('citas.edit', id))
}

const confirmarEliminacion = (id) => {
  selectedId.value = id
  modalMode.value = 'confirm'
  showModal.value = true
}

const eliminarCita = () => {
  router.delete(route('citas.destroy', selectedId.value), {
    preserveScroll: true,
    onSuccess: () => {
      notyf.success('Cita eliminada correctamente')
      showModal.value = false
      selectedId.value = null
      router.reload()
    },
    onError: () => {
      notyf.error('No se pudo eliminar la cita')
    }
  })
}

// Paginación
const paginationData = computed(() => {
  const p = citasPaginator.value || {}
  return {
    currentPage: props.pagination?.current_page || p.current_page || 1,
    lastPage:    props.pagination?.last_page || p.last_page || 1,
    perPage:     props.pagination?.per_page || p.per_page || 10,
    from:        props.pagination?.from || p.from || 0,
    to:          props.pagination?.to || p.to || 0,
    total:       props.pagination?.total || p.total || 0,
    prevPageUrl: p.prev_page_url ?? null,
    nextPageUrl: p.next_page_url ?? null,
    links:       p.links ?? []
  }
})


const handlePerPageChange = (newPerPage) => {
  perPage.value = newPerPage
  router.get(route('citas.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstadoCita.value,
    per_page: perPage.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const handlePageChange = (newPage) => {
  router.get(route('citas.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1] || 'desc',
    estado: filtroEstadoCita.value,
    per_page: perPage.value,
    page: newPage
  }, { preserveState: true, preserveScroll: true })
}


// Helpers
const formatearFecha = (date) => {
  if (!date) return '—'
  try {
    const d = new Date(date)
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch { return '—' }
}

const formatearHora = (date) => {
  if (!date) return '—'
  try {
    const d = new Date(date)
    return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
  } catch { return '—' }
}


const obtenerEstadoCitaClase = (estado) => {
  const clases = {
    'pendiente': 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-yellow-100 dark:border-yellow-900/30',
    'programado': 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-100 dark:border-blue-900/30',
    'en_proceso': 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/30',
    'completado': 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-100 dark:border-green-900/30',
    'cancelado': 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-100 dark:border-red-900/30',
    'reprogramado': 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 border-purple-100 dark:border-purple-900/30'
  }
  return clases[estado] || 'bg-gray-50 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border-gray-100 dark:border-gray-900/30'
}

const obtenerEstadoCitaLabel = (estado) => {
  const labels = {
    'pendiente': 'Pendiente',
    'programado': 'Programado',
    'en_proceso': 'En Proceso',
    'completado': 'Completado',
    'cancelado': 'Cancelado',
    'reprogramado': 'Reprogramado'
  }
  return labels[estado] || 'Desconocido'
}

// Lógica de Calendario
const daysInMonth = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  const date = new Date(year, month, 1)
  const days = []
  
  const firstDayOfWeek = date.getDay()
  const prevMonthLastDay = new Date(year, month, 0).getDate()
  for (let i = firstDayOfWeek - 1; i >= 0; i--) {
    days.push({ day: prevMonthLastDay - i, month: 'prev', date: new Date(year, month - 1, prevMonthLastDay - i) })
  }

  const lastDay = new Date(year, month + 1, 0).getDate()
  for (let i = 1; i <= lastDay; i++) {
    days.push({ day: i, month: 'current', date: new Date(year, month, i) })
  }

  const remainingCells = 42 - days.length
  for (let i = 1; i <= remainingCells; i++) {
    days.push({ day: i, month: 'next', date: new Date(year, month + 1, i) })
  }

  return days
})

const monthYearLabel = computed(() => currentMonth.value.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' }))

const changeMonth = (offset) => {
  currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + offset, 1)
}

const getCitasForDay = (date) => {
  const dateStr = date.toISOString().split('T')[0]
  return citasData.value.filter(c => {
    const citaDate = new Date(c.fecha_hora).toISOString().split('T')[0]
    return citaDate === dateStr
  })
}

const isToday = (date) => {
  const today = new Date()
  return date.getDate() === today.getDate() && 
         date.getMonth() === today.getMonth() && 
         date.getFullYear() === today.getFullYear()
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }

/* Animaciones suaves para la galería */
@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes zoom-in { from { transform: scale(0.95); } to { transform: scale(1); } }
.animate-in { animation: fade-in 0.3s ease-out, zoom-in 0.3s ease-out; }
</style>
