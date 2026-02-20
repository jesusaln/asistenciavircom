<template>
  <Head title="Gestión de Vacaciones" />
  
  <div class="vacaciones-index min-h-screen bg-slate-50 dark:bg-slate-950 transition-colors duration-500 py-8 px-6 lg:px-10" :style="cssVars">
    <div class="max-w-[1600px] mx-auto">
      
      <!-- Header Premium -->
      <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 mb-12 animate-in fade-in slide-in-from-top-4 duration-700">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-lg shadow-orange-500/20">
              <FontAwesomeIcon icon="umbrella-beach" />
            </div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Gestión de Vacaciones</h1>
          </div>
          <p class="text-slate-500 dark:text-slate-400 font-medium ml-13">Administración estratégica de periodos de descanso corporativo</p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
          <Link
            :href="route('vacaciones.create')"
            class="group px-8 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-2xl flex items-center"
          >
            <FontAwesomeIcon icon="plus" class="mr-3 text-xs" />
            Nueva Solicitud
          </Link>
          
          <button
            @click="showCrearParaEmpleado = true"
            class="px-8 py-4 bg-emerald-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-xl shadow-emerald-500/20 flex items-center"
          >
            <FontAwesomeIcon icon="user-plus" class="mr-3 text-xs" />
            Asignar Directo
          </button>

          <button
            @click="irRegistro"
            :disabled="!selectedEmpleadoId"
            class="px-8 py-4 bg-indigo-500 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:scale-105 active:scale-95 transition-all shadow-xl shadow-indigo-500/20 flex items-center disabled:opacity-30 disabled:grayscale disabled:scale-100"
          >
            <FontAwesomeIcon icon="history" class="mr-3 text-xs" />
            Registro Histórico
          </button>
        </div>
      </div>

      <!-- Estadísticas Premium Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <!-- Total Card -->
        <div class="group bg-white dark:bg-slate-900/40 backdrop-blur-xl p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800/60 shadow-xl transition-all duration-300 hover:shadow-2xl">
           <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Universo de Solicitudes</div>
           <div class="flex items-end justify-between">
              <div class="text-4xl font-black text-slate-900 dark:text-white">{{ stats.total }}</div>
              <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="layer-group" size="lg" />
              </div>
           </div>
        </div>

        <!-- Pendientes Card -->
        <div class="group bg-amber-500/5 dark:bg-amber-500/10 p-8 rounded-[2rem] border border-amber-200/30 dark:border-amber-500/20 shadow-xl transition-all duration-300 hover:shadow-2xl">
           <div class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-[0.2em] mb-4">Pendientes de Veredicto</div>
           <div class="flex items-end justify-between">
              <div class="text-4xl font-black text-amber-700 dark:text-amber-400">{{ stats.pendientes }}</div>
              <div class="w-12 h-12 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="hourglass-half" size="lg" />
              </div>
           </div>
        </div>

        <!-- Aprobadas Card -->
        <div class="group bg-emerald-500/5 dark:bg-emerald-500/10 p-8 rounded-[2rem] border border-emerald-200/30 dark:border-emerald-500/20 shadow-xl transition-all duration-300 hover:shadow-2xl">
           <div class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-[0.2em] mb-4">Solicitudes Formalizadas</div>
           <div class="flex items-end justify-between">
              <div class="text-4xl font-black text-emerald-700 dark:text-emerald-400">{{ stats.aprobadas }}</div>
              <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="check-circle" size="lg" />
              </div>
           </div>
        </div>

        <!-- Rechazadas Card -->
        <div class="group bg-rose-500/5 dark:bg-rose-500/10 p-8 rounded-[2rem] border border-rose-200/30 dark:border-rose-500/20 shadow-xl transition-all duration-300 hover:shadow-2xl">
           <div class="text-[10px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-[0.2em] mb-4">Peticiones Denegadas</div>
           <div class="flex items-end justify-between">
              <div class="text-4xl font-black text-rose-700 dark:text-rose-400">{{ stats.rechazadas }}</div>
              <div class="w-12 h-12 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-600 group-hover:scale-110 transition-transform">
                <FontAwesomeIcon icon="ban" size="lg" />
              </div>
           </div>
        </div>
      </div>

      <!-- Filtros Avanzados Premium -->
      <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[2rem] border border-slate-100 dark:border-slate-800/60 p-8 mb-12 shadow-lg animate-in fade-in slide-in-from-bottom-4 duration-1000">
        <div class="flex flex-wrap items-end gap-6">
          <div class="flex-1 min-w-[250px] space-y-3">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Filtro por Colaborador</label>
            <div class="relative group">
              <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition-colors">
                 <FontAwesomeIcon icon="search" />
              </div>
              <select
                v-model="filters.empleado"
                @change="applyFilters"
                class="w-full pl-14 pr-10 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-2xl text-xs font-bold text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm"
              >
                <option value="">Todos los integrantes del equipo</option>
                <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">
                  {{ empleado.name }} — {{ empleado.puesto }}
                </option>
              </select>
            </div>
          </div>

          <div class="flex-1 min-w-[200px] space-y-3">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Filtrar por Veredicto</label>
            <select
              v-model="filters.estado"
              @change="applyFilters"
              class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-2xl text-xs font-bold text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm"
            >
              <option value="">Estados: Ver todos</option>
              <option value="pendiente">Fase de Pendencia</option>
              <option value="aprobada">Estatus: Aprobada</option>
              <option value="rechazada">Estatus: Rechazada</option>
            </select>
          </div>

          <div class="flex-1 min-w-[200px] space-y-3">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Cronología: Rango Inicial</label>
            <input
              v-model="filters.fecha_desde"
              type="date"
              @change="applyFilters"
              class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-2xl text-xs font-bold text-slate-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm"
            />
          </div>

          <div class="flex-1 min-w-[200px] space-y-3">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Cronología: Rango Final</label>
            <input
              v-model="filters.fecha_hasta"
              type="date"
              @change="applyFilters"
              class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-2xl text-xs font-bold text-slate-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all shadow-sm"
            />
          </div>

          <button
            @click="resetFilters"
            class="px-8 py-5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 hover:text-rose-500 transition-all flex items-center"
          >
            <FontAwesomeIcon icon="rotate" class="mr-3" />
            Limpiar
          </button>
        </div>
      </div>

      <!-- Tabla de Solicitudes Dark Premium -->
      <div class="bg-white dark:bg-slate-900/40 backdrop-blur-xl rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800/60 overflow-hidden animate-in fade-in duration-1000">
        <div class="px-10 py-6 border-b border-slate-100 dark:border-slate-800/60 bg-slate-50/30 dark:bg-slate-900/40 flex items-center justify-between">
          <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-[0.2em]">Registro Central de Periodos</h2>
          <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest bg-white dark:bg-slate-950 px-4 py-1.5 rounded-full border border-slate-100 dark:border-slate-800/60 shadow-sm">
            {{ vacaciones.total }} Operaciones Documentadas
          </div>
        </div>

        <div class="overflow-x-auto text-nowrap">
          <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800/40">
            <thead class="bg-slate-50/50 dark:bg-slate-950/50">
              <tr>
                <th class="px-10 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Integrante y Perfil</th>
                <th class="px-10 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Cronograma</th>
                <th class="px-10 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Impacto</th>
                <th class="px-10 py-5 text-center text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Veredicto</th>
                <th class="px-10 py-5 text-left text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Documentación</th>
                <th class="px-10 py-5 text-right text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Controles</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/20">
              <template v-if="vacaciones.data.length > 0">
                <tr
                  v-for="vacacion in vacaciones.data"
                  :key="vacacion.id"
                  class="group hover:bg-slate-50/50 dark:hover:bg-emerald-500/[0.02] transition-all duration-300"
                  :class="{'bg-emerald-500/[0.05] dark:bg-emerald-500/[0.08] ring-1 ring-inset ring-emerald-500/20': vacacion.id === Number(props.highlightId)}"
                  :ref="el => { if (vacacion.id && el) { rowRefs[String(vacacion.id)] = el } }"
                >
                  <!-- Empleado -->
                  <td class="px-10 py-6">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center border-2 border-white dark:border-slate-700 shadow-md group-hover:scale-105 transition-transform overflow-hidden">
                        <img v-if="vacacion.empleado.profile_photo_url" :src="vacacion.empleado.profile_photo_url" class="w-full h-full object-cover">
                        <span v-else class="text-xs font-black text-slate-400">{{ vacacion.empleado.name.charAt(0) }}</span>
                      </div>
                      <div class="flex flex-col gap-0.5">
                        <div class="text-sm font-black text-slate-900 dark:text-white group-hover:text-emerald-500 transition-colors uppercase tracking-tight">{{ vacacion.empleado.name }}</div>
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ vacacion.empleado.puesto }}</div>
                      </div>
                    </div>
                  </td>

                  <!-- Fechas -->
                  <td class="px-10 py-6">
                    <div class="flex flex-col gap-1.5">
                      <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded-lg uppercase tracking-widest">{{ formatDate(vacacion.fecha_inicio) }}</span>
                        <FontAwesomeIcon icon="arrow-right" class="text-[10px] text-slate-300" />
                        <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2.5 py-1 rounded-lg uppercase tracking-widest">{{ formatDate(vacacion.fecha_fin) }}</span>
                      </div>
                    </div>
                  </td>

                  <!-- Días -->
                  <td class="px-10 py-6 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-sm font-black text-slate-900 dark:text-white shadow-inner">
                      {{ vacacion.dias_solicitados }}
                    </div>
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-2 font-mono">Días Laborales</div>
                  </td>

                  <!-- Estado -->
                  <td class="px-10 py-6 text-center">
                    <div
                      :class="getEstadoClasses(vacacion.estado)"
                      class="inline-flex items-center px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest transition-all duration-300"
                    >
                      <span
                        class="w-2 h-2 rounded-full mr-2.5 animate-pulse"
                        :class="getEstadoDotColor(vacacion.estado)"
                      ></span>
                      {{ getEstadoLabel(vacacion.estado) }}
                    </div>
                  </td>

                  <!-- Motivo -->
                  <td class="px-10 py-6">
                    <div class="text-xs font-medium text-slate-600 dark:text-slate-400 italic max-w-xs truncate" :title="vacacion.motivo">
                      {{ vacacion.motivo || 'Sin justificación documentada' }}
                    </div>
                    <Link :href="route('registro-vacaciones.por-empleado', vacacion.user_id)" class="text-[9px] font-black text-indigo-500 uppercase tracking-[0.2em] hover:underline mt-1 block">Ver Saldo Global →</Link>
                  </td>

                  <!-- Acciones -->
                  <td class="px-10 py-6 text-right">
                    <div class="flex items-center justify-end gap-3">
                      <Link
                        :href="route('vacaciones.show', vacacion.id)"
                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-emerald-500 hover:text-white transition-all shadow-sm"
                        title="Auditar Detalles"
                      >
                        <FontAwesomeIcon icon="folder-open" class="text-xs" />
                      </Link>

                      <button
                        v-if="vacacion.estado === 'pendiente'"
                        @click="aprobarVacacion(vacacion)"
                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-emerald-600 hover:text-white transition-all shadow-sm"
                        title="Ratificar Aprobación"
                      >
                        <FontAwesomeIcon icon="check" class="text-xs" />
                      </button>

                      <button
                        v-if="vacacion.estado === 'pendiente'"
                        @click="rechazarVacacion(vacacion)"
                        class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-400 hover:bg-rose-500 hover:text-white transition-all shadow-sm"
                        title="Declinar Petición"
                      >
                        <FontAwesomeIcon icon="times" class="text-xs" />
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- Empty State Premium -->
              <tr v-else>
                <td colspan="6" class="px-10 py-40 text-center">
                  <div class="flex flex-col items-center">
                    <div class="w-24 h-24 rounded-[2.5rem] bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-5xl mb-8 grayscale opacity-40 transform -rotate-6">
                       🏖️
                    </div>
                    <p class="text-slate-500 dark:text-slate-400 font-black uppercase tracking-[0.3em] text-sm">Sin registros de descanso encontrados</p>
                    <Link :href="route('vacaciones.create')" class="mt-10 text-emerald-500 font-black text-[11px] uppercase tracking-[0.2em] border-b-2 border-emerald-500/20 hover:border-emerald-500 transition-all pb-1">Iniciar nueva gestión de periodo →</Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación Premium -->
        <div v-if="vacaciones.last_page > 1" class="px-10 py-8 bg-slate-50/50 dark:bg-slate-950/50 border-t border-slate-100 dark:border-slate-800/40 flex flex-col md:flex-row justify-between items-center gap-8 font-mono">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                Expediente <span class="text-slate-900 dark:text-white">{{ vacaciones.current_page }}</span> de <span class="text-slate-900 dark:text-white">{{ vacaciones.last_page }}</span> • {{ vacaciones.total }} resultados totales
            </span>
            
            <div class="flex gap-2">
              <button
                @click="changePage(vacaciones.current_page - 1)"
                :disabled="vacaciones.current_page === 1"
                class="px-6 py-3 text-[10px] font-black rounded-xl bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-30 uppercase tracking-widest transition-all shadow-sm"
              >
                Anterior
              </button>

              <div class="flex gap-1.5">
                <button
                  v-for="page in getPageNumbers()"
                  :key="page"
                  @click="changePage(page)"
                  :class="[
                    'w-10 h-10 flex items-center justify-center text-[11px] font-black rounded-xl transition-all',
                    page === vacaciones.current_page
                      ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900 shadow-xl'
                      : 'bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-50'
                  ]"
                >
                  {{ page }}
                </button>
              </div>

              <button
                @click="changePage(vacaciones.current_page + 1)"
                :disabled="vacaciones.current_page === vacaciones.last_page"
                class="px-6 py-3 text-[10px] font-black rounded-xl bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-30 uppercase tracking-widest transition-all shadow-sm"
              >
                Siguiente
              </button>
            </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Botón flotante Premium para Ajustes Rápidos -->
  <button
    @click="showAjuste = true"
    class="fixed bottom-10 right-10 group w-16 h-16 bg-indigo-600 text-white rounded-[1.5rem] shadow-2xl shadow-indigo-600/40 hover:scale-110 active:scale-95 transition-all duration-300 flex items-center justify-center z-[40]"
    title="Ajuste Rápido de Saldo"
  >
    <FontAwesomeIcon icon="sliders" />
    <span class="absolute right-full mr-4 px-4 py-2 bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl opacity-0 translate-x-4 group-hover:opacity-100 group-hover:translate-x-0 transition-all pointer-events-none whitespace-nowrap">Ajuste de Saldo</span>
  </button>

  <!-- Modal: Crear para Empleado (Premium) -->
  <Transition name="modal">
    <div v-if="showCrearParaEmpleado" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center z-[100] p-6" @click.self="showCrearParaEmpleado = false">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-[0_0_100px_rgba(0,0,0,0.3)] w-full max-w-lg border border-slate-100 dark:border-slate-800/60 overflow-hidden animate-in fade-in zoom-in-95 duration-300">
        <div class="flex items-center justify-between px-10 py-8 border-b border-slate-100 dark:border-slate-800/60">
          <div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Asignación Directa</h3>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Selecciona el perfil de destino</p>
          </div>
          <button @click="showCrearParaEmpleado = false" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
            <FontAwesomeIcon icon="times" size="lg" />
          </button>
        </div>

        <div class="p-10 space-y-8">
          <div class="space-y-3">
            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Colaborador en Sistema</label>
            <div class="relative group">
              <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition-colors">
                 <FontAwesomeIcon icon="user-tie" />
              </div>
              <select
                v-model="empleadoSeleccionado"
                class="w-full pl-16 pr-10 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-2xl text-xs font-bold text-slate-900 dark:text-white appearance-none cursor-pointer focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all"
              >
                <option value="">Elegir perfil corporativo...</option>
                <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">
                  {{ empleado.name }}
                </option>
              </select>
            </div>
          </div>

          <button
            @click="crearParaEmpleado"
            :disabled="!empleadoSeleccionado"
            class="w-full py-5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl disabled:opacity-30 disabled:grayscale"
          >
            Continuar con la Gestión
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Modal: Ajuste de Días (Premium) -->
  <Transition name="modal">
    <div v-if="showAjuste" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md flex items-center justify-center z-[101] p-6" @click.self="closeAjuste">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-[0_0_100px_rgba(0,0,0,0.3)] w-full max-w-lg border border-slate-100 dark:border-slate-800/60 overflow-hidden animate-in fade-in zoom-in-95 duration-300">
        <div class="flex items-center justify-between px-10 py-8 border-b border-slate-100 dark:border-slate-800/60 font-black">
          <div>
            <h3 class="text-xl text-slate-900 dark:text-white uppercase tracking-tight">Corrección de Saldo</h3>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-1">Ajuste manual de registros históricos</p>
          </div>
          <button @click="closeAjuste" class="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors text-lg">
            <FontAwesomeIcon icon="times" />
          </button>
        </div>
        
        <div class="p-10 space-y-8">
          <div class="space-y-6">
            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Sujeto del Ajuste</label>
              <select v-model="ajuste.empleadoId" class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-2xl text-xs font-bold text-slate-900 dark:text-white appearance-none transition-all focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 cursor-pointer">
                <option value="">Elegir colaborador...</option>
                <option v-for="empleado in empleados" :key="empleado.id" :value="empleado.id">{{ empleado.name }}</option>
              </select>
            </div>
            
            <div class="grid grid-cols-2 gap-6">
              <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Periodo/Año</label>
                <input type="number" v-model.number="ajuste.anio" class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-2xl text-xs font-bold text-slate-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500" />
              </div>
              <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Delta Días (+/-)</label>
                <input type="number" v-model.number="ajuste.dias" class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-2xl text-xs font-bold text-slate-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500" />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-4">Justificación Técnica</label>
              <textarea v-model="ajuste.motivo" placeholder="Explica el motivo del ajuste..." rows="3" class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800/60 rounded-2xl text-xs font-bold text-slate-900 dark:text-white focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 resize-none"></textarea>
            </div>
          </div>

          <div v-if="ajusteError" class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-2xl text-rose-600 text-[10px] font-black uppercase text-center tracking-widest">{{ ajusteError }}</div>

          <div class="flex gap-4">
            <button @click="closeAjuste" class="flex-1 py-5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-slate-200 transition-all">Cancelar</button>
            <button @click="submitAjuste" :disabled="ajusteLoading" class="flex-1 py-5 bg-indigo-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl shadow-indigo-600/20 disabled:opacity-30">
              {{ ajusteLoading ? 'Ejecutando...' : 'Aplicar Ajuste' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import { Notyf } from 'notyf'
import 'notyf/notyf.min.css'
import AppLayout from '@/Layouts/AppLayout.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { useCompanyColors } from '@/Composables/useCompanyColors'

defineOptions({
  layout: AppLayout,
  inheritAttrs: false
})

const props = defineProps({
  vacaciones: Object,
  stats: Object,
  empleados: Array,
  filters: Object,
  sorting: Object,
  highlightId: [Number, String]
})

const { cssVars } = useCompanyColors()

const notyf = new Notyf({
  duration: 4000,
  position: { x: 'right', y: 'top' },
  types: [
    { type: 'success', background: '#10b981', icon: false },
    { type: 'error', background: '#ef4444', icon: false }
  ]
})

const filters = ref({
  empleado: props.filters?.empleado || '',
  estado: props.filters?.estado || '',
  fecha_desde: props.filters?.fecha_desde || '',
  fecha_hasta: props.filters?.fecha_hasta || '',
})

const resetFilters = () => {
    filters.value = { empleado: '', estado: '', fecha_desde: '', fecha_hasta: '' }
    applyFilters()
}

const showCrearParaEmpleado = ref(false)
const empleadoSeleccionado = ref('')
const selectedEmpleadoId = computed(() => empleadoSeleccionado.value || filters.value.empleado)

const irRegistro = () => {
  if (selectedEmpleadoId.value) {
    router.visit(route('registro-vacaciones.por-empleado', selectedEmpleadoId.value))
  }
}

const rowRefs = ref({})
onMounted(() => {
  if (props.highlightId) {
    nextTick(() => {
      const id = String(props.highlightId)
      const el = rowRefs.value[id]
      if (el && el.scrollIntoView) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }
    })
  }
})

const showAjuste = ref(false)
const ajusteLoading = ref(false)
const ajusteError = ref('')
const ajuste = ref({
  empleadoId: '',
  anio: new Date().getFullYear(),
  dias: 1,
  motivo: ''
})

const closeAjuste = () => {
  showAjuste.value = false
  ajusteLoading.value = false
  ajusteError.value = ''
  ajuste.value = { empleadoId: '', anio: new Date().getFullYear(), dias: 1, motivo: '' }
}

const submitAjuste = async () => {
  ajusteError.value = ''
  const a = ajuste.value
  if (!a.empleadoId) {
    ajusteError.value = 'Seleccione un empleado'
    return
  }
  ajusteLoading.value = true
  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    const res = await fetch(route('registro-vacaciones.ajustar', a.empleadoId), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: JSON.stringify({ dias: a.dias, anio: a.anio, motivo: a.motivo })
    })
    if (!res.ok) {
      const data = await res.json().catch(() => ({}))
      throw new Error(data?.message || 'No se pudo aplicar el ajuste')
    }
    closeAjuste()
    notyf.success('Ajuste aplicado correctamente')
    router.reload({ only: ['vacaciones', 'stats'] })
  } catch (e) {
    ajusteError.value = e.message
    notyf.error(ajusteError.value)
  } finally {
    ajusteLoading.value = false
  }
}

const applyFilters = () => {
  router.get(route('vacaciones.index'), {
    ...filters.value,
    page: 1
  }, { preserveState: true, preserveScroll: true })
}

const aprobarVacacion = (vacacion) => {
  if (confirm('¿Ratificar aprobación de este periodo vacacional?')) {
    router.post(route('vacaciones.aprobar', vacacion.id), {
      observaciones: ''
    }, {
      onSuccess: () => notyf.success('Vacaciones ratificadas exitosamente'),
      onError: () => notyf.error('Error al procesar la aprobación')
    })
  }
}

const rechazarVacacion = (vacacion) => {
  const observaciones = prompt('Justificación del rechazo operacional:')
  if (observaciones !== null && confirm('¿Confirmar declinación de la solicitud?')) {
    router.post(route('vacaciones.rechazar', vacacion.id), {
      observaciones: observaciones || ''
    }, {
      onSuccess: () => notyf.success('Petición declinada correctamente'),
      onError: () => notyf.error('Error al procesar la declinación')
    })
  }
}

const formatDate = (date) => {
  if (!date) return '—'
  try {
    return new Date(date).toLocaleDateString('es-MX', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    }).replace('.', '')
  } catch { return 'Inválida' }
}

const getEstadoClasses = (estado) => {
  const classes = {
    'pendiente': 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20',
    'aprobada': 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20',
    'rechazada': 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20',
  }
  return classes[estado] || 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200'
}

const getEstadoDotColor = (estado) => {
  const dots = {
    'pendiente': 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]',
    'aprobada': 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]',
    'rechazada': 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.5)]'
  }
  return dots[estado] || 'bg-slate-400'
}

const getEstadoLabel = (estado) => {
  const labels = {
    'pendiente': 'Fase Pendiente',
    'aprobada': 'Ratificada',
    'rechazada': 'Declinada'
  }
  return labels[estado] || 'Estado Indefinido'
}

const getPageNumbers = () => {
  const currentPage = props.vacaciones.current_page
  const lastPage = props.vacaciones.last_page
  const pages = []
  for (let i = Math.max(1, currentPage - 2); i <= Math.min(lastPage, currentPage + 2); i++) {
    pages.push(i)
  }
  return pages
}

const changePage = (page) => {
  router.get(route('vacaciones.index'), {
    ...filters.value,
    page: page
  }, { preserveState: true, preserveScroll: true })
}

const crearParaEmpleado = () => {
  if (empleadoSeleccionado.value) {
    router.visit(route('vacaciones.create-para-empleado', empleadoSeleccionado.value))
    showCrearParaEmpleado.value = false
    empleadoSeleccionado.value = ''
  }
}
</script>

<style scoped>
.vacaciones-index {
  min-height: 100vh;
}

select {
  background-image: none !important;
}

.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
</style>
