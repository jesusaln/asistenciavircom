<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="close">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden" role="dialog" aria-modal="true">
          <!-- Header del modal -->
          <div class="flex justify-between items-center p-6 border-b border-gray-200 dark:border-slate-800 bg-gray-50">
            <h2 class="text-2xl font-semibold text-gray-800">Detalles de la Cita</h2>
            <button @click="close" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-full hover:bg-gray-200" aria-label="Cerrar modal">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Contenido del modal con scroll -->
          <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
            <div class="space-y-6">
              <!-- Información General -->
              <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-blue-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Información General
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Cliente</label>
                    <p>{{ cita.cliente.nombre_razon_social }}</p>
                  </div>
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Tipo de Servicio</label>
                    <p>{{ formatearTipoServicio(cita.tipo_servicio) }}</p>
                  </div>
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Fecha y Hora</label>
                    <p>{{ formatearFechaHora(cita.fecha_hora) }}</p>
                  </div>
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Descripción</label>
                    <p>{{ cita.descripcion }}</p>
                  </div>
                </div>
              </div>

              <!-- Información del Equipo -->
              <div class="bg-green-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-green-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  Información del Equipo
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Tipo de Equipo</label>
                    <p>{{ formatearTipoEquipo(cita.tipo_equipo) }}</p>
                  </div>
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Marca del Equipo</label>
                    <p>{{ cita.marca_equipo }}</p>
                  </div>
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Modelo del Equipo</label>
                    <p>{{ cita.modelo_equipo }}</p>
                  </div>
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Problema Reportado</label>
                    <p>{{ cita.problema_reportado }}</p>
                  </div>
                </div>
              </div>

              <!-- Estado y Técnico -->
              <div class="bg-yellow-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-yellow-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Estado y Técnico
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Estado</label>
                    <p>{{ formatearEstado(cita.estado) }}</p>
                  </div>
                  <div class="mb-2">
                    <label class="block text-gray-700 text-sm font-bold mb-1">Técnico</label>
                    <p>{{ cita.tecnico.nombre }}</p>
                  </div>
                </div>
              </div>

              <!-- Fotos -->
              <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="mb-4">
                  <label class="block text-gray-700 text-sm font-bold mb-1">Foto del Equipo</label>
                  <img v-if="cita.foto_equipo" :src="generarUrl(cita.foto_equipo)" alt="Foto del Equipo" class="w-full h-40 object-cover mt-2 rounded-lg" @error="handleImageError($event, 'equipo')">
                  <p v-else class="text-gray-700">No hay foto del equipo disponible</p>
                </div>
                <div class="mb-4">
                  <label class="block text-gray-700 text-sm font-bold mb-1">Foto de la Hoja de Servicio</label>
                  <img v-if="cita.foto_hoja_servicio" :src="generarUrl(cita.foto_hoja_servicio)" alt="Foto de la Hoja de Servicio" class="w-full h-40 object-cover mt-2 rounded-lg" @error="handleImageError($event, 'hoja')">
                  <p v-else class="text-gray-700">No hay foto de la hoja de servicio disponible</p>
                </div>
                <div class="mb-4">
                  <label class="block text-gray-700 text-sm font-bold mb-1">Foto de Identificación del Cliente</label>
                  <img v-if="cita.foto_identificacion" :src="generarUrl(cita.foto_identificacion)" alt="Foto de Identificación del Cliente" class="w-full h-40 object-cover mt-2 rounded-lg" @error="handleImageError($event, 'identificacion')">
                  <p v-else class="text-gray-700">No hay foto de identificación disponible</p>
                </div>
              </div>

              <!-- Evidencias -->
              <div class="mt-6">
                <label class="block text-gray-700 text-sm font-bold mb-1">Evidencias</label>
                <p v-if="cita.evidencias" class="text-gray-700 whitespace-pre-wrap">{{ cita.evidencias }}</p>
                <p v-else class="text-gray-700">No hay evidencias disponibles</p>
              </div>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 dark:border-slate-800">
            <div class="flex justify-end space-x-3">
              <button @click="close" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
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
    otro_servicio: 'Otro Servicio'
  };
  return tipos[tipo] || 'Desconocido';
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
  const fecha = new Date(fechaHora);
  return fecha.toLocaleString();
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

.modal-enter-active .bg-white dark:bg-slate-900,
.modal-leave-active .bg-white dark:bg-slate-900 {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-white dark:bg-slate-900,
.modal-leave-to .bg-white dark:bg-slate-900 {
  transform: scale(0.95);
}
</style>

