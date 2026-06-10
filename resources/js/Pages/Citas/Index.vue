<template>
  <Head title="Citas de Servicio" />
  <div class="citas-index min-h-screen bg-[var(--ui-surface)] transition-colors duration-500">
    <div class="w-full px-4 lg:px-10 py-10">
      <!-- Header Estratégico -->      <CitasHeader
        :total="estadisticas.total"
        :programadas="estadisticas.programadas"
        :por-atender="estadisticas.porAtender"
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
        v-model:fecha-desde="fechaDesde"
        v-model:fecha-hasta="fechaHasta"
        @date-change="handleDateChange"
        @open-availability="showAvailabilityModal = true"
      />

      <!-- Vista de Tabla Premium -->
      <div v-if="viewMode === 'table'" class="mt-10 space-y-8">
        <CitasTable 
          :items="citasDocumentos" 
          @ver-detalles="verDetalles" 
          @editar="editarCita" 
          @reprogramar="abrirReprogramar"
          @cancelar="confirmarCancelacion"
          @ver-galeria="openGallery"
          @descargar-evidencias="descargarEvidenciasCita"
        />

        <!-- Paginación de Alto Rendimiento (Control Central de Datos) -->
        <div class="bg-slate-900 dark:bg-slate-900 backdrop-blur-2xl rounded-[2.5rem] border border-slate-800 px-10 py-8 transition-all shadow-2xl relative overflow-hidden group">
          <!-- Efecto decorativo -->
          <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-600/10 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
          
          <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8">
            <div class="flex flex-col md:flex-row items-center gap-6">
              <div class="flex flex-col">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.3em] mb-1">Métricas de Vista</span>
                <p class="text-[11px] font-black text-slate-200 uppercase tracking-widest">
                  Resultados: <span class="text-blue-400">{{ paginationData.from }} - {{ paginationData.to }}</span> de <span class="text-white">{{ paginationData.total }}</span>
                </p>
              </div>
              
              <div class="h-8 w-px bg-slate-800 hidden md:block"></div>

              <div class="flex flex-col">
                <span class="text-[9px] font-black text-slate-500 uppercase tracking-[0.3em] mb-1">Densidad de Datos</span>
                <select
                  :value="paginationData.perPage"
                  @change="handlePerPageChange(parseInt($event.target.value))"
                  class="bg-slate-800 border-2 border-slate-700 rounded-2xl text-[10px] font-black uppercase tracking-wider py-2.5 px-5 text-white focus:ring-2 focus:ring-blue-500/50 transition-all outline-none cursor-pointer hover:border-slate-600 shadow-lg"
                >
                  <option value="10">10 REG / PÁG</option>
                  <option value="25">25 REG / PÁG</option>
                  <option value="50">50 REG / PÁG</option>
                  <option value="100">100 REG / PÁG</option>
                  <option value="500">VER TODOS (500)</option>
                </select>
              </div>
            </div>

            <div class="w-full lg:w-auto">
              <Pagination 
                :pagination-data="citasPaginator"
                @page-change="handlePageChange"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Vista de Calendario Operativo -->
      <div v-else class="mt-10 bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transition-all duration-500">
        <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-slate-50/30 dark:bg-slate-900/50">
          <div class="flex items-center gap-6">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-wider capitalize">{{ monthYearLabel }}</h2>
            <div class="flex gap-2">
              <button @click="changeMonth(-1)" class="w-11 h-11 flex items-center justify-center bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 hover:border-slate-900 dark:hover:border-slate-700 rounded-2xl transition-all shadow-sm">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
              </button>
              <button @click="currentMonth = new Date()" class="px-6 h-11 text-[10px] font-black text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:white uppercase tracking-wide border-2 border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-2xl transition-all shadow-sm">
                Hoy
              </button>
              <button @click="changeMonth(1)" class="w-11 h-11 flex items-center justify-center bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 hover:border-slate-900 dark:hover:border-slate-700 rounded-2xl transition-all shadow-sm">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </button>
            </div>
          </div>
          
          <div class="flex flex-wrap items-center gap-4">
             <div v-for="(color, label) in legendColors" :key="label" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
               <div :class="color" class="w-2 h-2 rounded-full shadow-sm"></div>
               <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide">{{ label }}</span>
             </div>
          </div>
        </div>

        <div class="grid grid-cols-7 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
          <div v-for="day in ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']" :key="day" class="py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
            {{ day }}
          </div>
        </div>

        <div class="grid grid-cols-7 border-slate-100 dark:border-slate-800">
          <div v-for="(day, idx) in daysInMonth" :key="idx" 
               :class="['border-r border-b border-slate-100 dark:border-slate-800 p-4 transition-all relative group min-h-[180px]', 
                        day.month === 'current' ? 'bg-white dark:bg-slate-950' : 'bg-slate-50/30 dark:bg-slate-900/20 opacity-40']">
            <div class="flex justify-between items-start mb-3">
              <span :class="['text-sm font-black w-10 h-10 flex items-center justify-center rounded-2xl transition-all shadow-sm', 
                             isToday(day.date) ? 'bg-blue-600 text-white shadow-sky-500/30' : 'text-slate-500 dark:text-slate-300 border border-slate-200 dark:border-slate-800']">
                {{ day.day }}
              </span>
            </div>
            
            <div class="space-y-2">
              <template v-for="item in getCitasForDay(day.date)" :key="item.id">
                <div v-if="item.isCita"
                    @click="verDetalles({ raw: item, titulo: `Cita #${item.id}` })"
                    :class="['p-2.5 rounded-xl text-[11px] font-black cursor-pointer truncate transition-all hover:scale-[1.03] shadow-sm border-l-4',
                             obtenerEstadoCitaClase(item.raw || item)]"
                    :title="`${item.raw?.cliente?.nombre_razon_social || item.cliente?.nombre_razon_social} - ${item.raw?.problema_reportado || item.problema_reportado}`">
                  <div class="flex items-center justify-between mb-2">
                     <span class="opacity-100 dark:text-white font-black text-[9px] tracking-tighter">
                        {{ formatearHora(item.raw?.fecha_hora || item.fecha_hora) }} - {{ formatearHora(item.raw?.fecha_hora_fin || item.fecha_hora_fin) }}
                     </span>
                  </div>
                  <div class="uppercase truncate dark:text-white font-black text-[11px] leading-tight mb-1">{{ item.raw?.cliente?.nombre_razon_social || item.cliente?.nombre_razon_social }}</div>
                  
                  <!-- Técnico Asignado (NUEVO: Full Name) -->
                  <div v-if="item.raw?.tecnico || item.tecnico" class="flex items-center gap-1.5 mt-1.5 py-1 px-2 bg-black/5 dark:bg-white/5 rounded-lg border border-black/5 dark:border-white/5">
                     <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ backgroundColor: (item.raw?.tecnico?.color || item.tecnico?.color || '#94a3b8') }"></span>
                     <span class="text-[9px] text-slate-500 dark:text-slate-400 uppercase font-black truncate">
                        {{ item.raw?.tecnico?.name || item.tecnico_nombre || item.tecnico?.name }}
                     </span>
                  </div>

                  <div v-if="item.raw?.problema_reportado" class="text-[8px] opacity-60 truncate italic mt-1.5">"{{ item.raw.problema_reportado }}"</div>
                </div>
                <div v-else-if="item.isBloqueo"
                    class="p-2 rounded-xl text-[9px] font-black truncate transition-all bg-slate-100 dark:bg-slate-800 text-slate-400 border-l-4 border-slate-300 dark:border-slate-700"
                    :title="`${item.tecnico}: ${item.motivo}`">
                  🌴 {{ item.tecnico }}: {{ item.motivo }}
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal de Detalles (Expediente 360°) -->
      <CitasModal 
        :show="showModal && modalMode === 'details'" 
        :selected="selectedCita" 
        :auditoria="auditoriaForModal"
        @close="cerrarModalCita"
        @editar="editarCita"
        @cancelar="confirmarCancelacion"
        @descargar-evidencias="descargarEvidenciasCita"
        @ver-galeria="openGallery"
      />

      <!-- Modal de Confirmación de Cancelación -->
      <Transition name="modal-fade">
        <div v-if="showModal && modalMode === 'confirm'" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="cerrarModalCita">
          <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md"></div>
          <div class="relative w-full max-w-md bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 p-8 text-center">
            <div class="w-20 h-20 bg-rose-50 dark:bg-rose-900/20 rounded-[2rem] flex items-center justify-center mx-auto mb-6 text-rose-600 border border-rose-100 dark:border-rose-800">
               <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">¿Cancelar Cita?</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-2">
               Cita <strong>#{{ selectedCita?.folio || selectedCita?.id }}</strong> — {{ selectedCita?.cliente?.nombre_razon_social || 'Cliente' }}
            </p>
            <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide leading-loose mb-8">
               Esta acción cambiará el estado a cancelado y liberará el horario del técnico.
            </p>
            <div class="flex gap-4">
               <button @click="cerrarModalCita" class="flex-1 py-4 bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wide rounded-2xl transition-all hover:bg-slate-200 dark:hover:bg-slate-700">Regresar</button>
               <button @click="cancelarCita" class="flex-1 py-4 bg-rose-600 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl shadow-xl shadow-rose-600/20 transition-all hover:bg-rose-700">Confirmar Cancelación</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Modal Reprogramar -->
      <Transition name="modal-fade">
        <div v-if="showReprogramarModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 md:p-8" @click.self="showReprogramarModal = false">
          <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-md"></div>
          
          <div class="relative w-full max-w-6xl bg-white dark:bg-slate-950 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col transition-all max-h-[90vh]">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-slate-100 dark:border-slate-800 bg-transparent/30 dark:bg-slate-900/50 flex items-center justify-between shrink-0">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-600 flex items-center justify-center text-white shadow-lg">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                  <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider">Reajustar Cronograma</h3>
                  <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide">
                    Cita #{{ citaReprogramar?.id }} - Cliente: {{ citaReprogramar?.cliente?.nombre_razon_social || citaReprogramar?.raw?.cliente?.nombre_razon_social || 'N/D' }}
                  </p>
                </div>
              </div>
              <button @click="showReprogramarModal = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            
            <form @submit.prevent="submitReprogramar" class="flex-1 overflow-hidden flex flex-col md:flex-row min-h-0">
              <!-- Sidebar: Configuración -->
              <div class="w-full md:w-80 border-r border-slate-100 dark:border-slate-800 p-8 space-y-8 bg-transparent/20 dark:bg-slate-900/10 flex flex-col shrink-0 overflow-y-auto">
                <!-- Fecha -->
                <div>
                  <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Fecha de Reajuste</label>
                  <input 
                    type="date" 
                    v-model="reprogramarDate" 
                    @change="handleReprogramarDateChange"
                    class="w-full bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl p-4 text-xs font-black uppercase text-slate-900 dark:text-white focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-sm"
                  >
                </div>

                <!-- Técnicos List -->
                <div class="flex-1 flex flex-col min-h-[250px]">
                  <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Asignar Especialista</label>
                  <div class="space-y-2 overflow-y-auto custom-scrollbar pr-2 flex-1">
                    <button 
                      v-for="t in props.tecnicos" 
                      :key="t.id"
                      type="button"
                      @click="seleccionarTecnicoReprogramar(t)"
                      :class="[
                        'w-full flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left',
                        reprogramarForm.tecnico_id === t.id 
                          ? 'bg-purple-50 dark:bg-purple-900/30 border-purple-500 shadow-md' 
                          : 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700'
                      ]"
                    >
                      <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-black text-white uppercase shadow-sm" :style="{ backgroundColor: t.color || '#8b5cf6' }">
                        {{ (t.name || t.nombre || 'T')?.charAt(0) }}
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                          <p :class="['text-[11px] font-black truncate uppercase tracking-tight', reprogramarForm.tecnico_id === t.id ? 'text-purple-600 dark:text-purple-400' : 'text-slate-700 dark:text-slate-300']">
                            {{ t.name || t.nombre }}
                          </p>
                          <span v-if="t.citas_asignadas_count > 0" class="shrink-0 px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[7px] font-black text-slate-500 dark:text-slate-400 uppercase">
                            {{ t.citas_asignadas_count }} Serv
                          </span>
                        </div>
                        <p class="text-[8px] font-bold text-slate-400 uppercase">Seleccionar para reasignar</p>
                      </div>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Main View: Grid de Horas -->
              <div class="flex-1 p-8 flex flex-col overflow-y-auto">
                <div v-if="tecnicoSeleccionadoObj" class="flex-1 flex flex-col">
                  <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-3">
                      <div class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></div>
                      <h4 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">
                        Especialista: {{ tecnicoSeleccionadoObj.name || tecnicoSeleccionadoObj.nombre }}
                      </h4>
                    </div>
                    <div class="flex gap-4">
                      <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-emerald-500 border border-emerald-400"></div>
                        <span class="text-[9px] font-black text-slate-400 uppercase">Libre</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-purple-600 border border-purple-500"></div>
                        <span class="text-[9px] font-black text-slate-400 uppercase">Seleccionado</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-rose-500 border border-rose-400"></div>
                        <span class="text-[9px] font-black text-slate-400 uppercase">Ocupado</span>
                      </div>
                    </div>
                  </div>

                  <!-- Grid Horario -->
                  <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <div 
                      v-for="hora in 13" 
                      :key="'rep-slot-' + hora"
                      :class="getSlotClassesReprogramar(hora + 7)"
                      @click="seleccionarBloque(hora + 7)"
                    >
                      <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black uppercase text-white">
                          {{ formatearHoraIntervalo(hora + 7) }}
                        </span>
                        <div v-if="isSlotBusyReprogramar(hora + 7)" class="w-6 h-6 rounded-lg bg-white/20 flex items-center justify-center text-white">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                          </svg>
                        </div>
                      </div>
                      
                      <div class="flex items-center justify-between">
                        <p class="text-[9px] font-bold uppercase tracking-tighter text-white/90">
                          {{ getSlotStatusTextReprogramar(hora + 7) }}
                        </p>
                        <span v-if="!isSlotBusyReprogramar(hora + 7) && !isSlotSelectedReprogramar(hora + 7)" class="text-[8px] font-black text-white opacity-0 group-hover:opacity-100 transition-all uppercase">
                          Seleccionar
                        </span>
                      </div>
                    </div>
                  </div>

                  <div v-if="availabilityError || Object.keys(reprogramarForm.errors).length > 0" class="mb-8 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-900/30 rounded-2xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div class="text-[9px] font-black text-rose-600 dark:text-rose-400 uppercase leading-tight">
                      <p v-if="availabilityError">{{ availabilityError }}</p>
                      <p v-for="(err, key) in reprogramarForm.errors" :key="key">{{ err }}</p>
                    </div>
                  </div>

                  <!-- Resumen y Botones -->
                  <div class="mt-auto p-6 bg-slate-900 dark:bg-white rounded-3xl flex flex-col sm:flex-row items-center justify-between gap-6 shadow-2xl transition-all">
                    <div class="flex items-center gap-6">
                      <div class="flex flex-col">
                        <span class="text-[8px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Horario Propuesto</span>
                        <p class="text-xs font-black text-white dark:text-slate-900 uppercase tracking-wider">
                          {{ selectedStart !== null ? formatearHoraBloque(selectedStart) : '--:--' }} 
                          <span class="text-purple-500 mx-2">-></span> 
                          {{ selectedEnd !== null ? formatearHoraBloque(selectedEnd) : '--:--' }}
                        </p>
                      </div>
                      <div class="h-8 w-px bg-slate-800 dark:bg-slate-200 hidden sm:block"></div>
                      <div class="flex flex-col hidden sm:flex">
                        <span class="text-[8px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-1">Duración</span>
                        <p class="text-xs font-black text-purple-400 dark:text-purple-600 uppercase tracking-wider">
                          {{ (selectedStart !== null && selectedEnd !== null) ? (selectedEnd - selectedStart) : 0 }} Hora(s)
                        </p>
                      </div>
                    </div>
                    <div class="flex gap-3 w-full sm:w-auto">
                      <button type="button" @click="showReprogramarModal = false" class="flex-1 sm:flex-none px-6 py-3 bg-slate-800 dark:bg-slate-100 text-slate-300 dark:text-slate-700 text-[10px] font-black uppercase tracking-widest rounded-2xl hover:scale-105 transition-all">
                        Cancelar
                      </button>
                      <button type="submit" :disabled="reprogramarForm.processing || !!availabilityError || selectedStart === null" class="flex-1 sm:flex-none px-8 py-3 bg-purple-600 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl hover:scale-105 active:scale-95 transition-all shadow-xl shadow-purple-600/20 disabled:opacity-50">
                        Confirmar Reajuste
                      </button>
                    </div>
                  </div>
                </div>

                <div v-else class="flex-1 flex flex-col items-center justify-center text-center p-12">
                   <div class="w-24 h-24 bg-transparent dark:bg-slate-900 rounded-[2.5rem] flex items-center justify-center text-slate-300 dark:text-slate-700 mb-6 border-2 border-dashed border-slate-200 dark:border-slate-800">
                      <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                   </div>
                   <h4 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-wider mb-2">Selecciona un Especialista</h4>
                   <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest max-w-xs leading-loose">
                     Elige un técnico de la lista lateral para visualizar su carga de trabajo y reasignar la cita.
                   </p>
                </div>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Galería Inmersiva -->
      <Transition name="modal-fade">
        <div v-if="showGalleryModal" class="fixed inset-0 z-[150] bg-slate-950/98 backdrop-blur-2xl flex flex-col" @click.self="closeGallery">
          <div class="flex justify-between items-center p-8 bg-gradient-to-b from-black/50 to-transparent">
             <div class="flex items-center gap-6">
                <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-white border border-white/10"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                <div>
                  <p class="text-[10px] font-black uppercase tracking-[0.3em] text-white/50 mb-1">{{ imageTitle }}</p>
                  <p class="text-xs font-bold text-white uppercase tracking-wide">Archivo {{ currentImageIndex + 1 }} / {{ galleryImages.length }}</p>
                </div>
             </div>
             <button @click="closeGallery" class="w-14 h-14 flex items-center justify-center bg-white/5 hover:bg-white/10 text-white rounded-2xl transition-all border border-white/10 group"><svg class="w-8 h-8 group-hover:rotate-90 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
          </div>

          <div class="flex-1 flex items-center justify-center relative px-4">
             <button v-if="galleryImages.length > 1" @click.stop="prevImage" class="absolute left-10 w-16 h-16 flex items-center justify-center bg-black/40 hover:bg-white text-white hover:text-black rounded-full backdrop-blur-xl transition-all hover:scale-110 z-10 group border border-white/10 hover:border-white shadow-2xl">
                <svg class="w-8 h-8 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
             </button>
             
             <img :src="galleryImages[currentImageIndex]" class="max-h-[75vh] max-w-[85vw] object-contain rounded-3xl shadow-[0_40px_100px_rgba(0,0,0,0.8)] border border-white/5 transition-all duration-700 animate-in fade-in zoom-in-95" :key="currentImageIndex">

             <button v-if="galleryImages.length > 1" @click.stop="nextImage" class="absolute right-10 w-16 h-16 flex items-center justify-center bg-black/40 hover:bg-white text-white hover:text-black rounded-full backdrop-blur-xl transition-all hover:scale-110 z-10 group border border-white/10 hover:border-white shadow-2xl">
                <svg class="w-8 h-8 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
             </button>
          </div>

          <div v-if="galleryImages.length > 1" class="p-10 flex justify-center gap-4 bg-gradient-to-t from-black/50 to-transparent">
             <button 
               v-for="(img, idx) in galleryImages" :key="idx" 
               @click.stop="currentImageIndex = idx"
               :class="['w-20 h-20 rounded-2xl overflow-hidden border-4 transition-all shadow-2xl transform hover:scale-110', currentImageIndex === idx ? 'border-blue-500 scale-125' : 'border-transparent opacity-30 hover:opacity-100']"
             >
               <img :src="img" class="w-full h-full object-cover">
             </button>
          </div>
        </div>
      </Transition>

      <!-- Quick Availability Modal -->
      <QuickAvailabilityModal 
        :show="showAvailabilityModal"
        :tecnicos="props.tecnicos"
        @close="showAvailabilityModal = false"
      />

      <!-- Capa de bloqueo para evitar Click-Through al cerrar modales -->
      <div v-if="blockingClicks" class="fixed inset-0 z-[999] bg-transparent pointer-events-auto cursor-default"></div>
    </div>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, router, usePage, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import { SUPER_ADMIN_ROLES } from '@/constants/systemRoles'

// Componentes Premium
import CitasHeader from '@/Components/IndexComponents/CitasHeader.vue'
import CitasTable from '@/Components/IndexComponents/CitasTable.vue'
import CitasModal from '@/Components/IndexComponents/CitasModal.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/IndexComponents/Modales.vue'
import QuickAvailabilityModal from '@/Components/IndexComponents/QuickAvailabilityModal.vue'

const page = usePage()

const isSuperAdmin = computed(() => {
  const roles = page.props.auth?.user?.roles || []
  return roles.some(r => SUPER_ADMIN_ROLES.includes(r.name))
})

const puedeCancelarCita = (cita) => {
  if (!cita) return false
  if (['completado', 'cancelado'].includes(cita.estado)) return isSuperAdmin.value
  return true
}

defineOptions({ layout: AppLayout, inheritAttrs: false })

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

// Props
const props = defineProps({
  citas: { type: Object, required: true },
  stats: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  sorting: { type: Object, default: () => ({ sort_by: 'fecha_hora', sort_direction: 'desc' }) },
  pagination: { type: Object, default: () => ({}) },
  tecnicos: { type: Array, default: () => [] },
  clientes: { type: Array, default: () => [] },
  estados: { type: Array, default: () => [] },
  bloqueos: { type: Object, default: () => ({ vacaciones: [], dias_bloqueados: [] }) },
})

// UI State
const showModal = ref(false)
const modalMode = ref('details')
const selectedCita = ref(null)
const selectedId = ref(null)
const viewMode = ref('table') 
const currentMonth = ref(new Date())
const showGalleryModal = ref(false)
const galleryImages = ref([])
const currentImageIndex = ref(0)
const imageTitle = ref('')
const showAvailabilityModal = ref(false)
const blockingClicks = ref(false)

const cerrarModalCita = () => {
  showModal.value = false
  blockingClicks.value = true
  setTimeout(() => {
    blockingClicks.value = false
  }, 350)
}

onMounted(() => {
  if (page.props.flash?.success) notyf.success(page.props.flash.success)
  if (page.props.flash?.error) notyf.error(page.props.flash.error)
  window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => window.removeEventListener('keydown', handleKeydown))

const handleKeydown = (e) => {
  if (!showGalleryModal.value) {
    if (e.key === 'Escape' && showModal.value) cerrarModalCita()
    return
  }
  if (e.key === 'Escape') closeGallery()
  if (e.key === 'ArrowRight') nextImage()
  if (e.key === 'ArrowLeft') prevImage()
}

// Filtros y Paginación
// Persistencia de filtros en localStorage para que sobrevivan al reload
const STORAGE_KEY = 'citas_filtros_v1'

const cargarFiltrosGuardados = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (!raw) return null
    return JSON.parse(raw)
  } catch (e) {
    return null
  }
}

const filtrosGuardados = cargarFiltrosGuardados()
const filtrosIniciales = filtrosGuardados || props.filters || {}

const searchTerm = ref(filtrosIniciales.search ?? '')
const sortBy = ref(`${props.sorting.sort_by}-${props.sorting.sort_direction}`)
const filtroEstadoCita = ref(filtrosIniciales.estado ?? '')
const perPage = ref(props.pagination?.per_page || 10)
const fechaDesde = ref(filtrosIniciales.fecha_desde ?? '')
const fechaHasta = ref(filtrosIniciales.fecha_hasta ?? '')

const guardarFiltros = () => {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      search: searchTerm.value,
      estado: filtroEstadoCita.value,
      fecha_desde: fechaDesde.value,
      fecha_hasta: fechaHasta.value,
    }))
  } catch (e) {
    // localStorage no disponible o cuota llena — ignorar silenciosamente
  }
}

watch([searchTerm, filtroEstadoCita, fechaDesde, fechaHasta], guardarFiltros)

const estadisticas = computed(() => ({
  total: props.stats?.total ?? 0,
  programadas: props.stats?.programadas ?? 0,
  porAtender: props.stats?.por_atender ?? 0,
  enProceso: props.stats?.en_proceso ?? 0,
  completadas: props.stats?.completadas ?? 0,
  canceladas: props.stats?.canceladas ?? 0
}))

const citasPaginator = computed(() => props.citas)

const citasDocumentos = computed(() => {
  const data = props.citas?.data || []
  return data.map(c => ({
    id: c.id,
    raw: c
  }))
})

const paginationData = computed(() => ({
  currentPage: props.pagination?.current_page || citasPaginator.value.current_page || 1,
  lastPage:    props.pagination?.last_page || citasPaginator.value.last_page || 1,
  perPage:     props.pagination?.per_page || citasPaginator.value.per_page || 10,
  from:        props.pagination?.from || citasPaginator.value.from || 0,
  to:          props.pagination?.to || citasPaginator.value.to || 0,
  total:       props.pagination?.total || citasPaginator.value.total || 0,
}))

// Handlers Operativos
const fetchData = (params = {}) => {
  router.get(route('citas.index'), {
    search: searchTerm.value,
    sort_by: sortBy.value.split('-')[0],
    sort_direction: sortBy.value.split('-')[1],
    estado: filtroEstadoCita.value,
    per_page: perPage.value,
    view_mode: viewMode.value,
    fecha_desde: fechaDesde.value,
    fecha_hasta: fechaHasta.value,
    ...params
  }, { preserveState: true, preserveScroll: true })
}

watch(viewMode, (newMode) => {
  if (newMode === 'calendar') {
    // Al entrar a calendario, limpiamos filtros de estado para ver todo el panorama
    filtroEstadoCita.value = '';
    fetchData({ page: 1 });
  }
})

const handleSearchChange = (val) => { searchTerm.value = val; fetchData({ page: 1 }); }
const handleEstadoCitaChange = (val) => { filtroEstadoCita.value = val; fetchData({ page: 1 }); }
const handleSortChange = (val) => { sortBy.value = val; fetchData({ page: 1 }); }
const handlePerPageChange = (val) => { perPage.value = val; fetchData({ page: 1 }); }
const handlePageChange = (val) => { fetchData({ page: val }); }
const handleDateChange = () => { fetchData({ page: 1 }); }

const limpiarFiltros = () => {
  searchTerm.value = ''
  sortBy.value = 'fecha_hora-desc'
  filtroEstadoCita.value = ''
  fechaDesde.value = ''
  fechaHasta.value = ''
  localStorage.removeItem(STORAGE_KEY)
  fetchData({ page: 1 })
  notyf.success('Filtros limpiados')
}

const verDetalles = (doc) => {
  selectedCita.value = doc.raw
  modalMode.value = 'details'
  showModal.value = true
}

const editarCita = (id) => router.visit(route('citas.edit', id))

const confirmarCancelacion = (cita) => {
  selectedCita.value = cita
  modalMode.value = 'confirm'
  showModal.value = true
}

const cancelarCita = () => {
  router.post(route('citas.cancelar', selectedCita.value.id), {
    motivo: 'Cancelado desde el panel administrativo.'
  }, {
    onSuccess: () => {
      notyf.success('Cita cancelada exitosamente y horario liberado')
      cerrarModalCita()
    },
    onError: (errors) => {
      const msg = errors.general || 'Ocurrió un error al intentar cancelar la cita';
      notyf.error(msg);
    }
  })
}

const crearNuevaCita = () => {
  router.visit(route('citas.create'))
}

const descargarEvidenciasCita = (citaId) => {
  if (!citaId) return
  window.open(route('citas.download-evidencias', citaId), '_blank')
}

// Auditoría para Modal
const auditoriaForModal = computed(() => {
  if (!selectedCita.value) return {}
  const meta = selectedCita.value.metadata || {}
  return {
    creado_por: selectedCita.value.creado_por_nombre || meta.creado_por || 'Sistema',
    creado_en: selectedCita.value.created_at,
    actualizado_en: selectedCita.value.updated_at
  }
})

// Utils Galería
const storageSrc = (foto) => {
  if (!foto) return ''
  const path = typeof foto === 'object' && foto !== null ? (foto.path || foto.url || '') : String(foto || '')
  const p = path.trim()
  if (!p) return ''
  if (/^https?:\/\//i.test(p)) return p
  return p.startsWith('/') ? p : `/storage/${p}`
}

const openGallery = (images, title) => {
  galleryImages.value = images.map(i => storageSrc(i))
  currentImageIndex.value = 0
  imageTitle.value = title
  showGalleryModal.value = true
}

const closeGallery = () => showGalleryModal.value = false
const nextImage = () => currentImageIndex.value = (currentImageIndex.value + 1) % galleryImages.value.length
const prevImage = () => currentImageIndex.value = (currentImageIndex.value - 1 + galleryImages.value.length) % galleryImages.value.length

// Reprogramar Logic
const showReprogramarModal = ref(false)
const citaReprogramar = ref(null)
const reprogramarDate = ref('')
const selectedStart = ref(null)
const selectedEnd = ref(null)
const clickStep = ref(0)
const availabilityError = ref('')
const busySlotsReprogramar = ref([])

const reprogramarForm = useForm({
  fecha_hora: '',
  fecha_hora_fin: '',
  tecnico_id: '',
  estado: 'reprogramado'
})

const originalStart = ref(null)
const originalEnd = ref(null)

const abrirReprogramar = (cita) => {
  citaReprogramar.value = cita
  
  let fechaPart = ''
  let startHour = 8
  let endHour = 9

  if (cita?.fecha_hora) {
    const rawStr = String(cita.fecha_hora)
    // Extraer fecha directamente YYYY-MM-DD sin desfase de zona horaria
    fechaPart = rawStr.split('T')[0].split(' ')[0]
    const d = new Date(rawStr.replace(' ', 'T'))
    if (!isNaN(d.getTime())) startHour = d.getHours()
  }

  if (cita?.fecha_hora_fin) {
    const rawFinStr = String(cita.fecha_hora_fin)
    const dFin = new Date(rawFinStr.replace(' ', 'T'))
    if (!isNaN(dFin.getTime())) endHour = dFin.getHours()
  } else {
    endHour = startHour + 1
  }

  reprogramarDate.value = fechaPart || new Date().toISOString().split('T')[0]
  reprogramarForm.tecnico_id = cita.tecnico_id
  originalStart.value = startHour
  originalEnd.value = endHour
  selectedStart.value = startHour
  selectedEnd.value = endHour
  
  syncReprogramarForm()
  showReprogramarModal.value = true
  fetchBusySlotsReprogramar()
  checkAvailabilityReprogramar()
}

const syncReprogramarForm = () => {
  if (reprogramarDate.value && selectedStart.value !== null) {
    reprogramarForm.fecha_hora = `${reprogramarDate.value} ${String(selectedStart.value).padStart(2, '0')}:00:00`
    const end = selectedEnd.value || selectedStart.value + 1
    reprogramarForm.fecha_hora_fin = `${reprogramarDate.value} ${String(end).padStart(2, '0')}:00:00`
    reprogramarForm.estado = 'reprogramado'
  }
}

const isSlotBusyReprogramar = (h) => {
  if (!busySlotsReprogramar.value.length) return false
  return busySlotsReprogramar.value.some(slot => {
    const startH = parseInt(slot.start.split(':')[0])
    const endH = parseInt(slot.end.split(':')[0])
    return h >= startH && h < endH
  })
}

const seleccionarBloque = (h) => {
  if (isSlotBusyReprogramar(h)) {
    notyf.error('Este bloque de horario ya está ocupado')
    return
  }

  if (clickStep.value === 0) { 
    selectedStart.value = h; 
    
    // Lógica Inteligente: Intentar mantener la duración original
    const duracionOriginal = originalEnd.value - originalStart.value;
    const finPropuesto = h + duracionOriginal;
    
    // Verificar si el rango propuesto está libre (max 20:00)
    let rangoLibre = finPropuesto <= 20;
    if (rangoLibre) {
      for (let i = h; i < finPropuesto; i++) {
        if (isSlotBusyReprogramar(i)) {
          rangoLibre = false;
          break;
        }
      }
    }

    if (rangoLibre) {
      selectedEnd.value = finPropuesto;
      clickStep.value = 0; // Terminado con 1 solo clic
    } else {
      selectedEnd.value = null; 
      clickStep.value = 1; // Esperar segundo clic para fin manual
    }
  }
  else { 
    if (h <= selectedStart.value) { 
      selectedStart.value = h; 
      selectedEnd.value = h + 1; 
    }
    else { 
      // Validar si hay bloques ocupados en medio
      for (let i = selectedStart.value; i < h; i++) {
        if (isSlotBusyReprogramar(i)) {
          notyf.error('El rango seleccionado incluye bloques ocupados')
          return
        }
      }
      selectedEnd.value = h; 
    }
    clickStep.value = 0;
  }
  syncReprogramarForm();
  checkAvailabilityReprogramar();
}

const isBloqueDentroDeRango = (h) => selectedStart.value !== null && selectedEnd.value !== null && h > selectedStart.value && h < selectedEnd.value
const formatearHoraBloque = (h) => h ? `${h > 12 ? h - 12 : h}:00 ${h >= 12 ? 'PM' : 'AM'}` : ''

const tecnicoSeleccionadoObj = computed(() => {
  return props.tecnicos.find(t => t.id === reprogramarForm.tecnico_id)
})

const validarRangoSeleccionado = () => {
  if (selectedStart.value !== null) {
    const end = selectedEnd.value || selectedStart.value + 1
    for (let h = selectedStart.value; h < end; h++) {
      if (isSlotBusyReprogramar(h)) {
        availabilityError.value = '⚠️ El horario propuesto incluye bloques ocupados. Por favor, selecciona un espacio libre.'
        selectedStart.value = null
        selectedEnd.value = null
        syncReprogramarForm()
        break
      }
    }
  }
}

const handleReprogramarDateChange = async () => {
  syncReprogramarForm()
  await fetchBusySlotsReprogramar()
  await checkAvailabilityReprogramar()
  validarRangoSeleccionado()
}

const seleccionarTecnicoReprogramar = async (t) => {
  reprogramarForm.tecnico_id = t.id
  syncReprogramarForm()
  await fetchBusySlotsReprogramar()
  await checkAvailabilityReprogramar()
  validarRangoSeleccionado()
}

const formatearHoraIntervalo = (h) => {
  const h12 = h > 12 ? h - 12 : (h === 0 ? 12 : h)
  const nextH = h + 1
  const nextH12 = nextH > 12 ? nextH - 12 : nextH
  const ampm = nextH >= 12 ? 'PM' : 'AM'
  return `${h12}:00 - ${nextH12}:00 ${ampm}`
}

const isSlotSelectedReprogramar = (h) => {
  if (selectedStart.value === null) return false
  if (selectedEnd.value === null) return h === selectedStart.value
  return h >= selectedStart.value && h < selectedEnd.value
}

const getSlotStatusTextReprogramar = (h) => {
  if (isSlotBusyReprogramar(h)) return 'No Disponible'
  if (isSlotSelectedReprogramar(h)) return 'Seleccionado'
  return 'Espacio Libre'
}

const getSlotClassesReprogramar = (h) => {
  const base = 'relative p-4 rounded-2xl border-2 transition-all group overflow-hidden'
  if (isSlotBusyReprogramar(h)) return `${base} bg-rose-500 border-rose-400 shadow-sm cursor-not-allowed opacity-80 text-white`
  if (isSlotSelectedReprogramar(h)) return `${base} bg-purple-600 border-purple-500 shadow-xl scale-[1.02] z-10 text-white`
  return `${base} bg-emerald-500 border-emerald-400 hover:bg-emerald-600 hover:shadow-lg cursor-pointer text-white`
}

const fetchBusySlotsReprogramar = async () => {
  if (!reprogramarForm.tecnico_id || !reprogramarDate.value) return
  const res = await fetch(route('api.citas.busy-slots', { 
    tecnico_id: reprogramarForm.tecnico_id, 
    fecha: reprogramarDate.value,
    exclude_id: citaReprogramar.value?.id 
  }))
  const data = await res.json()
  if (data.success) busySlotsReprogramar.value = data.slots
}

const checkAvailabilityReprogramar = async () => {
  if (!reprogramarForm.tecnico_id || !reprogramarForm.fecha_hora) return
  const res = await fetch(route('api.citas.check-availability', { 
    tecnico_id: reprogramarForm.tecnico_id, 
    fecha_hora: reprogramarForm.fecha_hora,
    fecha_hora_fin: reprogramarForm.fecha_hora_fin,
    cita_id: citaReprogramar.value.id
  }))
  const data = await res.json()
  availabilityError.value = data.available ? '' : data.message
}

const submitReprogramar = () => {
  reprogramarForm.put(route('citas.update', citaReprogramar.value.id), {
    preserveScroll: true,
    onSuccess: () => { 
      showReprogramarModal.value = false; 
      notyf.success('Cronograma reajustado exitosamente'); 
    },
    onError: (errors) => {
      const firstErr = Object.values(errors)[0];
      availabilityError.value = firstErr || 'Verifica la disponibilidad del técnico en este horario.';
      notyf.error(firstErr || 'No se pudo reajustar. Horario no disponible.');
    }
  })
}

// Calendario Logic
const legendColors = {
  'Programado': 'bg-blue-500',
  'En Proceso': 'bg-indigo-500',
  'Completado': 'bg-emerald-500',
  'Cancelado': 'bg-rose-500',
  'Reprogramado': 'bg-purple-500'
}

const daysInMonth = computed(() => {
  const y = currentMonth.value.getFullYear(), m = currentMonth.value.getMonth()
  const first = new Date(y, m, 1).getDay()
  const last = new Date(y, m + 1, 0).getDate()
  const prevLast = new Date(y, m, 0).getDate()
  const days = []
  for (let i = first - 1; i >= 0; i--) days.push({ day: prevLast - i, month: 'prev', date: new Date(y, m - 1, prevLast - i) })
  for (let i = 1; i <= last; i++) days.push({ day: i, month: 'current', date: new Date(y, m, i) })
  const rem = 42 - days.length
  for (let i = 1; i <= rem; i++) days.push({ day: i, month: 'next', date: new Date(y, m + 1, i) })
  return days
})

const monthYearLabel = computed(() => currentMonth.value.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' }))
const changeMonth = (o) => currentMonth.value = new Date(currentMonth.value.getFullYear(), currentMonth.value.getMonth() + o, 1)
const isToday = (d) => d.toDateString() === new Date().toDateString()

const getCitasForDay = (d) => {
  // Usar formato YYYY-MM-DD local para evitar problemas de zona horaria (UTC vs Local)
  const formatLocal = (date) => {
    const dt = new Date(date);
    return `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}-${String(dt.getDate()).padStart(2, '0')}`;
  };
  
  const s = formatLocal(d);
  const c = (props.citas?.data || []).filter(i => formatLocal(i.fecha_hora) === s).map(i => ({ ...i, isCita: true }))
  const b = (props.bloqueos?.dias_bloqueados || []).filter(i => i.fecha === s).map(i => ({ ...i, isBloqueo: true, tecnico: i.tecnico_nombre }))
  return [...c, ...b]
}

const formatearHora = (d) => d ? new Date(d).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: true }) : ''
const bordeCalendarioPorEstado = (e) => legendColors[obtenerLabelEstado(e)] || ''
const obtenerLabelEstado = (e) => ({ pendiente: 'Pendiente', programado: 'Programado', en_proceso: 'En Proceso', completado: 'Completado', cancelado: 'Cancelado', reprogramado: 'Reprogramado' }[e] || 'Pendiente')

const isAtrasada = (c) => {
  if (!c?.fecha_hora || !['pendiente', 'programado'].includes(c.estado)) return false
  const d = new Date(c.fecha_hora)
  const t = new Date(); t.setHours(0,0,0,0)
  return d < t
}

const obtenerEstadoCitaClase = (c) => {
  if (isAtrasada(c)) return 'bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-200 border-rose-200 dark:border-rose-800'
  const e = c?.estado || 'pendiente'
  const m = {
    pendiente: 'bg-orange-50 dark:bg-orange-950/50 text-orange-600 dark:text-orange-200 border-orange-200 dark:border-orange-800',
    programado: 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-200 border-blue-200 dark:border-blue-800',
    en_proceso: 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-200 border-indigo-200 dark:border-indigo-800',
    completado: 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800',
    cancelado: 'bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-300 border-slate-200 dark:border-slate-800',
    reprogramado: 'bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-200 border-purple-200 dark:border-purple-800'
  }
  return m[e] || m.pendiente
}
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.5s ease, transform 0.5s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; transform: scale(0.95); }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }

@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes zoom-in { from { transform: scale(0.9); } to { transform: scale(1); } }
.animate-in { animation: fade-in 0.5s ease-out, zoom-in 0.5s ease-out; }
</style>
