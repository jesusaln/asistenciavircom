<template>
  <!-- Modal con overlay mejorado -->
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden" role="dialog" :aria-labelledby="modalTitleId" aria-modal="true">
          <!-- Header del modal -->
          <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gray-50">
            <h2 :id="modalTitleId" class="text-2xl font-semibold text-gray-800">
              Detalles del Cliente
            </h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-full hover:bg-gray-200" aria-label="Cerrar modal">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Contenido del modal con scroll -->
          <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
            <div v-if="hasClientData" class="space-y-6">
              <!-- Información Personal/Empresa -->
              <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-blue-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Información General
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <ClientField label="Nombre/Razón Social" :value="cliente.nombre_razon_social" />
                  <ClientField label="RFC" :value="cliente.rfc" />
                </div>
              </div>

              <!-- Información de Contacto -->
              <div class="bg-green-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-green-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  Información de Contacto
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <ClientField label="Correo Electrónico" :value="cliente.email" type="email" />
                  <ClientField label="Teléfono" :value="cliente.telefono" type="phone" />
                </div>
              </div>

              <!-- Dirección -->
              <div class="bg-yellow-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-yellow-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Dirección
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <ClientField label="Dirección" :value="formatAddress" class="md:col-span-2 lg:col-span-3" />
                  <ClientField label="Colonia" :value="cliente.colonia" />
                  <ClientField label="Municipio/Ciudad" :value="cliente.municipio" />
                  <ClientField label="Código Postal" :value="cliente.codigo_postal" />
                  <ClientField label="Estado" :value="cliente.estado" />
                  <ClientField label="País" :value="cliente.pais" />
                </div>
              </div>

              <!-- Información Fiscal -->
              <div class="bg-purple-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-purple-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Información Fiscal
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <ClientField label="Régimen Fiscal" :value="cliente.regimen_fiscal" />
                  <ClientField label="Uso de CFDI" :value="cliente.uso_cfdi" />
                </div>
              </div>
            </div>

            <!-- Estado vacío mejorado -->
            <div v-else class="text-center py-12">
              <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <h3 class="text-lg font-medium text-gray-900 mb-2">No hay información disponible</h3>
              <p class="text-gray-500">No se ha proporcionado información del cliente.</p>
            </div>
          </div>

          <!-- Footer del modal -->
          <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-end space-x-3">
              <button @click="closeModal" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
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
import {  computed, onMounted, onUnmounted, ref, watch } from 'vue';
import ClientField from './ClientField.vue'; // Ajusta la ruta de importación según sea necesario

const props = defineProps({
  cliente: {
    type: Object,
    default: () => ({})
  },
  isOpen: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close']);

const modalTitleId = ref(`modal-title-${Math.random().toString(36).substr(2, 9)}`);

const hasClientData = computed(() => {
  return props.cliente && Object.keys(props.cliente).length > 0;
});

const formatAddress = computed(() => {
  const { calle, numero_exterior, numero_interior } = props.cliente;
  const parts = [calle, numero_exterior, numero_interior].filter(Boolean);
  return parts.length > 0 ? parts.join(' ') : 'No especificada';
});

const closeModal = () => {
  emit('close');
};

const handleEscapeKey = (event) => {
  if (event.key === 'Escape' && props.isOpen) {
    closeModal();
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleEscapeKey);
  if (props.isOpen) {
    document.body.style.overflow = 'hidden';
  }
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscapeKey);
  document.body.style.overflow = '';
});

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform 0.3s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
  transform: scale(0.95);
}
</style>

