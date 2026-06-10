<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4" @click.self="close">
        <div class="cita-modal-card bg-[var(--ui-surface)] text-[var(--ui-text)] rounded-3xl shadow-[var(--ui-shadow)] w-full max-w-4xl max-h-[90vh] overflow-hidden border border-[var(--ui-border)]" role="dialog" aria-modal="true">
          <!-- Header del modal -->
          <div class="flex justify-between items-center p-6 border-b border-[var(--ui-border)] bg-[var(--ui-surface-alt)]">
            <h2 class="text-2xl font-black uppercase tracking-wider">Detalles de la Cita</h2>
            <button @click="close" class="text-[var(--ui-text-soft)] hover:text-[var(--ui-text)] transition-colors p-2 rounded-full hover:bg-black/5 dark:hover:bg-white/5" aria-label="Cerrar modal">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Contenido del modal con scroll -->
          <div class="p-8 overflow-y-auto custom-scrollbar max-h-[calc(90vh-140px)] bg-[var(--ui-surface)]">
            <div class="space-y-10">
              <!-- Información General -->
              <div class="bg-[var(--ui-surface-alt)] rounded-[32px] p-8 border border-[var(--ui-border)]">
                <h3 class="text-xs font-black text-blue-600 dark:text-blue-400 mb-8 flex items-center uppercase tracking-[0.2em]">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Información Logística
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div>
                    <label class="block text-[var(--ui-text-soft)] text-[10px] font-black uppercase tracking-wide mb-2">Cliente</label>
                    <p class="font-bold text-lg">{{ cita?.cliente?.nombre_razon_social || 'Cliente no registrado o eliminado' }}</p>
                  </div>
                  <div>
                    <label class="block text-[var(--ui-text-soft)] text-[10px] font-black uppercase tracking-wide mb-2">Servicios</label>
                    <p class="font-bold text-lg uppercase">{{ formatearTipoServicio(cita?.tipo_servicio) }}</p>
                  </div>
                  <div>
                    <label class="block text-[var(--ui-text-soft)] text-[10px] font-black uppercase tracking-wide mb-2">Fecha y Hora</label>
                    <p class="font-bold text-lg uppercase">{{ formatearFechaHora(cita?.fecha_hora) }}</p>
                  </div>
                  <div>
                    <label class="block text-[var(--ui-text-soft)] text-[10px] font-black uppercase tracking-wide mb-2">Técnico</label>
                    <p class="font-bold text-lg uppercase">{{ cita?.tecnico?.name || cita?.tecnico?.nombre || 'Técnico no asignado' }}</p>
                  </div>
                </div>
              </div>

              <!-- Información del Equipo -->
              <div class="bg-[var(--ui-surface-alt)] rounded-[32px] p-8 border border-[var(--ui-border)]">
                <h3 class="text-xs font-black text-brand-500 mb-8 flex items-center uppercase tracking-[0.2em]">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Detalles del Equipo
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                  <div>
                    <label class="block text-[var(--ui-text-soft)] text-[10px] font-black uppercase tracking-wide mb-2">Equipo</label>
                    <p class="font-bold uppercase">{{ formatearTipoEquipo(cita?.tipo_equipo) }}</p>
                  </div>
                  <div>
                    <label class="block text-[var(--ui-text-soft)] text-[10px] font-black uppercase tracking-wide mb-2">Marca / Modelo</label>
                    <p class="font-bold uppercase">{{ cita?.marca_equipo || 'N/A' }} - {{ cita?.modelo_equipo || 'N/A' }}</p>
                  </div>
                  <div>
                    <label class="block text-[var(--ui-text-soft)] text-[10px] font-black uppercase tracking-wide mb-2">Status</label>
                    <p class="font-bold uppercase text-brand-500">{{ formatearEstado(cita?.estado) }}</p>
                  </div>
                </div>
              </div>

              <!-- Fotos de Evidencia -->
              <div class="space-y-4">
                 <label class="block text-[var(--ui-text-soft)] text-[10px] font-black uppercase tracking-wide">Evidencias Visuales</label>
                  <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                    <div v-if="cita?.foto_equipo" class="group relative aspect-video rounded-2xl overflow-hidden bg-black/5">
                        <img :src="generarUrl(cita?.foto_equipo)" class="w-full h-full object-cover group-hover:scale-110 transition-transform">
                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-[10px] font-black text-white uppercase tracking-wide">Ver Equipo</span>
                        </div>
                    </div>
                    <div v-if="cita?.foto_hoja_servicio" class="group relative aspect-video rounded-2xl overflow-hidden bg-black/5">
                        <img :src="generarUrl(cita?.foto_hoja_servicio)" class="w-full h-full object-cover group-hover:scale-110 transition-transform">
                        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                            <span class="text-[10px] font-black text-white uppercase tracking-wide">Ver Hoja</span>
                        </div>
                    </div>
                  </div>
              </div>

              <!-- Descripción -->
              <div class="bg-indigo-50/50 dark:bg-sky-900/10 rounded-[32px] p-8 border border-indigo-100 dark:border-indigo-900/20">
                <label class="block text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-wide mb-4">Problema Reportado</label>
                <p class="text-slate-700 dark:text-slate-300 italic font-medium">"{{ cita?.problema_reportado || cita?.descripcion || 'Sin descripción adicional' }}"</p>
              </div>

              <!-- GPS Validation (Nuevo) -->
              <div v-if="cita?.latitud && cita?.longitud" class="bg-rose-50/50 dark:bg-rose-900/10 rounded-[32px] p-8 border border-rose-100 dark:border-rose-900/20 flex items-center justify-between">
                <div>
                    <label class="block text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase tracking-wide mb-2">Validación Geográfica</label>
                    <p class="text-xs font-bold text-slate-600 dark:text-slate-400">Ubicación capturada al momento del reporte</p>
                </div>
                <a :href="`https://www.google.com/maps?q=${cita?.latitud},${cita?.longitud}`" target="_blank" class="px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white text-[10px] font-black uppercase tracking-wide rounded-2xl transition-all shadow-lg shadow-rose-200 dark:shadow-none">
                    Abrir Mapas
                </a>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="px-6 py-4 bg-[var(--ui-surface-alt)] border-t border-[var(--ui-border)]">
            <div class="flex justify-end space-x-3">
              <button @click="close" class="px-6 py-2 bg-slate-600 text-white rounded-xl hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500/50 focus:ring-offset-2 transition-colors">
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  show: Boolean,
  cita: Object,
});

const emit = defineEmits(['close']);

// Función para cerrar el modal
const close = () => {
  emit('close');
};

// Formatear tipo de servicio
const formatearTipoServicio = (tipo) => {
  const tipos = {
    instalacion: 'Instalación',
    diagnostico: 'Diagnóstico',
    reparacion: 'Reparación',
    garantia: 'Garantía',
    mantenimiento: 'Mantenimiento',
    servicio_limpieza: 'Servicio limpieza',
    otro: 'Otro',
    otro_servicio: 'Otro Servicio'
  };
  return tipos[tipo] || tipo || 'Desconocido';
};

// Formatear tipo de equipo
const formatearTipoEquipo = (tipo) => {
  const tipos = {
    minisplit: 'Minisplit',
    boiler: 'Boiler',
    refrigerador: 'Refrigerador',
    lavadora: 'Lavadora',
    secadora: 'Secadora',
    estufa: 'Estufa',
    campana: 'Campana',
    horno_de_microondas: 'Horno de Microondas',
    licuadora: 'Licuadora',
    otro_equipo: 'Otro Equipo'
  };
  return tipos[tipo] || 'Desconocido';
};

// Formatear estado
const formatearEstado = (estado) => {
  const estados = {
    pendiente: 'Pendiente',
    en_proceso: 'En Proceso',
    completado: 'Completado',
    cancelado: 'Cancelado'
  };
  return estados[estado] || 'Desconocido';
};

// Formatear fecha y hora
const formatearFechaHora = (fechaHora) => {
  if (!fechaHora) return 'Sin fecha/hora';
  const fecha = new Date(fechaHora);
  return isNaN(fecha.getTime()) ? 'Fecha inválida' : fecha.toLocaleString();
};

// Generar URL absoluta para las imágenes
const generarUrl = (ruta) => {
  if (!ruta) return null;
  return `${window.location.origin}/storage/${ruta}`;
};

// Manejar errores en las imágenes
const handleImageError = (event, tipo) => {
  console.warn(`Error al cargar la imagen (${tipo}):`, event.target.src);
  event.target.src = '/images/placeholder-product.svg'; // Imagen de placeholder
  event.target.alt = `Imagen ${tipo} no disponible`;
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-active .cita-modal-card,
.modal-leave-active .cita-modal-card {
  transition: transform 0.3s ease;
}

.modal-enter-from .cita-modal-card,
.modal-leave-to .cita-modal-card {
  transform: scale(0.95);
}
</style>
