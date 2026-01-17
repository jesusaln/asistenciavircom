<template>
  <div>
    <Head title="Crear Cita" />
    <div class="citas-create min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
      
      <!-- Header Premium Flotante/Sticky -->
      <div class="sticky top-0 z-30 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-700 shadow-sm transition-all">
        <div class="max-w-[1600px] mx-auto px-6 lg:px-12 py-5 flex items-center justify-between">
          <div class="flex items-center gap-6">
            <Link :href="route('citas.index')" class="group w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-gray-700 border border-gray-100 dark:border-gray-600 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-100 dark:hover:border-blue-900/30 transition-all shadow-sm">
              <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </Link>
            <div>
              <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight transition-colors">Crear <span class="text-blue-600">Nueva Cita</span></h1>
              <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">Módulo de Asistencia Técnica Vircom</p>
            </div>
          </div>
          
          <div class="flex items-center gap-4">
            <button @click="saveDraft" class="hidden md:flex items-center gap-2 px-5 py-2.5 text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest hover:text-gray-700 dark:hover:text-white transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
              Guardar Borrador
            </button>
            <div class="w-px h-8 bg-gray-200 dark:bg-gray-700 hidden md:block"></div>
            <button @click="submit" :disabled="form.processing || !selectedCliente" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:scale-95 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-xl shadow-blue-200 dark:shadow-none active:scale-95 flex items-center gap-3">
              <template v-if="form.processing">
                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Procesando...
              </template>
              <template v-else>
                <span>Crear Cita</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </template>
            </button>
          </div>
        </div>
      </div>

      <div class="max-w-[1600px] mx-auto px-6 lg:px-12 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
          
          <!-- Columna Izquierda: Formulario Principal (8/12) -->
          <form @submit.prevent="submit" class="lg:col-span-8 space-y-10">
            
            <!-- Sección 1: Cliente e Identificación -->
            <div class="bg-white dark:bg-gray-800 rounded-[32px] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 p-8 lg:p-12 transition-all">
              <div class="flex items-center gap-4 mb-10">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-sm">
                  <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                  <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Identificación del Cliente</h2>
                  <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">Busca y selecciona un cliente registrado</p>
                </div>
              </div>

              <div class="space-y-8">
                <BuscarCliente
                  ref="buscarClienteRef"
                  :clientes="clientes"
                  :cliente-seleccionado="selectedCliente"
                  @cliente-seleccionado="onClienteSeleccionado"
                  @crear-nuevo-cliente="onCrearNuevoCliente"
                  label-busqueda="Cliente"
                  placeholder-busqueda="Ingresa nombre, RFC, email o teléfono..."
                  :requerido="true"
                  titulo-cliente-seleccionado="Cuenta Seleccionada"
                  mensaje-vacio="Módulo de Búsqueda"
                  submensaje-vacio="Escribe arriba para encontrar clientes de forma inteligente"
                  :mostrar-opcion-nuevo-cliente="true"
                  :mostrar-estado-cliente="true"
                  :mostrar-info-comercial="true"
                />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <FormField
                    v-model="form.tecnico_id"
                    label="Técnico Responsable"
                    type="select"
                    id="tecnico_id"
                    :options="tecnicosOptions"
                    :error="form.errors.tecnico_id"
                    required
                  />
                  
                  <!-- Selector de Póliza -->
                  <div v-if="clientePolizas.length > 0">
                    <label class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Póliza de Servicio</label>
                    <select v-model="form.poliza_id" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-2xl text-sm font-semibold focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                      <option value="">Sin póliza (cobro normal)</option>
                      <option v-for="pol in clientePolizas" :key="pol.id" :value="pol.id">
                        🛡️ {{ pol.nombre }} - {{ pol.folio }} ({{ pol.visitas_disponibles }} visitas disp.)
                      </option>
                    </select>
                    <p v-if="form.poliza_id" class="text-[10px] text-emerald-600 mt-2 font-bold">✅ Esta visita se descontará de la póliza seleccionada</p>
                  </div>
                  <div v-else-if="selectedCliente && clientePolizas.length === 0" class="flex flex-col justify-end pb-1">
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/30 rounded-2xl">
                      <p class="text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest">Sin Póliza</p>
                      <p class="text-xs text-amber-700 dark:text-amber-300">Este cliente no tiene póliza activa. Esta visita generará un cargo.</p>
                    </div>
                  </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8" v-if="form.ticket_id">
                  <div class="flex flex-col justify-end pb-1">
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/30 rounded-2xl flex items-center gap-3">
                      <div class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center font-black">🎫</div>
                      <div>
                        <p class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">Ticket Vinculado</p>
                        <p class="text-sm font-black text-blue-900 dark:text-white">FOLIO #{{ form.ticket_id }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sección 2: Programación y Logística -->
            <div class="bg-white dark:bg-gray-800 rounded-[32px] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 p-8 lg:p-12 transition-all">
              <div class="flex items-center gap-4 mb-10">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-sm">
                  <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                  <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Programación de la Cita</h2>
                  <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">Define cuándo y dónde se realizará el servicio</p>
                </div>
              </div>

              <div class="space-y-8">
                <!-- Selector de Fecha Mejorado -->
                <div class="space-y-4">
                   <label class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest transition-colors">Fecha Sugerida <span class="text-red-500">*</span></label>
                   <div class="flex flex-wrap gap-3">
                      <button type="button" v-for="q in quickDates" :key="q.val" @click="setQuickDate(q.val)" :class="selectedDateType === q.val ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-200 dark:shadow-none' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'" class="px-5 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest border transition-all active:scale-95">
                        {{ q.label }}
                      </button>
                   </div>
                   <input type="date" v-model="internalDate" :min="todayDate" class="w-full md:w-auto px-6 py-4 bg-gray-50 dark:bg-gray-900 border-gray-100 dark:border-gray-700 rounded-2xl text-lg font-black text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all outline-none">
                </div>

                <!-- Selector de Horarios -->
                <div class="space-y-4">
                  <label class="block text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest transition-colors">Intervalo de Atención</label>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                     <button type="button" @click="setQuickTime('09:00')" :class="form.fecha_hora.includes('09:00') ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-700'" class="p-6 rounded-3xl border-2 text-left transition-all group overflow-hidden relative">
                        <div class="relative z-10">
                          <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase mb-1">Mañana</p>
                          <p class="text-xl font-black text-gray-900 dark:text-white transition-colors">09:00 AM</p>
                        </div>
                        <div class="absolute -right-2 -bottom-2 text-blue-500/10 dark:text-blue-500/5 group-hover:scale-125 transition-transform"><svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z"/></svg></div>
                     </button>
                     <button type="button" @click="setQuickTime('13:00')" :class="form.fecha_hora.includes('13:00') ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-700'" class="p-6 rounded-3xl border-2 text-left transition-all group overflow-hidden relative">
                        <div class="relative z-10">
                          <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase mb-1">Mediodía</p>
                          <p class="text-xl font-black text-gray-900 dark:text-white transition-colors">01:00 PM</p>
                        </div>
                        <div class="absolute -right-2 -bottom-2 text-orange-500/10 dark:text-orange-500/5 group-hover:scale-125 transition-transform"><svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z"/></svg></div>
                     </button>
                     <button type="button" @click="setQuickTime('17:00')" :class="form.fecha_hora.includes('17:00') ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-700'" class="p-6 rounded-3xl border-2 text-left transition-all group overflow-hidden relative">
                        <div class="relative z-10">
                          <p class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase mb-1">Tarde</p>
                          <p class="text-xl font-black text-gray-900 dark:text-white transition-colors">05:00 PM</p>
                        </div>
                        <div class="absolute -right-2 -bottom-2 text-indigo-500/10 dark:text-indigo-500/5 group-hover:scale-125 transition-transform"><svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z"/></svg></div>
                     </button>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <FormField
                    v-model="form.prioridad"
                    label="Prioridad del Servicio"
                    type="select"
                    id="prioridad"
                    :options="prioridadOptions"
                    :error="form.errors.prioridad"
                  />
                  <FormField
                    v-model="form.tipo_servicio"
                    label="Tipo de Asistencia"
                    type="select"
                    id="tipo_servicio"
                    :options="tipoServicioOptions"
                    :error="form.errors.tipo_servicio"
                    required
                  />
                </div>

                <FormField
                  v-model="form.direccion_servicio"
                  label="Ubicación Exacta"
                  type="textarea"
                  id="direccion_servicio"
                  :error="form.errors.direccion_servicio"
                  placeholder="Calle, número, colonia y referencias visuales..."
                  :rows="3"
                />
              </div>
            </div>

            <!-- Sección 3: Datos Técnicos del Equipo -->
            <div class="bg-white dark:bg-gray-800 rounded-[32px] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 p-8 lg:p-12 transition-all">
               <div class="flex items-center gap-4 mb-10">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm">
                  <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                  <h2 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Información del Equipo</h2>
                  <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mt-1">Especificaciones técnicas para el diagnóstico</p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <FormField
                  v-model="form.tipo_equipo"
                  label="Tipo de Equipo"
                  type="select"
                  id="tipo_equipo"
                  :options="tipoEquipoOptions"
                  :error="form.errors.tipo_equipo"
                />
                
                <div class="space-y-1">
                  <label for="marca_equipo" class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">Marca</label>
                  <input 
                    v-model="form.marca_equipo" 
                    list="marcas-list" 
                    id="marca_equipo"
                    type="text"
                    class="w-full px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 shadow-sm transition-all text-gray-900 dark:text-gray-100"
                    placeholder="Ej. Samsung"
                    @input="onInputToUpper('marca_equipo')"
                  >
                  <datalist id="marcas-list">
                    <option v-for="marca in marcasComunes" :key="marca" :value="marca"></option>
                  </datalist>
                </div>

                <FormField
                  v-model="form.modelo_equipo"
                  label="Modelo"
                  id="modelo_equipo"
                  placeholder="Ej. AR12MV"
                  @input="onInputToUpper('modelo_equipo')"
                />
              </div>

              <div class="mt-8">
                 <FormField
                  v-model="form.descripcion"
                  label="Observaciones y Fallas Reportadas"
                  type="textarea"
                  id="descripcion"
                  :error="form.errors.descripcion"
                  placeholder="Describe detalladamente el problema que presenta el equipo..."
                  :rows="4"
                />
              </div>
            </div>
          </form>

          <!-- Columna Derecha: Sidebar con Resumen y Ayuda (4/12) -->
          <div class="lg:col-span-4 space-y-8">
            
            <!-- Card de Resumen Informativo -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-[32px] p-8 text-white shadow-xl shadow-blue-200 dark:shadow-none transition-transform hover:scale-[1.01] sticky top-32">
              <h3 class="text-xs font-black uppercase tracking-[0.2em] opacity-70 mb-6">Resumen de Cita</h3>
              
              <div class="space-y-6">
                <div class="flex items-start gap-4">
                  <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-md">👤</div>
                  <div>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Cliente</p>
                    <p class="text-sm font-black truncate max-w-[200px]">{{ selectedCliente?.nombre_razon_social || 'No seleccionado' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-4">
                  <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-md">📅</div>
                  <div>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Programación</p>
                    <p class="text-sm font-black">{{ form.fecha_hora ? formatearFecha(form.fecha_hora) : 'Pendiente de definir' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-4">
                  <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-md">🛠️</div>
                  <div>
                    <p class="text-[10px] font-black uppercase tracking-widest opacity-60">Tipo de Servicio</p>
                    <p class="text-sm font-black uppercase">{{ formatearTipoServicioShort(form.tipo_servicio) || 'Sin asignar' }}</p>
                  </div>
                </div>
              </div>

              <div class="mt-10 pt-8 border-t border-white/10 space-y-4">
                <div v-if="visitLimitInfo" class="bg-white/10 rounded-2xl p-4 border border-white/10">
                   <p class="text-xs font-black uppercase tracking-widest mb-1 flex items-center gap-2">
                     <span class="text-sm">⚠️</span> Póliza de Servicio
                   </p>
                   <p class="text-[11px] font-medium leading-relaxed opacity-90">{{ visitLimitInfo }}</p>
                </div>
                
                <p class="text-[10px] text-center font-bold opacity-50 uppercase tracking-tighter">Verifica que todos los datos sean correctos antes de guardar</p>
              </div>
            </div>

            <!-- Card Instrucciones -->
            <div class="bg-white dark:bg-gray-800 rounded-[32px] border border-gray-100 dark:border-gray-700 p-8 shadow-sm transition-colors">
               <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest mb-4">Guía Rápida</h3>
               <ul class="space-y-3">
                 <li v-for="(tip, idx) in helperTips" :key="idx" class="flex gap-3 text-xs font-medium text-gray-500 dark:text-gray-400 leading-relaxed">
                   <span class="text-blue-500 font-black">{{ idx+1 }}.</span>
                   {{ tip }}
                 </li>
               </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer Mobile Fijo -->
      <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 p-4 z-40">
         <button @click="submit" :disabled="form.processing || !selectedCliente" class="w-full py-4 bg-blue-600 text-white text-sm font-black uppercase tracking-widest rounded-2xl shadow-lg active:scale-95 transition-all">
            {{ form.processing ? 'Procesando...' : 'Confirmar y Guardar Cita' }}
         </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormField from '@/Components/FormField.vue';
import BuscarCliente from '@/Components/CreateComponents/BuscarCliente.vue';
import Swal from 'sweetalert2';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tecnicos: Array,
    clientes: Array,
    ticket_id: [String, Number],
    prefill: Object
});

const selectedCliente = ref(null);
const buscarClienteRef = ref(null);
const internalDate = ref(new Date().toISOString().split('T')[0]);
const selectedDateType = ref('today');

const form = useForm({
    cliente_id: '',
    tecnico_id: '',
    ticket_id: props.ticket_id || '',
    poliza_id: '',
    fecha_hora: `${new Date().toISOString().split('T')[0]}T09:00`,
    estado: 'programado',
    prioridad: 'media',
    tipo_servicio: 'diagnostico',
    descripcion: '',
    tipo_equipo: 'minisplit',
    marca_equipo: '',
    modelo_equipo: '',
    numero_serie: '',
    direccion_servicio: '',
    observaciones: '',
    notas: '',
    extra_visit_cost: 0,
    notify: true
});

const clientePolizas = ref([]);

onMounted(() => {
    // Restaurar borrador si existe
    const draft = sessionStorage.getItem('cita_draft');
    if (draft) {
        try {
            const parsed = JSON.parse(draft);
            Object.assign(form, parsed);
            if (form.cliente_id) {
                selectedCliente.value = props.clientes.find(c => c.id == form.cliente_id);
            }
        } catch (e) {
            console.error('Error restaurando borrador', e);
        }
    }
});

const quickDates = [
    { label: 'Hoy', val: 'today' },
    { label: 'Mañana', val: 'tomorrow' },
    { label: 'Lunes Prox.', val: 'monday' }
];

const setQuickDate = (type) => {
    selectedDateType.value = type;
    const now = new Date();
    let date;
    if (type === 'today') date = now;
    else if (type === 'tomorrow') {
        date = new Date();
        date.setDate(now.getDate() + 1);
    } else if (type === 'monday') {
        date = new Date();
        date.setDate(now.getDate() + (1 + 7 - now.getDay()) % 7 || 7);
    }
    internalDate.value = date.toISOString().split('T')[0];
    updateDateTime();
};

const setQuickTime = (time) => {
    updateDateTime(time);
};

const updateDateTime = (time) => {
    const t = time || form.fecha_hora.split('T')[1] || '09:00';
    form.fecha_hora = `${internalDate.value}T${t}`;
};

const onClienteSeleccionado = (cliente) => {
    selectedCliente.value = cliente;
    form.cliente_id = cliente?.id || '';
    form.poliza_id = ''; // Reset poliza al cambiar cliente
    clientePolizas.value = []; // Limpiar pólizas previas
    
    if (cliente?.calle) {
        form.direccion_servicio = `${cliente.calle} ${cliente.num_exterior || ''}, ${cliente.colonia || ''}, ${cliente.municipio || ''}`.replace(/\s+,/g, ',');
    }
    
    // Cargar pólizas activas del cliente
    if (cliente?.id) {
        fetch(route('api.clientes.polizas', cliente.id))
            .then(res => res.json())
            .then(data => {
                clientePolizas.value = data.polizas || [];
                // Autoseleccionar si solo hay una
                if (clientePolizas.value.length === 1) {
                    form.poliza_id = clientePolizas.value[0].id;
                }
            })
            .catch(err => console.log('No se pudieron cargar pólizas', err));
    }
};

const onInputToUpper = (field) => {
    form[field] = form[field].toUpperCase();
};

const onCrearNuevoCliente = (text) => {
    window.open(route('clientes.create', { name: text }), '_blank');
};

const saveDraft = () => {
    sessionStorage.setItem('cita_draft', JSON.stringify(form.data()));
    Swal.fire({
      title: 'Borrador Guardado',
      text: 'Puedes continuar después. Los datos están seguros en este navegador.',
      icon: 'success',
      toast: true,
      position: 'top-end',
      timer: 3000,
      showConfirmButton: false
    });
};

const submit = () => {
    form.post(route('citas.store'), {
        onSuccess: () => {
            sessionStorage.removeItem('cita_draft');
            Swal.fire('¡Éxito!', 'La cita ha sido agendada correctamente', 'success');
        },
        onError: () => {
             Swal.fire('Error', 'Por favor verifica los campos marcados en rojo', 'error');
        }
    });
};

const visitLimitInfo = computed(() => {
    if (!selectedCliente.value?.puntos) return null;
    return `Este cliente tiene una póliza activa. Consultar disponibilidad de visitas gratuitas para evitar cargos extra.`;
});

const helperTips = [
    'Asegúrate de confirmar la dirección con el cliente.',
    'Selecciona al técnico con menor carga de trabajo para hoy.',
    'Especificar el modelo ayuda al técnico a llevar refacciones.',
    'Puedes guardar como borrador y terminar la carga después.'
];

const priorityClasses = { baja: 'bg-green-100 text-green-700', media: 'bg-blue-100 text-blue-700', alta: 'bg-orange-100 text-orange-700', urgente: 'bg-red-100 text-red-700' };

// Options
const tecnicosOptions = computed(() => [
    { value: '', text: 'Selecciona Especialista', disabled: true },
    ...props.tecnicos.map(t => ({ value: t.id, text: t.name }))
]);

const prioridadOptions = [
    { value: 'baja', text: 'Baja - Programable' },
    { value: 'media', text: 'Media - Normal' },
    { value: 'alta', text: 'Alta - Prioritaria' },
    { value: 'urgente', text: 'URGENTE - Inmediata' }
];

const tipoServicioOptions = [
    { value: 'garantia', text: 'Servicio de Garantía' },
    { value: 'instalacion', text: 'Instalación Nueva' },
    { value: 'reparacion', text: 'Reparación Técnica' },
    { value: 'mantenimiento', text: 'Mantenimiento Preventivo' },
    { value: 'diagnostico', text: 'Diagnóstico Inicial' },
    { value: 'otro', text: 'Otro tipo de atención' }
];

const tipoEquipoOptions = [
    { value: 'minisplit', text: 'Aire Acondicionado (Minisplit)' },
    { value: 'boiler', text: 'Calentador (Boiler)' },
    { value: 'refrigerador', text: 'Refrigeración / Frigobar' },
    { value: 'lavadora', text: 'Lavandería (Lavadora)' },
    { value: 'secadora', text: 'Secadora de Gas/Electrica' },
    { value: 'estufa', text: 'Cocina (Estufa/Parrilla)' },
    { value: 'otro_equipo', text: 'Otro Equipo Línea Blanca' }
];

const marcasComunes = ['SAMSUNG', 'LG', 'WHIRLPOOL', 'MABE', 'FRIGIDAIRE', 'GE', 'BOSCH', 'CARRIER', 'YORK', 'RHEEM'];

const formatearFecha = (fh) => new Date(fh).toLocaleString('es-MX', { dateStyle: 'long', timeStyle: 'short' });
const formatearTipoServicioShort = (t) => tipoServicioOptions.find(o => o.value === t)?.text;
const todayDate = new Date().toISOString().split('T')[0];
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; }

.citas-create {
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.5);
}
.dark input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(0.8);
}
</style>
