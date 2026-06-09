<template>
  <div class="citas-wizard min-h-screen bg-[#f8fafc] dark:bg-[#0f172a] transition-colors duration-500">
    <Head :title="`Editar Cita #${cita.id}`" />

    <!-- Progress Header Premium -->
    <div class="sticky top-0 z-40 bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border-b border-slate-200/60 dark:border-slate-800/60 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col py-4">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
              <Link :href="route('citas.index')" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all duration-300 group">
                <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              </Link>
              <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Editar <span class="text-amber-500 dark:text-amber-400">Cita #{{ cita.id }}</span></h1>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.15em] mt-0.5">Asistencia Vircom &bull; Step {{ currentStep }} of 4</p>
              </div>
            </div>

            <!-- Stats/Context Fast View -->
             <div class="hidden md:flex items-center gap-3">
                <div v-if="internalTime && internalEndTime" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 dark:bg-blue-900/30 border border-blue-200/50 dark:border-blue-700/50 animate-fade-in text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-tighter shadow-sm">
                    {{ internalTime }} - {{ internalEndTime }}
                </div>
                <div v-if="selectedCliente" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200/50 dark:border-slate-700/50 animate-fade-in">
                    <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                    <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300 truncate max-w-[150px]">{{ selectedCliente.nombre_razon_social }}</span>
                </div>
            </div>
          </div>

          <!-- Stepper Visual -->
          <div class="flex items-center justify-between relative mt-2 px-2">
            <!-- Line background -->
            <div class="absolute left-0 right-0 h-1 bg-slate-200 dark:bg-slate-800 top-1/2 -translate-y-1/2 rounded-full overflow-hidden">
               <div 
                class="h-full bg-blue-600 dark:bg-blue-500 transition-all duration-700 ease-out shadow-[0_0_15px_rgba(37,99,235,0.4)]"
                :style="{ width: `${progressWidth}%` }"
               ></div>
            </div>
            
            <!-- Step Nodes -->
            <div v-for="step in steps" :key="step.id" 
                 class="relative z-10 flex flex-col items-center group"
                 :class="{ 'cursor-pointer': step.id < currentStep }"
                 @click="step.id < currentStep && (currentStep = step.id)"
            >
              <div 
                class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm font-black transition-all duration-500 border-2"
                :class="[
                  currentStep === step.id ? 'bg-blue-600 border-blue-600 text-white scale-110 shadow-lg shadow-blue-200 dark:shadow-none' : 
                  currentStep > step.id ? 'bg-emerald-500 border-emerald-500 text-white' : 
                  'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-400 group-hover:border-slate-300 dark:group-hover:border-slate-600'
                ]"
              >
                <template v-if="currentStep > step.id">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template v-else>{{ step.id }}</template>
              </div>
              <span 
                class="absolute -bottom-7 whitespace-nowrap text-[10px] font-black uppercase tracking-widest transition-colors duration-300"
                :class="[currentStep >= step.id ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-600']"
              >
                {{ step.label }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Container with Transitions -->
    <main class="max-w-4xl mx-auto px-4 py-12 pb-32">
      <form @submit.prevent="submit" class="relative min-h-[500px]">
        <transition-group 
          name="step-fade" 
          enter-active-class="transition-all duration-500 ease-out" 
          enter-from-class="opacity-0 translate-x-8" 
          enter-to-class="opacity-100 translate-x-0" 
          leave-active-class="absolute top-0 w-full transition-all duration-300 ease-in" 
          leave-from-class="opacity-100 translate-x-0" 
          leave-to-class="opacity-0 -translate-x-8"
        >
          
          <!-- STEP 1: CLIENTE -->
          <div v-show="currentStep === 1" key="step1" class="space-y-8">
            <div class="section-card glass-morphism p-8 md:p-12">
               <div class="flex items-center gap-5 mb-12">
                  <div class="w-16 h-16 rounded-[24px] bg-blue-600 flex items-center justify-center text-white shadow-xl shadow-blue-200 dark:shadow-none transition-transform hover:rotate-3">
                     <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                  </div>
                  <div>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">Identidad del Cliente</h2>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em] mt-1">Vincular cuenta para el historial técnico</p>
                  </div>
               </div>

               <div class="space-y-10">
                  <BuscarCliente
                    ref="buscarClienteRef"
                    :clientes="clientes"
                    :cliente-seleccionado="selectedCliente"
                    @cliente-seleccionado="onClienteSeleccionado"
                    @crear-nuevo-cliente="onCrearNuevoCliente"
                    label-busqueda="Cliente"
                    placeholder-busqueda="Nombre, RFC o Teléfono..."
                    :requerido="true"
                    :mostrar-opcion-nuevo-cliente="true"
                    :mostrar-estado-cliente="true"
                    :mostrar-info-comercial="true"
                    class="premium-search"
                  />

                  <!-- Info de Póliza Contextual -->
                  <div v-if="selectedCliente && clientePolizas.length > 0" class="animate-scale-in p-6 bg-emerald-50 dark:bg-emerald-900/10 border-2 border-emerald-100 dark:border-emerald-800/30 rounded-[24px]">
                    <div class="flex items-start gap-4">
                      <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold shrink-0">🛡️</div>
                      <div class="flex-1">
                        <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest mb-1">Protección Activa</p>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ clientePolizas[0].nombre }} <span class="ml-2 font-normal opacity-60">#{{ clientePolizas[0].folio }}</span></h4>
                        
                        <div class="grid grid-cols-2 gap-4 mt-4 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-tighter">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                {{ clientePolizas[0].visitas_disponibles }} Visitas Disponibles
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                Cobertura 24/7
                            </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else-if="selectedCliente && !form.ticket_id" class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-[24px] border border-slate-200/50 dark:border-slate-700/50 text-center">
                      <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Sin póliza activa vinculada</p>
                      <p class="text-[11px] text-slate-400 mt-1 italic">Este servicio será facturado a precio regular</p>
                  </div>
               </div>
            </div>
          </div>

          <!-- STEP 2: LOGÍSTICA & ESTADÍA -->
          <div v-show="currentStep === 2" key="step2" class="space-y-8">
            <div class="section-card glass-morphism p-8 md:p-12">
               <div class="flex items-center gap-5 mb-12">
                  <div class="w-16 h-16 rounded-[24px] bg-amber-500 flex items-center justify-center text-white shadow-xl shadow-amber-200 dark:shadow-none transition-transform hover:rotate-3">
                     <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </div>
                  <div>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">Agenda & Especialista</h2>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em] mt-1">Sincronización de tiempos y recursos</p>
                  </div>
               </div>

               <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                  <div class="space-y-6">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Selección de Especialista</label>
                    <div class="grid grid-cols-1 gap-3">
                        <button 
                            v-for="tecnico in tecnicos" :key="tecnico.id" 
                            type="button"
                            @click="form.tecnico_id = tecnico.id"
                            class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all duration-300"
                            :class="[form.tecnico_id === tecnico.id ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500 shadow-sm' : 'bg-white dark:bg-slate-900/50 border-slate-100 dark:border-slate-800 hover:border-slate-200 dark:hover:border-slate-700']"
                        >
                            <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center font-black text-slate-500">
                                {{ getTecnicoInitial(tecnico) }}
                            </div>
                            <div class="text-left flex-1">
                                <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ getTecnicoDisplayName(tecnico) }}</p>
                                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-tighter mt-0.5">Disponibilidad Inmediata</p>
                            </div>
                            <div v-if="form.tecnico_id === tecnico.id" class="text-blue-500">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </div>
                        </button>
                    </div>
                    <div v-if="form.errors.tecnico_id" class="text-xs text-red-500 font-bold px-2 mt-2 tracking-tight">{{ form.errors.tecnico_id }}</div>
                    
                    <!-- Availability Warning -->
                    <div v-if="availabilityError" class="animate-shake mt-4 p-4 bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800/30 rounded-2xl flex items-center gap-3">
                        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <p class="text-[11px] font-bold text-red-600 dark:text-red-400 uppercase tracking-tight">{{ availabilityError }}</p>
                    </div>
                  </div>

                  <div class="space-y-8">
                     <!-- Date Selector UI Premium -->
                     <div class="space-y-4">
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Fecha & Hora</label>
                             <div class="w-full max-w-sm px-8 py-8 bg-white dark:bg-slate-800 rounded-[40px] shadow-2xl shadow-blue-500/10 border border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center transition-all hover:border-blue-400 group">
                                <div class="w-full flex flex-col items-center gap-6">
                                    <!-- Selector de Fecha Grande -->
                                    <div class="relative group w-full flex justify-center items-center">
                                        <input 
                                            ref="dateInputRef"
                                            type="date" 
                                            v-model="internalDate" 
                                            :min="todayDate"
                                            @change="updateDateTime()"
                                            class="w-full max-w-xs bg-slate-50 dark:bg-slate-900/40 border-2 border-slate-100 dark:border-slate-800 focus:border-blue-500 rounded-[24px] pl-6 pr-16 py-4 text-3xl font-black text-slate-900 dark:text-white transition-all outline-none text-center cursor-pointer hover:bg-white dark:hover:bg-slate-900"
                                        >
                                        <button 
                                            type="button" 
                                            @click="dateInputRef?.showPicker()"
                                            class="absolute right-3 w-12 h-12 bg-blue-600 hover:bg-blue-700 rounded-[18px] flex items-center justify-center text-white shadow-lg transition-transform hover:scale-105 active:scale-95 z-10"
                                            title="Seleccionar Fecha"
                                        >
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </button>
                                    </div>
                                    
                                    <!-- Rango de Tiempo Horizontal -->
                                    <!-- Grid de Horarios -->
                                    <div class="w-full pt-4 border-t border-slate-100 dark:border-slate-800">
                                        <p class="text-center text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Selecciona el Horario (Clic: Inicio y Fin)
                                        </p>
                                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                                            <button 
                                                v-for="hora in 13" :key="hora"
                                                type="button"
                                                @click="seleccionarBloque(hora + 7)"
                                                :class="[
                                                    'py-2 rounded-xl text-xs font-bold transition-all border',
                                                    (hora + 7) === selectedStart
                                                        ? 'bg-emerald-500 text-white border-emerald-600 shadow-md shadow-emerald-500/30 ring-2 ring-offset-1 ring-emerald-400 dark:ring-offset-slate-900'
                                                        : ((hora + 7) === selectedEnd
                                                            ? 'bg-rose-500 text-white border-rose-600 shadow-md shadow-rose-500/30 ring-2 ring-offset-1 ring-rose-400 dark:ring-offset-slate-900'
                                                            : (isBloqueDentroDeRango(hora + 7) 
                                                                ? 'bg-blue-100 border-blue-300 text-blue-700 dark:bg-blue-900/40 dark:border-blue-700/60 dark:text-blue-300 scale-105'
                                                                : 'bg-white dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20'))
                                                ]"
                                            >
                                                {{ formatearHoraBloque(hora + 7) }}
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between w-full px-5 py-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800/50 mt-2">
                                        <div class="flex flex-col items-start">
                                            <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest mb-1">Inicia</span>
                                            <span class="text-sm font-black text-slate-800 dark:text-white bg-white dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">{{ formatearHoraBloque(selectedStart) || '--:--' }}</span>
                                        </div>
                                        <div class="text-slate-300 dark:text-slate-600">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-1">Finaliza</span>
                                            <span class="text-sm font-black text-slate-800 dark:text-white bg-white dark:bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 shadow-sm">{{ formatearHoraBloque(selectedEnd) || '--:--' }}</span>
                                        </div>
                                    </div>

                                    <!-- Lista de Horarios Ocupados -->
                                    <div v-if="busySlots.length > 0" class="w-full mt-4 space-y-2">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest text-center">Horarios Ocupados</p>
                                        <div class="flex flex-wrap justify-center gap-2">
                                            <div 
                                                v-for="slot in busySlots" :key="slot.id"
                                                class="px-3 py-1 rounded-full bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/30 text-[9px] font-bold text-red-500 uppercase flex items-center gap-1"
                                            >
                                                <div class="w-1 h-1 rounded-full bg-red-500"></div>
                                                {{ slot.start }} - {{ slot.end }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else-if="form.tecnico_id && !isFetchingBusy" class="w-full mt-4">
                                        <p class="text-[9px] font-black text-emerald-500 uppercase tracking-widest text-center">Todo el día disponible</p>
                                    </div>
                                </div>
                                
                                <div v-if="form.errors.fecha_hora || form.errors.fecha_hora_fin" class="mt-4 p-3 bg-red-50 dark:bg-rose-900/10 rounded-xl border border-red-100 dark:border-rose-900/20 w-full">
                                    <p class="text-[10px] text-red-600 dark:text-rose-400 font-bold uppercase tracking-tight text-center">
                                        {{ form.errors.fecha_hora || form.errors.fecha_hora_fin }}
                                    </p>
                                </div>
                            </div>
                     </div>

                     <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormField v-model="form.prioridad" label="Grado de Prioridad" type="select" id="prioridad" :options="prioridadOptions" :error="form.errors.prioridad" />
                        <FormField v-model="form.tipo_servicio" label="Servicios" type="select" id="tipo_servicio" :options="tipoServicioOptions" :error="form.errors.tipo_servicio" required />
                     </div>

                      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <FormField v-model="form.estado" label="Estado de la Cita" type="select" id="estado" :options="estadoOptions" :error="form.errors.estado" />
                      </div>


                     <!-- Campo Condicional: Tipo de Mantenimiento -->
                     <Transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="transform -translate-y-4 opacity-0"
                        enter-to-class="transform translate-y-0 opacity-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="transform translate-y-0 opacity-100"
                        leave-to-class="transform -translate-y-4 opacity-0"
                     >
                        <div v-if="form.tipo_servicio === 'mantenimiento'" class="p-6 bg-blue-50/50 dark:bg-blue-900/10 rounded-[32px] border border-blue-100 dark:border-blue-900/20">
                            <FormField 
                                v-model="form.tipo_mantenimiento" 
                                label="Tipo de Mantenimiento" 
                                type="select" 
                                id="tipo_mantenimiento" 
                                :options="tipoMantenimientoOptions" 
                                :error="form.errors.tipo_mantenimiento" 
                            />
                        </div>
                     </Transition>
                  </div>
               </div>
            </div>
          </div>

          <!-- STEP 3: EQUIPO Y DIAGNÓSTICO -->
          <div v-show="currentStep === 3" key="step3" class="space-y-8">
             <div class="section-card glass-morphism p-8 md:p-12">
               <div class="flex items-center gap-5 mb-12">
                  <div class="w-16 h-16 rounded-[24px] bg-indigo-500 flex items-center justify-center text-white shadow-xl shadow-indigo-200 dark:shadow-none transition-transform hover:rotate-3">
                     <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  </div>
                  <div>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">Detalles del Equipo</h2>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em] mt-1">Especificaciones técnicas y fallas</p>
                  </div>
               </div>

               <div class="space-y-10">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <FormField v-model="form.tipo_equipo" label="Categoría" type="select" id="tipo_equipo" :options="tipoEquipoOptions" :error="form.errors.tipo_equipo" />
                        
                        <div class="space-y-1.5">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest pl-1">Marca (Opcional)</label>
                            <input v-model="form.marca_equipo" list="marcas-list" class="w-full h-12 px-5 bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all font-bold text-slate-800 dark:text-white" placeholder="Escribe la marca o elige sugerencia" @input="onInputToUpper('marca_equipo')">
                            <datalist id="marcas-list">
                                <option v-for="m in marcasComunes" :key="m" :value="m" />
                            </datalist>
                        </div>

                        <FormField v-model="form.modelo_equipo" label="Modelo (Opcional)" id="modelo_equipo" placeholder="AR12..." @input="onInputToUpper('modelo_equipo')" />
                    </div>

                    <FormField
                        v-model="form.folio"
                        label="Folio tienda / servicio (opcional)"
                        id="folio_cita_edit"
                        placeholder="Ej. folio LG, Mirage, Liverpool…"
                        :error="form.errors.folio"
                    />
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 -mt-6 mb-2 px-1">
                      Deja vacío al guardar para conservar el folio actual. Escribe un valor para reemplazarlo.
                    </p>

                    <div class="grid grid-cols-1 gap-8">
                        <FormField 
                            v-model="form.descripcion" 
                            :label="form.tipo_servicio === 'instalacion' ? 'Detalles del Servicio / Instalación' : 'Descripción de la Falla'" 
                            type="textarea" 
                            id="descripcion" 
                            :rows="4" 
                            :placeholder="form.tipo_servicio === 'instalacion' ? 'Describe los detalles de la instalación (ej. piso, altura, materiales)...' : 'Describe brevemente el problema detectado...'" 
                            :error="form.errors.descripcion" 
                        />
                        <FormField v-model="form.direccion_servicio" label="Ubicación de Atención" type="textarea" id="direccion_servicio" :rows="3" placeholder="Calle, cruzamientos, referencias particulares..." :error="form.errors.direccion_servicio" />
                    </div>
               </div>
             </div>

          <!-- REPORTE DE TRABAJO (SI ESTÁ EN PROCESO O COMPLETADO) -->
          <div v-if="['en_proceso', 'completado'].includes(form.estado)" class="section-card glass-morphism p-8 md:p-12 mt-8">
               <div class="flex items-center gap-5 mb-12">
                <div class="w-16 h-16 rounded-[24px] bg-green-500 flex items-center justify-center text-white shadow-xl shadow-green-200 dark:shadow-none transition-transform hover:rotate-3">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                  <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">Reporte de Trabajo Realizado</h2>
                  <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em] mt-1">Evidencias, tiempos y diagnóstico final</p>
                </div>
              </div>

              <div class="space-y-8">
                 <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="md:col-span-3">
                       <FormField v-model="form.trabajo_realizado" label="Detalle de la Intervención" type="textarea" id="trabajo_realizado" placeholder="Explica qué se hizo, piezas cambiadas, pruebas realizadas..." :rows="3" />
                    </div>
                    <FormField v-model="form.tiempo_servicio" label="Tiempo (Min)" type="number" id="tiempo_servicio" :min="0" />
                 </div>

                 <!-- Galería de Evidencias -->
                 <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest transition-colors">Evidencias Visuales Existentes</label>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
                       <!-- Fotos Finales -->
                       <div v-for="(foto, idx) in cita.fotos_finales" :key="idx" class="aspect-square rounded-2xl overflow-hidden bg-slate-100 dark:bg-slate-700 border border-slate-100 dark:border-slate-700 relative group cursor-pointer" @click="openGallery(['/storage/' + foto], 'Evidencia #' + (idx+1))">
                          <img :src="'/storage/' + foto" class="w-full h-full object-cover group-hover:scale-110 transition-transform">
                          <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                             <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                          </div>
                      </div>
                       
                       <!-- Subida de Nuevas -->
                       <label for="new_photos" class="aspect-square rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-all group">
                          <svg class="w-6 h-6 text-slate-300 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                          <span class="text-[9px] font-black text-slate-400 uppercase mt-1">Añadir</span>
                          <input id="new_photos" type="file" multiple class="hidden" accept="image/*" @change="handleNewPhotos">
                       </label>
                    </div>

                    <!-- Previsualización de Nuevas -->
                    <div v-if="previewNewPhotos.length > 0" class="flex flex-wrap gap-3 p-4 bg-slate-50 dark:bg-slate-900/30 rounded-2xl animate-in fade-in">
                       <div v-for="(preview, idx) in previewNewPhotos" :key="idx" class="relative group w-20 h-20 rounded-xl overflow-hidden border-2 border-blue-200 dark:border-blue-900/30">
                          <img :src="preview" class="w-full h-full object-cover">
                          <button type="button" @click="removeNewPhoto(idx)" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity">×</button>
                       </div>
                    </div>
                 </div>
                 
                 <div class="flex flex-col justify-end" v-if="cita.ticket_id && form.estado === 'completado'">
                       <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/30 rounded-2xl flex items-center gap-4 animate-in slide-in-from-top-2">
                         <input type="checkbox" v-model="form.cerrar_ticket" id="cerrar_ticket" class="w-5 h-5 rounded-lg border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500">
                         <label for="cerrar_ticket" class="text-sm font-black text-blue-900 dark:text-blue-300 cursor-pointer">Resolver ticket #{{ cita.ticket_id }} automáticamente</label>
                       </div>
                    </div>
                 
              </div>
            </div>

          </div>

          <!-- STEP 4: RESUMEN FINAL -->
          <div v-show="currentStep === 4" key="step4" class="space-y-8">
            <div class="section-card bg-slate-900 dark:bg-slate-800 dark:border-slate-700 p-12 text-white shadow-2xl relative overflow-hidden">
                <!-- Background decorative element -->
                 <div class="absolute top-0 right-0 p-12 opacity-10 pointer-events-none">
                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                </div>

                <div class="relative z-10">
                    <h2 class="text-3xl font-black tracking-tighter mb-2">Resumen Operativo</h2>
                    <p class="text-blue-400 text-xs font-black uppercase tracking-[0.2em] mb-12">Confirmación de Datos para Envío</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div class="space-y-8">
                            <div class="group">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 group-hover:text-blue-400 transition-colors">Titular de la Cita</p>
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-xl">👤</div>
                                    <p class="text-lg font-black">{{ selectedCliente?.nombre_razon_social || 'No identificado' }}</p>
                                </div>
                            </div>
                            <div class="group">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 group-hover:text-blue-400 transition-colors">Programación (Horario de Atención)</p>
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-xl">📅</div>
                                    <div>
                                        <p class="text-lg font-black uppercase">{{ formatearFecha(form.fecha_hora) }}</p>
                                        <p class="text-xs font-bold text-blue-400 uppercase tracking-tighter">
                                            Rango: {{ formatearHora12(internalTime) }} - {{ formatearHora12(internalEndTime) }}
                                        </p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter mt-1">Asignado a: {{ selectedTecnicoName }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-8">
                             <div class="group">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 group-hover:text-blue-400 transition-colors">Servicio & Equipo</p>
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-xl">🛠️</div>
                                    <div>
                                        <p class="text-lg font-black uppercase">{{ formatearTipoServicioShort(form.tipo_servicio) }}</p>
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-tighter">{{ form.tipo_equipo }} - {{ form.marca_equipo }}</p>
                                        <p v-if="form.folio?.trim()" class="text-xs font-mono font-bold text-amber-300 mt-1">Folio: {{ form.folio.trim() }}</p>
                                    </div>
                                </div>
                            </div>
                             <div class="group">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2 group-hover:text-blue-400 transition-colors">Destino del Servicio</p>
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-xl">📍</div>
                                    <p class="text-sm font-bold text-slate-300 max-w-[250px] leading-relaxed">{{ form.direccion_servicio || 'Dirección no especificada' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-16 p-8 border-t border-white/5 bg-white/5 rounded-[32px]">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center gap-4">
                                <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)] animate-pulse"></div>
                                <p class="text-xs font-bold text-slate-300 uppercase tracking-widest">Listo para procesar cita técnica en el sistema</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <button type="button" @click="prevStep" class="px-8 py-4 bg-white/10 text-white rounded-[22px] font-black uppercase text-xs tracking-[0.2em] hover:bg-white/20 transition-all active:scale-95">
                                    Atrás
                                </button>
                                <button type="button" @click="submit" :disabled="form.processing" class="px-12 py-4 bg-white text-slate-900 rounded-[22px] font-black uppercase text-xs tracking-[0.2em] hover:bg-blue-500 hover:text-white transition-all duration-500 shadow-xl shadow-black/20 hover:scale-105 active:scale-95 disabled:opacity-50">
                                    {{ form.processing ? 'Procesando...' : 'Confirmar y Finalizar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          </div>
        </transition-group>

        <!-- Dynamic Wizards Actions Controls -->
        <div class="fixed bottom-0 left-0 right-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border-t border-slate-200/60 dark:border-slate-800/60 p-5 z-50">
          <div class="max-w-4xl mx-auto flex items-center justify-between">
            <button 
              v-show="currentStep > 1"
              type="button" 
              @click="prevStep" 
              class="flex items-center gap-3 px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black uppercase text-[10px] tracking-widest hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              Atrás
            </button>
            <div v-show="currentStep === 1" class="w-2"></div>

            <div class="flex items-center gap-4">
                <button 
                    type="button"
                    @click="saveDraft"
                    class="hidden md:flex items-center gap-2 px-6 py-3 text-slate-400 dark:text-slate-500 font-bold uppercase text-[10px] tracking-widest hover:text-blue-500 transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Borrador
                </button>
                
                <button 
                v-if="currentStep < 4"
                type="button" 
                @click="nextStep" 
                :disabled="!canProceed"
                class="group flex items-center gap-3 px-10 py-4 rounded-2xl bg-blue-600 text-white font-black uppercase text-xs tracking-[0.2em] hover:bg-blue-700 transition-all shadow-xl shadow-blue-500/20 active:scale-95 disabled:opacity-30 disabled:scale-95 disabled:shadow-none"
                >
                Siguiente
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
          </div>
        </div>
      </form>
    </main>
  </div>

    <!-- Modal Galería -->
    <div v-if="showGalleryModal" class="fixed inset-0 bg-black/95 z-[60] flex items-center justify-center p-6" @click.self="showGalleryModal = false">
       <button @click="showGalleryModal = false" class="absolute top-8 right-8 w-14 h-14 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-full text-white transition-all backdrop-blur-md z-[70]">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
       </button>
       <img :src="galleryImages[0]" class="max-h-[85vh] max-w-[90vw] object-contain rounded-2xl shadow-2xl animate-in zoom-in-95" :key="galleryImages[0]">
    </div>

</template>

<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormField from '@/Components/FormField.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import Swal from 'sweetalert2';
import { OfflineService } from '@/Services/OfflineService';

defineOptions({ layout: AppLayout });

const props = defineProps({
    cita: Object,
    tecnicos: Array,
    clientes: Array,
});

const currentStep = ref(1);
const selectedCliente = ref(null);
const buscarClienteRef = ref(null);
const internalDate = ref('');
const clientePolizas = ref([]);
const isCheckingAvailability = ref(false);
const availabilityError = ref('');
const busySlots = ref([]);
const isFetchingBusy = ref(false);
const dateInputRef = ref(null);


const formatISO = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const z = (n) => n.toString().padStart(2, '0');
    return `${d.getFullYear()}-${z(d.getMonth() + 1)}-${z(d.getDate())}T${z(d.getHours())}:${z(d.getMinutes())}`;
};

const getSafeDefaultDate = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() + 30);
    
    // Si cae en domingo, mover al lunes
    if (now.getDay() === 0) now.setDate(now.getDate() + 1);
    
    // Ajustar hora si está fuera de rango (8-20)
    if (now.getHours() < 8) {
        now.setHours(8, 0, 0, 0);
    } else if (now.getHours() >= 20) {
        now.setDate(now.getDate() + 1);
        now.setHours(8, 0, 0, 0);
    }
    
    // Formatear como YYYY-MM-DDTHH:mm usando la zona local
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const form = useForm({
    cliente_id: props.cita.cliente_id,
    tecnico_id: props.cita.tecnico_id,
    poliza_id: '',
    fecha_hora: formatISO(props.cita.fecha_hora),
    fecha_hora_fin: props.cita.fecha_hora_fin ? formatISO(props.cita.fecha_hora_fin) : '',
    estado: props.cita.estado,
    prioridad: props.cita.prioridad,
    tipo_servicio: props.cita.tipo_servicio,
    descripcion: props.cita.descripcion,
    problema_reportado: props.cita.problema_reportado || props.cita.descripcion,
    tipo_equipo: props.cita.tipo_equipo,
    marca_equipo: props.cita.marca_equipo,
    modelo_equipo: props.cita.modelo_equipo,
    direccion_servicio: props.cita.direccion_servicio,
    trabajo_realizado: props.cita.trabajo_realizado || '',
    tiempo_servicio: props.cita.tiempo_servicio || 0,
    observaciones: props.cita.observaciones || '',
    notas: props.cita.notas || '',
    cerrar_ticket: false,
    nuevas_fotos: [],
    latitud: props.cita.latitud || null,
    longitud: props.cita.longitud || null,
    fecha_gps: props.cita.fecha_gps || null,
    tipo_mantenimiento: props.cita.tipo_mantenimiento || 'preventivo_lavado',
    folio: props.cita.folio || '',
    notify: true,
    _method: 'put'
});


const isOnline = ref(navigator.onLine);
onMounted(() => {
    window.addEventListener('online', () => isOnline.value = true);
    window.addEventListener('offline', () => isOnline.value = false);
});
const previewNewPhotos = ref([]);
const showGalleryModal = ref(false);
const galleryImages = ref([]);

const steps = [
  { id: 1, label: 'Cliente' },
  { id: 2, label: 'Agenda' },
  { id: 3, label: 'Detalles' },
  { id: 4, label: 'Resumen' }
];

const checkAvailability = async () => {
    if (!form.tecnico_id || !form.fecha_hora || form.fecha_hora.startsWith('T')) return;
    
    isCheckingAvailability.value = true;
    availabilityError.value = '';
    
    try {
        const response = await fetch(route('api.citas.check-availability', {
            tecnico_id: form.tecnico_id,
            fecha_hora: form.fecha_hora,
            fecha_hora_fin: form.fecha_hora_fin, // Sincronizar fin con la validación
            cliente_id: form.cliente_id,
            cita_id: props.cita.id // Excluir la cita actual de la verificación
        }));
        const data = await response.json();
        
        if (!data.available) {
            availabilityError.value = data.message;
        }
    } catch (err) {
        console.error('Availability check failed', err);
    } finally {
        isCheckingAvailability.value = false;
    }
};

watch(() => [form.tecnico_id, form.fecha_hora], () => {
    if (form.tecnico_id && form.fecha_hora) checkAvailability();
});

const fetchBusySlots = async () => {
    if (!form.tecnico_id || !internalDate.value) {
        busySlots.value = [];
        return;
    }
    
    isFetchingBusy.value = true;
    try {
        const response = await fetch(route('api.citas.busy-slots', {
            tecnico_id: form.tecnico_id,
            fecha: internalDate.value
        }));
        const data = await response.json();
        if (data.success) {
            busySlots.value = data.slots;
        }
    } catch (err) {
        console.error('Failed to fetch busy slots', err);
    } finally {
        isFetchingBusy.value = false;
    }
};

watch(() => [form.tecnico_id, internalDate.value], () => {
    fetchBusySlots();
});

// Sincronizar descripción con problema_reportado (IGUAL QUE EN MÓVIL)
watch(() => form.descripcion, (newVal) => {
    form.problema_reportado = newVal;
});

// Incluir tipo de mantenimiento en la descripción automáticamente
watch(() => [form.tipo_servicio, form.tipo_mantenimiento], ([service, type]) => {
    if (service === 'mantenimiento') {
        const typeText = tipoMantenimientoOptions.find(o => o.value === type)?.text || type;
        if (!form.descripcion.includes(typeText)) {
            form.descripcion = `[${typeText.toUpperCase()}] ${form.descripcion}`;
        }
    }
});

onMounted(() => {
    
    // Disable saving draft on edit
    // Populate form time based on form.fecha_hora loaded from cita
    if (form.fecha_hora) {
        const fhParts = form.fecha_hora.split('T');
        internalDate.value = fhParts[0];
        internalTime.value = fhParts[1]?.substring(0, 5) || '09:00';
    }
    
    if (form.fecha_hora_fin) {
        const fhendParts = form.fecha_hora_fin.split('T');
        internalEndTime.value = fhendParts[1]?.substring(0, 5) || '10:00';
    } else {
        internalEndTime.value = '10:00';
    }
    
    if (props.clientes) {
       selectedCliente.value = props.clientes.find(c => c.id == props.cita.cliente_id);
    }

    if (form.tecnico_id) fetchBusySlots();
});

const progressWidth = computed(() => ((currentStep.value - 1) / (steps.length - 1)) * 100);

const canProceed = computed(() => {
    if (currentStep.value === 1) return !!form.cliente_id;
    if (currentStep.value === 2) return !!form.tecnico_id && !!form.fecha_hora && !availabilityError.value;
    if (currentStep.value === 3) return !!form.tipo_equipo && !!form.descripcion;
    return true;
});

const nextStep = () => { if (canProceed.value && currentStep.value < 4) currentStep.value++; };
const prevStep = () => { if (currentStep.value > 1) currentStep.value--; };

const internalTime = ref(form.fecha_hora.split('T')[1]?.substring(0, 5) || '09:00');
const internalEndTime = ref(form.fecha_hora_fin ? form.fecha_hora_fin.split('T')[1]?.substring(0, 5) : '');

// Block logic
const clickStep = ref(0)
const selectedStart = ref(internalTime.value ? parseInt(internalTime.value.split(':')[0]) : null)
const selectedEnd = ref(internalEndTime.value ? parseInt(internalEndTime.value.split(':')[0]) : null)

const seleccionarBloque = (hora) => {
    if (clickStep.value === 0) {
        selectedStart.value = hora;
        selectedEnd.value = null; // Wait for end time click
        clickStep.value = 1;
    } else {
        if (hora <= selectedStart.value) {
            if (hora === selectedStart.value) {
                selectedEnd.value = hora + 1; // Double click same block = 1 hour
                clickStep.value = 0;
            } else {
                selectedStart.value = hora; // Clicked earlier time, reset start
            }
        } else {
            selectedEnd.value = hora;
            clickStep.value = 0;
        }
    }
    
    if (selectedStart.value !== null) {
        internalTime.value = `${String(selectedStart.value).padStart(2, '0')}:00`;
    }
    if (selectedEnd.value !== null) {
        internalEndTime.value = `${String(selectedEnd.value).padStart(2, '0')}:00`;
    } else {
        internalEndTime.value = '';
    }
    
    updateDateTimeFromTime();
}

const isBloqueDentroDeRango = (hora) => {
    return selectedStart.value !== null && selectedEnd.value !== null && hora > selectedStart.value && hora < selectedEnd.value;
}

const formatearHoraBloque = (hora) => {
    if (!hora) return '';
    const ampm = hora >= 12 ? 'PM' : 'AM';
    const h12 = hora > 12 ? hora - 12 : (hora === 0 ? 12 : hora);
    return `${h12}:00 ${ampm}`;
}

const updateDateTimeFromTime = () => {
    if (selectedStart.value !== null && selectedEnd.value !== null) {
        form.fecha_hora = `${internalDate.value}T${internalTime.value}`;
        form.fecha_hora_fin = `${internalDate.value}T${internalEndTime.value}`;
    } else {
        form.fecha_hora = '';
        form.fecha_hora_fin = '';
    }
};

// Forzar límites cada que cambie la variable, no solo en @change
watch(() => internalTime.value, (newVal) => {
    if (newVal < '08:00' || newVal > '20:00') updateDateTimeFromTime();
    form.fecha_hora = `${internalDate.value}T${internalTime.value}`;
});
watch(() => internalEndTime.value, (newVal) => {
    if (newVal > '20:00' || newVal < internalTime.value) updateDateTimeFromTime();
    form.fecha_hora_fin = `${internalDate.value}T${internalEndTime.value}`;
});

const setDuration = (hours) => {
    if (!internalTime.value) return;
    
    const [h, m] = internalTime.value.split(':').map(Number);
    let newHour = h + hours;
    
    // Validar tope de 8pm (20:00)
    if (newHour > 20) {
        newHour = 20;
        Swal.fire({
            title: 'Ajuste de Horario',
            text: 'La cita no puede terminar después de las 8:00 P.M.',
            icon: 'info',
            toast: true,
            position: 'top-end',
            timer: 3000,
            showConfirmButton: false
        });
    }
    
    internalEndTime.value = `${newHour.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
    updateDateTimeFromTime();
};

const updateDateTime = () => {
    // Asegurar que la hora está en el rango correcto
    if (internalTime.value < '08:00') internalTime.value = '08:00';
    if (internalTime.value > '20:00') internalTime.value = '20:00';
    if (internalEndTime.value > '20:00') internalEndTime.value = '20:00';

    const newDateTimeStr = `${internalDate.value}T${internalTime.value}`;
    const date = new Date(newDateTimeStr);

    if (date.getDay() === 0) {
        Swal.fire('Día no laboral', 'No se pueden programar citas los domingos.', 'warning');
        date.setDate(date.getDate() + 1);
        internalDate.value = date.toISOString().split('T')[0];
    }
    
    form.fecha_hora = `${internalDate.value}T${internalTime.value}`;
    form.fecha_hora_fin = `${internalDate.value}T${internalEndTime.value}`;
};

const onClienteSeleccionado = (cliente) => {
    selectedCliente.value = cliente;
    form.cliente_id = cliente?.id || '';
    form.poliza_id = '';
    clientePolizas.value = [];
    
    if (cliente?.calle) {
        form.direccion_servicio = `${cliente.calle} ${cliente.num_exterior || ''}, ${cliente.colonia || ''}, ${cliente.municipio || ''}`.replace(/\s+,/g, ',');
    }
    
    if (cliente?.id) {
        fetch(route('api.clientes.polizas', cliente.id))
            .then(res => res.json())
            .then(data => {
                clientePolizas.value = data.polizas || [];
                if (clientePolizas.value.length === 1) form.poliza_id = clientePolizas.value[0].id;
            })
            .catch(err => console.log('Polizas load fail', err));
    }
};

const onInputToUpper = (field) => { form[field] = form[field].toUpperCase(); };
const onCrearNuevoCliente = (text) => window.open(route('clientes.create', { name: text }), '_blank');

const saveDraft = () => {
    sessionStorage.setItem('cita_wizard_draft', JSON.stringify({ data: form.data(), step: currentStep.value }));
    Swal.fire({ title: 'Borrador Guardado', icon: 'success', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
};


const submit = async () => {
    await getLocation();

    if (!isOnline.value) {
        try {
            await OfflineService.enqueueReport(props.cita.id, form.data());
            Swal.fire({
                title: 'Reporte Guardado Localmente',
                text: 'No tienes conexión. El reporte se enviará automáticamente cuando recuperes la señal.',
                icon: 'info',
                confirmButtonText: 'Entendido'
            });
            return;
        } catch (err) {
            console.error('Offline save failed', err);
            Swal.fire('Error', 'No se pudo guardar el reporte localmente.', 'error');
            return;
        }
    }

    form.post(route('citas.update', props.cita.id), {
        onSuccess: () => Swal.fire('Actualizado', 'La información se guardó correctamente', 'success'),
        onError: (errors) => {
            console.error('Validation errors:', errors);
            const errorMsg = Object.values(errors).join('\\n');
            Swal.fire('Verifica los campos', errorMsg || 'Hay errores en la información.', 'error');
        }
    });
};


const selectedTecnicoName = computed(() => {
    const t = props.tecnicos.find((tecnico) => String(tecnico.id) === String(form.tecnico_id));
    return t ? getTecnicoDisplayName(t) : 'Sin asignar';
});

const formatearFecha = (fh) => {
    if (!fh) return 'Pendiente';
    return new Date(fh).toLocaleString('es-MX', { 
        weekday: 'long', day: 'numeric', month: 'long'
    });
};

const formatearHora12 = (timeStr) => {
    if (!timeStr) return '---';
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'P.M.' : 'A.M.';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${m} ${ampm}`;
};

const formatearTipoServicioShort = (t) => tipoServicioOptions.find(o => o.value === t)?.text;

const todayDate = new Date().toISOString().split('T')[0];

const prioridadOptions = [
    { value: 'baja', text: 'Baja - Programable' },
    { value: 'media', text: 'Media - Normal' },
    { value: 'alta', text: 'Alta - Prioritaria' },
    { value: 'urgente', text: 'URGENTE - Inmediata' }
];

const tipoServicioOptions = [
    { value: 'garantia', text: 'Garantía' },
    { value: 'instalacion', text: 'Instalación' },
    { value: 'reparacion', text: 'Reparación' },
    { value: 'mantenimiento', text: 'Mantenimiento' },
    { value: 'diagnostico', text: 'Diagnóstico' },
    { value: 'servicio_limpieza', text: 'Servicio limpieza' },
    { value: 'otro', text: 'Otro' }
];

const tipoMantenimientoOptions = [
    { value: 'preventivo_lavado', text: 'Mantenimiento Preventivo (Lavado/Limpieza)' },
    { value: 'preventivo_basico', text: 'Mantenimiento Preventivo Básico' },
    { value: 'correctivo_menor', text: 'Mantenimiento Correctivo (Carga de gas/Piezas)' },
    { value: 'integral', text: 'Mantenimiento Integral' }
];

const tipoEquipoOptions = [
    { value: 'minisplit', text: 'Minisplit' },
    { value: 'aire_acondicionado', text: 'Aire Acondicionado' },
    { value: 'paquete', text: 'Unidad Paquete' },
    { value: 'refrigerador', text: 'Refrigerador' },
    { value: 'congelador', text: 'Congelador' },
    { value: 'enfriador_agua', text: 'Enfriador de Agua' },
    { value: 'lavadora', text: 'Lavadora' },
    { value: 'secadora', text: 'Secadora' },
    { value: 'estufa', text: 'Estufa' },
    { value: 'microondas', text: 'Microondas' },
    { value: 'lavavajillas', text: 'Lavavajillas' },
    { value: 'boiler', text: 'Boiler' },
    { value: 'otro_equipo', text: 'Otro Equipo / Electrodoméstico' }
];

const estadoOptions = [
    { value: 'pendiente', text: 'Pendiente' },
    { value: 'programado', text: 'Programado' },
    { value: 'en_proceso', text: 'En Proceso' },
    { value: 'completado', text: 'Completado' },
    { value: 'cancelado', text: 'Cancelado' }
];

const marcasComunes = ['MIRAGE', 'LG', 'TCL', 'MABE', 'SAMSUNG'];

const getTecnicoDisplayName = (tecnico) => tecnico?.nombre || tecnico?.name || 'Sin nombre';

const getTecnicoInitial = (tecnico) => getTecnicoDisplayName(tecnico).charAt(0).toUpperCase() || '?';

const getLocation = () => {
    return new Promise((resolve) => {
        if (!navigator.geolocation) {
            console.warn('Geolocation not supported');
            return resolve(null);
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                form.latitud = pos.coords.latitude;
                form.longitud = pos.coords.longitude;
                form.fecha_gps = new Date().toISOString();
                resolve(pos.coords);
            },
            (err) => {
                console.warn('Geolocation error:', err.message);
                resolve(null);
            },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    });
};

const handleNewPhotos = (e) => {
    const files = Array.from(e.target.files);
    files.forEach(file => {
        form.nuevas_fotos.push(file);
        const reader = new FileReader();
        reader.onload = (e) => previewNewPhotos.value.push(e.target.result);
        reader.readAsDataURL(file);
    });
};

const removeNewPhoto = (idx) => {
    form.nuevas_fotos.splice(idx, 1);
    previewNewPhotos.value.splice(idx, 1);
};

const openGallery = (imgs, title) => {
    galleryImages.value = imgs;
    showGalleryModal.value = true;
};

</script>

<style scoped>
.glass-morphism {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 40px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.05);
}
.dark .glass-morphism {
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.05);
}
.section-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }

.premium-search :deep(input) {
    height: 60px !important;
    border-radius: 20px !important;
    font-size: 1.1rem !important;
    font-weight: 700 !important;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    display: none;
    -webkit-appearance: none;
}
</style>
