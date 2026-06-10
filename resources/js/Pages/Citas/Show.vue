<template>
  <Head title="Ver Cita" />
  <div class="citas-show min-h-screen bg-[var(--ui-surface)] transition-colors duration-200">
    <!-- Header Premium -->
    <div class="bg-white dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700 shadow-sm transition-colors sticky top-0 z-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
          <div class="flex items-center gap-4">
            <Link :href="route('citas.index')" class="w-10 h-10 flex items-center justify-center rounded-xl bg-[var(--ui-surface)] dark:bg-slate-700 text-slate-400 hover:text-brand-600 dark:hover:text-white transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </Link>
            <div>
              <h1 class="text-2xl font-black text-slate-900 dark:text-white transition-colors">Detalles de la Cita <span class="text-blue-600 dark:text-blue-400">#{{ cita.id }}</span></h1>
              <div class="flex flex-wrap items-center gap-2 mt-0.5">
                <span :class="obtenerEstadoCitaClase(cita.estado)" class="px-2 py-0.5 rounded-xl text-[10px] font-black uppercase tracking-wider border transition-colors">
                  {{ obtenerEstadoCitaLabel(cita.estado) }}
                </span>
                <span v-if="cita.folio" class="px-2 py-0.5 rounded-xl text-[10px] font-black font-mono tracking-tight bg-brand-50 dark:bg-brand-900/20 text-brand-800 dark:text-brand-200 dark:text-brand-200 border border-brand-200 dark:border-brand-800/30" title="Folio de tienda o interno">Folio {{ cita.folio }}</span>
                <span v-if="cita.tipo_equipo" class="px-2 py-0.5 rounded-xl text-[10px] font-black uppercase tracking-wider bg-indigo-50 dark:bg-sky-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800/50">
                  {{ formatearTipoEquipo(cita.tipo_equipo) }} {{ cita.marca_equipo ? ' - ' + cita.marca_equipo : '' }}
                </span>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">{{ formatearFechaHora(cita.fecha_hora) }}</span>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
             <Link :href="route('todos.index', { cita_id: cita.id })" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-white text-xs font-black uppercase tracking-wide rounded-xl transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                Crear Pendiente
             </Link>
             <Link :href="route('citas.edit', cita.id)" class="px-5 py-2.5 bg-brand-500 hover:bg-brand-600 text-white text-xs font-black uppercase tracking-wide rounded-xl transition-all shadow-xl shadow-brand-200 dark:shadow-none active:scale-95 flex items-center gap-2">
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
        <div class="lg:col-span-2 space-y-6">
          <!-- Card Cliente y Servicio -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors">
            <div class="p-8">
              <div class="flex items-center gap-2 mb-6">
                <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Información General</h2>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Cliente -->
                <div class="space-y-6">
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Cliente</label>
                    <div class="flex items-center gap-4 p-4 bg-[var(--ui-surface)]/50 rounded-2xl border border-slate-100 dark:border-slate-700 transition-colors">
                      <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-sky-900/20/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-black text-lg">
                        {{ getInitials(clienteNombre) }}
                      </div>
                      <div>
                        <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ clienteNombre }}</p>
                        <p class="text-xs text-slate-500 font-medium">{{ cita.cliente?.telefono || 'Sin teléfono' }}</p>
                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Dirección del Servicio</label>
                    <div class="p-4 bg-[var(--ui-surface)]/50 rounded-2xl border border-slate-100 dark:border-slate-700 text-sm font-bold text-slate-700 dark:text-slate-200 transition-colors">
                      {{ cita.direccion_servicio || 'No especificada' }}
                    </div>
                    <button
                      v-if="urlMapsCita"
                      type="button"
                      class="mt-3 w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-sky-600 hover:bg-sky-700 text-white !text-white font-black text-xs uppercase tracking-wide shadow-md shadow-blue-600/25 border border-blue-500/30 transition-all active:scale-[0.98]"
                      @click="comoLlegar"
                    >
                      <svg class="w-4 h-4 shrink-0 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                      Cómo llegar
                    </button>
                  </div>
                </div>

                <!-- Detalle Cita -->
                <div class="space-y-6">
                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Tipo de Servicio</label>
                      <div class="px-4 py-3 bg-[var(--ui-surface)]/50 rounded-2xl border border-slate-100 dark:border-slate-700 text-xs font-black text-blue-600 dark:text-blue-400 uppercase transition-colors">
                        {{ formatearTipoServicio(cita.tipo_servicio) }}
                      </div>
                    </div>
                    <div>
                      <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Duración</label>
                      <div class="px-4 py-3 bg-[var(--ui-surface)]/50 rounded-2xl border border-slate-100 dark:border-slate-700 text-xs font-black text-emerald-600 dark:text-slate-400 uppercase flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ cita.tiempo_servicio_formateado || 'N/A' }}
                      </div>
                    </div>
                  </div>
                  <div>
                    <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Técnico Asignado</label>
                    <div class="flex items-center gap-2 p-4 bg-[var(--ui-surface)]/50 rounded-2xl border border-slate-100 dark:border-slate-700 transition-colors">
                      <div class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm">
                        {{ getInitials(tecnicoNombre) }}
                      </div>
                      <p class="text-sm font-black text-slate-900 dark:text-white uppercase">{{ tecnicoNombre }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Descripción -->
              <div class="mt-8">
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Descripción del Requerimiento</label>
                <div class="p-6 bg-sky-50 dark:bg-sky-900/20/30 dark:bg-blue-900/10 rounded-2xl border border-blue-100/50 dark:border-blue-900/20 text-sm font-medium text-slate-700 dark:text-slate-200 italic leading-relaxed transition-colors">
                   "{{ cita.descripcion || 'Sin descripción detallada' }}"
                </div>
              </div>

              <!-- Notas Internas (NUEVO) -->
              <div v-if="cita.notas_internas" class="mt-8">
                <div class="flex items-center gap-2 mb-2">
                  <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                  <label class="block text-[10px] font-black text-brand-600 dark:text-brand-400 uppercase tracking-wide">Notas Internas (Confidencial)</label>
                </div>
                <div class="p-6 bg-brand-50/50 dark:bg-brand-900/10 rounded-2xl border border-brand-200/50 dark:border-brand-800/30 text-sm font-bold text-slate-800 dark:text-slate-200 leading-relaxed transition-colors">
                   {{ cita.notas_internas }}
                </div>
                <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase mt-2 px-2 tracking-widest">⚠️ Estas notas no son visibles para el cliente</p>
              </div>

              <!-- Geocalización (Nuevo) -->
              <div v-if="cita.latitud && cita.longitud" class="mt-8">
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Validación GPS del Servicio</label>
                <div class="flex items-center justify-between p-4 bg-[var(--ui-surface)] dark:bg-black/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center text-rose-500">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide">Ubicación Capturada</p>
                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Sincronizado: {{ formatDateTime(cita.fecha_gps) }}</p>
                        </div>
                    </div>
                    <a :href="`https://www.google.com/maps?q=${cita.latitud},${cita.longitud}`" target="_blank" rel="noopener noreferrer" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white !text-white text-[10px] font-black uppercase tracking-wide rounded-xl transition-colors shadow-sm border border-blue-500/30">
                        Ver en Mapa
                    </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Card Items del Servicio (NUEVO) -->
          <div v-if="cita.items?.length > 0" class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors">
            <div class="p-8">
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-2">
                  <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                  <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Conceptos del Servicio</h2>
                </div>
                <span class="text-[10px] font-black bg-slate-100 dark:bg-slate-700 px-3 py-1 rounded-full text-slate-500 transition-colors">{{ cita.items.length }} CONCEPTOS</span>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full text-left">
                  <thead>
                    <tr class="border-b border-slate-50 dark:border-slate-700">
                      <th class="pb-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Concepto</th>
                      <th class="pb-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Cant.</th>
                      <th class="pb-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Unitario</th>
                      <th class="pb-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Total</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50">
                    <tr v-for="item in cita.items" :key="item.id" class="group transition-colors">
                      <td class="py-4">
                        <p class="text-xs font-black text-slate-900 dark:text-white uppercase leading-tight">{{ item.citable?.nombre || 'Producto/Servicio' }}</p>
                        <p v-if="item.notas" class="text-[9px] text-slate-400 dark:text-slate-500 font-medium mt-1 uppercase">{{ item.notas }}</p>
                      </td>
                      <td class="py-4 text-center">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">x{{ item.cantidad }}</span>
                      </td>
                      <td class="py-4 text-right">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400">${{ formatearPrecio(item.precio) }}</span>
                      </td>
                      <td class="py-4 text-right">
                        <span class="text-xs font-black text-blue-600 dark:text-blue-400">${{ formatearPrecio(item.precio * item.cantidad) }}</span>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="border-t-2 border-slate-50 dark:border-slate-700">
                      <td colspan="3" class="pt-6 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Estimado</td>
                      <td class="pt-6 text-right text-lg font-black text-slate-900 dark:text-white">${{ formatearPrecio(cita.total) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>

          <!-- Card Equipo -->
          <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors">
            <div class="p-8">
              <div class="flex items-center gap-2 mb-6">
                <div class="w-1.5 h-6 bg-brand-500 rounded-full"></div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Detalles del Equipo</h2>
              </div>

              <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                 <div>
                   <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1.5">Categoría</label>
                   <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors uppercase">{{ formatearTipoEquipo(cita.tipo_equipo) }}</p>
                 </div>
                 <div>
                   <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1.5">Marca</label>
                   <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors uppercase">{{ cita.marca_equipo || 'N/A' }}</p>
                 </div>
                 <div>
                   <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1.5">Modelo</label>
                   <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors uppercase">{{ cita.modelo_equipo || 'N/A' }}</p>
                 </div>
                 <div>
                   <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-1.5">Estado Inicial</label>
                   <p class="text-sm font-bold text-brand-600 dark:text-brand-400 transition-colors uppercase">REPORTADO</p>
                 </div>
              </div>

              <div class="mt-8">
                <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Problema Reportado</label>
                <div class="p-6 bg-brand-50 dark:bg-brand-900/20/50 dark:bg-brand-900/10 rounded-2xl border border-brand-100/50 dark:border-brand-900/20 text-sm font-bold text-brand-800 dark:text-brand-200 dark:text-brand-200 dark:text-brand-400 transition-colors">
                  {{ cita.problema_reportado || 'Sin reporte de falla específico' }}
                </div>
              </div>
            </div>
          </div>

          <!-- Card Evidencias Previas (NUEVO) -->
          <div v-if="cita.evidencias_previas && cita.evidencias_previas.length > 0" class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors">
            <div class="p-8">
              <div class="flex items-center gap-2 mb-6">
                <div class="w-1.5 h-6 bg-purple-500 rounded-full"></div>
                <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Evidencias del Cliente</h2>
              </div>
              <p class="text-xs text-slate-500 mb-6 font-medium">Fotos enviadas por el cliente al momento de agendar el diagnóstico.</p>
              
              <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4">
                <div v-for="(foto, idx) in cita.evidencias_previas" :key="idx" class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-700 border border-slate-100 dark:border-slate-700 group cursor-pointer shadow-sm relative transition-all hover:scale-[1.03]" @click="openGallery([foto], 'Evidencia Cliente #' + (idx + 1))">
                  <img :src="storageSrc(foto)" loading="lazy" decoding="async" class="w-full h-full object-cover transition-transform group-hover:scale-105">
                  <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-2 text-center">
                    <span class="text-[10px] font-black text-white uppercase tracking-wide">VER FOTO</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card Reporte y Evidencias -->
          <div v-if="cita.trabajo_realizado || cita.fotos_finales" class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors">
             <div class="p-8">
                <div class="flex items-center gap-2 mb-6">
                  <div class="w-1.5 h-6 bg-brand-500 rounded-full"></div>
                  <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Reporte de Servicio Terminado</h2>
                </div>

                <div v-if="cita.trabajo_realizado" class="mb-8">
                   <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-2">Trabajo Realizado</label>
                   <div class="p-6 bg-emerald-50 dark:bg-emerald-900/20/50 dark:bg-slate-800/10 rounded-2xl border border-emerald-100/50 dark:border-emerald-900/20 text-sm font-medium text-slate-700 dark:text-slate-200 italic leading-relaxed transition-colors">
                      "{{ cita.trabajo_realizado }}"
                   </div>
                </div>

                <div>
                   <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-4">Evidencias Fotográficas</label>
                   <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                      <!-- Imagen 1 -->
                      <div v-if="cita.foto_equipo" class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-700 border border-slate-100 dark:border-slate-700 group cursor-pointer shadow-sm relative transition-all hover:scale-[1.03]" @click="openGallery([cita.foto_equipo], 'Equipo')">
                        <img :src="storageSrc(cita.foto_equipo)" loading="lazy" decoding="async" class="w-full h-full object-cover transition-transform group-hover:scale-105">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-2 text-center">
                           <span class="text-[10px] font-black text-white uppercase tracking-wide">EQUIPO</span>
                           <svg class="w-4 h-4 text-white mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                      </div>
                      
                      <!-- Imagen 2 -->
                      <div v-if="cita.foto_hoja_servicio" class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-700 border border-slate-100 dark:border-slate-700 group cursor-pointer shadow-sm relative transition-all hover:scale-[1.03]" @click="openGallery([cita.foto_hoja_servicio], 'Hoja de Servicio')">
                        <img :src="storageSrc(cita.foto_hoja_servicio)" loading="lazy" decoding="async" class="w-full h-full object-cover transition-transform group-hover:scale-105">
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-2 text-center">
                           <span class="text-[10px] font-black text-white uppercase tracking-wide">HOJA SERVICIO</span>
                           <svg class="w-4 h-4 text-white mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                      </div>

                      <!-- Fotos Finales -->
                      <template v-if="cita.fotos_finales">
                         <div v-for="(foto, idx) in cita.fotos_finales" :key="idx" class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-700 border border-slate-100 dark:border-slate-700 group cursor-pointer shadow-sm relative transition-all hover:scale-[1.03]" @click="openGallery([foto], 'Evidencia')">
                            <img :src="storageSrc(foto)" loading="lazy" decoding="async" class="w-full h-full object-cover transition-transform group-hover:scale-105">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center p-2 text-center">
                               <span class="text-[10px] font-black text-white uppercase tracking-wide">EVIDENCIA #{{ idx + 1 }}</span>
                            </div>
                         </div>
                      </template>
                   </div>
                </div>
             </div>
          </div>
        </div>

        <!-- Columna Derecha: Sidebar con Acciones y Totales -->
        <div class="space-y-6">
           <!-- Card Acciones de Estado -->
           <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-700 overflow-hidden transition-colors">
             <div class="p-6">
                <div class="flex items-center gap-2 mb-6">
                  <div class="w-1.5 h-6 bg-indigo-500 rounded-full"></div>
                  <h2 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wide">Acciones Rápidas</h2>
                </div>

                <div class="space-y-3">
                   <button v-if="['pendiente', 'programado', 'programada', 'reprogramado', 'reprogramada'].includes(cita.estado)" @click="cambiarEstado('en_proceso')" class="w-full py-4 bg-brand-500 hover:bg-brand-600 text-white text-xs font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-brand-200 dark:shadow-none active:scale-95 flex items-center justify-center gap-2">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                     Iniciar Cita
                   </button>
                   
                   <button v-if="cita.estado === 'en_proceso'" @click="cambiarEstado('completado')" class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-emerald-200 dark:shadow-none active:scale-95 flex items-center justify-center gap-2">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                     Finalizar Cita
                   </button>

                   <div v-if="cita.items?.length > 0 && ['completado', 'completada'].includes(cita.estado) && !cita.venta" class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-700">
                      <Link :href="route('ventas.create', { cita_id: cita.id })" class="w-full py-4 bg-sky-600 hover:bg-sky-700 text-white text-xs font-black uppercase tracking-wide rounded-2xl transition-all shadow-xl shadow-sky-200 dark:shadow-none active:scale-95 flex items-center justify-center gap-2">
                        <span>💰</span> Generar Cobro
                      </Link>
                      <p class="text-[10px] text-slate-400 text-center mt-2 font-bold uppercase tracking-wide italic">Carga los cargos extra a la cuenta del cliente</p>
                   </div>

                   <button v-if="['pendiente', 'en_proceso', 'programado', 'programada', 'reprogramado', 'reprogramada'].includes(cita.estado)" @click="cambiarEstado('cancelado')" class="w-full py-4 bg-white dark:bg-black/50 text-rose-600 border border-rose-100 dark:border-rose-900/30 hover:bg-slate-50 dark:hover:bg-rose-900/10 text-xs font-black uppercase tracking-wide rounded-2xl transition-all active:scale-95 flex items-center justify-center gap-2">
                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                     Cancelar Cita
                   </button>
                </div>
             </div>
           </div>

           <!-- Card Información Extra -->
           <div class="bg-slate-50 dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 p-6 shadow-sm">
              <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-wide mb-4">Información Operativa</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">Este reporte es estrictamente técnico. Para realizar cargos por materiales o servicios adicionales, diríjase al módulo de <strong>Ventas</strong> y vincule el folio de esta cita.</p>
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
               <p class="text-sm font-bold uppercase tracking-wide text-white/70">{{ imageTitle }}</p>
               <p class="text-[10px] text-white/50">Visualización de Evidencia</p>
             </div>
           </div>
           <button @click="closeGallery" class="w-10 h-10 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-full transition-all backdrop-blur-md">
              <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
           </button>
        </div>
        <div class="flex-1 flex items-center justify-center relative p-4">
           <img :src="galleryImages[0]" loading="eager" decoding="async" class="max-h-[85vh] max-w-[90vw] object-contain rounded-2xl shadow-xl animate-in fade-in zoom-in-95" :key="galleryImages[0]">
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import axios from 'axios';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';
const { formatDateTime } = useFormatters();

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

/** URL pública para rutas guardadas en BD (relativas a storage o ya absolutas). Soporta strings o objetos {path}. */
const storageSrc = (foto) => {
  if (!foto) return '';
  const path = typeof foto === 'object' && foto !== null ? (foto.path || foto.url || '') : String(foto || '');
  const p = path.trim();
  if (!p) return '';
  if (/^https?:\/\//i.test(p)) return p;
  if (p.startsWith('/storage/')) return p;
  if (p.startsWith('storage/')) return `/${p}`;
  return `/storage/${p.replace(/^\/+/, '')}`;
};

const openGallery = (images, title) => {
  if (!images || images.length === 0) return;
  galleryImages.value = images.map((img) => storageSrc(img));
  imageTitle.value = title;
  showGalleryModal.value = true;
};

const closeGallery = () => { showGalleryModal.value = false; };

const clienteNombre = computed(() => props.cita.cliente?.nombre_razon_social || 'Cliente desconocido');
const tecnicoNombre = computed(() => props.cita.tecnico?.name || 'Varios técnicos / Sin asignar');

/** URL de Google Maps (coordenadas, dirección de servicio o armado desde cliente / cita). */
const urlMapsCita = computed(() => {
    const c = props.cita;
    if (!c) return null;
    
    // Priorizamos la dirección escrita si existe, para evitar errores de coordenadas capturadas en otro punto
    const serv = c.direccion_servicio && String(c.direccion_servicio).trim();
    if (serv) {
        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(serv.replace(/,/g, ' '))}`;
    }

    // Fallback a coordenadas si no hay dirección escrita
    if (c.latitud != null && c.longitud != null && String(c.latitud) !== '' && String(c.longitud) !== '') {
        return `https://www.google.com/maps?q=${encodeURIComponent(String(c.latitud))},${encodeURIComponent(String(c.longitud))}`;
    }

    const cl = c.cliente;
    const partes = [];
    if (cl?.calle) partes.push(String(cl.calle).trim());
    const num = [cl?.numero_exterior, cl?.numero_interior ? `Int. ${cl.numero_interior}` : '']
        .filter(Boolean)
        .map((s) => String(s).trim())
        .join(' ');
    if (num) partes.push(num);
    if (cl?.colonia) partes.push(String(cl.colonia).trim());
    if (c.direccion_calle) partes.push(String(c.direccion_calle).trim());
    if (c.direccion_colonia) partes.push(String(c.direccion_colonia).trim());
    if (c.direccion_cp) partes.push(`C.P. ${String(c.direccion_cp).trim()}`);
    if (partes.length === 0) return null;
    const q = partes.join(', ');
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q.replace(/,/g, ' '))}`;
});

const comoLlegar = async () => {
    const url = urlMapsCita.value;
    if (!url) {
        await Swal.fire('Sin ubicación', 'No hay datos suficientes para abrir el mapa.', 'info');
        return;
    }
    const puedeIniciar = ['pendiente', 'programado', 'programada', 'reprogramado', 'reprogramada'].includes(props.cita.estado);
    if (!puedeIniciar) {
        window.open(url, '_blank', 'noopener,noreferrer');
        return;
    }
    const r = await Swal.fire({
        title: '¿Vas en camino?',
        html: 'Si eliges <strong>Sí, voy en camino</strong>, el sistema <strong>iniciará el servicio</strong> y comenzará a contar el tiempo desde ahora.<br><br>Si solo necesitas ver la ruta, usa <strong>Solo ver mapa</strong>.',
        icon: 'question',
        showDenyButton: true,
        confirmButtonText: 'Sí, voy en camino',
        denyButtonText: 'Solo ver mapa',
        confirmButtonColor: '#2563eb',
        denyButtonColor: '#64748b',
        reverseButtons: true,
    });
    if (r.isDenied) {
        window.open(url, '_blank', 'noopener,noreferrer');
        return;
    }
    if (!r.isConfirmed) return;
    try {
        await axios.post(route('citas.iniciar', props.cita.id));
        window.open(url, '_blank', 'noopener,noreferrer');
        window.location.reload();
    } catch (e) {
        const data = e.response?.data;
        const msg = data?.message || data?.errors?.general?.[0] || 'No se pudo iniciar el servicio.';
        await Swal.fire('No se pudo iniciar', typeof msg === 'string' ? msg : 'Error al iniciar el servicio.', 'error');
    }
};

const getInitials = (name) => {
  if (!name) return '?';
  const parts = name.split(' ');
  return (parts.length > 1 ? parts[0][0] + parts[1][0] : parts[0][0]).toUpperCase();
};

const formatearTipoServicio = (tipo) => {
  const tipos = { instalacion: 'Instalación', diagnostico: 'Diagnóstico', reparacion: 'Reparación', garantia: 'Garantía', mantenimiento: 'Mantenimiento', servicio_limpieza: 'Servicio limpieza', otro: 'Otro' };
  return tipos[tipo] || tipo || 'Desconocido';
};

const formatearTipoEquipo = (tipo) => {
  const tipos = { 
    minisplit: 'Minisplit', 
    aire_acondicionado: 'Aire Acondicionado',
    paquete: 'Unidad Paquete',
    refrigerador: 'Refrigerador', 
    congelador: 'Congelador',
    enfriador_agua: 'Enfriador de Agua',
    lavadora: 'Lavadora', 
    secadora: 'Secadora', 
    estufa: 'Estufa', 
    microondas: 'Microondas',
    lavavajillas: 'Lavavajillas',
    campana: 'Campana',
    boiler: 'Boiler' 
  };
  return tipos[tipo] || tipo || 'Equipo Especializado';
};

const obtenerEstadoCitaLabel = (estado) => {
  const labels = { 
    pendiente: 'Pendiente', 
    en_proceso: 'En Proceso', 
    completado: 'Completado', 
    completada: 'Completada', 
    cancelado: 'Cancelado', 
    cancelada: 'Cancelada', 
    programado: 'Programado', 
    programada: 'Programada', 
    reprogramado: 'Reprogramado', 
    reprogramada: 'Reprogramada' 
  };
  return labels[estado] || estado || 'Desconocido';
};

const obtenerEstadoCitaClase = (estado) => {
  const clases = {
    pendiente: 'bg-brand-50 dark:bg-brand-900/20 text-brand-800 dark:text-brand-200 dark:text-brand-400 border-brand-100 dark:border-brand-900/30',
    en_proceso: 'bg-sky-50 dark:bg-sky-900/20 text-sky-700 dark:text-sky-400 border-sky-100 dark:border-sky-900/30',
    completado: 'bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 text-emerald-800 dark:text-emerald-200 dark:text-slate-400 border-emerald-100 dark:border-emerald-900/30',
    completada: 'bg-emerald-50 dark:bg-emerald-900/20 dark:bg-slate-800/20 text-emerald-800 dark:text-emerald-200 dark:text-slate-400 border-emerald-100 dark:border-emerald-900/30',
    cancelado: 'bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-200 dark:text-rose-400 border-rose-100 dark:border-rose-900/30',
    cancelada: 'bg-rose-50 dark:bg-rose-900/20 text-rose-800 dark:text-rose-200 dark:text-rose-400 border-rose-100 dark:border-rose-900/30'
  };
  return clases[estado] || 'bg-[var(--ui-surface)]/20 text-slate-700 dark:text-slate-400 border-slate-100 dark:border-slate-900/30';
};

const formatearPrecio = (valor) => {
  if (valor == null) return '0.00';
  return Number(valor).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatearFechaHora = (fh) => fh ? formatDateTime(fh) : '—';

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
    const response = await axios.post(route('citas.cambiar-estado', props.cita.id), { estado: nuevoEstado });
    if (!response.data?.success) return;
    if (nuevoEstado === 'completado') {
      const ventas = await Swal.fire({
        title: '¿Hubo venta en este servicio?',
        html: 'Si cobraste materiales o un servicio adicional al cliente, puedes registrar la venta y vincularla a esta cita.',
        icon: 'question',
        showDenyButton: true,
        confirmButtonText: 'Sí, ir a ventas',
        denyButtonText: 'No',
        confirmButtonColor: '#2563eb',
        denyButtonColor: '#64748b',
        reverseButtons: true,
      });
      if (ventas.isConfirmed) {
        router.visit(route('ventas.create', { cita_id: props.cita.id }));
        return;
      }
    }
    window.location.reload();
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
