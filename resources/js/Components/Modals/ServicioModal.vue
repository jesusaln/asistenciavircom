<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="closeModal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden" role="dialog" aria-modal="true">
          <!-- Header del modal -->
          <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gray-50">
            <h2 class="text-2xl font-semibold text-gray-800">Detalles del Servicio</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-full hover:bg-gray-200" aria-label="Cerrar modal">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Información General
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ servicio.nombre }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ servicio.descripcion }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Código</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ servicio.codigo }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Categoría</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ categoriaNombre || 'Cargando...' }}</p>
                  </div>
                </div>
              </div>

              <!-- Detalles del Servicio -->
              <div class="bg-green-50 rounded-lg p-4">
                <h3 class="text-lg font-medium text-green-800 mb-4 flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.654 0 3-1.346 3-3s-1.346-3-3-3-3 1.346-3 3 1.346 3 3 3zm0 2c-2.21 0-4 1.79-4 4v1h8v-1c0-2.21-1.79-4-4-4zm0 8c2.21 0 4-1.79 4-4h-8c0 2.21 1.79 4 4 4z" />
                  </svg>
                  Detalles del Servicio
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Precio</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ servicio.precio }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Duración (minutos)</label>
                    <p class="mt-1 block w-full rounded-md bg-gray-100 p-2">{{ servicio.duracion }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <div class="mt-1 flex items-center gap-2">
                      <button
                        @click="toggleEstado"
                        :disabled="isUpdating"
                        class="px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2"
                        :class="localEstado === 'activo' 
                          ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                          : 'bg-red-100 text-red-800 hover:bg-red-200'"
                      >
                        <span v-if="isUpdating" class="animate-spin">⏳</span>
                        <span v-else>{{ localEstado === 'activo' ? '✓ Activo' : '✗ Inactivo' }}</span>
                      </button>
                      <span class="text-xs text-gray-500">Click para cambiar</span>
                    </div>
                  </div>
                </div>
              </div>
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
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  servicio: Object,
  isOpen: Boolean
});

const emit = defineEmits(['close', 'updated']);

const closeModal = () => {
  emit('close');
};

const categoriaNombre = ref('Cargando...');
const localEstado = ref('');
const isUpdating = ref(false);

// Sync local state with prop
watch(() => props.servicio, (newVal) => {
  if (newVal) {
    localEstado.value = newVal.estado;
  }
}, { immediate: true });

// Toggle estado
const toggleEstado = async () => {
  if (!props.servicio || isUpdating.value) return;
  
  isUpdating.value = true;
  const nuevoEstado = localEstado.value === 'activo' ? 'inactivo' : 'activo';
  
  try {
    const response = await fetch(`/servicios/${props.servicio.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ estado: nuevoEstado })
    });
    
    if (response.ok) {
      localEstado.value = nuevoEstado;
      emit('updated', { ...props.servicio, estado: nuevoEstado });
    } else {
      console.error('Error updating estado:', await response.text());
    }
  } catch (error) {
    console.error('Error toggling estado:', error);
  } finally {
    isUpdating.value = false;
  }
};

// Función para obtener el nombre de la categoría desde el backend
const fetchCategoriaNombre = async (categoriaId) => {
  try {
    const response = await fetch(`/api/categorias/${categoriaId}`); // Ajusta la URL según tu API
    if (!response.ok) {
      throw new Error('No se pudo obtener la categoría');
    }
    const data = await response.json();
    categoriaNombre.value = data.nombre; // Asume que la respuesta tiene un campo 'nombre'
  } catch (error) {
    console.error('Error al obtener la categoría:', error);
    categoriaNombre.value = 'No disponible';
  }
};

// Llama a fetchCategoriaNombre cuando el modal se abre
onMounted(() => {
  if (props.servicio && props.servicio.categoria_id) {
    fetchCategoriaNombre(props.servicio.categoria_id);
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

