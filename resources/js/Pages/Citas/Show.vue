<template>
  <Head title="Ver Cita" />
  <div class="citas-show min-h-screen bg-gray-50 dark:bg-slate-950 dark:bg-gray-900 transition-colors duration-300">
    <!-- Header Premium -->
    <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 shadow-sm transition-colors sticky top-0 z-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
          <div class="flex items-center gap-4">
            <Link :href="route('citas.index')" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-slate-950 dark:bg-gray-700 text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-white transition-all">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </Link>
            <div>
              <h1 class="text-xl font-black text-gray-900 dark:text-white dark:text-white transition-colors">Detalles de la Cita <span class="text-blue-600 dark:text-blue-400">#{{ cita.id }}</span></h1>
              <div class="flex items-center gap-2 mt-0.5">
                <span :class="obtenerEstadoCitaClase(cita.estado)" class="px-2 py-0.5 rounded-lg text-[10px] font-black uppercase tracking-wider border transition-colors">
                  {{ obtenerEstadoCitaLabel(cita.estado) }}
                </span>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ formatearFechaHora(cita.fecha_hora) }}</span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-3">
             <Link :href="route('citas.edit', cita.id)" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-amber-200 dark:shadow-none active:scale-95 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Editar
             </Link>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Columna Izquierda: Información Principal -->
        <div class="lg:col-span-2 space-y-8">
          <!-- Card Programación del Servicio (NUEVO REDISEÑO) -->
          <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
            <div class="p-8">
              <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </div>
                  <div>
                    <h2 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">Programación del Servicio</h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Control de tiempos y logística</p>
                  </div>
                </div>
                <!-- Badge de Duración Total (si ya terminó) -->
                <div v-if="cita.tiempo_servicio" class="px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-100 dark:border-emerald-800/50">
                   <p class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest text-center">Duración Final</p>
                   <p class="text-sm font-black text-emerald-700 dark:text-emerald-300">{{ cita.tiempo_servicio_formateado }}</p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <!-- Izquierda: Tiempos -->
                <div class="space-y-6">
                  <div class="relative pl-8 border-l-2 border-dashed border-gray-100 dark:border-gray-700 space-y-8">
                    <!-- Punto Inicio -->
                    <div class="relative">
                      <div class="absolute -left-[41px] top-0 w-4 h-4 rounded-full bg-blue-600 border-4 border-white dark:border-slate-900 shadow-sm"></div>
                      <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Inicio Programado</label>
                      <p class="text-lg font-black text-gray-900 dark:text-white">{{ formatearSoloHora(cita.fecha_hora) }} <span class="text-xs font-bold text-gray-400 ml-2">{{ formatearSoloFecha(cita.fecha_hora) }}</span></p>
                    </div>

                    <!-- Punto Fin -->
                    <div class="relative">
                      <div class="absolute -left-[41px] top-0 w-4 h-4 rounded-full bg-indigo-400 border-4 border-white dark:border-slate-900 shadow-sm"></div>
                      <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1">Término Estimado</label>
                      <p class="text-lg font-black text-gray-900 dark:text-white">{{ formatearSoloHora(cita.fecha_fin_estimada) }} <span class="text-xs font-bold text-gray-400 ml-2">{{ formatearSoloFecha(cita.fecha_fin_estimada) }}</span></p>
                    </div>

                    <!-- Tiempos Reales (Si existen) -->
                    <div v-if="cita.inicio_servicio" class="pt-4 border-t border-gray-50 dark:border-gray-700 mt-4 space-y-4">
                       <div class="flex items-center justify-between">
                          <span class="text-[9px] font-bold text-gray-400 uppercase">Inicio Real</span>
                          <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">{{ formatearSoloHora(cita.inicio_servicio) }}</span>
                       </div>
                       <div v-if="cita.fin_servicio" class="flex items-center justify-between">
                          <span class="text-[9px] font-bold text-gray-400 uppercase">Fin Real</span>
                          <span class="text-xs font-black text-indigo-600 dark:text-indigo-400">{{ formatearSoloHora(cita.fin_servicio) }}</span>
                       </div>
                    </div>
                  </div>
                </div>

                <!-- Derecha: Datos de Asignación -->
                <div class="space-y-6">
                  <div>
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Técnico Asignado</label>
                    <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-slate-950 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                      <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-black text-lg">
                        {{ getInitials(tecnicoNombre) }}
                      </div>
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-black text-gray-900 dark:text-white uppercase truncate">{{ tecnicoNombre }}</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-tighter">Especialista Asignado</p>
                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-3">Tipo de Asistencia</label>
                    <div class="px-5 py-4 bg-blue-50 dark:bg-blue-900/10 rounded-2xl border border-blue-100 dark:border-blue-800/30 flex items-center gap-3">
                      <div class="w-2 h-2 rounded-full bg-blue-600"></div>
                      <span class="text-sm font-black text-blue-700 dark:text-blue-400 uppercase tracking-tight">{{ formatearTipoServicio(cita.tipo_servicio) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card Cliente -->
          <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
            <div class="p-8">
              <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                  <h2 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">Datos del Cliente</h2>
                  <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Información de contacto y ubicación</p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                 <div class="flex items-center gap-5 p-5 bg-gray-50 dark:bg-slate-950 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <div class="w-14 h-14 rounded-full bg-white dark:bg-slate-900 border-4 border-emerald-100 dark:border-emerald-900/50 flex items-center justify-center text-2xl">👤</div>
                    <div>
                       <p class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-0.5">Nombre / Razón Social</p>
                       <p class="text-lg font-black text-gray-900 dark:text-white tracking-tight uppercase leading-none">{{ clienteNombre }}</p>
                       <p class="text-sm text-emerald-600 dark:text-emerald-400 font-bold mt-1">{{ cita.cliente?.telefono || 'Sin teléfono registrado' }}</p>
                    </div>
                 </div>
                 <div class="p-5 bg-amber-50/30 dark:bg-amber-900/10 rounded-2xl border border-amber-100/50 dark:border-amber-900/20">
                    <p class="text-[10px] font-black text-amber-600 dark:text-amber-500 uppercase tracking-widest mb-2 flex items-center gap-2">
                       <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                       Ubicación del Servicio
                    </p>
                    <p class="text-sm font-bold text-gray-700 dark:text-gray-200 leading-relaxed">{{ cita.direccion_servicio || 'No se proporcionó una dirección específica.' }}</p>
                 </div>
              </div>
            </div>
          </div>

          <!-- Card Equipo (ESTILIZADO) -->
          <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
            <div class="p-8">
              <div class="flex items-center gap-4 mb-8">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </div>
                <div>
                  <h2 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">Detalles Técnicos</h2>
                  <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Especificaciones del equipo reportado</p>
                </div>
              </div>

              <div class="grid grid-cols-2 md:grid-cols-4 gap-6 bg-gray-50/50 dark:bg-gray-950/20 p-6 rounded-3xl border border-gray-100 dark:border-gray-800">
                 <div>
                   <label class="block text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Equipo</label>
                   <p class="text-xs font-black text-gray-900 dark:text-white uppercase">{{ formatearTipoEquipo(cita.tipo_equipo) }}</p>
                 </div>
                 <div>
                   <label class="block text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Marca</label>
                   <p class="text-xs font-black text-gray-900 dark:text-white uppercase">{{ cita.marca_equipo || 'N/A' }}</p>
                 </div>
                 <div>
                   <label class="block text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Modelo</label>
                   <p class="text-xs font-black text-gray-900 dark:text-white uppercase">{{ cita.modelo_equipo || 'N/A' }}</p>
                 </div>
                 <div>
                   <label class="block text-[9px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-1.5">Estado</label>
                   <p class="text-[10px] font-black text-amber-600 dark:text-amber-500 uppercase bg-amber-50 dark:bg-amber-900/20 px-2 py-0.5 rounded-lg inline-block">REPORTADO</p>
                 </div>
              </div>

              <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Problema Reportado</label>
                  <div class="p-5 bg-red-50/50 dark:bg-red-900/10 rounded-2xl border border-red-100/50 dark:border-red-900/20 text-sm font-bold text-red-700 dark:text-red-400">
                    {{ cita.problema_reportado || 'Sin reporte de falla específico' }}
                  </div>
                </div>
                <div>
                  <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Descripción General</label>
                  <div class="p-5 bg-blue-50/50 dark:bg-blue-900/10 rounded-2xl border border-blue-100/50 dark:border-blue-900/20 text-sm font-medium text-gray-700 dark:text-gray-300 italic">
                    "{{ cita.descripcion || 'Sin descripción detallada' }}"
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card Reporte y Evidencias -->
          <div v-if="cita.trabajo_realizado || cita.fotos_finales" class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
             <div class="p-8">
                <div class="flex items-center gap-2 mb-6">
                  <div class="w-1.5 h-6 bg-green-500 rounded-full"></div>
                  <h2 class="text-sm font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-widest">Reporte de Servicio Terminado</h2>
                </div>

                <div v-if="cita.trabajo_realizado" class="mb-8">
                   <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Trabajo Realizado</label>
                   <div class="p-6 bg-green-50/50 dark:bg-green-900/10 rounded-2xl border border-green-100/50 dark:border-green-900/20 text-sm font-medium text-gray-700 dark:text-gray-200 italic leading-relaxed transition-colors">
                      "{{ cita.trabajo_realizado }}"
                   </div>
                </div>

                <div>
                   <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Evidencias Fotográficas</label>
                   <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                      <!-- Imagen 1 -->
                      <div v-if="cita.foto_equipo" class="aspect-square rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-700 border border-gray-100 dark:border-gray-700 group cursor-pointer shadow-sm relative transition-all hover:scale-[1.03]" @click="openGallery([cita.foto_equipo], 'Equipo')">
                        <img :src="cita.foto_equipo" class="w-full h-full object-cover transition-transform group-hover:scale-110">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-2 text-center">
                           <span class="text-[10px] font-black text-white uppercase tracking-widest">EQUIPO</span>
                           <svg class="w-5 h-5 text-white mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                      </div>
                      
                      <!-- Imagen 2 -->
                      <div v-if="cita.foto_hoja_servicio" class="aspect-square rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-700 border border-gray-100 dark:border-gray-700 group cursor-pointer shadow-sm relative transition-all hover:scale-[1.03]" @click="openGallery([cita.foto_hoja_servicio], 'Hoja de Servicio')">
                        <img :src="cita.foto_hoja_servicio" class="w-full h-full object-cover transition-transform group-hover:scale-110">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-2 text-center">
                           <span class="text-[10px] font-black text-white uppercase tracking-widest">HOJA SERVICIO</span>
                           <svg class="w-5 h-5 text-white mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                      </div>

                      <!-- Fotos Finales -->
                      <template v-if="cita.fotos_finales">
                         <div v-for="(foto, idx) in cita.fotos_finales" :key="idx" class="aspect-square rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-700 border border-gray-100 dark:border-gray-700 group cursor-pointer shadow-sm relative transition-all hover:scale-[1.03]" @click="openGallery([foto], 'Evidencia')">
                            <img :src="'/storage/' + foto" class="w-full h-full object-cover transition-transform group-hover:scale-110">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-2 text-center">
                               <span class="text-[10px] font-black text-white uppercase tracking-widest">EVIDENCIA #{{ idx + 1 }}</span>
                            </div>
                         </div>
                      </template>
                   </div>
                </div>
             </div>
          </div>
        </div>

        <!-- Columna Derecha: Sidebar con Acciones y Totales -->
        <div class="space-y-8">
           <!-- Card Acciones de Estado -->
           <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
             <div class="p-6">
                <div class="flex items-center gap-2 mb-6">
                  <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                  <h2 class="text-sm font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-widest">Acciones Rápidas</h2>
                </div>

                <div class="space-y-3">
                   <button v-if="cita.estado === 'pendiente'" @click="cambiarEstado('en_proceso')" class="w-full py-4 bg-amber-500 hover:bg-amber-600 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-amber-200 dark:shadow-none active:scale-95 flex items-center justify-center gap-2">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                     Iniciar Cita
                   </button>
                   
                   <button v-if="cita.estado === 'en_proceso'" @click="cambiarEstado('completado')" class="w-full py-4 bg-green-600 hover:bg-green-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-green-200 dark:shadow-none active:scale-95 flex items-center justify-center gap-2">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                     Finalizar Cita
                   </button>

                   <div v-if="cita.items?.length > 0 && cita.estado === 'completado' && !cita.venta" class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
                      <Link :href="route('ventas.create', { cita_id: cita.id })" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-blue-200 dark:shadow-none active:scale-95 flex items-center justify-center gap-2">
                        <span>💰</span> Generar Cobro
                      </Link>
                      <p class="text-[10px] text-gray-400 text-center mt-2 font-bold uppercase tracking-tighter italic">Carga los cargos extra a la cuenta del cliente</p>
                   </div>

                   <button v-if="['pendiente', 'en_proceso', 'programado'].includes(cita.estado)" @click="cambiarEstado('cancelado')" class="w-full py-4 bg-white dark:bg-slate-900 dark:bg-gray-900/50 text-red-600 border border-red-100 dark:border-red-900/30 hover:bg-red-50 dark:hover:bg-red-900/10 text-xs font-black uppercase tracking-widest rounded-2xl transition-all active:scale-95 flex items-center justify-center gap-2">
                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                     Cancelar Cita
                   </button>
                </div>
             </div>
           </div>

           <!-- Card Venta Relacionada -->
           <div v-if="cita.venta" class="bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 rounded-3xl shadow-xl shadow-green-200/50 dark:shadow-none p-6 text-white text-center group transition-transform hover:scale-[1.02]">
              <div class="w-14 h-14 bg-white dark:bg-slate-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4 backdrop-blur-md">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
              <h3 class="text-xs font-black uppercase tracking-widest opacity-80 mb-1">Cita Liquidada</h3>
              <p class="text-xl font-black mb-4">Venta #{{ cita.venta.numero_venta }}</p>
              <Link :href="route('ventas.show', cita.venta.id)" class="inline-block py-2.5 px-6 bg-white dark:bg-slate-900 text-green-600 text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 transition-all active:scale-95">
                Ver Detalles de Pago
              </Link>
           </div>

           <!-- Card Resumen Financiero -->
           <div v-if="cita.items?.length > 0" class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden transition-colors">
              <div class="p-6">
                <div class="flex items-center gap-2 mb-6">
                  <div class="w-1.5 h-6 bg-green-600 rounded-full"></div>
                  <h2 class="text-sm font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-widest">Resumen de Cargos</h2>
                </div>

                <div class="space-y-4 mb-6">
                   <div v-for="item in cita.items" :key="item.id" class="flex justify-between items-start gap-4 pb-4 border-b border-gray-50 dark:border-gray-700 last:border-0">
                      <div>
                        <p class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase">{{ item.citable?.nombre || 'Item' }}</p>
                        <p class="text-[9px] font-bold text-gray-400 uppercase">{{ item.cantidad }} x ${{ item.precio }}</p>
                      </div>
                      <span class="text-sm font-black text-gray-900 dark:text-white dark:text-white">${{ (item.cantidad * item.precio).toFixed(2) }}</span>
                   </div>
                </div>

                <div class="space-y-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                   <div class="flex justify-between text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400">
                     <span>SUBTOTAL</span>
                     <span>${{ formatearPrecio(cita.subtotal) }}</span>
                   </div>
                   <div class="flex justify-between text-xs font-bold text-gray-500 dark:text-gray-400 dark:text-gray-400">
                     <span>IVA (16%)</span>
                     <span>${{ formatearPrecio(cita.iva) }}</span>
                   </div>
                   <div class="flex justify-between items-center pt-2 mt-2 border-t-2 border-dashed border-gray-100 dark:border-gray-700">
                      <span class="text-xs font-black text-gray-900 dark:text-white dark:text-white">TOTAL</span>
                      <span class="text-2xl font-black text-blue-600 dark:text-blue-400">${{ formatearPrecio(cita.total) }}</span>
                   </div>
                </div>
              </div>
           </div>
        </div>
      </div>
    </div>

    <!-- Modal de Galería de Fotos -->
    <Transition name="modal">
      <div v-if="showGalleryModal" class="fixed inset-0 bg-black/95 z-[60] flex flex-col" @click.self="closeGallery">
        <div class="flex justify-between items-center p-6 text-white bg-gradient-to-b from-black/80 to-transparent">
           <div class="flex items-center gap-4">
             <div>
               <p class="text-sm font-bold uppercase tracking-widest text-white/70">{{ imageTitle }}</p>
               <p class="text-[10px] text-white/50">Visualización de Evidencia</p>
             </div>
           </div>
           <button @click="closeGallery" class="w-12 h-12 flex items-center justify-center bg-white dark:bg-slate-900/10 hover:bg-white dark:bg-slate-900/20 rounded-full transition-all backdrop-blur-md">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
           </button>
        </div>
        <div class="flex-1 flex items-center justify-center relative p-4">
           <img :src="galleryImages[0]" class="max-h-[85vh] max-w-[90vw] object-contain rounded-2xl shadow-2xl animate-in fade-in zoom-in-95" :key="galleryImages[0]">
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

defineOptions({ layout: AppLayout });

const props = defineProps({
  cita: Object,
  tecnicos: Array,
  clientes: Array,
});

// Estado Galería
const showGalleryModal = ref(false);
const galleryImages = ref([]);
const imageTitle = ref('');

const openGallery = (images, title) => {
  if (!images || images.length === 0) return;
  galleryImages.value = images.map(img => img.startsWith('/') || img.startsWith('http') ? img : '/storage/' + img);
  imageTitle.value = title;
  showGalleryModal.value = true;
};

const closeGallery = () => { showGalleryModal.value = false; };

const clienteNombre = computed(() => props.cita.cliente?.nombre_razon_social || 'Cliente desconocido');
const tecnicoNombre = computed(() => props.cita.tecnico?.name || 'Varios técnicos / Sin asignar');

const getInitials = (name) => {
  if (!name) return '?';
  const parts = name.split(' ');
  return (parts.length > 1 ? parts[0][0] + parts[1][0] : parts[0][0]).toUpperCase();
};

const formatearTipoServicio = (tipo) => {
  const tipos = { instalacion: 'Instalación', diagnostico: 'Diagnóstico', reparacion: 'Reparación', garantia: 'Garantía', mantenimiento: 'Mantenimiento', otro: 'Otro' };
  return tipos[tipo] || tipo || 'Desconocido';
};

const formatearTipoEquipo = (tipo) => {
  const tipos = { minisplit: 'Minisplit', boiler: 'Boiler', refrigerador: 'Refrigerador', lavadora: 'Lavadora', secadora: 'Secadora', estufa: 'Estufa', campana: 'Campana' };
  return tipos[tipo] || tipo || 'Equipo Especializado';
};

const obtenerEstadoCitaLabel = (estado) => {
  const labels = { pendiente: 'Pendiente', en_proceso: 'En Proceso', completado: 'Finalizada', cancelado: 'Cancelada', programado: 'Programada', reprogramado: 'Reprogramada' };
  return labels[estado] || estado || 'Desconocido';
};

const obtenerEstadoCitaClase = (estado) => {
  const clases = {
    pendiente: 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-yellow-100 dark:border-yellow-900/30',
    en_proceso: 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/30',
    completado: 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-100 dark:border-green-900/30',
    cancelado: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-100 dark:border-red-900/30'
  };
  return clases[estado] || 'bg-gray-50 dark:bg-slate-950 dark:bg-gray-900/20 text-gray-700 dark:text-gray-400 border-gray-100 dark:border-gray-900/30';
};

const formatearFechaHora = (fh) => fh ? new Date(fh).toLocaleString('es-MX', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
const formatearSoloHora = (fh) => fh ? new Date(fh).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: true }) : '—';
const formatearSoloFecha = (fh) => fh ? new Date(fh).toLocaleDateString('es-MX', { day: '2-digit', month: 'short' }) : '—';
const formatearPrecio = (p) => parseFloat(p || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const cambiarEstado = async (nuevoEstado) => {
  const result = await Swal.fire({
    title: '¿Confirmar cambio?',
    text: `Se actualizará el estado a "${obtenerEstadoCitaLabel(nuevoEstado)}"`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3b82f6',
    confirmButtonText: 'Sí, confirmar'
  });
  if (!result.isConfirmed) return;
  try {
    const response = await axios.post(`/citas/${props.cita.id}/cambiar-estado`, { estado: nuevoEstado });
    if (response.data.success) window.location.reload();
  } catch (error) {
    Swal.fire('Error', 'No se pudo actualizar el estado', 'error');
  }
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
.modal-enter-from, .modal-leave-to { opacity: 0; }

.animate-in { animation: fade-in 0.3s ease-out, zoom-in-95 0.3s ease-out; }
@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes zoom-in-95 { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>
