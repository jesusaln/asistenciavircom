<template>
  <div>
    <Head title="Editar Cita" />
    <div class="citas-edit min-h-screen bg-gray-50 dark:bg-slate-950 dark:bg-gray-900 transition-colors duration-300">
      
      <!-- Header Premium Sticky -->
      <div class="sticky top-0 z-30 bg-white dark:bg-slate-900/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-700 shadow-sm transition-all">
        <div class="max-w-[1600px] mx-auto px-6 lg:px-12 py-5 flex items-center justify-between">
          <div class="flex items-center gap-6">
            <Link :href="route('citas.index')" class="group w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-900 dark:bg-gray-700 border border-gray-100 dark:border-gray-600 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-100 dark:hover:border-blue-900/30 transition-all shadow-sm">
              <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </Link>
            <div>
              <h1 class="text-2xl font-black text-gray-900 dark:text-white dark:text-white tracking-tight transition-colors">Modificar <span class="text-amber-500">Cita #{{ cita.id }}</span></h1>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest">Editando Control de Asistencia</span>
                <span :class="obtenerEstadoCitaClase(form.estado)" class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider border transition-colors">{{ form.estado }}</span>
              </div>
            </div>
          </div>
          
          <div class="flex items-center gap-4">
            <button @click="submit" :disabled="form.processing" class="px-8 py-3 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 disabled:scale-95 text-white text-xs font-black uppercase tracking-widest rounded-2xl transition-all shadow-xl shadow-amber-200 dark:shadow-none active:scale-95 flex items-center gap-3">
              <template v-if="form.processing">
                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                Actualizando...
              </template>
              <template v-else>
                <span>Guardar Cambios</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </template>
            </button>
          </div>
        </div>
      </div>

      <div class="max-w-[1600px] mx-auto px-6 lg:px-12 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
          
          <!-- Columna Izquierda: Formulario (8/12) -->
          <form @submit.prevent="submit" class="lg:col-span-8 space-y-10">
            
            <!-- Sección 1: Cliente e Identificación -->
            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-[32px] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 p-8 lg:p-12 transition-all">
              <div class="flex items-center gap-4 mb-10">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-sm">
                  <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                  <h2 class="text-xl font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-tight">Identificación del Cliente</h2>
                  <p class="text-xs font-bold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mt-1">Información de contacto y asignación técnica</p>
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
                  :mostrar-editar="true"
                  @editar-cliente="onEditarCliente"
                />
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <FormField
                    v-model="form.tecnico_id"
                    label="Especialista a Cargo"
                    type="select"
                    id="tecnico_id"
                    :options="tecnicosOptions"
                    :error="form.errors.tecnico_id"
                    required
                  />
                  <FormField
                    v-model="form.estado"
                    label="Estatus Operativo"
                    type="select"
                    id="estado"
                    :options="estadoOptions"
                    :error="form.errors.estado"
                    required
                  />
                </div>
              </div>
            </div>

            <!-- Sección 2: Programación -->
            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-[32px] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 p-8 lg:p-12 transition-all">
              <div class="flex items-center gap-4 mb-10">
                <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-sm">
                  <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                  <h2 class="text-xl font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-tight">Programación Actual</h2>
                  <p class="text-xs font-bold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mt-1">Re-agendar o ajustar tiempos de atención</p>
                </div>
              </div>

              <div class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                   <FormField
                      v-model="form.fecha_hora"
                      label="Fecha y Hora de la Cita"
                      type="datetime-local"
                      id="fecha_hora"
                      :error="form.errors.fecha_hora"
                      required
                    />
                    <FormField
                      v-model="form.prioridad"
                      label="Grado de Prioridad"
                      type="select"
                      id="prioridad"
                      :options="prioridadOptions"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <FormField
                      v-model="form.tipo_servicio"
                      label="Categoría de Servicio"
                      type="select"
                      id="tipo_servicio"
                      :options="tipoServicioOptions"
                      required
                    />
                    <div class="flex flex-col justify-end" v-if="cita.ticket_id && form.estado === 'completado'">
                       <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/30 rounded-2xl flex items-center gap-4 animate-in slide-in-from-top-2">
                         <input type="checkbox" v-model="form.cerrar_ticket" id="cerrar_ticket" class="w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                         <label for="cerrar_ticket" class="text-sm font-black text-blue-900 dark:text-blue-300 cursor-pointer">Resolver ticket #{{ cita.ticket_id }} automáticamente</label>
                       </div>
                    </div>
                </div>

                <FormField
                  v-model="form.direccion_servicio"
                  label="Dirección de Atención"
                  type="textarea"
                  id="direccion_servicio"
                  :rows="2"
                />
              </div>
            </div>

            <!-- Sección 3: Equipo y Reporte -->
            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-[32px] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 p-8 lg:p-12 transition-all">
               <div class="flex items-center gap-4 mb-10">
                <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-sm">
                  <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                  <h2 class="text-xl font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-tight">Equipamiento e Información Técnica</h2>
                  <p class="text-xs font-bold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mt-1">Detalles del activo y reporte de fallas</p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <FormField v-model="form.tipo_equipo" label="Equipo" type="select" id="tipo_equipo" :options="tipoEquipoOptions" />
                <FormField v-model="form.marca_equipo" label="Marca" id="marca_equipo" @input="onInputToUpper('marca_equipo')" />
                <FormField v-model="form.modelo_equipo" label="Modelo" id="modelo_equipo" @input="onInputToUpper('modelo_equipo')" />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                 <FormField v-model="form.descripcion" label="Descripción del Problema" type="textarea" id="descripcion" :rows="3" />
                 <FormField v-model="form.observaciones" label="Observaciones Administrativas" type="textarea" id="observaciones" :rows="3" />
              </div>
            </div>

            <!-- Sección 4: Reporte de Trabajo (Si está completado o en proceso) -->
            <div v-if="['en_proceso', 'completado'].includes(form.estado)" class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-[32px] shadow-2xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 p-8 lg:p-12 transition-all">
               <div class="flex items-center gap-4 mb-10">
                <div class="w-14 h-14 rounded-2xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center text-green-600 dark:text-green-400 shadow-sm">
                  <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                  <h2 class="text-xl font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-tight">Reporte de Trabajo Realizado</h2>
                  <p class="text-xs font-bold text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest mt-1">Evidencias, tiempos y diagnóstico final</p>
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
                    <label class="block text-xs font-black text-gray-400 dark:text-gray-500 dark:text-gray-400 uppercase tracking-widest transition-colors">Evidencias Visuales Existentes</label>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-4">
                       <!-- Fotos Finales -->
                       <div v-for="(foto, idx) in cita.fotos_finales" :key="idx" class="aspect-square rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-700 border border-gray-100 dark:border-gray-700 relative group cursor-pointer" @click="openGallery(['/storage/' + foto], 'Evidencia #' + (idx+1))">
                          <img :src="'/storage/' + foto" class="w-full h-full object-cover group-hover:scale-110 transition-transform">
                          <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                             <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                          </div>
                          <span class="absolute bottom-1 right-1 bg-black/50 text-white text-[8px] px-1 rounded uppercase font-bold">#{{ idx+1 }}</span>
                       </div>
                       
                       <!-- Subida de Nuevas -->
                       <label for="new_photos" class="aspect-square rounded-2xl border-2 border-dashed border-gray-200 dark:border-slate-800 dark:border-gray-700 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800 dark:bg-slate-950 dark:hover:bg-gray-900/50 transition-all group">
                          <svg class="w-6 h-6 text-gray-300 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                          <span class="text-[9px] font-black text-gray-400 uppercase mt-1">Añadir</span>
                          <input id="new_photos" type="file" multiple class="hidden" accept="image/*" @change="handleNewPhotos">
                       </label>
                    </div>

                    <!-- Previsualización de Nuevas -->
                    <div v-if="previewNewPhotos.length > 0" class="flex flex-wrap gap-3 p-4 bg-gray-50 dark:bg-slate-950 dark:bg-gray-900/30 rounded-2xl animate-in fade-in">
                       <div v-for="(preview, idx) in previewNewPhotos" :key="idx" class="relative group w-20 h-20 rounded-xl overflow-hidden border-2 border-blue-200 dark:border-blue-900/30">
                          <img :src="preview" class="w-full h-full object-cover">
                          <button type="button" @click="removeNewPhoto(idx)" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity">×</button>
                       </div>
                    </div>
                 </div>
              </div>
            </div>
          </form>

          <!-- Columna Derecha: Sidebar (4/12) -->
          <div class="lg:col-span-4 space-y-8">
            <!-- Totales Card -->
            <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-[32px] p-8 text-white shadow-xl shadow-blue-200 dark:shadow-none sticky top-32">
               <h3 class="text-xs font-black uppercase tracking-[0.2em] opacity-70 mb-8">Información de Seguimiento</h3>
               
               <div class="space-y-6 mb-10">
                  <div class="flex items-center gap-4">
                     <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900/20 flex items-center justify-center backdrop-blur-md">⚙️</div>
                     <div>
                       <p class="text-[10px] font-black uppercase opacity-60">Servicio</p>
                       <p class="text-sm font-black uppercase tracking-tight">{{ formatearTipoServicio(form.tipo_servicio) }}</p>
                     </div>
                  </div>
                  <div class="flex items-center gap-4">
                     <div class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900/20 flex items-center justify-center backdrop-blur-md">📍</div>
                     <div>
                       <p class="text-[10px] font-black uppercase opacity-60">Destino</p>
                       <p class="text-sm font-black truncate max-w-[200px]">{{ selectedCliente?.nombre_razon_social }}</p>
                     </div>
                  </div>
               </div>

               <div class="pt-8 border-t border-white/10">
                  <div class="flex justify-between items-center bg-white dark:bg-slate-900/10 rounded-2xl p-4 border border-white/10">
                     <p class="text-xs font-black uppercase tracking-widest opacity-80">Saldo en Cita</p>
                     <p class="text-2xl font-black">${{ parseFloat(cita.total || 0).toLocaleString() }}</p>
                  </div>
                  <p class="text-[9px] text-center font-bold opacity-40 uppercase tracking-tighter mt-4">Actualizar la cita mantendrá el historial de cambios intacto</p>
               </div>
            </div>

            <!-- Ayuda Card -->
            <div class="bg-white dark:bg-slate-900 dark:bg-gray-800 rounded-[32px] border border-gray-100 dark:border-gray-700 p-8 shadow-sm transition-colors">
               <h3 class="text-xs font-black text-gray-900 dark:text-white dark:text-white uppercase tracking-widest mb-4">Ayuda de Edición</h3>
               <p class="text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400 leading-relaxed mb-4">Puedes cambiar el estado de la cita para habilitar el reporte técnico. Al marcar como completada, se recomienda cerrar el ticket asociado.</p>
               <Link :href="route('citas.show', cita.id)" class="text-xs font-black text-blue-600 hover:text-blue-700 dark:text-blue-400 uppercase tracking-widest flex items-center gap-2 transition-colors">
                  <span>Ver vista previa pública</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
               </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Galería -->
    <div v-if="showGalleryModal" class="fixed inset-0 bg-black/95 z-[60] flex items-center justify-center p-6" @click.self="showGalleryModal = false">
       <button @click="showGalleryModal = false" class="absolute top-8 right-8 w-14 h-14 flex items-center justify-center bg-white dark:bg-slate-900/10 hover:bg-white dark:bg-slate-900/20 rounded-full text-white transition-all backdrop-blur-md z-[70]">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
       </button>
       <img :src="galleryImages[0]" class="max-h-[85vh] max-w-[90vw] object-contain rounded-2xl shadow-2xl animate-in zoom-in-95" :key="galleryImages[0]">
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
    cita: Object,
    tecnicos: Array,
    clientes: Array,
});

const selectedCliente = ref(props.cita.cliente);
const previewNewPhotos = ref([]);
const showGalleryModal = ref(false);
const galleryImages = ref([]);

// Formatear fecha para datetime-local
const formatISO = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const z = (n) => n.toString().padStart(2, '0');
    return `${d.getFullYear()}-${z(d.getMonth() + 1)}-${z(d.getDate())}T${z(d.getHours())}:${z(d.getMinutes())}`;
};

const form = useForm({
    cliente_id: props.cita.cliente_id,
    tecnico_id: props.cita.tecnico_id,
    fecha_hora: formatISO(props.cita.fecha_hora),
    estado: props.cita.estado,
    prioridad: props.cita.prioridad,
    tipo_servicio: props.cita.tipo_servicio,
    descripcion: props.cita.descripcion,
    direccion_servicio: props.cita.direccion_servicio,
    observaciones: props.cita.observaciones,
    notas: props.cita.notas,
    tipo_equipo: props.cita.tipo_equipo,
    marca_equipo: props.cita.marca_equipo,
    modelo_equipo: props.cita.modelo_equipo,
    trabajo_realizado: props.cita.trabajo_realizado,
    tiempo_servicio: props.cita.tiempo_servicio,
    cerrar_ticket: false,
    nuevas_fotos: []
});

const onClienteSeleccionado = (c) => {
    selectedCliente.value = c;
    form.cliente_id = c?.id || '';
};

const onEditarCliente = (c) => window.open(route('clientes.edit', c.id), '_blank');
const onInputToUpper = (f) => form[f] = form[f].toUpperCase();

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

const submit = () => {
    form.post(route('citas.update', props.cita.id), {
        onSuccess: () => Swal.fire('Actualizado', 'La información se guardó correctamente', 'success'),
        onError: () => Swal.fire('Error', 'Verifica los datos del formulario', 'error')
    });
};

const obtenerEstadoCitaClase = (e) => {
    const c = { pendiente: 'border-yellow-200 text-yellow-600 bg-yellow-50 dark:bg-yellow-900/20', en_proceso: 'border-blue-200 text-blue-600 bg-blue-50 dark:bg-blue-900/20', completado: 'border-green-200 text-green-600 bg-green-50 dark:bg-green-900/20' };
    return c[e] || 'border-gray-200 dark:border-slate-800 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-slate-950';
};

const tecnicosOptions = computed(() => props.tecnicos.map(t => ({ value: t.id, text: t.name })));
const estadoOptions = [
    { value: 'pendiente', text: 'Pendiente' },
    { value: 'programado', text: 'Programado' },
    { value: 'en_proceso', text: 'En Proceso' },
    { value: 'completado', text: 'Completado' },
    { value: 'cancelado', text: 'Cancelado' }
];

const prioridadOptions = [
    { value: 'baja', text: 'Baja' },
    { value: 'media', text: 'Media' },
    { value: 'alta', text: 'Alta' },
    { value: 'urgente', text: 'Urgente' }
];

const tipoServicioOptions = [
    { value: 'garantia', text: 'Garantía' },
    { value: 'instalacion', text: 'Instalación' },
    { value: 'reparacion', text: 'Reparación' },
    { value: 'mantenimiento', text: 'Mantenimiento' },
    { value: 'diagnostico', text: 'Diagnóstico' },
    { value: 'otro', text: 'Otro' }
];

const tipoEquipoOptions = [
    { value: 'minisplit', text: 'Minisplit' },
    { value: 'boiler', text: 'Boiler' },
    { value: 'refrigerador', text: 'Refrigerador' },
    { value: 'lavadora', text: 'Lavadora' },
    { value: 'secadora', text: 'Secadora' },
    { value: 'estufa', text: 'Estufa' }
];

const formatearTipoServicio = (t) => tipoServicioOptions.find(o => o.value === t)?.text || t;
</script>

<style scoped>
.animate-in { animation: fade-in 0.3s ease-out, slide-in-from-top-2 0.3s ease-out; }
@keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes slide-in-from-top-2 { from { transform: translateY(-0.5rem); } to { transform: translateY(0); } }
@keyframes zoom-in-95 { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.zoom-in-95 { animation: zoom-in-95 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
</style>
