<template>
  <div class="citas-wizard min-h-screen bg-[var(--ui-surface)] transition-colors duration-500">
    <Head title="Agendar Nueva Cita" />

    <!-- Progress Header Premium -->
    <div class="sticky top-0 z-40 bg-white/70 dark:bg-slate-800/50 backdrop-blur-xl border-b border-slate-200/60 dark:border-slate-800/60 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col py-4">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-4">
              <Link :href="route('citas.index')" class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-brand-600 dark:hover:text-blue-400 hover:bg-slate-50 dark:hover:bg-blue-900/20 transition-all duration-200 group">
                <svg class="w-4 h-4 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              </Link>
              <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Agendar <span class="text-blue-600 dark:text-blue-400">Cita Técnica</span></h1>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.15em] mt-0.5">{{ $page.props.empresa_config?.nombre_empresa || 'Empresa' }} &bull; Step {{ currentStep }} of 4</p>
              </div>
            </div>

            <!-- Stats/Context Fast View -->
             <div class="hidden md:flex items-center gap-2">
                <div v-if="internalTime && internalEndTime" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-sky-50 dark:bg-sky-900/20 border border-sky-200 dark:border-sky-800/30 animate-fade-in text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-wide shadow-sm">
                    {{ internalTime }} - {{ internalEndTime }}
                </div>
                <div v-if="selectedCliente" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200/50 dark:border-slate-700/50 animate-fade-in">
                    <div class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></div>
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-200 truncate max-w-[150px]">{{ selectedCliente.nombre_razon_social }}</span>
                </div>
            </div>
          </div>

          <!-- Stepper Visual -->
          <div class="flex items-center justify-between relative mt-2 px-2">
            <!-- Line background -->
            <div class="absolute left-0 right-0 h-1 bg-slate-200 dark:bg-slate-800 top-1/2 -translate-y-1/2 rounded-full overflow-hidden">
               <div 
                class="h-full bg-blue-600 dark:bg-brand-500 transition-all duration-700 ease-out shadow-[0_0_15px_rgba(37,99,235,0.4)]"
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
                  currentStep === step.id ? 'bg-blue-600 border-blue-600 text-white scale-110 shadow-xl shadow-sky-200 dark:shadow-none' : 
                  currentStep > step.id ? 'bg-brand-500 border-emerald-500 text-white' : 
                  'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-400 group-hover:border-brand-500 dark:group-hover:border-brand-500'
                ]"
              >
                <template v-if="currentStep > step.id">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template v-else>{{ step.id }}</template>
              </div>
              <span 
                class="absolute -bottom-7 whitespace-nowrap text-[10px] font-black uppercase tracking-wide transition-colors duration-200"
                :class="[currentStep >= step.id ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500']"
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
          leave-active-class="absolute top-0 w-full transition-all duration-200 ease-in" 
          leave-from-class="opacity-100 translate-x-0" 
          leave-to-class="opacity-0 -translate-x-8"
        >
          
          <!-- STEP 1: CLIENTE -->
          <div v-show="currentStep === 1" key="step1" class="space-y-6">
            <div class="section-card glass-morphism p-8 md:p-12">
               <div class="flex items-center gap-5 mb-12">
                  <div class="w-16 h-16 rounded-[24px] bg-blue-600 flex items-center justify-center text-white shadow-xl shadow-sky-200 dark:shadow-none transition-transform hover:rotate-3">
                     <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
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
                  <div v-if="selectedCliente && clientePolizas.length > 0" class="animate-scale-in p-6 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/30 rounded-[24px]">
                    <div class="flex items-start gap-4">
                      <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center text-emerald-600 dark:text-slate-400 font-bold shrink-0">🛡️</div>
                      <div class="flex-1">
                        <p class="text-[10px] font-black text-emerald-600 dark:text-slate-400 uppercase tracking-wide mb-1">Protección Activa</p>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ clientePolizas[0].nombre }} <span class="ml-2 font-normal opacity-60">#{{ clientePolizas[0].folio }}</span></h4>
                        
                        <div class="grid grid-cols-2 gap-4 mt-4 text-[11px] font-bold text-emerald-800 dark:text-emerald-200 dark:text-emerald-200 dark:text-emerald-300 uppercase tracking-wide">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-brand-500"></div>
                                {{ clientePolizas[0].visitas_disponibles }} Visitas Disponibles
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-brand-500"></div>
                                Cobertura 24/7
                            </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-else-if="selectedCliente && !form.ticket_id" class="p-6 bg-[var(--ui-surface)] dark:bg-slate-800/50 rounded-[24px] border border-slate-200/50 dark:border-slate-700/50 text-center">
                      <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wide">Sin póliza activa vinculada</p>
                      <p class="text-[11px] text-slate-400 mt-1 italic">Este servicio será facturado a precio regular</p>
                  </div>
               </div>
            </div>
          </div>

          <!-- STEP 2: LOGÍSTICA & ESTADÍA -->
          <div v-show="currentStep === 2" key="step2" class="space-y-6">
            <div class="section-card glass-morphism p-8 md:p-12">
               <div class="flex items-center gap-5 mb-12">
                  <div class="w-16 h-16 rounded-[24px] bg-brand-500 flex items-center justify-center text-white shadow-xl shadow-brand-200 dark:shadow-none transition-transform hover:rotate-3">
                     <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </div>
                  <div>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">Agenda & Especialista</h2>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em] mt-1">Sincronización de tiempos y recursos</p>
                  </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                  <!-- Columna Izquierda: Especialista y Prioridad -->
                  <div class="space-y-12">
                     <div class="space-y-6">
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-wide pl-1">Selección de Especialista</label>
                        <div class="grid grid-cols-1 gap-3">
                            <button 
                                v-for="tecnico in tecnicos" :key="tecnico.id" 
                                type="button"
                                @click="form.tecnico_id = tecnico.id"
                                class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all duration-200"
                                :class="[form.tecnico_id === tecnico.id ? 'bg-sky-50 dark:bg-sky-900/20 border-sky-500 shadow-sm' : 'bg-white dark:bg-black/50 border-slate-100 dark:border-slate-800 hover:border-brand-500 dark:hover:border-brand-500']"
                            >
                                <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center font-black text-slate-500">
                                    {{ tecnico.nombre.charAt(0) }}
                                </div>
                                <div class="text-left flex-1">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ tecnico.nombre }}</p>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide mt-0.5">Disponibilidad Inmediata</p>
                                </div>
                                <div v-if="form.tecnico_id === tecnico.id" class="text-blue-500">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                </div>
                            </button>
                        </div>
                        <div v-if="form.errors.tecnico_id" class="text-xs text-rose-500 font-bold px-2 mt-2 tracking-tight">{{ form.errors.tecnico_id }}</div>

                        <!-- Selección de Ayudante (Opcional) -->
                        <div class="space-y-6 mt-6">
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-wide pl-1">Selección de Ayudante (Opcional)</label>
                            <div class="grid grid-cols-1 gap-3">
                                <!-- Opción para no asignar ayudante -->
                                <button 
                                    type="button"
                                    @click="form.ayudante_id = ''"
                                    class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all duration-200"
                                    :class="[!form.ayudante_id ? 'bg-sky-50 dark:bg-sky-900/20 border-sky-500 shadow-sm' : 'bg-white dark:bg-black/50 border-slate-100 dark:border-slate-800 hover:border-brand-500 dark:hover:border-brand-500']"
                                >
                                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-black text-slate-400 text-lg">
                                        ✕
                                    </div>
                                    <div class="text-left flex-1">
                                        <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">Sin Ayudante</p>
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide mt-0.5">Asignar solo técnico líder</p>
                                    </div>
                                    <div v-if="!form.ayudante_id" class="text-blue-500">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    </div>
                                </button>

                                <template v-for="tecnico in tecnicos" :key="'ayudante-' + tecnico.id">
                                    <button 
                                        v-if="tecnico.id !== form.tecnico_id"
                                        type="button"
                                        @click="form.ayudante_id = tecnico.id"
                                        class="flex items-center gap-4 p-4 rounded-2xl border-2 transition-all duration-200"
                                        :class="[form.ayudante_id === tecnico.id ? 'bg-sky-50 dark:bg-sky-900/20 border-sky-500 shadow-sm' : 'bg-white dark:bg-black/50 border-slate-100 dark:border-slate-800 hover:border-brand-500 dark:hover:border-brand-500']"
                                    >
                                        <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center font-black text-slate-500">
                                            {{ tecnico.nombre.charAt(0) }}
                                        </div>
                                        <div class="text-left flex-1">
                                            <p class="text-sm font-bold text-slate-900 dark:text-white leading-tight">{{ tecnico.nombre }}</p>
                                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-wide mt-0.5">Ayudante Técnico</p>
                                        </div>
                                        <div v-if="form.ayudante_id === tecnico.id" class="text-blue-500">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        </div>
                                    </button>
                                </template>
                            </div>
                            <div v-if="form.errors.ayudante_id" class="text-xs text-rose-500 font-bold px-2 mt-2 tracking-tight">{{ form.errors.ayudante_id }}</div>
                        </div>
                        
                        <!-- Availability Warning -->
                        <div v-if="availabilityError" class="animate-shake mt-4 p-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800/30 rounded-xl flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p class="text-[11px] font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">{{ availabilityError }}</p>
                        </div>
                     </div>

                     <!-- Grado de Prioridad -->
                     <div class="space-y-6">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide pl-1">Grado de Prioridad</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button 
                                v-for="opcion in prioridadOptions" :key="opcion.value"
                                type="button"
                                @click="form.prioridad = opcion.value"
                                :class="[
                                    'py-4 px-2 rounded-2xl text-[10px] sm:text-xs font-bold transition-all border-2 text-center',
                                    form.prioridad === opcion.value 
                                        ? (opcion.value === 'urgente' ? 'bg-rose-600 text-white border-rose-600 shadow-xl shadow-rose-500/30' : 
                                           opcion.value === 'alta' ? 'bg-brand-500 text-white border-brand-500 shadow-xl shadow-brand-500/30' :
                                           opcion.value === 'media' ? 'bg-blue-600 text-white border-blue-600 shadow-xl shadow-sky-500/30' :
                                           'bg-emerald-600 text-white border-emerald-600 shadow-xl shadow-emerald-500/20')
                                        : 'bg-white dark:bg-slate-900/50 border-slate-100 dark:border-slate-800 text-slate-400 hover:border-brand-500 dark:hover:border-brand-500'
                                ]"
                            >
                                {{ opcion.text }}
                            </button>
                        </div>
                        <p v-if="form.errors.prioridad" class="text-xs text-rose-500 mt-1 font-bold">{{ form.errors.prioridad }}</p>
                     </div>
                  </div>

                  <!-- Columna Derecha: Fecha y Servicio -->
                  <div class="space-y-12">
                     <!-- Date Selector UI Premium -->
                     <div class="space-y-6">
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-wide pl-1">Fecha & Hora</label>
                        <div class="w-full px-8 py-8 bg-white dark:bg-slate-800 rounded-[40px] shadow-2xl shadow-sky-500/10 border border-slate-300 dark:border-slate-600 flex flex-col items-center justify-center transition-all hover:border-brand-500 group">
                            <div class="w-full flex flex-col items-center gap-6">
                                <!-- Selector de Fecha Grande -->
                                <div class="relative group w-full flex items-center justify-center">
                                    <input 
                                        ref="dateInputRef"
                                        type="date" 
                                        v-model="internalDate" 
                                        :min="todayDate"
                                        @change="updateDateTime()"
                                        class="w-full max-w-xs bg-[var(--ui-surface)] dark:bg-slate-800/50 border-2 border-slate-100 dark:border-slate-800 focus:border-brand-500 rounded-[24px] pl-6 pr-16 py-4 text-2xl font-black text-slate-900 dark:text-white transition-all outline-none text-center cursor-pointer hover:bg-white dark:hover:bg-slate-900"
                                    >
                                    <button 
                                        type="button" 
                                        @click="dateInputRef?.showPicker()"
                                        class="absolute right-3 w-10 h-10 bg-sky-600 hover:bg-sky-700 rounded-[18px] flex items-center justify-center text-white shadow-xl transition-transform hover:scale-105 active:scale-95 z-10"
                                        title="Seleccionar Fecha"
                                    >
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2-2v12a2 2 0 002 2z"/></svg>
                                    </button>
                                </div>
                                
                                <!-- Rango de Tiempo Horizontal -->
                                <div class="w-full pt-4 border-t border-slate-100 dark:border-slate-800">
                                    <p class="text-center text-[9px] font-black text-slate-500 uppercase tracking-wide mb-3 flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Selecciona el Horario (Clic: Inicio y Fin)
                                    </p>
                                    <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                                        <button 
                                            v-for="hora in 13" :key="hora"
                                            type="button"
                                            @click="seleccionarBloque(hora + 7)"
                                            :disabled="isSlotBusy(hora + 7)"
                                            :class="[
                                                'py-2 rounded-xl text-xs font-bold transition-all border',
                                                isSlotBusy(hora + 7)
                                                    ? 'bg-rose-100 dark:bg-rose-900/40 border-rose-300 dark:border-rose-800 text-rose-500 cursor-not-allowed opacity-60'
                                                    : ((hora + 7) === selectedStart
                                                        ? 'bg-brand-500 text-white border-emerald-600 shadow-md shadow-emerald-500/20 ring-2 ring-offset-1 ring-emerald-400 dark:ring-offset-slate-900'
                                                        : ((hora + 7) === selectedEnd
                                                            ? 'bg-brand-500 text-white border-rose-600 shadow-md shadow-rose-500/30 ring-2 ring-offset-1 ring-rose-400 dark:ring-offset-slate-900'
                                                            : (isBloqueDentroDeRango(hora + 7) 
                                                                ? 'bg-sky-100 border-blue-300 text-sky-800 dark:text-sky-200 dark:bg-sky-900/40 dark:border-blue-700/60 dark:text-blue-300 scale-105'
                                                                : 'bg-white dark:bg-black/50 border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 hover:border-brand-500 hover:bg-slate-50 dark:hover:bg-blue-900/20')))
                                            ]"
                                        >
                                            {{ formatearHoraBloque(hora + 7) }}
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between w-full px-5 py-4 bg-[var(--ui-surface)] dark:bg-black/50 rounded-2xl border border-slate-100 dark:border-slate-800/50 mt-2">
                                    <div class="flex flex-col items-start">
                                        <span class="text-[9px] font-black text-blue-500 uppercase tracking-wide mb-1">Inicia</span>
                                        <span class="text-sm font-black text-slate-800 dark:text-white bg-white dark:bg-slate-800 px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-600 shadow-sm">{{ formatearHoraBloque(selectedStart) || '--:--' }}</span>
                                    </div>
                                    <div class="text-slate-300 dark:text-slate-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="text-[9px] font-black text-rose-500 uppercase tracking-wide mb-1">Finaliza</span>
                                        <span class="text-sm font-black text-slate-800 dark:text-white bg-white dark:bg-slate-800 px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-600 shadow-sm">{{ formatearHoraBloque(selectedEnd) || '--:--' }}</span>
                                    </div>
                                </div>

                                <div v-if="busySlots.length > 0 || isOnVacation" class="w-full mt-4 space-y-2">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-wide text-center">
                                        {{ isOnVacation ? 'Estado del Día' : 'Horarios Ocupados' }}
                                    </p>
                                    <div v-if="isOnVacation" class="p-4 bg-brand-50 dark:bg-brand-900/20 border border-brand-200 dark:border-brand-800/30 rounded-xl text-center">
                                        <p class="text-[11px] font-bold text-brand-600 dark:text-brand-400 uppercase">🌴 {{ vacationMessage || 'Técnico de vacaciones / descanso' }}</p>
                                    </div>
                                    <div v-else class="flex flex-wrap justify-center gap-2">
                                        <div 
                                            v-for="slot in busySlots" :key="slot.id"
                                            class="px-3 py-1 rounded-full bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-900/30 text-[9px] font-bold text-rose-500 uppercase flex items-center gap-1"
                                        >
                                            <div class="w-1 h-1 rounded-full bg-brand-500"></div>
                                            {{ slot.start }} - {{ slot.end }}
                                        </div>
                                    </div>
                                </div>
                                <div v-else-if="form.tecnico_id && !isFetchingBusy" class="w-full mt-4">
                                    <p class="text-[9px] font-black text-emerald-500 uppercase tracking-wide text-center">Todo el día disponible</p>
                                </div>
                            </div>
                            
                            <p v-if="form.errors.fecha_hora || form.errors.fecha_hora_fin" class="text-[10px] text-rose-600 dark:text-rose-400 font-bold uppercase tracking-wider text-center mt-4">
                                {{ form.errors.fecha_hora || form.errors.fecha_hora_fin }}
                            </p>
                        </div>
                     </div>

                     <!-- Tipo de Servicio -->
                     <div class="space-y-6">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide pl-1">Tipo de Servicio <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <button 
                                v-for="opcion in tipoServicioOptions" :key="opcion.value"
                                type="button"
                                @click="form.tipo_servicio = opcion.value"
                                :class="[
                                    'py-4 px-2 rounded-2xl text-[10px] sm:text-xs font-bold transition-all border-2 flex flex-col items-center justify-center gap-2 text-center',
                                    form.tipo_servicio === opcion.value 
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-xl shadow-indigo-500/30 scale-[1.02]'
                                        : 'bg-white dark:bg-slate-900/50 border-slate-100 dark:border-slate-800 text-slate-400 hover:border-brand-300 dark:hover:border-brand-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/20'
                                ]"
                            >
                                <font-awesome-icon :icon="getServicioIcon(opcion.value)" class="text-xl mb-1" />
                                <span>{{ opcion.text }}</span>
                            </button>
                        </div>
                     <!-- Campo Condicional: Tipo de Mantenimiento -->
                     <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="transform -translate-y-4 opacity-0"
                        enter-to-class="transform translate-y-0 opacity-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="transform translate-y-0 opacity-100"
                        leave-to-class="transform -translate-y-4 opacity-0"
                     >
                        <div v-if="form.tipo_servicio === 'mantenimiento'" class="p-6 bg-sky-50 dark:bg-sky-900/20 rounded-xl border border-sky-200 dark:border-sky-800/30">
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
        </div>
          <!-- STEP 3: EQUIPO Y DIAGNÓSTICO -->
          <div v-show="currentStep === 3" key="step3" class="space-y-6">
             <div class="section-card glass-morphism p-8 md:p-12">
               <div class="flex items-center gap-5 mb-12">
                  <div class="w-16 h-16 rounded-[24px] bg-indigo-500 flex items-center justify-center text-white shadow-xl shadow-indigo-200 dark:shadow-none transition-transform hover:rotate-3">
                     <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  </div>
                  <div>
                    <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">Detalles del Equipo</h2>
                    <p class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.1em] mt-1">Especificaciones técnicas y fallas</p>
                  </div>
               </div>

               <div class="space-y-10">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-3 space-y-4">
                            <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide mb-3 pl-1">Categoría de Equipo</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                <button 
                                    v-for="opt in tipoEquipoOptions" :key="opt.value"
                                    type="button"
                                    @click="form.tipo_equipo = opt.value"
                                    class="py-3 px-2 rounded-2xl text-[10px] sm:text-xs font-bold transition-all border-2 flex flex-col items-center justify-center gap-2 text-center"
                                    :class="[
                                        form.tipo_equipo === opt.value 
                                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-xl shadow-indigo-500/30 scale-[1.02]' 
                                            : 'bg-white dark:bg-slate-900/50 border-slate-100 dark:border-slate-800 text-slate-400 hover:border-amber-300'
                                    ]"
                                >
                                    <font-awesome-icon :icon="getEquipoIcon(opt.value)" class="text-lg sm:text-xl mb-0.5" />
                                    <span>{{ opt.text }}</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="space-y-1.5">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-wide pl-1">Marca (Opcional)</label>
                            <input v-model="form.marca_equipo" list="marcas-list" class="w-full h-12 px-5 bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-brand-500/10 focus:border-brand-500 transition-all font-bold text-slate-800 dark:text-white" placeholder="Escribe la marca o elige sugerencia" @input="onInputToUpper('marca_equipo')">
                            <datalist id="marcas-list">
                                <option v-for="m in marcasComunes" :key="m" :value="m" />
                            </datalist>
                        </div>

                        <FormField v-model="form.modelo_equipo" label="Modelo (Opcional)" id="modelo_equipo" placeholder="AR12..." @input="onInputToUpper('modelo_equipo')" />
                    </div>

                    <FormField
                        v-model="form.folio"
                        label="Folio tienda / servicio (opcional)"
                        id="folio_cita"
                        placeholder="Ej. folio Liverpool, LG, Mirage…"
                        :error="form.errors.folio"
                    />
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 -mt-6 mb-2 px-1">
                      Si lo dejas vacío, se genera el folio interno de la cita automáticamente.
                    </p>

                    <div class="grid grid-cols-1 gap-8">
                        <FormField 
                            v-model="form.descripcion" 
                            label="Descripción (Visible para el Cliente)" 
                            type="textarea" 
                            id="descripcion" 
                            :rows="15" 
                            :placeholder="form.tipo_servicio === 'instalacion' ? 'Describe los detalles de la instalación (ej. piso, altura, materiales)...' : 'Describe brevemente la situación o requerimiento...'" 
                            :error="form.errors.descripcion" 
                        />

                        <FormField 
                            v-model="form.notas_internas" 
                            label="Notas Internas (Solo Capturista y Técnico)" 
                            type="textarea" 
                            id="notas_internas" 
                            :rows="3" 
                            placeholder="Notas que no verá el cliente..." 
                            :error="form.errors.notas_internas" 
                        />

                        <div class="space-y-4">
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-wide pl-1">Imágenes enviadas por el Cliente</label>
                            <div class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-[32px] hover:border-brand-500 dark:hover:border-brand-500 transition-all group relative overflow-hidden">
                                <input 
                                    type="file" 
                                    multiple 
                                    accept="image/*"
                                    @change="(e) => form.evidencias_previas = Array.from(e.target.files)"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                />
                                <div class="text-center">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-400 group-hover:text-brand-500 transition-colors">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-600 dark:text-slate-300">Haz clic o arrastra imágenes aquí</p>
                                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-1">Sube las fotos que te envió el cliente</p>
                                </div>
                            </div>
                            
                            <!-- Preview de imágenes seleccionadas -->
                            <div v-if="form.evidencias_previas?.length > 0" class="flex flex-wrap gap-3 mt-4">
                                <div v-for="(file, index) in form.evidencias_previas" :key="index" class="relative w-20 h-20 rounded-xl overflow-hidden shadow-md">
                                    <img :src="URL.createObjectURL(file)" class="w-full h-full object-cover" />
                                    <button @click.prevent="form.evidencias_previas.splice(index, 1)" class="absolute top-1 right-1 bg-rose-500 text-white rounded-full p-1 shadow-lg">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </div>
                            <p v-if="form.errors.evidencias_previas" class="text-xs text-rose-500 font-bold">{{ form.errors.evidencias_previas }}</p>
                        </div>

                    </div>
               </div>
             </div>
          </div>

          <!-- STEP 4: RESUMEN FINAL -->
          <div v-show="currentStep === 4" key="step4" class="space-y-6">
            <div class="section-card bg-slate-900 dark:bg-slate-800 dark:border-slate-700 p-12 text-white shadow-2xl relative overflow-hidden">
                <!-- Background decorative element -->
                 <div class="absolute top-0 right-0 p-12 opacity-10 pointer-events-none">
                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                </div>

                <div class="relative z-10">
                    <h2 class="text-2xl font-black tracking-tighter mb-2">Resumen Operativo</h2>
                    <p class="text-blue-400 text-xs font-black uppercase tracking-[0.2em] mb-12">Confirmación de Datos para Envío</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div class="space-y-6">
                            <div class="group">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-wide mb-2 group-hover:text-blue-400 transition-colors">Titular de la Cita</p>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-xl">👤</div>
                                    <p class="text-lg font-black">{{ selectedCliente?.nombre_razon_social || 'No identificado' }}</p>
                                </div>
                            </div>
                            <div class="group">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-wide mb-2 group-hover:text-blue-400 transition-colors">Programación (Horario de Atención)</p>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-xl">📅</div>
                                    <div>
                                        <p class="text-lg font-black uppercase">{{ formatearFecha(form.fecha_hora) }}</p>
                                        <p class="text-xs font-bold text-blue-400 uppercase tracking-wide">
                                            Rango: {{ formatearHora12(internalTime) }} - {{ formatearHora12(internalEndTime) }}
                                        </p>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mt-1">Asignado a: {{ selectedTecnicoName }}</p>
                                        <p v-if="selectedAyudanteName" class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mt-0.5">Ayudante: {{ selectedAyudanteName }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                             <div class="group">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-wide mb-2 group-hover:text-blue-400 transition-colors">Servicio & Equipo</p>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-xl">🛠️</div>
                                    <div>
                                        <p class="text-lg font-black uppercase">{{ formatearTipoServicioShort(form.tipo_servicio) }}</p>
                                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">{{ form.tipo_equipo }} - {{ form.marca_equipo }}</p>
                                        <p v-if="form.folio?.trim()" class="text-xs font-mono font-bold text-brand-300 mt-1">Folio: {{ form.folio.trim() }}</p>
                                    </div>
                                </div>
                            </div>
                             <div class="group">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-wide mb-2 group-hover:text-blue-400 transition-colors">Destino del Servicio</p>
                                <div class="flex items-start gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center text-xl">📍</div>
                                    <p class="text-sm font-bold text-slate-300 max-w-[250px] leading-relaxed">{{ selectedCliente?.calle ? `${selectedCliente.calle} ${selectedCliente.num_exterior || ''}, ${selectedCliente.colonia || ''}` : 'Dirección no especificada' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-16 p-8 border-t border-white/5 bg-white/5 rounded-[32px]">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center gap-4">
                                <div class="w-2 h-2 rounded-full bg-brand-500 shadow-[0_0_8px_rgba(16,185,129,0.6)] animate-pulse"></div>
                                <p class="text-xs font-bold text-slate-300 uppercase tracking-wide">Listo para procesar cita técnica en el sistema</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <button type="button" @click="prevStep" class="px-8 py-4 bg-white/10 text-white rounded-[22px] font-black uppercase text-xs tracking-[0.2em] hover:bg-white/20 transition-all active:scale-95">
                                    Atrás
                                </button>
                                <button type="button" @click="submit" :disabled="form.processing" class="px-12 py-4 bg-white text-slate-900 rounded-[22px] font-black uppercase text-xs tracking-[0.2em] hover:bg-slate-500 hover:text-white transition-all duration-500 shadow-xl shadow-black/20 hover:scale-105 active:scale-95 disabled:opacity-50">
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
        <div class="fixed bottom-0 left-0 right-0 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl border-t border-slate-200/60 dark:border-slate-800/60 p-5 z-50">
          <div class="max-w-4xl mx-auto flex items-center justify-between">
            <button 
              v-show="currentStep > 1"
              type="button" 
              @click="prevStep" 
              class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-200 font-black uppercase text-[10px] tracking-wide hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              Atrás
            </button>
            <div v-show="currentStep === 1" class="w-2"></div>

            <div class="flex items-center gap-4">
                <button 
                    type="button"
                    @click="saveDraft"
                    class="hidden md:flex items-center gap-2 px-6 py-3 text-slate-400 dark:text-slate-500 font-bold uppercase text-[10px] tracking-wide hover:text-blue-500 transition-all"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Borrador
                </button>
                
                <button 
                v-if="currentStep < 4"
                type="button" 
                @click="nextStep" 
                :disabled="!canProceed"
                class="group flex items-center gap-2 px-10 py-4 rounded-2xl bg-blue-600 text-white font-black uppercase text-xs tracking-[0.2em] hover:bg-blue-700 transition-all shadow-xl shadow-sky-500/20 active:scale-95 disabled:opacity-30 disabled:scale-95 disabled:shadow-none"
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
</template>

<script setup>
import { useFormatters } from '@/Composables/useFormatters';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormField from '@/Components/FormField.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import Swal from '@/Utils/Swal';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { 
    faShieldAlt, faWrench, faCogs, faBroom, faSearch, faSprayCan, faMapPin,
    faSnowflake, faThermometerHalf, faBox, faIceCream, faWind, faTshirt, faFire, faMicrochip, faUtensils
} from '@fortawesome/free-solid-svg-icons';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tecnicos: Array,
    clientes: Array,
    ticket_id: [String, Number],
    prefill: Object
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
const isOnVacation = ref(false);
const vacationMessage = ref('');

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
    cliente_id: props.prefill?.cliente_id || '',
    tecnico_id: props.prefill?.tecnico_id || '',
    ayudante_id: props.prefill?.ayudante_id || '',
    ticket_id: props.ticket_id || props.prefill?.ticket_id || '',
    poliza_id: props.prefill?.poliza_id || '',
    fecha_hora: props.prefill?.fecha_hora || '',
    fecha_hora_fin: props.prefill?.fecha_hora_fin || '',
    estado: props.prefill?.estado || 'programado',
    prioridad: props.prefill?.prioridad || 'media',
    tipo_servicio: props.prefill?.tipo_servicio || '',
    descripcion: props.prefill?.descripcion || '',
    problema_reportado: props.prefill?.descripcion || '',
    tipo_equipo: props.prefill?.tipo_equipo || 'minisplit',
    marca_equipo: props.prefill?.marca_equipo || '',
    modelo_equipo: props.prefill?.modelo_equipo || '',
    notas_internas: props.prefill?.notas_internas || '',
    evidencias_previas: [],
    folio: props.prefill?.folio || '',
    notify: true
});

onMounted(() => {
    // Si viene un cliente_id en el prefill, cargamos sus datos
    if (props.prefill?.cliente_id) {
        // Intentar encontrarlo en la lista inicial (si existe)
        const c = props.clientes?.find(x => x.id == props.prefill.cliente_id);
        if (c) {
            onClienteSeleccionado(c);
        } else {
            // Si no está en los primeros 50, pedir al backend
            axios.get(route('api.clientes.show', props.prefill.cliente_id))
                .then(response => {
                    const data = response.data;
                    if (data.id) onClienteSeleccionado(data);
                })
                .catch(err => console.error('Error prefilling client', err));
        }
    }
});

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
        const response = await axios.get(route('api.citas.check-availability', {
            tecnico_id: form.tecnico_id,
            fecha_hora: form.fecha_hora,
            fecha_hora_fin: form.fecha_hora_fin, // Sincronizar fin con la validación
            cliente_id: form.cliente_id
        }));
        const data = response.data;
        
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
        const response = await axios.get(route('api.citas.busy-slots', {
            tecnico_id: form.tecnico_id,
            fecha: internalDate.value
        }));
        const data = response.data;
        if (data.success) {
            busySlots.value = data.slots;
            isOnVacation.value = data.on_vacation || false;
            vacationMessage.value = data.message || '';
            
            if (isOnVacation.value) {
                availabilityError.value = vacationMessage.value;
            } else if (availabilityError.value === vacationMessage.value) {
                availabilityError.value = '';
            }
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
    // Inicializar la fecha para el calendario, pero no la hora de la cita
    const defaultDate = getSafeDefaultDate();
    internalDate.value = defaultDate.split('T')[0];

    // Restaurar borrador
    const draft = sessionStorage.getItem('cita_wizard_draft');
    if (draft) {
        try {
            const parsed = JSON.parse(draft);
            Object.assign(form, parsed.data);
            currentStep.value = parsed.step || 1;
            if (form.cliente_id) {
                selectedCliente.value = props.clientes.find(c => c.id == form.cliente_id);
                if (selectedCliente.value) onClienteSeleccionado(selectedCliente.value);
            }
        } catch (e) { console.error('Draft restore error', e); }
    }
    
    // Prefill si viene de props
    if (props.prefill) {
        if (props.prefill.cliente_id) {
             const cl = props.clientes.find(c => c.id == props.prefill.cliente_id);
             if (cl) onClienteSeleccionado(cl);
        }
        Object.assign(form, props.prefill);
        
        // Sincronizar estados internos con prefill
        if (form.fecha_hora) {
            internalDate.value = form.fecha_hora.split('T')[0] || form.fecha_hora.split(' ')[0];
            internalTime.value = (form.fecha_hora.includes('T') ? form.fecha_hora.split('T')[1] : form.fecha_hora.split(' ')[1])?.substring(0, 5);
            selectedStart.value = internalTime.value ? parseInt(internalTime.value.split(':')[0]) : null;
        }
        if (form.fecha_hora_fin) {
            internalEndTime.value = (form.fecha_hora_fin.includes('T') ? form.fecha_hora_fin.split('T')[1] : form.fecha_hora_fin.split(' ')[1])?.substring(0, 5);
            selectedEnd.value = internalEndTime.value ? parseInt(internalEndTime.value.split(':')[0]) : null;
        }
    }

    if (form.tecnico_id) fetchBusySlots();
});

const progressWidth = computed(() => ((currentStep.value - 1) / (steps.length - 1)) * 100);

const canProceed = computed(() => {
    if (currentStep.value === 1) return !!form.cliente_id;
    if (currentStep.value === 2) {
        let isValid = !!form.tecnico_id && !!form.fecha_hora && !!form.fecha_hora_fin && !!form.tipo_servicio && !availabilityError.value;
        if (form.tipo_servicio === 'mantenimiento') {
            isValid = isValid && !!form.tipo_mantenimiento;
        }
        return isValid;
    }
    if (currentStep.value === 3) return !!form.tipo_equipo && !!form.descripcion;
    return true;
});

const nextStep = () => { if (canProceed.value && currentStep.value < 4) currentStep.value++; };
const prevStep = () => { if (currentStep.value > 1) currentStep.value--; };

const internalTime = ref(form.fecha_hora ? form.fecha_hora.split('T')[1]?.substring(0, 5) : '');
const internalEndTime = ref(form.fecha_hora_fin ? form.fecha_hora_fin.split('T')[1]?.substring(0, 5) : '');

// Block logic
const clickStep = ref(0)
const selectedStart = ref(internalTime.value ? parseInt(internalTime.value.split(':')[0]) : null)
const selectedEnd = ref(internalEndTime.value ? parseInt(internalEndTime.value.split(':')[0]) : null)

const isSlotBusy = (h) => {
    if (isOnVacation.value) return true;
    if (!busySlots.value.length) return false;
    return busySlots.value.some(slot => {
        // Formato esperado HH:mm:ss o HH:mm
        const startH = parseInt(slot.start.split(':')[0]);
        const endH = parseInt(slot.end.split(':')[0]);
        return h >= startH && h < endH;
    });
};

const seleccionarBloque = (hora) => {
    if (isSlotBusy(hora)) {
        Swal.fire({
            title: 'Horario Ocupado',
            text: 'Este bloque ya está asignado a otra cita para este técnico.',
            icon: 'warning',
            toast: true,
            position: 'top-end',
            timer: 3000,
            showConfirmButton: false
        });
        return;
    }

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
            // Validar si hay bloques ocupados en medio del rango
            for (let h = selectedStart.value; h < hora; h++) {
                if (isSlotBusy(h)) {
                    Swal.fire({
                        title: 'Conflicto de Horario',
                        text: 'El rango seleccionado incluye bloques que ya están ocupados.',
                        icon: 'error',
                        confirmButtonColor: '#2563eb'
                    });
                    return;
                }
            }
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

const updateDateTime = () => {
    const date = new Date(`${internalDate.value}T00:00:00`);
    if (date.getDay() === 0) {
        Swal.fire('Día no laboral', 'No se pueden programar citas los domingos.', 'warning');
        date.setDate(date.getDate() + 1);
        internalDate.value = date.toISOString().split('T')[0];
    }
    
    updateDateTimeFromTime();
    if (form.tecnico_id) fetchBusySlots();
};

const onClienteSeleccionado = (cliente) => {
    selectedCliente.value = cliente;
    form.cliente_id = cliente?.id || '';
    form.poliza_id = '';
    clientePolizas.value = [];
    
    if (cliente?.id) {
        axios.get(route('api.clientes.polizas', cliente.id))
            .then(response => {
                const data = response.data;
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

const submit = () => {
    form.post(route('citas.store'), {
        onSuccess: () => {
            sessionStorage.removeItem('cita_wizard_draft');
            Swal.fire({
                title: '¡Cita Confirmada!',
                text: 'El servicio ha sido agendado exitosamente.',
                icon: 'success',
                confirmButtonColor: '#2563eb'
            });
        },
        onError: (errors) => {
             console.error('Validation errors:', errors);
             const errorMsg = Object.values(errors).join('\n');
             Swal.fire({
                 title: 'Verifica los campos',
                 text: errorMsg || 'Hay errores en la información proporcionada.',
                 icon: 'error',
                 confirmButtonColor: '#2563eb'
             });
        }
    });
};

const selectedTecnicoName = computed(() => {
    const t = props.tecnicos.find(t => t.id === form.tecnico_id);
    return t ? t.nombre : 'Sin asignar';
});

const selectedAyudanteName = computed(() => {
    const t = props.tecnicos.find(t => t.id === form.ayudante_id);
    return t ? t.nombre : '';
});

watch(() => form.tecnico_id, (newVal) => {
    if (form.ayudante_id === newVal) {
        form.ayudante_id = '';
    }
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

const getServicioIcon = (tipo) => {
    const iconos = {
        'garantia': faShieldAlt,
        'instalacion': faWrench,
        'reparacion': faCogs,
        'mantenimiento': faBroom,
        'diagnostico': faSearch,
        'servicio_limpieza': faSprayCan,
        'otro': faMapPin
    };
    return iconos[tipo] || faMapPin;
};

const getEquipoIcon = (tipo) => {
    const iconos = {
        'minisplit': faSnowflake,
        'aire_acondicionado': faThermometerHalf,
        'paquete': faBox,
        'refrigerador': faIceCream,
        'congelador': faSnowflake,
        'enfriador_agua': faWind,
        'lavadora': faTshirt,
        'secadora': faTshirt,
        'estufa': faFire,
        'microondas': faMicrochip,
        'lavavajillas': faUtensils,
        'boiler': faFire,
        'otro_equipo': faBox
    };
    return iconos[tipo] || faBox;
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

const marcasComunes = ['MIRAGE', 'LG', 'TCL', 'MABE', 'SAMSUNG'];
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
